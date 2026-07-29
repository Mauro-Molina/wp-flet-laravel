<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlansSeeder::class);
    }

    public function test_register_login_refresh_and_logout(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mauro',
            'email' => 'mauro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Acme Fleet',
        ]);

        $register->assertCreated()
            ->assertJsonPath('data.user.email', 'mauro@example.com')
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'tenant']]);

        $access = $register->json('data.access_token');
        $refresh = $register->json('data.refresh_token');

        $this->withJwt($access)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'mauro@example.com');

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'mauro@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.requires_two_factor', false)
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);

        $rotated = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $login->json('data.refresh_token'),
        ]);

        $rotated->assertOk()->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);

        $this->withJwt($rotated->json('data.access_token'))
            ->postJson('/api/v1/auth/logout', [
                'refresh_token' => $rotated->json('data.refresh_token'),
            ])
            ->assertOk();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $rotated->json('data.refresh_token'),
        ])->assertUnauthorized();
    }

    public function test_access_token_includes_tenant_claim(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $parts = explode('.', $register->json('data.access_token'));
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        $this->assertSame($register->json('data.tenant.id'), $payload['tenant_id']);
        $this->assertSame('access', $payload['typ']);
        $this->assertSame(User::query()->where('email', 'owner@example.com')->value('id'), $payload['sub']);
    }
}
