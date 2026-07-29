<?php

namespace App\Domain\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RefreshTokenService
{
    public function issue(User $user, string $tenantId, ?string $familyId = null): array
    {
        $plain = Str::random(64);
        $familyId ??= (string) Str::uuid();

        RefreshToken::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'tenant_id' => $tenantId,
            'token_hash' => hash('sha256', $plain),
            'family_id' => $familyId,
            'expires_at' => now()->addSeconds((int) config('jwt.refresh_ttl', 60 * 60 * 24 * 30)),
            'user_agent' => request()?->userAgent(),
            'ip_address' => request()?->ip(),
        ]);

        return [
            'token' => $plain,
            'family_id' => $familyId,
        ];
    }

    public function rotate(string $plainToken): array
    {
        return DB::transaction(function () use ($plainToken) {
            $hash = hash('sha256', $plainToken);

            /** @var RefreshToken|null $existing */
            $existing = RefreshToken::query()->where('token_hash', $hash)->lockForUpdate()->first();

            if ($existing === null || ! $existing->isValid()) {
                if ($existing !== null) {
                    $this->revokeFamily($existing->family_id);
                }

                throw new RuntimeException('Invalid refresh token.');
            }

            $existing->forceFill([
                'revoked_at' => now(),
            ])->save();

            $rotated = $this->issue($existing->user, $existing->tenant_id, $existing->family_id);

            $existing->forceFill([
                'replaced_by' => hash('sha256', $rotated['token']),
            ])->save();

            return [
                'user' => $existing->user,
                'tenant_id' => $existing->tenant_id,
                'refresh_token' => $rotated['token'],
            ];
        });
    }

    public function revoke(string $plainToken): void
    {
        $hash = hash('sha256', $plainToken);

        RefreshToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeFamily(string $familyId): void
    {
        RefreshToken::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllForUser(User $user): void
    {
        RefreshToken::query()
            ->where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
