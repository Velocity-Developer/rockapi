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
}
