<?php

namespace Tests\Feature\Content;

use App\Domain\Licensing\LicenseValidator;
use App\Domain\Tenancy\TenantContext;
use App\FakeAgent\FakeAgentStore;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentLicenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['fake_agent.enabled' => true]);
        FakeAgentStore::reset();
    }

    public function test_suspended_license_blocks_content_before_proxy(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        app(LicenseValidator::class)->suspend($connected['site']);
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/content/posts')
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Site license does not allow content access (status: suspended).']);
    }

    public function test_disconnected_site_is_rejected(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/content/posts')
            ->assertForbidden();
    }
}
