<?php

namespace App\Services\Analytics;

use App\Models\FollowupAdvertiser;
use App\Models\CsMainProject;
use App\Models\Webhost;

class FollowupAdvertiserAnalytics
{

    //get CsMainProject dengan pembuatan yang memiliki data followupAdvsetiser
    public function cs_main_project_blm_followup($bulan)
    {
        $jenis_pembuatan = ['Pembuatan', 'Pembuatan apk', 'Pembuatan apk custom', 'Pembuatan web konsep', 'Pembuatan Tanpa Domain', 'Pembuatan Tanpa Hosting', 'Pembuatan Tanpa Domain+Hosting'];

        $result = CsMainProject::with('webhost:id_webhost,nama_web,id_paket,wa', 'webhost.paket')
            ->whereIn('jenis', $jenis_pembuatan)
            ->where('tgl_masuk', 'like', $bulan . '%')
            ->whereHas('webhost', function ($query) {
                $query->doesntHave('followup_advertiser');
            })
            ->count();

        return $result;
    }

    public function count_by_status($bulan)
    {
        return FollowupAdvertiser::selectRaw('status_ads, COUNT(*) as total')
            ->where('update_ads', 'like', $bulan . '%')
            ->groupBy('status_ads')
            ->pluck('total', 'status_ads');
    }
}
