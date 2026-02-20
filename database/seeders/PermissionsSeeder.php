<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // daftar permissions
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
            'timsupport-journal-perform-tim',
            'timads-journal-perform-tim',
            'edit-cuti',
        ];

        foreach ($permissions as $permission) {
            // check permission
            if (! Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
                $this->command->info('Permission created: ' . $permission);
            }
        }

        //give admin all
        $role_admin = Role::where('name', 'admin')->first();
        $role_admin->givePermissionTo(Permission::all());
    }
}
