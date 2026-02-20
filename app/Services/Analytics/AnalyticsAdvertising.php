<?php

namespace App\Services\Analytics;

use App\Models\CsMainProject;
use App\Models\Journal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsAdvertising
{
    public function journal_advertising_count_by_category($month = null, $userId = null)
    {
        $query = Journal::query()
            ->with('journalCategory:id,name,icon')
            ->where('role', 'advertising');

        if ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);

            $query->whereYear('start', $date->year)
                ->whereMonth('start', $date->month);
        } else {
            $query->whereYear('start', now()->year)
                ->whereMonth('start', now()->month);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->select(
            'journal_category_id',
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('journal_category_id')
            ->orderBy('journal_category_id')
            ->get();
    }
}
