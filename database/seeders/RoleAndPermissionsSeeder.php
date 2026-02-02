<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // buat permission jika belum ada
        $permissions = [
            'page-dashboard',
            'page-users',
            'view-other-user',
            'edit-other-user',
            'create-other-user',
            'edit-user',
            'delete-user',
            'edit-settings',
            'create-post',
            'edit-post',
            'delete-post',
            'edit-term',
            'add-billing',
            'timsupport-journal-perform-tim'
        ];

        foreach ($permissions as $permission) {
            // check permission
            if (! Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
                $this->command->info('Permission created: ' . $permission);
            }
        }

        // buat default role
        $roles = [
            'admin',
            'owner',
            'user',
            'manager_project',
            'manager_advertising',
            'finance',
            'support',
            'revisi',
            'advertising',
            'webdeveloper',
            'advertising_internal',
            'customer_service',
            'budi',
        ];

        foreach ($roles as $role) {
            // check role
            if (! Role::where('name', $role)->exists()) {
                Role::create(['name' => $role]);
                $this->command->info('Role created: ' . $role);
            }

            if ($role == 'admin') {
                $role_admin = Role::where('name', $role)->first();
                $role_admin->givePermissionTo(Permission::all());
            } elseif ($role == 'owner') {
                $role_owner = Role::where('name', $role)->first();
                $role_owner->givePermissionTo([
                    'page-dashboard',
                    'edit-settings',
                    'create-post',
                    'edit-post',
                    'delete-post',
                    'edit-user',
                    'delete-user',
                ]);
            } else {
                $role_user = Role::where('name', $role)->first();
                $role_user->givePermissionTo([
                    'page-dashboard',
                    'edit-user',
                    'delete-user',
                ]);
            }
        }
    }
}
