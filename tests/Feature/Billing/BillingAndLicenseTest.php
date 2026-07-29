<?php

namespace Tests\Feature\Billing;

use App\Domain\Licensing\LicenseSyncService;
use App\Domain\Tenancy\TenantContext;
use App\Enums\LicenseStatus;
use App\Models\SiteLicense;
use App\Models\TenantSubscription;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingAndLicenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_billing_status_returns_usage(): void
    {
        $owner = $this->createTenantOwner();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/billing/status')
            ->assertOk()
            ->assertJsonStructure(['data' => ['plan', 'usage', 'subscription_status']]);
    }

    public function test_license_validation_endpoint_is_fresh(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        TenantContext::set($owner['tenant']->id);
        SiteLicense::query()->where('site_id', $connected['site']->id)->update([
            'status' => LicenseStatus::Suspended,
        ]);
        TenantContext::clear();

        $this->withJwt($owner['token'])
            ->getJson('/api/v1/sites/'.$connected['site']->id.'/license')
            ->assertOk()
            ->assertJsonPath('data.license_status', 'suspended')
            ->assertJsonPath('data.allows_commands', false);
    }

    public function test_stripe_payment_failed_moves_sites_to_grace(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        $subscription = TenantSubscription::query()
            ->where('tenant_id', $owner['tenant']->id)
            ->firstOrFail();

        $subscription->forceFill([
            'stripe_subscription_id' => 'sub_test_123',
            'status' => 'active',
        ])->save();

        app(LicenseSyncService::class)->syncTenantSitesToGrace(
            $owner['tenant']->id,
            now()->addDays(7),
        );

        TenantContext::set($owner['tenant']->id);
        $license = SiteLicense::query()->where('site_id', $connected['site']->id)->first();
        $this->assertSame(LicenseStatus::Grace, $license->status);
    }
}
