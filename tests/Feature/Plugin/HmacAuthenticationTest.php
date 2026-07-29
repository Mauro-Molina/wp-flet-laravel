<?php

namespace Tests\Feature\Plugin;

use App\Domain\Commands\CreateCommandAction;
use App\Domain\Hmac\HmacService;
use App\Enums\LicenseStatus;
use App\Models\SiteLicense;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HmacAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_valid_hmac_allows_heartbeat(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        $this->pluginPost('/plugin/v1/heartbeat', $connected['site']->id, $connected['secret'])
            ->assertOk()
            ->assertJsonPath('data.status', 'connected');
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        $this->withHeaders([
            'X-Site-Id' => $connected['site']->id,
            'X-Timestamp' => (string) time(),
            'X-Signature' => 'invalid',
            'Accept' => 'application/json',
        ])->postJson('/plugin/v1/heartbeat', [])
            ->assertUnauthorized()
            ->assertJsonPath('errors.0.code', 'invalid_signature');
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $owner = $this->createTenantOwner();
        $connected = $this->createConnectedSite($owner['tenant']);

        $stale = (string) (time() - 600);
        $body = '{}';
        $signature = app(HmacService::class)->sign($connected['secret'], $stale, $body);

        $this->call(
            'POST',
            '/plugin/v1/heartbeat',
            [],
            [],
            [],
            [
                'HTTP_X-Site-Id' => $connected['site']->id,
                'HTTP_X-Timestamp' => $stale,
                'HTTP_X-Signature' => $signature,
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body,
        )
            ->assertUnauthorized()
            ->assertJsonPath('errors.0.code', 'hmac_error');
    }
}
