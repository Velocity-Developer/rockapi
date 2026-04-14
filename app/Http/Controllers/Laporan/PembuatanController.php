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
            ];
        })->values();

        $sumTotal = $data->sum('total');
        $sumTotalBulanIni = $data->sum('total_bulan_ini');
        $sumTotalBulanSebelumnya = $data->sum('total_bulan_sebelumnya');

        $data = $data->map(function ($item) use ($sumTotal) {
            $item['kontribusi'] = $sumTotal > 0
                ? round(($item['total'] / $sumTotal) * 100, 2)
                : 0;

            return $item;
        })->values();

        $chart = $data->map(function ($item) {
            return [
                'via' => $item['via'],
                'total_bulan_ini' => $item['total_bulan_ini'],
                'total_bulan_sebelumnya' => $item['total_bulan_sebelumnya'],
            ];
        })->values();

        $data->push([
            'via' => 'Total',
            'total_bulan_ini' => $sumTotalBulanIni,
            'total_bulan_sebelumnya' => $sumTotalBulanSebelumnya,
            'total' => $sumTotal,
            'kontribusi' => null,
        ]);
        $data->push([
            'via' => null,
            'total_bulan_ini' => $sumTotal > 0 ? round(($sumTotalBulanIni / $sumTotal) * 100, 2) : 0,
            'total_bulan_sebelumnya' => $sumTotal > 0 ? round(($sumTotalBulanSebelumnya / $sumTotal) * 100, 2) : 0,
            'total' => null,
            'kontribusi' => null,
        ]);

        return response()->json([
            'bulan' => $bulan,
            'bulan_sebelumnya' => $bulanSebelumnya->format('Y-m'),
            'opsi_via' => $this->via,
            'chart' => $chart,
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
