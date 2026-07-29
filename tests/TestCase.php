<?php

namespace Tests;

use App\Domain\Auth\JwtTokenService;
use App\Domain\Hmac\HmacService;
use App\Domain\Licensing\LicenseValidator;
use App\Domain\Rbac\AssignTenantRole;
use App\Domain\Rbac\Roles;
use App\Domain\Sites\CredentialService;
use App\Domain\Tenancy\TenantContext;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    /**
     * @return array{user: User, tenant: Tenant, token: string}
     */
    protected function createTenantOwner(string $role = Roles::OWNER): array
    {
        $this->ensurePlansSeeded();
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $tenant->users()->attach($user->getKey(), [
            'id' => (string) Str::uuid(),
            'is_default' => true,
        ]);

        TenantContext::set($tenant->id);
        app(AssignTenantRole::class)->execute($user, $role, $tenant->id);
        app(\App\Domain\Billing\SubscriptionService::class)->resolveForTenant($tenant->id);

        $token = app(JwtTokenService::class)->issueAccessToken($user, $tenant->id);

        return compact('user', 'tenant', 'token');
    }

    /**
     * @return array{site: Site, secret: string}
     */
    protected function createConnectedSite(Tenant $tenant): array
    {
        TenantContext::set($tenant->id);

        $site = Site::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
        ]);

        $issued = app(CredentialService::class)->issue($site);
        app(LicenseValidator::class)->resolveLicense($site);

        TenantContext::clear();

        return [
            'site' => $site,
            'secret' => $issued['plain_secret'],
        ];
    }

    protected function withJwt(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    protected function pluginPost(string $uri, string $siteId, string $secret, array $payload = []): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = app(HmacService::class)->sign($secret, $timestamp, $body);

        return $this->call(
            'POST',
            $uri,
            [],
            [],
            [],
            [
                'HTTP_X-Site-Id' => $siteId,
                'HTTP_X-Timestamp' => $timestamp,
                'HTTP_X-Signature' => $signature,
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body,
        );
    }

    protected function ensurePlansSeeded(): void
    {
        if (Plan::query()->count() === 0) {
            $this->seed(PlansSeeder::class);
        }
    }
}
