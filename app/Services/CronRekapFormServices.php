<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CronRekapFormServices
{
    //cron tiap 1 menit untuk daily
    public static function daily()
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('VELOCITYCOM_API_KEY'),
        ])->get('https://velocitydeveloper.com/wp-json/greeting/v1/rekap?type=today');

        if ($response->failed()) {
            Log::error('Failed to send rekap form', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            Log::channel('cron')->info('CronRekapFormServices | menit | gagal | ' . $response->body());
        }

        $data = $response->json();
        //jika success = true
        if ($data['success'] == true) {
            //ambil semua data.data
            $data_rekap = $data['data'];
            //simpan semua data_rekap ke table rekap_form menggunakan upsert untuk bulk insert/update
            $rekapForm = \App\Models\RekapForm::upsert(
                $data_rekap,
                ['id'] // uniqueBy column(s) - akan update semua kolom jika id sudah ada
            );
            // Log::channel('cron')->info('CronRekapFormServices | menit | berhasil | ' . count($data_rekap) . ' records processed');
        } else {
            Log::channel('cron')->info('CronRekapFormServices | menit | gagal | ' . json_encode($data));
        }
    }

    //cron sehari sekali untuk full rekap
    public static function full()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('VELOCITYCOM_API_KEY'),
        ])->get('https://velocitydeveloper.com/wp-json/greeting/v1/rekap?type=full&per_page=1000');

        if ($response->failed()) {
            Log::error('Failed to send rekap form', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            Log::channel('cron')->info('CronRekapFormServices | full | gagal | ' . $response->body());
        }

        $data = $response->json();
        //jika success = true
        if ($data['success'] == true) {
            //ambil semua data.data
            $data_rekap = $data['data'];
            //simpan semua data_rekap ke table rekap_form menggunakan upsert untuk bulk insert/update
            $rekapForm = \App\Models\RekapForm::upsert(
                $data_rekap,
                ['id'] // uniqueBy column(s) - akan update semua kolom jika id sudah ada
            );
            // Log::channel('cron')->info('CronRekapFormServices | full | berhasil | ' . count($data_rekap) . ' records processed');
        } else {
            Log::channel('cron')->info('CronRekapFormServices | full | gagal | ' . json_encode($data));
        }
    }
}
