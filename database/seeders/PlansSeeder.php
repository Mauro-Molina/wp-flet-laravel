<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'max_sites' => 5,
                'max_commands_per_month' => 500,
                'max_backups_per_site' => 5,
                'price_cents' => 2900,
                'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
                'features' => [
                    'api_rate_limit_per_minute' => (int) config('rate_limits.plans.starter', 60),
                ],
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'max_sites' => 25,
                'max_commands_per_month' => 5000,
                'max_backups_per_site' => 20,
                'price_cents' => 9900,
                'stripe_price_id' => env('STRIPE_PRICE_PRO'),
                'features' => [
                    'api_rate_limit_per_minute' => (int) config('rate_limits.plans.pro', 300),
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'max_sites' => 100,
                'max_commands_per_month' => 50000,
                'max_backups_per_site' => 100,
                'price_cents' => 29900,
                'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
                'features' => [
                    'api_rate_limit_per_minute' => (int) config('rate_limits.plans.enterprise', 1000),
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
