<?php

namespace App\Domain\Rbac;

/**
 * Atomic, composable permissions. Custom roles (later) assemble these.
 */
final class Permissions
{
    public const TENANTS_MANAGE = 'tenants.manage';

    public const USERS_VIEW = 'users.view';

    public const USERS_MANAGE = 'users.manage';

    public const SITES_VIEW = 'sites.view';

    public const SITES_MANAGE = 'sites.manage';

    public const SITES_CONNECT = 'sites.connect';

    public const COMMANDS_VIEW = 'commands.view';

    public const COMMANDS_CREATE = 'commands.create';

    public const BACKUPS_VIEW = 'backups.view';

    public const BACKUPS_CREATE = 'backups.create';

    public const BACKUPS_RESTORE = 'backups.restore';

    public const UPDATES_VIEW = 'updates.view';

    public const UPDATES_RUN = 'updates.run';

    public const SECURITY_VIEW = 'security.view';

    public const CONTENT_VIEW = 'content.view';

    public const CONTENT_MANAGE = 'content.manage';

    public const BILLING_VIEW = 'billing.view';

    public const BILLING_MANAGE = 'billing.manage';

    public const AUDIT_VIEW = 'audit.view';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::TENANTS_MANAGE,
            self::USERS_VIEW,
            self::USERS_MANAGE,
            self::SITES_VIEW,
            self::SITES_MANAGE,
            self::SITES_CONNECT,
            self::COMMANDS_VIEW,
            self::COMMANDS_CREATE,
            self::BACKUPS_VIEW,
            self::BACKUPS_CREATE,
            self::BACKUPS_RESTORE,
            self::UPDATES_VIEW,
            self::UPDATES_RUN,
            self::SECURITY_VIEW,
            self::CONTENT_VIEW,
            self::CONTENT_MANAGE,
            self::BILLING_VIEW,
            self::BILLING_MANAGE,
            self::AUDIT_VIEW,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function mapForRoles(): array
    {
        $all = self::all();

        return [
            Roles::OWNER => $all,
            Roles::ADMIN => array_values(array_diff($all, [
                self::BILLING_MANAGE,
                self::TENANTS_MANAGE,
            ])),
            Roles::DEVELOPER => [
                self::SITES_VIEW,
                self::COMMANDS_VIEW,
                self::COMMANDS_CREATE,
                self::UPDATES_VIEW,
                self::UPDATES_RUN,
                self::BACKUPS_VIEW,
                self::BACKUPS_CREATE,
                self::SECURITY_VIEW,
                self::CONTENT_VIEW,
                self::CONTENT_MANAGE,
            ],
            Roles::CLIENT_READONLY => [
                self::SITES_VIEW,
                self::COMMANDS_VIEW,
                self::UPDATES_VIEW,
                self::BACKUPS_VIEW,
                self::SECURITY_VIEW,
                self::CONTENT_VIEW,
                self::BILLING_VIEW,
            ],
        ];
    }
}
