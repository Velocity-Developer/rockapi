<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Setting;
use Carbon\Carbon;

//simpan waktu last cron console
Setting::set('last_cron_console', Carbon::now());

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

Schedule::call(function () {
    \App\Services\CronRekapFormServices::menit();
})->everyMinute();
