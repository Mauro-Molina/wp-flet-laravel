<?php

namespace App\Domain\Licensing;

use App\Domain\Billing\SubscriptionService;
use App\Enums\LicenseStatus;
use App\Models\Site;
use App\Models\SiteLicense;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * License checks are always read fresh from DB — never cached.
 */
class LicenseValidator
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function assertCommandAllowed(Site $site): void
    {
        $this->subscriptions->assertCommandQuota($site->tenant_id);

        $license = $this->resolveLicense($site);

        if (! $license->allowsCommands()) {
            throw new AuthorizationException(
                'Site license does not allow command execution (status: '.$license->status->value.').'
            );
        }
    }

    public function assertContentAllowed(Site $site): void
    {
        $license = $this->resolveLicense($site);

        if (! $license->allowsContent()) {
            throw new AuthorizationException(
                'Site license does not allow content access (status: '.$license->status->value.').'
            );
        }
    }

    public function isContentAllowed(Site $site): bool
    {
        try {
            $this->assertContentAllowed($site);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    public function isCommandAllowed(Site $site): bool
    {
        try {
            $this->assertCommandAllowed($site);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function validateForSite(Site $site): array
    {
        $license = $this->resolveLicense($site);
        $subscription = $this->subscriptions->resolveForTenant($site->tenant_id);

        return [
            'site_id' => $site->id,
            'license_status' => $license->status->value,
            'allows_commands' => $license->allowsCommands() && $this->isCommandAllowed($site),
            'grace_ends_at' => $license->grace_ends_at?->toIso8601String(),
            'subscription_status' => $subscription->status,
            'commands_remaining' => max(
                0,
                $subscription->plan->max_commands_per_month - $subscription->commands_used_this_period
            ),
        ];
    }

    public function resolveLicense(Site $site): SiteLicense
    {
        $license = $site->license()->first();

        if ($license === null) {
            $license = SiteLicense::query()->create([
                'tenant_id' => $site->tenant_id,
                'site_id' => $site->id,
                'status' => LicenseStatus::Active,
            ]);
            $site->setRelation('license', $license);
        }

        return $license;
    }

    public function suspend(Site $site): SiteLicense
    {
        $license = $this->resolveLicense($site);
        $license->forceFill([
            'status' => LicenseStatus::Suspended,
            'suspended_at' => now(),
        ])->save();

        return $license->refresh();
    }

    public function activate(Site $site): SiteLicense
    {
        $license = $this->resolveLicense($site);
        $license->forceFill([
            'status' => LicenseStatus::Active,
            'grace_ends_at' => null,
            'suspended_at' => null,
        ])->save();

        return $license->refresh();
    }
}
