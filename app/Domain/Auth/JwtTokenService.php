<?php

namespace App\Domain\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use stdClass;

class JwtTokenService
{
    public function issueAccessToken(User $user, string $tenantId, array $extraClaims = []): string
    {
        $now = time();
        $ttl = (int) config('jwt.access_ttl', 900);

        $payload = array_merge([
            'iss' => config('app.url'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'jti' => (string) Str::uuid(),
            'sub' => (string) $user->getKey(),
            'tenant_id' => $tenantId,
            'typ' => 'access',
        ], $extraClaims);

        return JWT::encode($payload, $this->secret(), 'HS256');
    }

    /**
     * Short-lived token used between password check and TOTP verification.
     */
    public function issueChallengeToken(User $user, string $tenantId): string
    {
        $now = time();

        $payload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + (int) config('jwt.challenge_ttl', 300),
            'jti' => (string) Str::uuid(),
            'sub' => (string) $user->getKey(),
            'tenant_id' => $tenantId,
            'typ' => '2fa_challenge',
        ];

        return JWT::encode($payload, $this->secret(), 'HS256');
    }

    public function decode(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->secret(), 'HS256'));
    }

    private function secret(): string
    {
        $secret = config('jwt.secret') ?: config('app.key');

        if (str_starts_with((string) $secret, 'base64:')) {
            $secret = base64_decode(substr((string) $secret, 7), true) ?: $secret;
        }

        return (string) $secret;
    }
}
