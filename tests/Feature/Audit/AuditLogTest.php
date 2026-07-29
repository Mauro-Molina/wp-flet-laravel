<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Site;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_sensitive_actions_are_audited_automatically(): void
    {
        $owner = $this->createTenantOwner();

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/sites', [
                'name' => 'Audited Site',
                'url' => 'https://audited.example.com',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('audit_log', [
            'action' => 'Site.created',
            'tenant_id' => $owner['tenant']->id,
        ]);

        $this->assertDatabaseHas('audit_log', [
            'actor_id' => $owner['user']->id,
        ]);
    }

    public function test_audit_log_rejects_updates(): void
    {
        $owner = $this->createTenantOwner();

        $log = AuditLog::query()->create([
            'tenant_id' => $owner['tenant']->id,
            'actor_id' => $owner['user']->id,
            'actor_type' => 'user',
            'action' => 'test',
            'created_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $log->update(['action' => 'mutated']);
    }
}
