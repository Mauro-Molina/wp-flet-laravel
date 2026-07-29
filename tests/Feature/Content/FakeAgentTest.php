<?php

namespace Tests\Feature\Content;

use App\Domain\Tenancy\TenantContext;
use App\FakeAgent\FakeAgentStore;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['fake_agent.enabled' => true]);
        FakeAgentStore::reset();
    }

    public function test_fake_agent_responds_to_hmac_posts(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        TenantContext::clear();

        $this->pluginPost('/fake-agent/wp/v2/posts', $connected['site']->id, $connected['secret'], [
            'title' => 'Via HMAC',
        ])
            ->assertCreated()
            ->assertJsonPath('title.rendered', 'Via HMAC');

        $this->pluginPost('/fake-agent/wp/v2/posts/1/publish', $connected['site']->id, $connected['secret'])
            ->assertOk()
            ->assertJsonPath('status', 'publish');
    }

    public function test_fake_agent_rejects_invalid_hmac(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        $this->call(
            'GET',
            '/fake-agent/wp/v2/posts',
            [],
            [],
            [],
            [
                'HTTP_X-Site-Id' => $connected['site']->id,
                'HTTP_X-Timestamp' => (string) time(),
                'HTTP_X-Signature' => 'invalid',
                'HTTP_ACCEPT' => 'application/json',
            ],
        )->assertUnauthorized();
    }
}
