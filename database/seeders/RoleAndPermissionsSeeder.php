<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //buat default permission
        Permission::create(['name' => 'view-dashboard']);
        Permission::create(['name' => 'edit-setting']);
        Permission::create(['name' => 'create-post']);
        Permission::create(['name' => 'edit-post']);
        Permission::create(['name' => 'delete-post']);

        //buat default role
        $role_admin = Role::create(['name' => 'admin']);
        $role_admin->givePermissionTo(Permission::all());

        $role_owner = Role::create(['name' => 'owner']);
        $role_owner->givePermissionTo([
            'view-dashboard',
            'edit-setting',
            'create-post',
            'edit-post',
            'delete-post',
        ]);

        $role_user = Role::create(['name' => 'user']);
        $role_user->givePermissionTo([
            'view-dashboard',
        ]);

        // Role::create(['name' => 'manager_project']);
        // Role::create(['name' => 'manager_advertising']);
        // Role::create(['name' => 'finance']);
        // Role::create(['name' => 'support']);
        // Role::create(['name' => 'revisi']);
        // Role::create(['name' => 'advertising']);
        // Role::create(['name' => 'webdev']);
        // Role::create(['name' => 'advertising_internal']);
        // Role::create(['name' => 'budi']);

    }
}
