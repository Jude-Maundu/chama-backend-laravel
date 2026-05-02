<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $config = config('permissions');

        // Create Permissions
        foreach ($config['groups'] as $group => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
        }

        // Additional permissions from member role if not in groups
        foreach ($config['roles']['member']['permissions'] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        foreach ($config['roles'] as $roleName => $details) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            
            if ($details['permissions'] === '*') {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($details['permissions']);
            }
        }
    }
}
