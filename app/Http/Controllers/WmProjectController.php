<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WmProject;
use App\Models\User;

class WmProjectController extends Controller
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
        $request->validate([
            'id_karyawan'           => 'required|integer',
            'id_cs_main_project'    => 'required|integer',
            'webmaster'             => 'nullable|string',
            'date_mulai'            => 'required|date',
            'date_selesai'          => 'nullable|date',
            'qc'                    => 'nullable|array',
            'catatan'               => 'nullable|string',
            'status_multi'          => 'required|string|in:pending,selesai',
            'user_id'               => 'required|integer',
            'status_project'        => 'nullable|string',
        ]);

        if ($request->user_id) {
            $webmaster = $this->user_get_webmaster($request->user_id);
        } elseif ($request->webmaster && !$request->user_id) {
            $webmaster = $request->webmaster;
        }

        //status_project
        $status_project = 'Belum dikerjakan';
        if ($request->date_mulai && $request->user_id) {
            $status_project = 'Dalam pengerjaan';
        }

        // update or create WmProject, untuk menghindari duplikasi
        $wm_project = WmProject::updateOrCreate(
            [
                'id'            => $request->id_cs_main_project,
                'id_karyawan'   => $request->id_karyawan,
                'user_id'       => $request->user_id,
            ],
            [
                'webmaster'     => $webmaster,
                'date_mulai'    => $request->date_mulai,
                'date_selesai'  => $request->date_selesai,
                'qc'            => $request->qc,
                'catatan'       => $request->catatan,
                'status_multi'  => $request->status_multi,
                'start'         => now(),
                'status_project' => $status_project,
            ],
        );

        return response()->json($wm_project);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get wm_project
        $wm_project = WmProject::find($id);
        return response()->json($wm_project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'id_karyawan'           => 'required|integer',
            'id_cs_main_project'    => 'required|integer',
            'webmaster'             => 'nullable|string',
            'date_mulai'            => 'required|date',
            'date_selesai'          => 'nullable|date',
            'qc'                    => 'nullable|array',
            'catatan'               => 'nullable|string',
            'status_multi'          => 'required|string|in:pending,selesai',
            'user_id'               => 'required|integer',
            'status_project'        => 'nullable|string',
        ]);

        if ($request->user_id) {
            $webmaster = $this->user_get_webmaster($request->user_id);
        } elseif ($request->webmaster && !$request->user_id) {
            $webmaster = $request->webmaster;
        }

        $status_multi = $request->status_multi ?? 'pending';
        if ($request->date_mulai && $request->date_selesai && $request->status_project == 'Selesai') {
            $status_multi = 'selesai';
        }

        //update wm_project
        $wm_project = WmProject::find($id);
        $wm_project->update([
            'id_karyawan'   => $request->id_karyawan,
            'id'            => $request->id_cs_main_project,
            'webmaster'     => $webmaster,
            'date_mulai'    => $request->date_mulai,
            'date_selesai'  => $request->date_selesai,
            'qc'            => $request->qc ? serialize($request->qc) : '',
            'catatan'       => $request->catatan,
            'status_multi'  => $status_multi,
            'user_id'       => $request->user_id,
            'status_project' => $request->status_project,
        ]);

        return response()->json($wm_project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //hapus wm_project
        WmProject::where('id_wm_project', $id)->delete();
    }

    private function user_get_webmaster($user_id)
    {
        //get user
        $user = User::find($user_id);

        //jika user tidak ada, return null
        if (!$user) {
            return null;
        }

        $karyawans = [
            'Aditya k' => [
                'id_karyawan' => 80,
                'user_id' => null
            ],
            'Aditya' => [
                'id_karyawan' => 81,
                'user_id' => null
            ],
            'Dita' => [
                'id_karyawan' => 34,
                'user_id' => null
            ],
            'Irawan' => [
                'id_karyawan' => 73,
                'user_id' => null
            ],
            'Lingga' => [
                'id_karyawan' => 75,
                'user_id' => null
            ],
            'Shudqi' => [
                'id_karyawan' => 65,
                'user_id' => null
            ],
            'Dimas' => [
                'id_karyawan' => 67,
                'user_id' => null
            ],
            'Yuda' => [
                'id_karyawan' => 28,
                'user_id' => null
            ],
            'Bima' => [
                'id_karyawan' => 72,
                'user_id' => null
            ],
            'Fajar' => [
                'id_karyawan' => 68,
                'user_id' => null
            ],
            'Galib' => [
                'id_karyawan' => 71,
                'user_id' => null
            ],
            'Reza' => [
                'id_karyawan' => 74,
                'user_id' => null
            ],
            'Joko' => [
                'id_karyawan' => 64,
                'user_id' => null
            ],
            'Anggun' => [
                'id_karyawan' => 76,
                'user_id' => null
            ],
            'Iksan' => [
                'id_karyawan' => 70,
                'user_id' => null
            ],
            'Support' => [
                'id_karyawan' => 11,
                'user_id' => null
            ]
        ];

        //dapatkan nama webmaster dari id_karyawan
        $nama = null;
        foreach ($karyawans as $name => $data) {
            if ($data['id_karyawan'] == $user->id_karyawan) {
                $nama = $name;
                break; // Hentikan loop jika sudah ditemukan (opsional)
            }
        }

        return $nama ?? $user->username;
    }
}
