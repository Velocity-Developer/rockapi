<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Webhost;
use App\Models\CsMainProject;

class PembuatanController extends Controller
{
    private $via = [
        'Whatsapp',
        'Whatsapp 2',
        'Whatsapp 3',
    ];

    private $jenis_pembuatan = [
        'Pembuatan',
        'Pembuatan apk',
        'Pembuatan apk custom',
        'Pembuatan web konsep',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting',
    ];

    public function bulanan(Request $request)
    {
        $bulan = $request->input('bulan'); // format YYYY-MM
        $bulanCarbon = Carbon::parse($bulan);
        $bulanSebelumnya = $bulanCarbon->copy()->subMonth();

        $rawDataBulanIni = $this->getDataBulanan($bulanCarbon);
        $rawDataBulanSebelumnya = $this->getDataBulanan($bulanSebelumnya);

        $data = collect($this->via)->map(function ($via) use ($rawDataBulanIni, $rawDataBulanSebelumnya) {
            $totalBulanIni = (int) ($rawDataBulanIni[$via] ?? 0);
            $totalBulanSebelumnya = (int) ($rawDataBulanSebelumnya[$via] ?? 0);

            return [
                'via' => $via,
                'total_bulan_ini' => $totalBulanIni,
                'total_bulan_sebelumnya' => $totalBulanSebelumnya,
                'total' => $totalBulanIni + $totalBulanSebelumnya,
                'selisih' => $totalBulanIni - $totalBulanSebelumnya,
            ];
        })->values();

        return response()->json([
            'bulan' => $bulan,
            'bulan_sebelumnya' => $bulanSebelumnya->format('Y-m'),
            'opsi_via' => $this->via,
            'data' => $data,
        ]);
    }

    private function getDataBulanan(Carbon $bulan)
    {
        return CsMainProject::query()
            ->join('tb_webhost', 'tb_webhost.id_webhost', '=', 'tb_cs_main_project.id_webhost')
            ->select('tb_webhost.via', DB::raw('COUNT(DISTINCT tb_cs_main_project.id_webhost) as total'))
            ->whereIn('tb_cs_main_project.jenis', $this->jenis_pembuatan)
            ->whereBetween('tb_cs_main_project.tgl_masuk', [
                $bulan->copy()->startOfMonth()->format('Y-m-d'),
                $bulan->copy()->endOfMonth()->format('Y-m-d 23:59:59'),
            ])
            ->whereIn('tb_webhost.via', $this->via)
            ->groupBy('tb_webhost.via')
            ->pluck('total', 'tb_webhost.via');
    }
}
