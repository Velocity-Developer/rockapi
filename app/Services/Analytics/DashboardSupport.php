<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use App\Models\CsMainProject;
use App\Models\Journal;

class DashboardSupport
{
    public function project_support_paket()
    {
        $data = CsMainProject::query()
            ->join('tb_webhost', 'tb_cs_main_project.id_webhost', '=', 'tb_webhost.id_webhost')
            ->join('tb_paket', 'tb_webhost.id_paket', '=', 'tb_paket.id_paket')
            ->join('cs_main_project_infos', 'tb_cs_main_project.id', '=', 'cs_main_project_infos.cs_main_project_id')
            ->where('cs_main_project_infos.jenis_project', 28)
            ->whereMonth('tb_cs_main_project.tgl_masuk', date('m'))
            ->whereYear('tb_cs_main_project.tgl_masuk', date('Y'))
            ->select('tb_paket.paket', DB::raw('count(*) as total'))
            ->groupBy('tb_paket.paket')
            ->get();

        return $data;
    }

    public function journal_support_daily()
    {
        $data = Journal::query()
            ->join('journal_categories', 'journals.journal_category_id', '=', 'journal_categories.id')
            ->where('journals.role', 'support')
            ->whereMonth('journals.start', date('m'))
            ->whereYear('journals.start', date('Y'))
            ->select(
                DB::raw('DATE(journals.start) as date'),
                'journal_categories.name as category',
                DB::raw('count(*) as total')
            )
            ->groupBy('date', 'category')
            ->orderBy('date', 'asc')
            ->get();

        return $data;
    }

    public function dashboard_counts()
    {
        $projek_belum_dikerjakan = CsMainProject::query()
            ->join('cs_main_project_infos', 'tb_cs_main_project.id', '=', 'cs_main_project_infos.cs_main_project_id')
            ->leftJoin('tb_wm_project', 'tb_cs_main_project.id', '=', 'tb_wm_project.id')
            ->where('cs_main_project_infos.jenis_project', 28)
            ->whereNull('tb_wm_project.id')
            ->where('tb_cs_main_project.tgl_masuk', '>', '2026-01-01')
            ->count();

        $projek_dikerjakan_bulan = CsMainProject::query()
            ->join('cs_main_project_infos', 'tb_cs_main_project.id', '=', 'cs_main_project_infos.cs_main_project_id')
            ->join('tb_wm_project', 'tb_cs_main_project.id', '=', 'tb_wm_project.id')
            ->where('cs_main_project_infos.jenis_project', 28)
            ->where('tb_wm_project.status_project', 'selesai')
            ->whereMonth('tb_cs_main_project.tgl_masuk', date('m'))
            ->whereYear('tb_cs_main_project.tgl_masuk', date('Y'))
            ->count();

        $projek_pending_bulan = CsMainProject::query()
            ->join('cs_main_project_infos', 'tb_cs_main_project.id', '=', 'cs_main_project_infos.cs_main_project_id')
            ->join('tb_wm_project', 'tb_cs_main_project.id', '=', 'tb_wm_project.id')
            ->where('cs_main_project_infos.jenis_project', 28)
            ->where('tb_wm_project.status_project', 'Dalam pengerjaan')
            ->whereMonth('tb_cs_main_project.tgl_masuk', date('m'))
            ->whereYear('tb_cs_main_project.tgl_masuk', date('Y'))
            ->count();

        $jurnal_hari_ini = Journal::query()
            ->where('role', 'support')
            ->whereDate('start', date('Y-m-d'))
            ->count();

        return [
            'projek_belum_dikerjakan' => $projek_belum_dikerjakan,
            'projek_dikerjakan_bulan' => $projek_dikerjakan_bulan,
            'projek_pending_bulan' => $projek_pending_bulan,
            'jurnal_hari_ini' => $jurnal_hari_ini,
        ];
    }
}
