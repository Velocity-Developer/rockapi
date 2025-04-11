<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'owner']);
        Role::create(['name' => 'user']);
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
