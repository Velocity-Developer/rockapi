<?php

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// simpan waktu last cron console
Setting::set('last_cron_console', Carbon::now());

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

Schedule::call(function () {
    \App\Services\CronRekapFormServices::daily();
})->everyMinute()->after(function () {
    \App\Services\CronRekapFormServices::daily_id();
});

// cron tiap 5 menit untuk full rekap
Schedule::call(function () {
    \App\Services\CronRekapFormServices::full();
})->everyFiveMinutes();

// cron tiap jam untuk full rekap
Schedule::call(function () {
    \App\Services\CronRekapFormServices::full_id();
})->hourly();
