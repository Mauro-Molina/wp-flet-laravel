<?php

namespace Tests\Feature\Content;

use App\Domain\Licensing\LicenseValidator;
use App\Domain\Rbac\Roles;
use App\Domain\Tenancy\TenantContext;
use App\Enums\LicenseStatus;
use App\FakeAgent\FakeAgentStore;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentProxyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['fake_agent.enabled' => true]);
        FakeAgentStore::reset();
    }

    public function test_list_posts_via_content_proxy(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/content/posts')
            ->assertOk()
            ->assertJsonPath('data.0.title.rendered', 'Welcome');
    }

    public function test_create_and_publish_post(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        TenantContext::clear();

        $create = $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/content/posts', [
                'title' => 'New draft',
                'content' => 'Body text',
            ])
            ->assertCreated()
            ->json('data');

        $postId = $create['id'];

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/content/posts/'.$postId.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'publish');
    }

    public function test_client_readonly_can_view_but_not_create(): void
    {
        $owner = $this->createTenantOwner(Roles::CLIENT_READONLY);
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        \App\Models\SiteUserAccess::query()->create([
            'tenant_id' => $owner['tenant']->id,
            'site_id' => $connected['site']->id,
            'user_id' => $owner['user']->id,
        ]);
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/content/posts')
            ->assertOk();

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/content/posts', [
                'title' => 'Forbidden',
            ])
            ->assertForbidden();
    }

    public function test_pages_settings_and_users_proxy(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/content/pages')
            ->assertOk()
            ->assertJsonPath('data.0.parent', 0);

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/content/settings')
            ->assertOk()
            ->assertJsonPath('data.title', 'WP Fleet Demo');

        $this->withJwt($owner['token'])
            ->patchJson('/api/v1/sites/'.$connected['site']->id.'/content/settings', [
                'title' => 'Updated Title',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/content/users', [
                'email' => 'dev@example.com',
                'role' => 'editor',
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'dev@example.com');
    }
}
