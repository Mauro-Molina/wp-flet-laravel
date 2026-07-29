<?php

namespace Tests\Feature\Auth;

use App\Domain\Auth\TwoFactorService;
use App\Domain\Tenancy\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_owner_with_2fa_enabled_must_complete_challenge(): void
    {
        $owner = $this->createTenantOwner();
        $secret = app(TwoFactorService::class)->generateSecret();
        $google2fa = new Google2FA();

        TenantContext::set($owner['tenant']->id);
        $owner['user']->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ])->save();
        TenantContext::clear();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $owner['user']->email,
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.requires_two_factor', true)
            ->assertJsonStructure(['data' => ['challenge_token', 'tenant_id']]);

        $code = $google2fa->getCurrentOtp($secret);

        $verified = $this->postJson('/api/v1/auth/2fa/verify', [
            'challenge_token' => $login->json('data.challenge_token'),
            'code' => $code,
        ]);

        $verified->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    }

    public function test_owner_can_enable_and_disable_two_factor(): void
    {
        $owner = $this->createTenantOwner();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $code = (new Google2FA())->getCurrentOtp($secret);

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/auth/2fa/enable', [
                'secret' => $secret,
                'code' => $code,
            ])
            ->assertOk()
            ->assertJsonPath('data.two_factor_enabled', true);

        $this->withJwt($owner['token'])
            ->postJson('/api/v1/auth/2fa/disable')
            ->assertOk()
            ->assertJsonPath('data.two_factor_enabled', false);

        $owner['user']->refresh();
        $this->assertFalse($owner['user']->two_factor_enabled);
    }
}
