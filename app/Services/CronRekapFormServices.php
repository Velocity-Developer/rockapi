<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CronRekapFormServices
{
    // cron tiap 1 menit untuk daily
    public static function daily()
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('VELOCITYCOM_API_KEY'),
        ])->get('https://velocitydeveloper.com/wp-json/greeting/v1/rekap?type=today');

        if ($response->failed()) {
            Log::error('Failed to send rekap form', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            Log::channel('cron')->info('CronRekapFormServices | menit | gagal | '.$response->body());
        }

        $data = $response->json();
        // jika success = true
        if ($data['success'] == true) {
            // ambil semua data.data
            $data_rekap = $data['data'];

            // mapping
            $data_rekap = collect($data['data'])->map(function ($item) {

                return [
                    // mapping sesuai kolom DB kamu
                    'nama' => $item['nama'] ?? null,
                    'no_whatsapp' => $item['no_whatsapp'] ?? null,
                    'jenis_website' => $item['jenis_website'] ?? null,
                    'ai_result' => $item['ai_result'] ?? null,
                    'via' => $item['via'] ?? null,
                    'utm_content' => $item['utm_content'] ?? null,
                    'utm_medium' => $item['utm_medium'] ?? null,
                    'greeting' => $item['greeting'] ?? null,
                    'status' => $item['status'] ?? null,
                    'gclid' => $item['gclid'] ?? null,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => now(),
                    'label' => $item['label'] ?? null,

                    // inject manual
                    'source' => 'vdcom',
                    'source_id' => (int) ($item['id'] ?? 0),
                ];
            })->filter(fn ($row) => ! empty($row['source_id']))->values()->toArray();

            // simpan semua data_rekap ke table rekap_form menggunakan upsert untuk bulk insert/update
            $rekapForm = \App\Models\RekapForm::upsert(
                $data_rekap,
                ['source', 'source_id']  // uniqueBy column(s) - akan update semua kolom jika id sudah ada
            );
            // Log::channel('cron')->info('CronRekapFormServices | menit | berhasil | ' . count($data_rekap) . ' records processed');

            return $rekapForm;
        } else {
            Log::channel('cron')->info('CronRekapFormServices | menit | gagal | '.json_encode($data));
        }

        // simpan waktu last cron rekapform daily
        Setting::set('last_cron_rekapform_daily', Carbon::now());
    }

    // cron sehari sekali untuk full rekap
    public static function full($per_page = 1000)
    {

        // simpan waktu last cron rekapform full
        Setting::set('last_cron_rekapform_full', Carbon::now());

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('VELOCITYCOM_API_KEY'),
        ])->get('https://velocitydeveloper.com/wp-json/greeting/v1/rekap?type=full&per_page='.$per_page);

        if ($response->failed()) {
            Log::error('Failed to send rekap form', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            Log::channel('cron')->info('CronRekapFormServices | full | gagal | '.$response->body());
        }

        $data = $response->json();
        // jika success = true
        if ($data['success'] == true) {
            // ambil semua data.data
            $data_rekap = $data['data'];

            // mapping
            $data_rekap = collect($data['data'])->map(function ($item) {

                return [
                    // mapping sesuai kolom DB kamu
                    'nama' => $item['nama'] ?? null,
                    'no_whatsapp' => $item['no_whatsapp'] ?? null,
                    'jenis_website' => $item['jenis_website'] ?? null,
                    'ai_result' => $item['ai_result'] ?? null,
                    'via' => $item['via'] ?? null,
                    'utm_content' => $item['utm_content'] ?? null,
                    'utm_medium' => $item['utm_medium'] ?? null,
                    'greeting' => $item['greeting'] ?? null,
                    'status' => $item['status'] ?? null,
                    'gclid' => $item['gclid'] ?? null,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => now(),
                    'label' => $item['label'] ?? null,

                    // inject manual
                    'source' => 'vdcom',
                    'source_id' => (int) ($item['id'] ?? 0),

                ];
            })->filter(fn ($row) => ! empty($row['source_id']))->values()->toArray();

            // simpan semua data_rekap ke table rekap_form menggunakan upsert untuk bulk insert/update
            $rekapForm = \App\Models\RekapForm::upsert(
                $data_rekap,
                ['source', 'source_id'] // uniqueBy column(s) - akan update semua kolom jika id sudah ada
            );
            // Log::channel('cron')->info('CronRekapFormServices | full | berhasil | ' . count($data_rekap) . ' records processed');

            return [
                'count' => $data['count'],
                'count_processed' => count($data_rekap),
                'total_records' => $data['total_records'],
            ];
        } else {
            Log::channel('cron')->info('CronRekapFormServices | full | gagal | '.json_encode($data));
        }
    }

    // cron tiap 1 menit untuk daily di vdcom/id
    public static function daily_id()
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('VELOCITYCOM_API_KEY'),
        ])->get('https://velocitydeveloper.com/id/wp-json/greeting/v1/rekap?type=today');

        if ($response->failed()) {
            Log::error('Failed to send rekap form', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            Log::channel('cron')->info('CronRekapFormServices velocitydeveloper.com/id | menit | gagal | '.$response->body());
        }

        $data = $response->json();
        // jika success = true
        if ($data['success'] == true) {
            // ambil semua data.data
            $data_rekap = $data['data'];

            // mapping
            $data_rekap = collect($data['data'])->map(function ($item) {

                $status = null;
                if ($item['cek_cs'] == 'Sesuai') {
                    $status = 'sesuai';
                } elseif ($item['cek_cs'] == 'Tidak ada nomer') {
                    $status = 'tidak ada nomor';
                } elseif ($item['cek_cs'] == 'Salah sambung') {
                    $status = 'salah sambung';
                }

                return [
                    // mapping sesuai kolom DB kamu
                    'nama' => $item['nama'] ?? null,
                    'no_whatsapp' => $item['no_whatsapp'] ?? null,
                    'jenis_website' => $item['jenis'] ?? null,
                    'ai_result' => $item['ai_result'] ?? null,
                    'via' => $item['device'] ?? null,
                    'status' => $status,
                    'gclid' => $item['gclid'] ?? null,
                    'greeting' => $item['greeting'] ?? null,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => now(),

                    // inject manual
                    'source' => 'vdcom_id',
                    'source_id' => (int) ($item['id'] ?? 0),
                ];
            })->filter(fn ($row) => ! empty($row['source_id']))->values()->toArray();

            // simpan semua data_rekap ke table rekap_form menggunakan upsert untuk bulk insert/update
            $rekapForm = \App\Models\RekapForm::upsert(
                $data_rekap,
                ['source', 'source_id']  // uniqueBy column(s) - akan update semua kolom jika id sudah ada
            );
            // Log::channel('cron')->info('CronRekapFormServices | menit | berhasil | ' . count($data_rekap) . ' records processed');

            return $rekapForm;
        } else {
            Log::channel('cron')->info('CronRekapFormServices velocitydeveloper.com/id | menit | gagal | '.json_encode($data));
        }

        // simpan waktu last cron rekapform daily
        Setting::set('last_cron_rekapform_daily_id', Carbon::now());
    }

    // cron sehari sekali untuk full rekap di vdcom/id
    public static function full_id($per_page = 100)
    {
        Log::channel('cron')->info('CronRekapFormServices velocitydeveloper.com/id | full | start');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('VELOCITYCOM_API_KEY'),
        ])->get('https://velocitydeveloper.com/id/wp-json/greeting/v1/rekap?type=full&per_page='.$per_page);

        if ($response->failed()) {
            Log::error('Failed to send rekap form', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            Log::channel('cron')->info('CronRekapFormServices velocitydeveloper.com/id | full | gagal | '.$response->body());
        }

        $data = $response->json();
        // jika success = true
        if ($data['success'] == true) {
            // ambil semua data.data
            $data_rekap = $data['data'];

            // mapping
            $data_rekap = collect($data['data'])->map(function ($item) {

                $status = null;
                if ($item['cek_cs'] == 'Sesuai') {
                    $status = 'sesuai';
                } elseif ($item['cek_cs'] == 'Tidak ada nomer') {
                    $status = 'tidak ada nomor';
                } elseif ($item['cek_cs'] == 'Salah sambung') {
                    $status = 'salah sambung';
                }

                return [
                    // mapping sesuai kolom DB kamu
                    'nama' => $item['nama'] ?? null,
                    'no_whatsapp' => $item['no_whatsapp'] ?? null,
                    'jenis_website' => $item['jenis'] ?? null,
                    'ai_result' => $item['ai_result'] ?? null,
                    'via' => $item['device'] ?? null,
                    'status' => $status,
                    'gclid' => $item['gclid'] ?? null,
                    'greeting' => $item['greeting'] ?? null,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => now(),

                    // inject manual
                    'source' => 'vdcom_id',
                    'source_id' => (int) ($item['id'] ?? 0),
                ];
            })->filter(fn ($row) => ! empty($row['source_id']))->values()->toArray();

            // simpan semua data_rekap ke table rekap_form menggunakan upsert untuk bulk insert/update
            $rekapForm = \App\Models\RekapForm::upsert(
                $data_rekap,
                ['source', 'source_id'] // uniqueBy column(s) - akan update semua kolom jika id sudah ada
            );
            // Log::channel('cron')->info('CronRekapFormServices | full | berhasil | ' . count($data_rekap) . ' records processed');

            return [
                'count' => $data['count'],
                'count_processed' => count($data_rekap),
                'total_records' => $data['total_records'],
            ];
        } else {
            Log::channel('cron')->info('CronRekapFormServices velocitydeveloper.com/id | full | gagal | '.json_encode($data));
        }

        // simpan waktu last cron rekapform full
        Setting::set('last_cron_rekapform_full_id', Carbon::now());
    }
}
