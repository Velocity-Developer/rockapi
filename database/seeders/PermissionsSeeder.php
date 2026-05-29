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
            'kelola-improve-chat',
            'manage-csmainproject',
            'manage-webhostsubscription',
            'manage-absensi',
            'manage-klienperpanjang',
            'manage-remindercs'
        ];

        foreach ($permissions as $permission) {
            // check permission
            if (! Permission::where('name', $permission)->exists()) {
                Permission::findOrCreate($permission, 'web');
                $this->command->info('Permission created: ' . $permission);
            }
        }

        // Ambil role admin (pastikan mengambil modelnya secara tepat)
        $role_admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        if ($role_admin) {
            // Ambil nama semua permission ber-guard 'web' dalam bentuk array
            $web_permissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();

            // Sinkronkan menggunakan array nama/ID
            $role_admin->syncPermissions($web_permissions);
            $this->command->info('Berhasil memberikan semua permission ke Admin.');
        }
    }
}
