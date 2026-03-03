<?php

namespace App\Http\Controllers;

use App\Models\Webhost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebhostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {

        $with = $request->query('with');
        if (!$with) {
            $with = [
                'paket',
                'csMainProjects',
                'csMainProjects.wm_project:id_wm_project,id_karyawan,user_id,id,date_mulai,date_selesai,catatan,status_multi,webmaster,status_project',
                'csMainProjects.wm_project.user:id,name,avatar',
                'customers'
            ];
        } else if ($with === 'false') {
            $with = [];
        } else {
            $with = $with ? explode(',', $with) : [];
        }

        $select = $request->query('select');
        if ($select) {
            $select = $select ? explode(',', $select) : [];
        }

        $query = Webhost::query();
        if ($with) {
            $query->with($with);
        }
        if ($select) {
            $query->select($select);
        } else {
            $query->select('id_webhost', 'nama_web', 'kategori');
        }

        $webhost = $query->find($id);

        // get by id, with paket,csMainProjects
        // $webhost = Webhost::with($with)->select($select)->find($id);

        return response()->json($webhost);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // get by id
        $webhost = Webhost::find($id);
        $webhost->delete();

        return response()->json($webhost);
    }

    // search by keyword
    public function search(string $keyword)
    {
        // jika keyword kosong, atau kurang dari 3 karakter
        if (empty($keyword) || $keyword && strlen($keyword) < 3) {
            return response()->json(['message' => 'Keyword minimal 3 karakter'], 404);
        }

        // hapus http:// dan https:// dari keyword
        $keyword = $keyword ? preg_replace('/^https?:\/\//', '', $keyword) : $keyword;

        $cacheKey = 'webhost_search_' . $keyword;

        $webhosts = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($keyword) {
            $gets = Webhost::where('nama_web', 'like', '%' . $keyword . '%')
                ->select('nama_web', 'id_webhost', 'kategori')
                ->limit(200)
                ->get();
            return $gets ? array_values($gets->toArray()) : [];
        });

        // jika kosong
        if (empty($webhosts)) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($webhosts);
    }
}
