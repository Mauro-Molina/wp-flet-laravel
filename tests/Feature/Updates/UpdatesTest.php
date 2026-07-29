<?php

namespace Tests\Feature\Updates;

use App\Domain\Tenancy\TenantContext;
use App\Domain\Updates\SyncPendingUpdatesAction;
use App\Models\SitePendingUpdate;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_list_pending_updates_and_run_update_command(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();

        app(SyncPendingUpdatesAction::class)->execute($connected['site'], [
            [
                'update_type' => 'plugin',
                'item_slug' => 'woocommerce',
                'item_name' => 'WooCommerce',
                'current_version' => '8.0.0',
                'available_version' => '8.1.0',
            ],
        ]);
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/updates')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/updates/run', [
                'update_type' => 'plugin',
                'idempotency_key' => 'update-1',
                'items' => ['woocommerce'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'update.plugins');
    }

    public function test_plugin_sync_updates_via_hmac(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        $this->pluginPost('/plugin/v1/updates/sync', $connected['site']->id, $connected['secret'], [
            'updates' => [
                [
                    'update_type' => 'core',
                    'item_slug' => 'wordpress',
                    'current_version' => '6.4',
                    'available_version' => '6.5',
                ],
            ],
        ])->assertOk()->assertJsonPath('data.synced', 1);

        TenantContext::set($owner['tenant']->id);
        $this->assertSame(1, SitePendingUpdate::query()->where('site_id', $connected['site']->id)->count());
    }
}
