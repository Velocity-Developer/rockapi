<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Setting::set('app_name', 'Velocity Developer');
        Setting::set('app_description', 'Aplikasi developed by Velocity Developer');
    }
}
