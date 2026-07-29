<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\JwtTokenService;
use App\Domain\Auth\RefreshTokenService;
use App\Domain\Auth\TwoFactorService;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public function __construct(
        private readonly JwtTokenService $jwt,
        private readonly RefreshTokenService $refreshTokens,
        private readonly TwoFactorService $twoFactor,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $email, string $password, ?string $tenantId = null): array
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $tenantId = $tenantId
            ?? $user->tenants()->wherePivot('is_default', true)->value('tenants.id')
            ?? $user->tenants()->value('tenants.id');

        if ($tenantId === null || ! $user->belongsToTenant($tenantId)) {
            throw ValidationException::withMessages([
                'tenant_id' => ['No accessible tenant for this account.'],
            ]);
        }

        TenantContext::set($tenantId);

        if ($user->requiresTwoFactor()) {
            $challenge = $this->jwt->issueChallengeToken($user, $tenantId);

            $this->audit->log('auth.login_2fa_required', $user);

            return [
                'requires_two_factor' => true,
                'challenge_token' => $challenge,
                'tenant_id' => $tenantId,
            ];
        }

        return $this->issueSession($user, $tenantId);
    }

    /**
     * @return array<string, mixed>
     */
    public function completeTwoFactor(string $challengeToken, string $code): array
    {
        $payload = $this->jwt->decode($challengeToken);

        if (($payload->typ ?? null) !== '2fa_challenge') {
            throw ValidationException::withMessages([
                'challenge_token' => ['Invalid challenge token.'],
            ]);
        }

        /** @var User $user */
        $user = User::query()->findOrFail($payload->sub);
        TenantContext::set($payload->tenant_id);

        if (! $this->twoFactor->verify($user, $code)) {
            $this->audit->log('auth.2fa_failed', $user);
            throw ValidationException::withMessages([
                'code' => ['Invalid authentication code.'],
            ]);
        }

        return $this->issueSession($user, $payload->tenant_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function issueSession(User $user, string $tenantId): array
    {
        TenantContext::set($tenantId);

        $refresh = $this->refreshTokens->issue($user, $tenantId);
        $access = $this->jwt->issueAccessToken($user, $tenantId);

        $this->audit->log('auth.login', $user);

        return [
            'requires_two_factor' => false,
            'user' => $user,
            'tenant_id' => $tenantId,
            'access_token' => $access,
            'refresh_token' => $refresh['token'],
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.access_ttl', 900),
        ];
    }
}
