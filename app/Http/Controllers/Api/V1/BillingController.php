<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\SubscriptionService;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    public function status(SubscriptionService $subscriptions): JsonResponse
    {
        $tenantId = TenantContext::idOrFail();

        return ApiResponse::success($subscriptions->usageSummary($tenantId));
    }

    public function plans(): JsonResponse
    {
        $plans = Plan::query()->where('is_active', true)->orderBy('price_cents')->get();

        return ApiResponse::success($plans->map(fn ($p) => [
            'slug' => $p->slug,
            'name' => $p->name,
            'max_sites' => $p->max_sites,
            'max_commands_per_month' => $p->max_commands_per_month,
            'max_backups_per_site' => $p->max_backups_per_site,
            'price_cents' => $p->price_cents,
        ])->values());
    }
}
