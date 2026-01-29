<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\CsMainProject;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectProfitController extends Controller
{
    //
    public function index(Request $request)
    {
        $data = [];

        // query
        $query = CsMainProject::with('webhost:id_webhost,nama_web,id_paket', 'webhost.paket:id_paket,paket');

        // filter by month
        $month_start = $request->input('month_start');
        $month_end = $request->input('month_end');
        $jangka_waktu = $request->input('jangka_waktu');

        // jika jangka_waktu = 1, maka filter by month
        if ($month_start && $jangka_waktu) {
            // buat month_end dengan mengurangi jangka_waktu tahun
            $month_end = $month_start;
            $month_start = Carbon::createFromFormat('Y-m', $month_start)->subYear($jangka_waktu)->format('Y-m');
        }

        if ($month_start && $month_end) {

            $month_start = Carbon::createFromFormat('Y-m', $month_start);
            $startOfMonth = $month_start->copy()->startOfMonth()->startOfDay()->toDateTimeString();

            $month_end = Carbon::createFromFormat('Y-m', $month_end);
            $endOfMonth = $month_end->copy()->endOfMonth()->endOfDay()->toDateTimeString();

            $query->whereBetween('tgl_masuk', [$startOfMonth, $endOfMonth]);
        }

        // ambil data dan group by webhost.nama_web
        $raw = $query->get()->groupBy(function ($item) {
            return optional($item->webhost)->nama_web ?: 'Tanpa Webhost';
        });

        // mari kita susun dan hitung
        $data = [];
        $total_profit = 0;
        $kategori_jenis = [];

        if ($raw) {
            foreach ($raw as $web => $projects) {

                $profit_web = 0;

                $results = [];
                foreach ($projects as $project) {
                    $biaya = $project->biaya;
                    $jenis = $project->jenis;

                    if (! isset($kategori_jenis[$jenis])) {
                        $kategori_jenis[$jenis]['biaya'] = 0;
                        $kategori_jenis[$jenis]['count'] = 0;
                    }
                    if (! isset($results[$jenis])) {
                        $results[$jenis]['biaya'] = 0;
                        $results[$jenis]['count'] = 0;
                    }

                    $results[$jenis]['count'] += 1;
                    $results[$jenis]['biaya'] += $biaya;

                    $kategori_jenis[$jenis]['count'] += 1;
                    $kategori_jenis[$jenis]['biaya'] += $biaya;

                    $total_profit += $biaya;
                    $profit_web += $biaya;
                }

                $data[] = [
                    'nama_web' => $web,
                    'total' => $projects->sum('biaya'),
                    // 'projects'      => $projects,
                    'count' => $projects->count(),
                    'profit' => $profit_web,
                    'results' => $results,
                ];
            }
        }

        return response()->json([
            'data' => $data,
            // 'raw'               => $raw,
            'month_start' => $startOfMonth,
            'month_end' => $endOfMonth,
            'total_profit' => $total_profit,
            'kategori_jenis' => $kategori_jenis,
            'bulan' => $this->bulans($month_start, $month_end),
        ]);
    }

    // buat array bulan
    private function bulans($start, $end)
    {
        // Rentang bulan
        // $start = Carbon::createFromFormat('Y-m', $start);
        // $end   = Carbon::createFromFormat('Y-m', $end);
        // $start = Carbon::createFromFormat('Y-m', '2024-01');
        // $end   = Carbon::createFromFormat('Y-m', '2025-01');

        // Inisialisasi array hasil
        $months = [];

        // Loop sampai akhir bulan (termasuk)
        while ($start <= $end) {
            $months[] = $start->translatedFormat('F Y'); // "Agustus 2024"
            $start->addMonth();
        }

        return $months;
    }
}
