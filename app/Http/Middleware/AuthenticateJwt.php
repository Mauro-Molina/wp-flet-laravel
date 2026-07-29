<?php

namespace App\Http\Middleware;

use App\Domain\Auth\JwtTokenService;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateJwt
{
    public function __construct(private readonly JwtTokenService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return ApiResponse::error('Unauthenticated.', 401, [
                ['code' => 'unauthenticated', 'message' => 'Bearer token required.'],
            ]);
        }

        try {
            $payload = $this->jwt->decode($matches[1]);
        } catch (Throwable) {
            return ApiResponse::error('Unauthenticated.', 401, [
                ['code' => 'invalid_token', 'message' => 'Access token is invalid or expired.'],
            ]);
        }

        if (($payload->typ ?? null) !== 'access') {
            return ApiResponse::error('Unauthenticated.', 401, [
                ['code' => 'invalid_token', 'message' => 'Access token required.'],
            ]);
        }

        /** @var User|null $user */
        $user = User::query()->find($payload->sub);

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        $tenantId = (string) $payload->tenant_id;

        if (! $user->belongsToTenant($tenantId)) {
            return ApiResponse::error('Forbidden.', 403, [
                ['code' => 'tenant_forbidden', 'message' => 'User does not belong to this tenant.'],
            ]);
        }

        TenantContext::set($tenantId);
        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }
}
