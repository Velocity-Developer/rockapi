<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CsMainProjectRequest;
use App\Models\CsMainProject;
use App\Models\Webhost;
use App\Models\TransaksiMasuk;
use App\Models\PmProject;

/**
 * @catatan CsMainProject
 * @simpan CsMainProject juga menyimpan di
 * CsMainProject,Webhost,TransaksiMasuk,PmProject, csMainProjectKaryawan
 * 
 **/
class CsMainProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //get cs_main_project, dengan paginasi 20
        $cs_main_project = CsMainProject::with('webhost', 'webhost.paket')
            ->paginate(20);
        return response()->json($cs_main_project);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CsMainProjectRequest $request)
    {

        //get webhost by nama_web
        $webhost = Webhost::where('nama_web', $request->input('nama_web'))->first();

        //jika webhost tidak ditemukan , buat webhost baru
        if (!$webhost) {
            $webhost = Webhost::create([
                'nama_web'          => str_replace(' ', '', $request->input('nama_web')),
                'id_paket'          => $request->input('paket'),
                'tgl_mulai'         => $request->input('tgl_masuk'),
                'id_server'         => '1',
                'id_server2'        => '1',
                'space'             => '0',
                'space_use'         => '0',
                'hp'                => $request->input('hp'),
                'telegram'          => $request->input('telegram'),
                'hpads'             => $request->input('hpads'),
                'wa'                => $request->input('wa'),
                'email'             => $request->input('email'),
                'tgl_exp'           => null,
                'tgl_update'        => date('Y-m-d'),
                'server_luar'       => $request->input('server') && $request->input('server') == '4' ? '0' : '1',
                'saldo'             => $request->input('saldo'),
                'kategori'          => '',
                'waktu'             => date('Y-m-d H:i:s'),
                'via'               => '',
                'konfirmasi_order'  => '',
                'kata_kunci'        => '',
            ]);
        }

        if ($request->input('biaya') - $request->input('dibayar') > 0) {
            $lunas = 'belum';
        } else {
            $lunas = 'lunas';
        }

        //olah data di_kerjakan_oleh
        $di_kerjakan_oleh = '';
        if ($request->input('dikerjakan_oleh')) {
            $count = count($request->input('dikerjakan_oleh'));
            $persen = 100 / $count;
            foreach ($request->input('dikerjakan_oleh') as $value) {
                $di_kerjakan_oleh .= ',' . $value . '[' . $persen . ']';
            }
        }

        //simpan data ke tabel cs_main_project
        $cs_main_project = CsMainProject::create([
            'id_webhost'        => $webhost->id_webhost,
            'jenis'             => $request->input('jenis'),
            'deskripsi'         => $request->input('deskripsi'),
            'trf'               => $request->input('trf'),
            'tgl_masuk'         => $request->input('tgl_masuk'),
            'tgl_deadline'      => $request->input('tgl_deadline'),
            'biaya'             => $request->input('biaya'),
            'dibayar'           => $request->input('dibayar'),
            'status'            => 'pending',
            'status_pm'         => 'pending',
            'lunas'             => $lunas,
            'dikerjakan_oleh'   => $di_kerjakan_oleh,
            'tanda'             => '0',
        ]);

        //simpan relasi ke cs_main_project_karyawan
        if ($di_kerjakan_oleh) {
            $count = count($request->input('dikerjakan_oleh'));
            $persen = 100 / $count;
            foreach ($request->input('dikerjakan_oleh') as $value) {
                DB::table('cs_main_project_karyawan')->insert([
                    'cs_main_project_id' => $cs_main_project->id,
                    'karyawan_id' => $value,
                    'porsi' => $persen ?? 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        //menyimpan log transaksi ke TransaksiMasuk
        $transaksi_masuk = TransaksiMasuk::create([
            'id'            => $cs_main_project->id,
            'tgl'           => $cs_main_project->tgl_masuk,
            'total_biaya'   => $cs_main_project->biaya,
            'bayar'         => $cs_main_project->dibayar,
            'pelunasan'     => 'N',
        ]);

        //simpan data ke tabel pm_project
        $pm_project = PmProject::create([
            'id' => $cs_main_project->id,
        ]);

        return response()->json([
            'cs_main_project' => $cs_main_project,
            'webhost' => $webhost,
            'transaksi_masuk' => $transaksi_masuk,
            'pm_project' => $pm_project
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get
        $cs_main_project = CsMainProject::find($id)
            ->with('webhost', 'webhost.paket', 'karyawans', 'transaksi_masuk', 'pm_project')
            ->get();
        return response()->json($cs_main_project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CsMainProjectRequest $request, string $id)
    {
        //get cs_main_project
        $cs_main_project = CsMainProject::find($id);

        //get webhost by nama_web
        $webhost = Webhost::where('id_webhost', $cs_main_project->id_webhost)->first();

        //jika webhost tidak ditemukan, error
        if (!$webhost) {
            return response()->json([
                'message' => 'Webhost tidak ditemukan',
            ], 404);
        }

        //update webhost
        $webhost->update([
            'nama_web'          => str_replace(' ', '', $request->input('nama_web')),
            'id_paket'          => $request->input('paket'),
            'tgl_mulai'         => $request->input('tgl_masuk'),
            'id_server'         => '1',
            'id_server2'        => '1',
            'space'             => '0',
            'space_use'         => '0',
            'hp'                => $request->input('hp'),
            'telegram'          => $request->input('telegram'),
            'hpads'             => $request->input('hpads'),
            'wa'                => $request->input('wa'),
            'email'             => $request->input('email'),
            'tgl_exp'           => null,
            'tgl_update'        => date('Y-m-d'),
            'server_luar'       => $request->input('server') && $request->input('server') == '4' ? '0' : '1',
            'saldo'             => $request->input('saldo'),
            'kategori'          => '',
            'waktu'             => date('Y-m-d H:i:s'),
            'via'               => '',
            'konfirmasi_order'  => '',
            'kata_kunci'        => '',
        ]);

        if ($request->input('biaya') - $request->input('dibayar') > 0) {
            $lunas = 'belum';
        } else {
            $lunas = 'lunas';
        }

        //olah data di_kerjakan_oleh
        $di_kerjakan_oleh = '';
        if ($request->input('dikerjakan_oleh')) {
            $count = count($request->input('dikerjakan_oleh'));
            $persen = 100 / $count;
            foreach ($request->input('dikerjakan_oleh') as $value) {
                $di_kerjakan_oleh .= ',' . $value . '[' . $persen . ']';
            }
        }

        //simpan data ke tabel cs_main_project
        $cs_main_project->update([
            'id_webhost'        => $webhost->id_webhost,
            'jenis'             => $request->input('jenis'),
            'deskripsi'         => $request->input('deskripsi'),
            'trf'               => $request->input('trf'),
            'tgl_masuk'         => $request->input('tgl_masuk'),
            'tgl_deadline'      => $request->input('tgl_deadline'),
            'biaya'             => $request->input('biaya'),
            'dibayar'           => $request->input('dibayar'),
            'status'            => 'pending',
            'status_pm'         => 'pending',
            'lunas'             => $lunas,
            'dikerjakan_oleh'   => $di_kerjakan_oleh,
            'tanda'             => '0',
        ]);

        //simpan relasi ke cs_main_project_karyawan
        if ($di_kerjakan_oleh) {
            //hapus relasi ke cs_main_project_karyawan
            DB::table('cs_main_project_karyawan')->where('cs_main_project_id', $cs_main_project->id)->delete();

            //simpan relasi baru ke cs_main_project_karyawan
            $count = count($request->input('dikerjakan_oleh'));
            $persen = 100 / $count;
            foreach ($request->input('dikerjakan_oleh') as $value) {
                DB::table('cs_main_project_karyawan')->insert([
                    'cs_main_project_id' => $cs_main_project->id,
                    'karyawan_id' => $value,
                    'porsi' => $persen ?? 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        //cek perubahan total biaya dan uang yang dibayar,
        //jika terjadi perubahan pada kolom dibayar maka simpan perubahan di log transaksi masuk
        if ($request->input('dibayar') && $cs_main_project->dibayar != $request->input('dibayar')) {
            if ($cs_main_project->dibayar > $request->input('dibayar')) {
                $dibayar = $cs_main_project->dibayar - $request->input('dibayar');
            } else {
                $dibayar = $request->input('dibayar') - $cs_main_project->dibayar;
            }

            //jika terjadi perubahan tambah di log transaksi masuk
            $tgl = date("Y-m-d");
            $biaya = $cs_main_project->biaya - $cs_main_project->dibayar;
            //simpan transaksi masuk baru
            TransaksiMasuk::create([
                'id'            => $cs_main_project->id,
                'tgl'           => $tgl,
                'total_biaya'   => $biaya,
                'bayar'         => $dibayar,
                'pelunasan'     => 'N',
            ]);

            //jika, jika opo yo urung paham, MAAF
            $biaya2 = $request->input('biaya') - $request->input('dibayar');
            if ($biaya2 > 0) {
                //hapus data transaksi_masuk where id = $cs_main_project->id dan pelunasan = Y
                TransaksiMasuk::where('id', $cs_main_project->id)->where('pelunasan', 'Y')->delete();
            }

            //TODO update ke tb_wm_project
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //delete cs_main_project_karyawan
        DB::table('cs_main_project_karyawan')->where('cs_main_project_id', $id)->delete();
        //delete transaksi_masuk
        TransaksiMasuk::where('id', $id)->delete();
        //delete pm_project
        PmProject::where('id', $id)->delete();

        //get cs_main_project
        $cs_main_project = CsMainProject::find($id);
        //delete cs_main_project
        $cs_main_project->delete();
    }
}
