<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //get all roles
        $roles = Role::all();

        //create user by looping roles
        foreach ($roles as $role) {

            //create user
            $user = User::create([
                'name'              => $role->name,
                'email'             => $role->name . '@example.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => Str::random(10),
            ]);

            //assign role
            $user->assignRole($role);
        }
    }
}
