<?php

namespace App\Domain\Licensing;

use App\Domain\Tenancy\TenantContext;
use App\Enums\LicenseStatus;
use App\Models\SiteLicense;
use App\Models\TenantSubscription;
use Illuminate\Support\Carbon;

class LicenseSyncService
{
    public function activateAllTenantSites(string $tenantId): void
    {
        TenantContext::bypass(function () use ($tenantId): void {
            SiteLicense::query()
                ->where('tenant_id', $tenantId)
                ->update([
                    'status' => LicenseStatus::Active,
                    'grace_ends_at' => null,
                    'suspended_at' => null,
                ]);
        });
    }

    public function syncTenantSitesToGrace(string $tenantId, Carbon $graceEndsAt): void
    {
        TenantContext::bypass(function () use ($tenantId, $graceEndsAt): void {
            SiteLicense::query()
                ->where('tenant_id', $tenantId)
                ->update([
                    'status' => LicenseStatus::Grace,
                    'grace_ends_at' => $graceEndsAt,
                ]);
        });
    }

    public function suspendAllTenantSites(string $tenantId): void
    {
        TenantContext::bypass(function () use ($tenantId): void {
            SiteLicense::query()
                ->where('tenant_id', $tenantId)
                ->update([
                    'status' => LicenseStatus::Suspended,
                    'suspended_at' => now(),
                ]);
        });
    }

    public function syncFromSubscription(TenantSubscription $subscription): void
    {
        if ($subscription->isActive()) {
            $this->activateAllTenantSites($subscription->tenant_id);
        } elseif ($subscription->isPastDue()) {
            $this->syncTenantSitesToGrace(
                $subscription->tenant_id,
                $subscription->grace_ends_at ?? now()->addDays(7),
            );
        } elseif ($subscription->status === 'canceled') {
            $this->suspendAllTenantSites($subscription->tenant_id);
        }
    }
}
