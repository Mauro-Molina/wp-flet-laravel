<?php

namespace App\Domain\Rbac;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignTenantRole
{
    public function execute(User $user, string $roleName, string $tenantId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        $role = Role::findOrCreate($roleName, 'api');
        $role->syncPermissions(Permissions::mapForRoles()[$roleName] ?? []);

        if (! $user->hasRole($roleName)) {
            $user->assignRole($role);
        }
    }
}
