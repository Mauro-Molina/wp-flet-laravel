<?php

namespace App\Domain\Rbac;

final class Roles
{
    public const OWNER = 'Owner';

    public const ADMIN = 'Admin';

    public const DEVELOPER = 'Developer';

    public const CLIENT_READONLY = 'Client-readonly';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::OWNER,
            self::ADMIN,
            self::DEVELOPER,
            self::CLIENT_READONLY,
        ];
    }
}
