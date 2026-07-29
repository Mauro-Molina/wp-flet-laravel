<?php

namespace App\Domain\Hmac;

use RuntimeException;

class HmacService
{
    public function sign(string $secret, string $timestamp, string $payload): string
    {
        return hash_hmac(
            config('hmac.algorithm', 'sha256'),
            $timestamp."\n".$payload,
            $secret,
        );
    }

    public function verify(string $secret, string $timestamp, string $payload, string $signature): bool
    {
        $expected = $this->sign($secret, $timestamp, $payload);

        return hash_equals($expected, $signature);
    }

    public function assertTimestampFresh(string $timestamp): void
    {
        if (! ctype_digit($timestamp)) {
            throw new RuntimeException('Invalid timestamp format.');
        }

        $ts = (int) $timestamp;
        $now = time();
        $window = (int) config('hmac.replay_window_seconds', 300);

        if ($ts < $now - $window || $ts > $now + $window) {
            throw new RuntimeException('Request timestamp outside allowed window.');
        }
    }

    public function hashSecret(string $plainSecret): string
    {
        return hash('sha256', $plainSecret);
    }

    public function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Sign outbound payload for plugin verification (commands).
     *
     * @return array{signature: string, timestamp: string}
     */
    public function signOutbound(string $secret, array $payload): array
    {
        $timestamp = (string) time();
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return [
            'signature' => $this->sign($secret, $timestamp, $body),
            'timestamp' => $timestamp,
        ];
    }
}
