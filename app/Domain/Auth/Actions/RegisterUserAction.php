<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Billing\SubscriptionService;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\JwtTokenService;
use App\Domain\Auth\RefreshTokenService;
use App\Domain\Rbac\AssignTenantRole;
use App\Domain\Rbac\Roles;
use App\Domain\Tenancy\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterUserAction
{
    public function __construct(
        private readonly JwtTokenService $jwt,
        private readonly RefreshTokenService $refreshTokens,
        private readonly AuditLogger $audit,
        private readonly AssignTenantRole $assignTenantRole,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * @return array{user: User, tenant: Tenant, access_token: string, refresh_token: string, token_type: string, expires_in: int}
     */
    public function execute(string $name, string $email, string $password, ?string $tenantName = null): array
    {
        return DB::transaction(function () use ($name, $email, $password, $tenantName) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $slugBase = Str::slug($tenantName ?: $name.'-workspace');
            $slug = $slugBase;
            $i = 1;
            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$i++;
            }

            $tenant = Tenant::query()->create([
                'name' => $tenantName ?: $name."'s Workspace",
                'slug' => $slug,
                'status' => 'active',
            ]);

            $tenant->users()->attach($user->getKey(), [
                'id' => (string) Str::uuid(),
                'is_default' => true,
            ]);

            TenantContext::set($tenant->id);
            $this->assignTenantRole->execute($user, Roles::OWNER, $tenant->id);
            $this->subscriptions->resolveForTenant($tenant->id);

            $refresh = $this->refreshTokens->issue($user, $tenant->id);
            $access = $this->jwt->issueAccessToken($user, $tenant->id);

            $this->audit->log('auth.register', $user, null, [
                'email' => $user->email,
                'tenant_id' => $tenant->id,
            ]);

            return [
                'user' => $user,
                'tenant' => $tenant,
                'access_token' => $access,
                'refresh_token' => $refresh['token'],
                'token_type' => 'Bearer',
                'expires_in' => (int) config('jwt.access_ttl', 900),
            ];
        });
    }
}
