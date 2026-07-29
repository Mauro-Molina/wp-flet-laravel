<?php

namespace Tests\Feature\Backups;

use App\Domain\Tenancy\TenantContext;
use App\Models\Backup;
use App\Models\Site;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_restore_requires_site_name_confirmation(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();

        $backup = Backup::query()->create([
            'tenant_id' => $owner['tenant']->id,
            'site_id' => $connected['site']->id,
            'type' => 'on_demand',
            'status' => 'completed',
            'label' => 'test',
        ]);
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/backups/'.$backup->id.'/restore', [
                'idempotency_key' => 'restore-1',
                'site_name_confirmation' => 'Wrong Name',
                'confirmed_destructive' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.field', 'site_name_confirmation');
    }

    public function test_on_demand_backup_creates_command(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        $connected['site']->forceFill(['status' => 'connected'])->save();
        TenantContext::clear();

        $response = $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites/'.$connected['site']->id.'/backups', [
                'idempotency_key' => 'backup-1',
                'label' => 'Pre-update',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('backups', [
            'site_id' => $connected['site']->id,
            'status' => 'pending',
        ]);
        $this->assertNotNull($response->json('data.command_id'));
    }
}
