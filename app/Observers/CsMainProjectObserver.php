<?php

namespace App\Observers;

use App\Models\CsMainProject;
use App\Models\CsMainProjectInfo;

class CsMainProjectObserver
{
    /**
     * Handle the CsMainProject "created" event.
     */
    public function created(CsMainProject $csMainProject): void
    {
        // Otomatis buat CsMainProjectInfo ketika CsMainProject dibuat
        $dikerjakan_oleh = $csMainProject->dikerjakan_oleh;
        $jenis_project = null;
        if ($dikerjakan_oleh) {
            $data = explode(',', $dikerjakan_oleh);
            $result = [];
            foreach ($data as $item) {
                // jika kosong, skip
                if ($item == '') {
                    continue;
                }
                if (preg_match('/^(\d+)\[(\d+)\]$/', $item, $matches)) {
                    $result[] = (int) $matches[1];
                }
            }
            $jenis_project = $result[0] ?? null;
        }

        $csMainProject->cs_main_project_info()->create([
            'cs_main_project_id' => $csMainProject->id,
            'author_id' => auth()->id(), // Menggunakan user yang sedang login
            'jenis_project' => $jenis_project, // Mengambil jenis dari CsMainProject
        ]);
    }

    /**
     * Handle the CsMainProject "updated" event.
     */
    public function updated(CsMainProject $csMainProject): void
    {
        // Update CsMainProjectInfo jika ada perubahan pada CsMainProject

        $dikerjakan_oleh = $csMainProject->dikerjakan_oleh;
        $jenis_project = null;
        if ($dikerjakan_oleh) {
            $data = explode(',', $dikerjakan_oleh);
            $result = [];
            foreach ($data as $item) {
                // jika kosong, skip
                if ($item == '') {
                    continue;
                }
                if (preg_match('/^(\d+)\[(\d+)\]$/', $item, $matches)) {
                    $result[] = (int) $matches[1];
                }
            }
            $jenis_project = $result[0] ?? null;
        }

        $csMainProject->cs_main_project_info()->update([
            'jenis_project' => $jenis_project, // Update jenis_project jika ada perubahan
        ]);
    }

    /**
     * Handle the CsMainProject "deleted" event.
     */
    public function deleted(CsMainProject $csMainProject): void
    {
        //
    }

    /**
     * Handle the CsMainProject "restored" event.
     */
    public function restored(CsMainProject $csMainProject): void
    {
        //
    }

    /**
     * Handle the CsMainProject "force deleted" event.
     */
    public function forceDeleted(CsMainProject $csMainProject): void
    {
        //
    }
}
