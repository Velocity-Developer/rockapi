<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //default admin users
        $user = User::create([
            'name'              => 'admin',
            'email'             => 'admin@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'remember_token'    => Str::random(10),
        ]);
        //default owner users
        $user = User::create([
            'name'              => 'owner',
            'email'             => 'owner@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'role'              => 'owner',
            'remember_token'    => Str::random(10),
        ]);
        //default manager_project users
        $user = User::create([
            'name'              => 'manager_project',
            'email'             => 'manager_project@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'role'              => 'manager_project',
            'remember_token'    => Str::random(10),
        ]);
        //default finance
        $user = User::create([
            'name'              => 'finance',
            'email'             => 'finance@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'role'              => 'finance',
            'remember_token'    => Str::random(10),
        ]);
    }
}
