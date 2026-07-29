<?php

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Exceptions\TenantContextMissingException;

/**
 * Request-scoped active tenant. Global scopes and Policies read from here.
 */
final class TenantContext
{
    private static ?string $tenantId = null;

    private static bool $bypassed = false;

    public static function set(?string $tenantId): void
    {
        self::$tenantId = $tenantId;
        self::$bypassed = false;

        if ($tenantId !== null && function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenantId);
        }
    }

    public static function id(): ?string
    {
        return self::$tenantId;
    }

    public static function idOrFail(): string
    {
        if (self::$tenantId === null) {
            throw new TenantContextMissingException;
        }

        return self::$tenantId;
    }

    public static function check(): bool
    {
        return self::$tenantId !== null;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
        self::$bypassed = false;

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId(null);
        }
    }

    /**
     * Temporarily disable tenant scoping (seeders, cross-tenant admin jobs only).
     */
    public static function bypass(callable $callback): mixed
    {
        $previousId = self::$tenantId;
        $previousBypass = self::$bypassed;

        self::$bypassed = true;
        self::$tenantId = null;

        try {
            return $callback();
        } finally {
            self::$tenantId = $previousId;
            self::$bypassed = $previousBypass;
        }
    }

    public static function isBypassed(): bool
    {
        return self::$bypassed;
    }
}
