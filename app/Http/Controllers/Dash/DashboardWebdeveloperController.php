<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CsMainProject;
use Carbon\Carbon;

class DashboardWebdeveloperController extends Controller
{

    private $jenis_project_webdeveloper = [
        'Jasa Update Web',
        'Pembuatan',
        'Pembuatan apk custom',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting',
        'Redesign',
        'Pembuatan web konsep',
    ];

    public function welcome()
    {
        //dapatkan total CsMainProject bulan ini
        $totalCsMainProject = CsMainProject::whereMonth('tgl_masuk', date('m'))
            ->whereYear('tgl_masuk', date('Y'))
            ->whereIn('jenis', $this->jenis_project_webdeveloper)
            ->count();

        //tanggal sekarang
        $now = Carbon::now();
        $tanggalSekarang = $now->day;

        // range bulan lalu
        $start = $now->copy()->subMonth()->startOfMonth();
        $end   = $now->copy()->subMonth()->startOfMonth()->addDays($tanggalSekarang - 1);

        $totalCsMainProjectLastMonth = CsMainProject::whereBetween('tgl_masuk', [$start, $end])
            ->whereIn('jenis', $this->jenis_project_webdeveloper)
            ->count();

        //hitung persentase
        $persentase = ($totalCsMainProject - $totalCsMainProjectLastMonth) / $totalCsMainProjectLastMonth * 100;

        return response()->json([
            'total_project_bulanini' => $totalCsMainProject,
            'total_project_bulanlalu' => $totalCsMainProjectLastMonth,
            'perform' => round($persentase, 2),
            'date' => date('m'),
        ]);
    }

    public function chart_project_tahun_terakhir_webdeveloper() {}
}
