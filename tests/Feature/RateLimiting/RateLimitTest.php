<?php

namespace Tests\Feature\RateLimiting;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        RateLimiter::clear('login:127.0.0.1');
    }

    public function test_auth_login_is_rate_limited(): void
    {
        config(['rate_limits.auth.login_per_minute' => 2]);

        RateLimiter::for('auth-login', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(2)->by('login:'.$request->ip());
        });

        $payload = ['email' => 'nobody@example.com', 'password' => 'wrong-password'];

        $this->postJson('/api/v1/auth/login', $payload)->assertStatus(422);
        $this->postJson('/api/v1/auth/login', $payload)->assertStatus(422);

        $this->postJson('/api/v1/auth/login', $payload)
            ->assertStatus(429)
            ->assertJsonPath('errors.0.code', 'rate_limit_exceeded');
    }

    public function test_authenticated_api_is_rate_limited_per_tenant_plan(): void
    {
        $owner = $this->createTenantOwner();

        config(['rate_limits.plans.starter' => 2]);
        RateLimiter::for('api-tenant', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(2)
                ->by('tenant:'.$request->attributes->get('tenant_id'));
        });

        $this->withJwt($owner['token'])->getJson('/api/v1/sites')->assertOk();
        $this->withJwt($owner['token'])->getJson('/api/v1/sites')->assertOk();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites')
            ->assertStatus(429)
            ->assertJsonPath('errors.0.code', 'rate_limit_exceeded');
    }

    public function test_api_responses_include_security_headers(): void
    {
        $owner = $this->createTenantOwner();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
