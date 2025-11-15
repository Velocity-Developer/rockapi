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
        $nama_web   = $request->input('nama_web');
        $jenis      = $request->input('jenis');

        //array tanggal
        $arrayTanggal = $this->arrayTanggal($per_page, $tgl_start, $tgl_end);

        //get data dari WebhostClientSupport
        $webhostClientSupportData = WebhostClientSupport::with('webhost:id_webhost,nama_web')
            ->when($tgl_start, function ($query) use ($arrayTanggal) {
                $query->whereIn('tanggal', $arrayTanggal);
            })
            ->when($nama_web, function ($query) use ($nama_web) {
                $query->whereHas('webhost', function ($subQuery) use ($nama_web) {
                    $subQuery->where('nama_web', 'like', '%' . $nama_web . '%');
                });
            })
            ->when($jenis, function ($query) use ($jenis) {
                $query->where('layanan', $jenis);
            })
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
            ->when($tgl_start, function ($query) use ($arrayTanggal) {
                $query->whereIn('tanggal', $arrayTanggal);
            })
            ->when($nama_web, function ($query) use ($nama_web) {
                $query->whereHas('cs_main_project.webhost', function ($subQuery) use ($nama_web) {
                    $subQuery->where('nama_web', 'like', '%' . $nama_web . '%');
                });
            })
            ->when($jenis, function ($query) use ($jenis) {
                $query->where('layanan', $jenis);
            })
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

    //by_tanggal
    public function by_tanggal($tanggal)
    {
        $results    = [];
        $count      = 0;

        //get data dari WebhostClientSupport
        $webhostClientSupportData = WebhostClientSupport::with('webhost:id_webhost,nama_web')
            ->when($tanggal, function ($query) use ($tanggal) {
                $query->where('tanggal', $tanggal);
            })
            ->get();
        if ($webhostClientSupportData->count() > 0) {
            foreach ($webhostClientSupportData as $item) {
                $item_data = $item->webhost;
                $item_data['id'] = $item->id;
                $item_data['layanan'] = $item->layanan;
                $results[$item->layanan][] = $item_data;
                $count++;
            }
        }

        //get data dari CsMainProjectClientSupport
        $csMainProjectClientSupportData = CsMainProjectClientSupport::with('cs_main_project:id,id_webhost,jenis', 'cs_main_project.webhost:id_webhost,nama_web')
            ->when($tanggal, function ($query) use ($tanggal) {
                $query->where('tanggal', $tanggal);
            })
            ->get();
        if ($csMainProjectClientSupportData->count() > 0) {
            foreach ($csMainProjectClientSupportData as $item) {
                $item_data = $item->cs_main_project;
                $item_data['id'] = $item->id;
                $item_data['layanan'] = $item->layanan;
                $item_data['nama_web'] = $item->cs_main_project->webhost->nama_web;
                $item_data['id_webhost'] = $item->cs_main_project->webhost->id_webhost;
                $item_data['cs_main_project_id'] = $item->cs_main_project_id;
                $results[$item->layanan][] = $item_data;
                $count++;
            }
        }

        return response()->json([
            'data' => $results,
            'count' => $count,
        ]);
    }


    //store
    public function store(Request $request)
    {

        //validasi
        $request->validate([
            'jenis' => 'required|string',
            'tanggal' => 'required|date',
            'id_webhost' => 'nullable|integer',
            'id_cs_main_project' => 'nullable|integer',
        ]);

        $jenis = $request->input('jenis');
        $tanggal = $request->input('tanggal');
        $id_webhost = $request->input('id_webhost');
        $id_cs_main_project = $request->input('id_cs_main_project');

        //ubah format tanggal
        $tanggal = Carbon::parse($tanggal)->format('Y-m-d H:i:s');

        ///jika jenis = tanya_jawab,create WebhostClientSupport
        if ($jenis == 'tanya_jawab') {
            $NewClientSupport = WebhostClientSupport::create([
                'layanan' => $jenis,
                'tanggal' => $tanggal,
                'webhost_id' => $id_webhost,
            ]);
        }
        ///jika bukan tanya_jawab ,create CsMainProjectClientSupport
        if ($jenis != 'tanya_jawab') {
            $NewClientSupport = CsMainProjectClientSupport::create([
                'layanan' => $jenis,
                'tanggal' => $tanggal,
                'cs_main_project_id' => $id_cs_main_project,
            ]);
        }

        //simpan data legacy
        // ClientSupport::updateOrCreate([
        //     'tanggal' => $tanggal
        // ], [
        //     'layanan'               => $jenis,
        //     'webhost_id'            => $id_webhost,
        //     'cs_main_project_id'    => $id_cs_main_project,
        // ]);

        return response()->json($NewClientSupport);
    }

    //destroy
    public function destroy(Request $request)
    {
        //validasi
        $request->validate([
            'id' => 'required|integer',
            'tanggal' => 'required|date',
            'layanan' => 'required|string',
        ]);
        $id = $request->input('id');
        $tanggal = $request->input('tanggal');
        $layanan = $request->input('layanan');

        //ubah format tanggal
        $tanggal = Carbon::parse($tanggal)->format('Y-m-d 00:00:00');

        //jika layanan = tanya_jawab,hapus WebhostClientSupport
        if ($layanan == 'tanya_jawab') {
            $ClientSupport = WebhostClientSupport::where('layanan', $layanan)
                ->where('tanggal', $tanggal)
                ->where('id', $id)
                ->delete();
        }
        //jika layanan bukan tanya_jawab,hapus CsMainProjectClientSupport
        if ($layanan != 'tanya_jawab') {
            $ClientSupport = CsMainProjectClientSupport::where('layanan', $layanan)
                ->where('tanggal', $tanggal)
                ->where('id', $id)
                ->delete();
        }

        return response()->json($ClientSupport);
    }
}
