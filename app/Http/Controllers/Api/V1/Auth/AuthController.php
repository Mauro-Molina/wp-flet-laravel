<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\Actions\LoginUserAction;
use App\Domain\Auth\Actions\RegisterUserAction;
use App\Domain\Auth\JwtTokenService;
use App\Domain\Auth\RefreshTokenService;
use App\Domain\Auth\TwoFactorService;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EnableTwoFactorRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SwitchTenantRequest;
use App\Http\Requests\Auth\VerifyTwoFactorRequest;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->input('tenant_name'),
        );

        return ApiResponse::success([
            'user' => $this->userPayload($result['user']),
            'tenant' => [
                'id' => $result['tenant']->id,
                'name' => $result['tenant']->name,
                'slug' => $result['tenant']->slug,
            ],
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_type' => $result['token_type'],
            'expires_in' => $result['expires_in'],
        ], status: 201);
    }

    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->input('tenant_id'),
        );

        if ($result['requires_two_factor']) {
            return ApiResponse::success([
                'requires_two_factor' => true,
                'challenge_token' => $result['challenge_token'],
                'tenant_id' => $result['tenant_id'],
            ]);
        }

        return ApiResponse::success([
            'requires_two_factor' => false,
            'user' => $this->userPayload($result['user']),
            'tenant_id' => $result['tenant_id'],
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_type' => $result['token_type'],
            'expires_in' => $result['expires_in'],
        ]);
    }

    public function verifyTwoFactor(VerifyTwoFactorRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action->completeTwoFactor(
            $request->string('challenge_token')->toString(),
            $request->string('code')->toString(),
        );

        return ApiResponse::success([
            'user' => $this->userPayload($result['user']),
            'tenant_id' => $result['tenant_id'],
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_type' => $result['token_type'],
            'expires_in' => $result['expires_in'],
        ]);
    }

    public function refresh(RefreshTokenRequest $request, RefreshTokenService $refreshTokens, JwtTokenService $jwt): JsonResponse
    {
        try {
            $rotated = $refreshTokens->rotate($request->string('refresh_token')->toString());
        } catch (RuntimeException) {
            return ApiResponse::error('Invalid refresh token.', 401, [
                ['code' => 'invalid_refresh_token', 'message' => 'Refresh token is invalid or revoked.'],
            ]);
        }

        TenantContext::set($rotated['tenant_id']);

        return ApiResponse::success([
            'access_token' => $jwt->issueAccessToken($rotated['user'], $rotated['tenant_id']),
            'refresh_token' => $rotated['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.access_ttl', 900),
            'tenant_id' => $rotated['tenant_id'],
        ]);
    }

    public function logout(Request $request, RefreshTokenService $refreshTokens, AuditLogger $audit): JsonResponse
    {
        $refresh = $request->input('refresh_token');

        if (is_string($refresh) && $refresh !== '') {
            $refreshTokens->revoke($refresh);
        } else {
            /** @var User $user */
            $user = $request->user();
            $refreshTokens->revokeAllForUser($user);
        }

        $audit->log('auth.logout');

        return ApiResponse::success(['logged_out' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success([
            'user' => $this->userPayload($user),
            'tenant_id' => TenantContext::id(),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function switchTenant(
        SwitchTenantRequest $request,
        JwtTokenService $jwt,
        RefreshTokenService $refreshTokens,
        AuditLogger $audit,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $tenantId = $request->string('tenant_id')->toString();

        if (! $user->belongsToTenant($tenantId)) {
            return ApiResponse::error('Forbidden.', 403, [
                ['code' => 'tenant_forbidden', 'message' => 'User does not belong to this tenant.'],
            ]);
        }

        TenantContext::set($tenantId);
        $refresh = $refreshTokens->issue($user, $tenantId);

        $audit->log('auth.switch_tenant', null, null, ['tenant_id' => $tenantId]);

        return ApiResponse::success([
            'tenant_id' => $tenantId,
            'access_token' => $jwt->issueAccessToken($user, $tenantId),
            'refresh_token' => $refresh['token'],
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.access_ttl', 900),
        ]);
    }

    public function beginTwoFactorSetup(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasAnyRole(['Owner', 'Admin'])) {
            return ApiResponse::error('Forbidden.', 403, [
                ['code' => 'forbidden', 'message' => '2FA setup is required only for Owner/Admin.'],
            ]);
        }

        $secret = $twoFactor->generateSecret();
        $request->session()?->put('pending_2fa_secret', $secret);

        // Stateless API: return secret once; client confirms via enable endpoint.
        return ApiResponse::success([
            'secret' => $secret,
            'otpauth_url' => $twoFactor->qrCodeUrl($user, $secret),
        ]);
    }

    public function enableTwoFactor(EnableTwoFactorRequest $request, TwoFactorService $twoFactor, AuditLogger $audit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $twoFactor->enable(
                $user,
                $request->string('secret')->toString(),
                $request->string('code')->toString(),
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, [
                ['code' => 'invalid_2fa_code', 'message' => $e->getMessage()],
            ]);
        }

        $audit->log('auth.2fa_enabled', $user);

        return ApiResponse::success(['two_factor_enabled' => true]);
    }

    public function disableTwoFactor(Request $request, TwoFactorService $twoFactor, AuditLogger $audit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasAnyRole(['Owner', 'Admin'])) {
            return ApiResponse::error('Forbidden.', 403, [
                ['code' => 'forbidden', 'message' => '2FA is only applicable to Owner/Admin roles.'],
            ]);
        }

        $twoFactor->disable($user);
        $audit->log('auth.2fa_disabled', $user);

        return ApiResponse::success(['two_factor_enabled' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'two_factor_enabled' => $user->two_factor_enabled,
        ];
    }
}
