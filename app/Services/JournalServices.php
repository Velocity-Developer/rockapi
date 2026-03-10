<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalDetailSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JournalServices
{
    public function store(Request $request): Journal
    {
        // default user_id jika tidak ada
        if (!$request->input('user_id')) {
            $request->merge([
                'user_id' => Auth::id()
            ]);
        }

        // create journal
        $journal = Journal::create([
            'title' => $request->title,
            'description' => $request->description,
            'start' => $request->start,
            'end' => $request->end,
            'status' => $request->status,
            'priority' => $request->priority,
            'user_id' => $request->user_id,
            'role' => $request->role,
            'webhost_id' => $request->webhost_id,
            'cs_main_project_id' => $request->cs_main_project_id,
            'journal_category_id' => $request->journal_category_id,
        ]);

        // simpan detail_support,jika ada data
        if (
            isset($request->detail_support) ||
            isset($request->detail_support['wa']) ||
            isset($request->detail_support['email']) ||
            isset($request->detail_support['biaya']) ||
            isset($request->detail_support['tanggal_bayar'])
        ) {
            $this->handleDetailSupport($journal->id, $request->detail_support);
        }

        // return journal lengkap relasi
        return Journal::with([
            'user',
            'journalCategory',
            'detail_support'
        ])->findOrFail($journal->id);
    }

    private function handleDetailSupport(int $journalId, ?array $detailSupport): void
    {
        if (!$detailSupport) {
            return;
        }

        $hasData = !empty($detailSupport['hp'] ?? null) ||
            !empty($detailSupport['wa'] ?? null) ||
            !empty($detailSupport['email'] ?? null) ||
            !empty($detailSupport['biaya'] ?? null) ||
            !empty($detailSupport['tanggal_bayar'] ?? null);

        if (!$hasData) {
            return;
        }

        JournalDetailSupport::updateOrCreate(
            ['journal_id' => $journalId],
            [
                'hp' => $detailSupport['hp'] ?? '',
                'wa' => $detailSupport['wa'] ?? '',
                'email' => $detailSupport['email'] ?? '',
                'biaya' => $detailSupport['biaya'] ?? null,
                'tanggal_bayar' => $detailSupport['tanggal_bayar'] ?? null,
            ]
        );
    }
}
