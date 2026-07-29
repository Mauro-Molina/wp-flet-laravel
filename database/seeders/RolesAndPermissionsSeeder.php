<?php

namespace Database\Seeders;

use App\Domain\Rbac\Permissions;
use App\Domain\Rbac\Roles;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        TenantContext::bypass(function (): void {
            foreach (Permissions::all() as $permission) {
                Permission::findOrCreate($permission, 'api');
            }

            // Template roles without team (name catalog). Tenant-scoped roles are
            // created on demand via Role::findOrCreate when assigning within a tenant.
            foreach (Roles::all() as $roleName) {
                $role = Role::findOrCreate($roleName, 'api');
                $role->syncPermissions(Permissions::mapForRoles()[$roleName]);
            }
        });
    }
}
