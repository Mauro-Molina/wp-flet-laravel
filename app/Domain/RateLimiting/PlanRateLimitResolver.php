<?php

namespace App\Domain\RateLimiting;

use App\Domain\Billing\SubscriptionService;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Http\Request;

class PlanRateLimitResolver
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function resolveForRequest(Request $request): int
    {
        $tenantId = $request->attributes->get('tenant_id')
            ?? TenantContext::id();

        if ($tenantId === null) {
            return (int) config('rate_limits.api_default_per_minute', 60);
        }

        return $this->resolveForTenant((string) $tenantId);
    }

    public function resolveForTenant(string $tenantId): int
    {
        $subscription = $this->subscriptions->resolveForTenant($tenantId);
        $slug = $subscription->plan->slug;

        $fromPlan = data_get($subscription->plan->features, 'api_rate_limit_per_minute');

        if (is_numeric($fromPlan)) {
            return (int) $fromPlan;
        }

        return (int) config("rate_limits.plans.{$slug}", config('rate_limits.api_default_per_minute', 60));
    }
}
