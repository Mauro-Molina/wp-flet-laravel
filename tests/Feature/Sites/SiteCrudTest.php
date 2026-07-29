<?php

namespace Tests\Feature\Sites;

use App\Domain\Tenancy\TenantContext;
use App\Models\Site;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_connect_disconnect_and_regenerate_credentials(): void
    {
        $owner = $this->createTenantOwner();

        $create = $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites', [
                'name' => 'My Blog',
                'url' => 'https://myblog.example.com',
            ])
            ->assertCreated();

        $siteId = $create->json('data.id');

        $connect = $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$siteId.'/connect')
            ->assertOk()
            ->assertJsonStructure(['data' => ['credentials' => ['secret', 'version']]]);

        $secret = $connect->json('data.credentials.secret');

        $regenerate = $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$siteId.'/credentials/regenerate')
            ->assertOk();

        $this->assertNotSame($secret, $regenerate->json('data.credentials.secret'));

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$siteId.'/disconnect')
            ->assertOk()
            ->assertJsonPath('data.status', 'disconnected');

        TenantContext::set($owner['tenant']->id);
        $this->assertSame('disconnected', Site::query()->find($siteId)->status);
    }

    public function test_update_site_metadata(): void
    {
        $owner = $this->createTenantOwner();

        $site = $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites', [
                'name' => 'Old Name',
                'url' => 'https://old.example.com',
            ])
            ->assertCreated();

        $this->withJwt($owner['token'])
            ->patchJson('/api/v1/sites/'.$site->json('data.id'), [
                'name' => 'New Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }
}
