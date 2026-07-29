<?php

namespace App\Domain\Auth;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;

class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'WP Fleet'),
            $user->email,
            $secret,
        );
    }

    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        return $this->google2fa->verifyKey($user->two_factor_secret, $code);
    }

    public function enable(User $user, string $secret, string $code): void
    {
        if (! $this->google2fa->verifyKey($secret, $code)) {
            throw new RuntimeException('Invalid two-factor code.');
        }

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
