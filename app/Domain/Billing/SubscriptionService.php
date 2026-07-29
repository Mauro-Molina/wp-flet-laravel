<?php

namespace App\Domain\Billing;

use App\Domain\Tenancy\TenantContext;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Subscription and plan limits — always read fresh from DB.
 */
class SubscriptionService
{
    public function resolveForTenant(string $tenantId): TenantSubscription
    {
        $subscription = TenantSubscription::query()
            ->with('plan')
            ->where('tenant_id', $tenantId)
            ->first();

        if ($subscription !== null) {
            return $subscription;
        }

        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        return TenantSubscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ])->load('plan');
    }

    public function assertCommandQuota(string $tenantId): void
    {
        $subscription = $this->resolveForTenant($tenantId);

        if ($subscription->isPastDue()) {
            if ($subscription->grace_ends_at === null || $subscription->grace_ends_at->isPast()) {
                throw new AuthorizationException('Subscription is past due. Command execution is blocked.');
            }
        }

        if (! $subscription->isActive() && ! $subscription->isPastDue()) {
            throw new AuthorizationException('Subscription is not active.');
        }

        $plan = $subscription->plan;
        if ($subscription->commands_used_this_period >= $plan->max_commands_per_month) {
            throw new AuthorizationException('Monthly command quota exceeded for current plan.');
        }
    }

    public function incrementCommandUsage(string $tenantId): void
    {
        $subscription = $this->resolveForTenant($tenantId);
        $subscription->increment('commands_used_this_period');
    }

    public function assertCanAddSite(Tenant $tenant): void
    {
        $subscription = $this->resolveForTenant($tenant->id);
        $currentSites = Site::query()->where('tenant_id', $tenant->id)->count();

        if ($currentSites >= $subscription->plan->max_sites) {
            throw new AuthorizationException('Site limit reached for current plan.');
        }
    }

    public function assertBackupQuota(Site $site): void
    {
        $subscription = $this->resolveForTenant($site->tenant_id);
        $backupCount = $site->backups()->where('status', 'completed')->count();

        if ($backupCount >= $subscription->plan->max_backups_per_site) {
            throw new AuthorizationException('Backup limit reached for this site on current plan.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function usageSummary(string $tenantId): array
    {
        $subscription = $this->resolveForTenant($tenantId);
        $plan = $subscription->plan;

        return [
            'plan' => [
                'slug' => $plan->slug,
                'name' => $plan->name,
            ],
            'subscription_status' => $subscription->status,
            'period' => [
                'start' => $subscription->current_period_start?->toIso8601String(),
                'end' => $subscription->current_period_end?->toIso8601String(),
            ],
            'usage' => [
                'sites' => Site::query()->where('tenant_id', $tenantId)->count(),
                'sites_limit' => $plan->max_sites,
                'commands_this_period' => $subscription->commands_used_this_period,
                'commands_limit' => $plan->max_commands_per_month,
                'api_rate_limit_per_minute' => (int) (
                    data_get($plan->features, 'api_rate_limit_per_minute')
                    ?? config("rate_limits.plans.{$plan->slug}", config('rate_limits.api_default_per_minute', 60))
                ),
            ],
        ];
    }
}
