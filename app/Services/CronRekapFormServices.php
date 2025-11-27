<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CronRekapFormServices
{
    //cron tiap 1 menit
    public static function menit()
    {

        Log::channel('cron')->info('CronRekapFormServices | menit | start | ' . now());
        // $response = Http::withHeaders([
        //     'X-API-KEY' => 'pbDXmVtiprhpL0GFqAYY',
        // ])->post('http://localhost:8000/api/public/rekap-form', $data);

        // if ($response->failed()) {
        //     Log::error('Failed to send rekap form', [
        //         'status' => $response->status(),
        //         'body' => $response->body(),
        //     ]);
        // }

        Log::channel('cron')->info('CronRekapFormServices | menit | end | ' . now());
    }
}
