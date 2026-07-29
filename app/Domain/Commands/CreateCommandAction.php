<?php

namespace App\Domain\Commands;

use App\Domain\Billing\SubscriptionService;
use App\Enums\CommandStatus;
use App\Domain\Licensing\LicenseValidator;
use App\Domain\Tenancy\TenantContext;
use App\Models\Command;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CreateCommandAction
{
    public function __construct(
        private readonly LicenseValidator $licenseValidator,
        private readonly SubscriptionService $subscriptions,
        private readonly ExpireStaleCommandsAction $expireStale,
    ) {}

    public function execute(
        Site $site,
        string $type,
        string $idempotencyKey,
        ?array $payload = null,
        ?User $creator = null,
    ): Command {
        TenantContext::set($site->tenant_id);

        if ($creator !== null && ! $creator->hasSiteAccess($site->id)) {
            throw new AuthorizationException('User does not have access to this site.');
        }

        $this->licenseValidator->assertCommandAllowed($site);
        $this->expireStale->execute($site->id);

        $existing = Command::query()
            ->where('tenant_id', $site->tenant_id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $command = Command::query()->create([
            'tenant_id' => $site->tenant_id,
            'site_id' => $site->id,
            'created_by' => $creator?->id,
            'type' => $type,
            'payload' => $payload,
            'status' => CommandStatus::Pending,
            'idempotency_key' => $idempotencyKey,
            'expires_at' => now()->addSeconds((int) config('hmac.command_ttl_seconds', 86400)),
        ]);

        $this->subscriptions->incrementCommandUsage($site->tenant_id);

        return $command;
    }
}
