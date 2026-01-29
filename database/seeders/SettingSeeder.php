<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

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
