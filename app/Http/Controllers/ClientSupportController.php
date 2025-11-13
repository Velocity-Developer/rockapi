<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientSupport;
use App\Models\CsMainProjectClientSupport;
use App\Models\WebhostClientSupport;
use Carbon\Carbon;

class ClientSupportController extends Controller
{
    //index
    public function index(Request $request)
    {
        $results    = [];
        $per_page   = $request->input('per_page', 50);
        $per_page   = intval($per_page);
        $tgl_start  = $request->input('tgl_start');
        $tgl_end    = $request->input('tgl_end');

        //array tanggal
        $arrayTanggal = $this->arrayTanggal($per_page, $tgl_start, $tgl_end);

        //get data dari WebhostClientSupport
        $webhostClientSupportData = WebhostClientSupport::with('webhost:id_webhost,nama_web')
            ->whereIn('tanggal', $arrayTanggal)
            ->get();
        if ($webhostClientSupportData->count() > 0) {
            foreach ($webhostClientSupportData as $item) {
                $tgl = $item->tanggal ? Carbon::parse($item->tanggal)->format('Y-m-d') : null;
                $results[$tgl]['tanggal'] = $tgl;
                $results[$tgl][$item->layanan][] = $item->webhost;
            }
        }

        //get data dari CsMainProjectClientSupport
        $csMainProjectClientSupportData = CsMainProjectClientSupport::with('cs_main_project:id,id_webhost,jenis', 'cs_main_project.webhost:id_webhost,nama_web')
            ->whereIn('tanggal', $arrayTanggal)
            ->get();
        if ($csMainProjectClientSupportData->count() > 0) {
            foreach ($csMainProjectClientSupportData as $item) {
                $tgl = $item->tanggal ? Carbon::parse($item->tanggal)->format('Y-m-d') : null;
                $results[$tgl]['tanggal'] = $tgl;
                $item_data = $item->cs_main_project;
                $item_data['nama_web'] = $item->cs_main_project->webhost->nama_web;
                $results[$tgl][$item->layanan][] = $item_data;
            }
        }

        //reset array key 
        $results = array_values($results);
        //balik array
        $results = array_reverse($results);

        return response()->json($results);
    }

    private function arrayTanggal($count = 0, $tgl_start = null, $tgl_end = null)
    {
        $count      = $count ?? 50;
        $tgl_start  = $tgl_start ?? Carbon::now()->toDateString();

        //jika $tgl_end tidak ada, set hari ini + $count
        $tgl_end    = $tgl_end ?? Carbon::now()->addDays($count)->toDateString();

        //array tanggal
        $tanggals = [];
        if ($tgl_start && $tgl_end) {
            //hasilkan tanggal dalam format Y-m-d
            $tanggals = Carbon::parse($tgl_start)->toPeriod($tgl_end)->toArray();
            $tanggals = array_map(function ($item) {
                return $item->format('Y-m-d');
            }, $tanggals);
        }

        return $tanggals;
    }
}
