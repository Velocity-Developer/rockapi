<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use App\Models\CsMainProject;
use App\Models\Journal;
use App\Models\User;
use Carbon\Carbon;

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

    public function journal_response_time_avg($month = null, $userId = null)
    {
        $query = Journal::query()
            ->join('journal_categories', 'journals.journal_category_id', '=', 'journal_categories.id')
            ->where('journals.role', 'support')
            ->whereYear('journals.start', date('Y'))
            ->whereNotNull('journals.end');

        // ✅ Filter bulan
        if ($month) {
            // $month format: YYYY-MM (contoh: 2026-02)
            $date = Carbon::createFromFormat('Y-m', $month);

            $query->whereYear('journals.start', $date->year)
                ->whereMonth('journals.start', $date->month);
        } else {
            // default: bulan & tahun sekarang
            $query->whereYear('journals.start', now()->year)
                ->whereMonth('journals.start', now()->month);
        }

        if ($userId) {
            $query->where('journals.user_id', $userId);
        }

        // Clone query untuk total rata-rata
        $queryTotal = clone $query;
        $totalAvg = $queryTotal->select(
            DB::raw('AVG(TIMESTAMPDIFF(MINUTE, journals.start, journals.end)) as total_avg_minutes')
        )->value('total_avg_minutes');
        $totalJournal = $queryTotal->select(
            DB::raw('COUNT(journals.id) as total_journal')
        )->value('total_journal');

        // Clone query untuk data user
        $queryUser = clone $query;
        $dataUser = $queryUser->join('users', 'journals.user_id', '=', 'users.id')
            ->select(
                'users.name as user_name',
                'users.id as user_id',
                DB::raw('COUNT(journals.id) as total_journal'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, journals.start, journals.end)) as avg_minutes')
            )
            ->groupBy('users.name', 'users.id')
            ->get();

        // Clone query untuk rincian user per kategori
        $queryUserDetails = clone $query;
        $dataUserDetails = $queryUserDetails->join('users', 'journals.user_id', '=', 'users.id')
            ->select(
                'users.name as user_name',
                'journal_categories.name as category_name',
                DB::raw('COUNT(journals.id) as total_journal'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, journals.start, journals.end)) as avg_minutes')
            )
            ->groupBy('users.name', 'journal_categories.name')
            ->orderBy('users.name')
            ->orderBy('journal_categories.name')
            ->get();

        // Clone query untuk data harian jika user_id ada
        $dataDaily = [];
        if ($userId) {
            $queryDaily = clone $query;
            $dataDaily = $queryDaily->select(
                DB::raw('DATE(journals.start) as date'),
                DB::raw('COUNT(journals.id) as total_journal'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, journals.start, journals.end)) as avg_minutes')
            )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        $data = $query->select(
            'journal_categories.name as category',
            DB::raw('COUNT(journals.id) as total_journal'),
            DB::raw('AVG(TIMESTAMPDIFF(MINUTE, journals.start, journals.end)) as avg_minutes')
        )
            ->groupBy('category')
            ->get();

        //data user support
        $dataUserSupport = User::role('support')
            ->select('id', 'name')
            ->get();

        return [
            'month'     => $month,
            'user_id'   => $userId,
            'data'      => $data,
            'data_user' => $dataUser,
            'data_user_details' => $dataUserDetails,
            'data_daily' => $dataDaily,
            'total_avg' => $totalAvg,
            'total_journal' => $totalJournal,
            'data_user_support' => $dataUserSupport,
        ];
    }
}
