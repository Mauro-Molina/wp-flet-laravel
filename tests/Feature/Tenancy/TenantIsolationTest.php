<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Rbac\Roles;
use App\Domain\Tenancy\TenantContext;
use App\Models\Site;
use App\Models\SiteUserAccess;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_owner_cannot_read_site_from_another_tenant_by_guessed_id(): void
    {
        $tenantA = $this->createTenantOwner();
        $tenantB = $this->createTenantOwner();

        TenantContext::set($tenantB['tenant']->id);
        $siteB = Site::query()->create([
            'tenant_id' => $tenantB['tenant']->id,
            'name' => 'Tenant B Site',
            'url' => 'https://b.example.com',
            'status' => 'connected',
        ]);

        TenantContext::clear();

        $this->withJwt($tenantA['token'])
            ->getJson('/api/v1/sites/'.$siteB->id)
            ->assertNotFound()
            ->assertJsonPath('errors.0.code', 'not_found');
    }

    public function test_owner_cannot_delete_site_from_another_tenant(): void
    {
        $tenantA = $this->createTenantOwner();
        $tenantB = $this->createTenantOwner();

        TenantContext::set($tenantB['tenant']->id);
        $siteB = Site::query()->create([
            'tenant_id' => $tenantB['tenant']->id,
            'name' => 'Tenant B Site',
            'url' => 'https://b.example.com',
            'status' => 'connected',
        ]);
        TenantContext::clear();

        $this->withJwt($tenantA['token'])
            ->deleteJson('/api/v1/sites/'.$siteB->id)
            ->assertNotFound();

        TenantContext::set($tenantB['tenant']->id);
        $this->assertDatabaseHas('sites', ['id' => $siteB->id]);
    }

    public function test_list_sites_never_includes_other_tenant_rows(): void
    {
        $tenantA = $this->createTenantOwner();
        $tenantB = $this->createTenantOwner();

        TenantContext::set($tenantA['tenant']->id);
        Site::query()->create([
            'tenant_id' => $tenantA['tenant']->id,
            'name' => 'A Site',
            'url' => 'https://a.example.com',
            'status' => 'connected',
        ]);

        TenantContext::set($tenantB['tenant']->id);
        Site::query()->create([
            'tenant_id' => $tenantB['tenant']->id,
            'name' => 'B Site',
            'url' => 'https://b.example.com',
            'status' => 'connected',
        ]);
        TenantContext::clear();

        $response = $this->withJwt($tenantA['token'])->getJson('/api/v1/sites');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('A Site'));
        $this->assertFalse($names->contains('B Site'));
    }

    public function test_developer_only_sees_assigned_sites(): void
    {
        $owner = $this->createTenantOwner();
        $developer = $this->createTenantOwner(Roles::DEVELOPER);

        // Move developer into owner's tenant with Developer role.
        $developer['user']->tenants()->sync([
            $owner['tenant']->id => [
                'id' => (string) Str::uuid(),
                'is_default' => true,
            ],
        ]);

        TenantContext::set($owner['tenant']->id);
        app(\App\Domain\Rbac\AssignTenantRole::class)
            ->execute($developer['user'], Roles::DEVELOPER, $owner['tenant']->id);

        $assigned = Site::query()->create([
            'tenant_id' => $owner['tenant']->id,
            'name' => 'Assigned',
            'url' => 'https://assigned.example.com',
            'status' => 'connected',
        ]);
        $hidden = Site::query()->create([
            'tenant_id' => $owner['tenant']->id,
            'name' => 'Hidden',
            'url' => 'https://hidden.example.com',
            'status' => 'connected',
        ]);

        SiteUserAccess::query()->create([
            'tenant_id' => $owner['tenant']->id,
            'site_id' => $assigned->id,
            'user_id' => $developer['user']->id,
        ]);

        $token = app(\App\Domain\Auth\JwtTokenService::class)
            ->issueAccessToken($developer['user'], $owner['tenant']->id);

        TenantContext::clear();

        $response = $this->withJwt($token)->getJson('/api/v1/sites');
        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Assigned'));
        $this->assertFalse($names->contains('Hidden'));

        $this->withJwt($token)->getJson('/api/v1/sites/'.$hidden->id)->assertForbidden();
    }

    public function test_querying_tenant_model_without_context_throws(): void
    {
        $this->expectException(\App\Domain\Tenancy\Exceptions\TenantContextMissingException::class);

        TenantContext::clear();
        Site::query()->get();
    }
}
