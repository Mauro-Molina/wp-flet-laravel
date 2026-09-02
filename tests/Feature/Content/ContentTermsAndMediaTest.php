<?php

namespace Tests\Feature\Content;

use App\Domain\Rbac\Roles;
use App\Domain\Tenancy\TenantContext;
use App\FakeAgent\FakeAgentStore;
use App\Models\SiteUserAccess;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContentTermsAndMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config([
            'fake_agent.enabled' => true,
            'content.agent_path_prefix' => 'wp-json/wpfleet/v1',
        ]);
        FakeAgentStore::reset();
    }

    public function test_create_post_with_author_categories_and_tags(): void
    {
        [$token, $siteId] = $this->connectedOwnerSite();

        $category = $this->withJwt($token)
            ->postJson("/api/v1/sites/{$siteId}/content/categories", [
                'name' => 'News',
                'slug' => 'news',
            ])
            ->assertCreated()
            ->json('data');

        $tag = $this->withJwt($token)
            ->postJson("/api/v1/sites/{$siteId}/content/tags", [
                'name' => 'Launch',
            ])
            ->assertCreated()
            ->json('data');

        $this->withJwt($token)
            ->postJson("/api/v1/sites/{$siteId}/content/posts", [
                'title' => 'Annotated post',
                'content' => 'Body',
                'status' => 'draft',
                'author' => 1,
                'categories' => [$category['id']],
                'tags' => [$tag['id'], 'extra-tag'],
                'excerpt' => 'Short',
            ])
            ->assertCreated()
            ->assertJsonPath('data.author', 1)
            ->assertJsonPath('data.categories.0.id', $category['id'])
            ->assertJsonPath('data.categories.0.name', 'News')
            ->assertJsonPath('data.tags.0.id', $tag['id'])
            ->assertJsonPath('data.tags.1.name', 'extra-tag');
    }

    public function test_list_and_create_categories_and_tags(): void
    {
        [$token, $siteId] = $this->connectedOwnerSite();

        $this->withJwt($token)
            ->getJson("/api/v1/sites/{$siteId}/content/categories")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Uncategorized');

        $this->withJwt($token)
            ->postJson("/api/v1/sites/{$siteId}/content/categories", [
                'name' => 'Guides',
                'parent' => 0,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Guides');

        $this->withJwt($token)
            ->postJson("/api/v1/sites/{$siteId}/content/tags", ['name' => 'alpha'])
            ->assertCreated();

        $this->withJwt($token)
            ->getJson("/api/v1/sites/{$siteId}/content/tags?search=alp")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'alpha');
    }

    public function test_set_featured_media_on_post(): void
    {
        [$token, $siteId] = $this->connectedOwnerSite();

        $media = $this->withJwt($token)
            ->postJson("/api/v1/sites/{$siteId}/content/media", [
                'filename' => 'hero.jpg',
                'mime_type' => 'image/jpeg',
                'file_base64' => base64_encode('fake-bytes'),
                'source_url' => 'https://cdn.example.com/hero.jpg',
            ])
            ->assertCreated()
            ->json('data');

        $this->withJwt($token)
            ->postJson("/api/v1/sites/{$siteId}/content/posts", [
                'title' => 'With image',
                'featured_media' => $media['id'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.featured_media', $media['id'])
            ->assertJsonPath('data.featured_image_url', 'https://cdn.example.com/hero.jpg');

        $this->withJwt($token)
            ->getJson("/api/v1/sites/{$siteId}/content/media/{$media['id']}")
            ->assertOk()
            ->assertJsonPath('data.id', $media['id']);
    }

    public function test_client_readonly_cannot_manage_terms(): void
    {
        $owner = $this->createTenantOwner(Roles::CLIENT_READONLY);
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        SiteUserAccess::query()->create([
            'tenant_id' => $owner['tenant']->id,
            'site_id' => $connected['site']->id,
            'user_id' => $owner['user']->id,
        ]);
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/content/categories')
            ->assertOk();

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/content/categories', [
                'name' => 'Nope',
            ])
            ->assertForbidden();
    }

    public function test_agent_unreachable_returns_502(): void
    {
        config(['fake_agent.enabled' => false]);
        Http::fake(fn () => throw new ConnectionException('connection refused'));

        [$token, $siteId] = $this->connectedOwnerSite();

        $this->withJwt($token)
            ->getJson("/api/v1/sites/{$siteId}/content/categories")
            ->assertStatus(502)
            ->assertJsonPath('errors.0.code', 'agent_unreachable');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function connectedOwnerSite(): array
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        TenantContext::clear();

        return [$owner['token'], $connected['site']->id];
    }
}
