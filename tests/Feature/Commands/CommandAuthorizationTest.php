<?php

namespace Tests\Feature\Commands;

use App\Domain\Commands\CreateCommandAction;
use App\Domain\Licensing\LicenseValidator;
use App\Domain\Rbac\Roles;
use App\Domain\Tenancy\TenantContext;
use App\Enums\LicenseStatus;
use App\Models\Site;
use App\Models\SiteLicense;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommandAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_command_requires_permission_and_active_license(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        SiteLicense::query()->where('site_id', $connected['site']->id)->update([
            'status' => LicenseStatus::Suspended,
            'suspended_at' => now(),
        ]);
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->withHeader('Idempotency-Key', 'cmd-1')
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/commands', [
                'type' => 'backup.create',
                'idempotency_key' => 'cmd-1',
            ])
            ->assertForbidden();
    }

    public function test_developer_without_site_access_cannot_create_command(): void
    {
        $owner = $this->createTenantOwner();
        $developer = $this->createTenantOwner(Roles::DEVELOPER);

        TenantContext::set($owner['tenant']->id);
        $site = Site::factory()->create([
            'tenant_id' => $owner['tenant']->id,
            'status' => 'connected',
        ]);
        app(LicenseValidator::class)->resolveLicense($site);
        TenantContext::clear();

        $developer['user']->tenants()->sync([
            $owner['tenant']->id => [
                'id' => (string) Str::uuid(),
                'is_default' => true,
            ],
        ]);

        TenantContext::set($owner['tenant']->id);
        app(\App\Domain\Rbac\AssignTenantRole::class)
            ->execute($developer['user'], Roles::DEVELOPER, $owner['tenant']->id);
        TenantContext::clear();

        $token = app(\App\Domain\Auth\JwtTokenService::class)
            ->issueAccessToken($developer['user'], $owner['tenant']->id);

        $this->withJwt($token)
            ->postJson('/api/v1/sites/'.$site->id.'/commands', [
                'type' => 'update.core',
                'idempotency_key' => 'dev-cmd-1',
            ])
            ->assertForbidden();
    }

    public function test_command_lifecycle_via_plugin(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();

        $command = app(CreateCommandAction::class)->execute(
            $connected['site'],
            'update.plugins',
            'lifecycle-1',
            ['plugin' => 'woocommerce'],
            $owner['user'],
        );
        TenantContext::clear();

        $heartbeat = $this->pluginPost('/plugin/v1/heartbeat', $connected['site']->id, $connected['secret']);
        $heartbeat->assertOk();
        $this->assertTrue(
            collect($heartbeat->json('data.commands'))->pluck('id')->contains($command->id)
        );

        $this->pluginPost(
            '/plugin/v1/commands/'.$command->id.'/complete',
            $connected['site']->id,
            $connected['secret'],
            ['result' => ['updated' => true]],
        )->assertOk()->assertJsonPath('data.status', 'completed');
    }

    public function test_idempotency_key_returns_same_command(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        TenantContext::clear();

        $payload = [
            'type' => 'backup.create',
            'idempotency_key' => 'same-key',
        ];

        $first = $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/commands', $payload)
            ->assertCreated();

        $second = $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/commands', $payload)
            ->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
    }
}
