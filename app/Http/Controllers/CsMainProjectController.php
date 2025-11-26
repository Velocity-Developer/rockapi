<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CsMainProjectRequest;
use App\Models\CsMainProject;
use App\Models\Webhost;
use App\Models\TransaksiMasuk;
use App\Models\PmProject;
use App\Models\WmProject;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Setting;
use Carbon\Carbon;

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
        return DB::transaction(function () use ($request) {
            // =============================
            // 1. Ambil atau buat Webhost
            // =============================
            $webhost = Webhost::where('id_webhost', $request->input('id_webhost'))->first();
            if (!$webhost) {
                $webhost = Webhost::create([
                    'nama_web'       => $request->input('nama_web'),
                    'id_paket'       => $request->input('paket'),
                    'tgl_mulai'      => $request->input('tgl_masuk'),
                    'id_server'      => 1,
                    'id_server2'     => 1,
                    'space'          => 0,
                    'space_use'      => 0,
                    'hp'             => $request->input('hp'),
                    'telegram'       => $request->input('telegram'),
                    'hpads'          => $request->input('hpads'),
                    'wa'             => $request->input('wa'),
                    'email'          => $request->input('email'),
                    'tgl_exp'        => null,
                    'tgl_update'     => now()->format('Y-m-d'),
                    'server_luar'    => $request->input('server') == '4' ? 0 : 1,
                    'saldo'          => $request->input('saldo'),
                    'kategori'       => $request->input('kategori_web'),
                    'waktu'          => null,
                    'via'            => '',
                    'konfirmasi_order' => '',
                    'kata_kunci'     => '',
                ]);
            } else {
                $webhost->update([
                    'nama_web'  => $request->input('nama_web'),
                    'id_paket'  => $request->input('paket'),
                    'hp'        => $request->input('hp'),
                    'telegram'  => $request->input('telegram'),
                    'hpads'     => $request->input('hpads'),
                    'wa'        => $request->input('wa'),
                    'email'     => $request->input('email'),
                    'saldo'     => $request->input('saldo'),
                ]);
            }

            // =============================
            // 2. Hitung status lunas
            // =============================
            $lunas = ($request->input('biaya') - $request->input('dibayar') > 0) ? 'belum' : 'lunas';

            // =============================
            // 3. Olah data dikerjakan_oleh
            // =============================
            $di_kerjakan_oleh = '';
            $karyawan_list = $request->input('dikerjakan_oleh', []);
            $persen = count($karyawan_list) > 0 ? 100 / count($karyawan_list) : 100;
            foreach ($karyawan_list as $value) {
                $di_kerjakan_oleh .= ',' . $value . '[' . $persen . ']';
            }

            // =============================
            // 4. Simpan ke cs_main_project
            // =============================
            $cs_main_project = CsMainProject::create([
                'id_webhost'      => $webhost->id_webhost,
                'jenis'           => $request->input('jenis'),
                'deskripsi'       => $request->input('deskripsi'),
                'trf'             => $request->input('trf'),
                'tgl_masuk'       => $request->input('tgl_masuk'),
                'tgl_deadline'    => $request->input('tgl_deadline'),
                'biaya'           => $request->input('biaya'),
                'dibayar'         => $request->input('dibayar'),
                'status'          => 'pending',
                'status_pm'       => 'pending',
                'lunas'           => $lunas,
                'dikerjakan_oleh' => $di_kerjakan_oleh,
                'tanda'           => 0,
            ]);

            // =============================
            // 5. Simpan relasi ke karyawan
            // =============================
            foreach ($karyawan_list as $value) {
                DB::table('cs_main_project_karyawan')->insert([
                    'cs_main_project_id' => $cs_main_project->id,
                    'karyawan_id'        => $value,
                    'porsi'              => $persen,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            // =============================
            // 6. Transaksi masuk
            // =============================
            $transaksi_masuk = TransaksiMasuk::create([
                'id'          => $cs_main_project->id,
                'tgl'         => $cs_main_project->tgl_masuk,
                'total_biaya' => $cs_main_project->biaya,
                'bayar'       => $cs_main_project->dibayar,
                'pelunasan'   => 'N',
            ]);

            // =============================
            // 7. PM project
            // =============================
            $pm_project = PmProject::create([
                'id' => $cs_main_project->id,
            ]);

            // =============================
            // 8. Customer
            // =============================
            $customer_id = $request->input('customer_id');
            if (!$customer_id && $request->input('nama') && $request->input('hp')) {
                $customer = Customer::create([
                    'nama'   => $request->input('nama'),
                    'hp'     => $request->input('hp'),
                    'email'  => $request->input('email'),
                    'wa'     => $request->input('wa'),
                    'alamat' => $request->input('alamat'),
                    'telegram' => $request->input('telegram'),
                    'hpads' => $request->input('hpads'),
                    'saldo' => $request->input('saldo'),
                    'jenis_kelamin' => $request->input('jenis_kelamin'),
                    'usia' => $request->input('usia'),
                ]);
                $customer_id = $customer->id;
            }
            //jika ada customer_id, update customer
            if ($customer_id) {
                Customer::where('id', $customer_id)->update([
                    'nama'   => $request->input('nama'),
                    'hp'     => $request->input('hp'),
                    'email'  => $request->input('email'),
                    'wa'     => $request->input('wa'),
                    'alamat' => $request->input('alamat'),
                    'telegram' => $request->input('telegram'),
                    'hpads' => $request->input('hpads'),
                    'saldo' => $request->input('saldo'),
                ]);
            }

            // =============================
            // 9. Invoice
            // =============================
            $unit = in_array($request->input('jenis'), ['Iklan Google', 'Deposit Iklan Google', 'Jasa update iklan google'])
                ? 'vcm'
                : 'vdi';
            $biaya_invoice = $request->input('biaya') ?? $request->input('dibayar');
            if ($unit == 'vcm' && $request->input('trf')) {
                $biaya_invoice = $request->input('trf');
            }
            $invoice = Invoice::create([
                'unit'               => $unit,
                'customer_id'        => $customer_id,
                'note'               => $request->input('deskripsi'),
                'status'             => 'lunas',
                'subtotal'           => $biaya_invoice,
                'pajak'              => 0,
                'nama_pajak'         => null,
                'nominal_pajak'      => 0,
                'total'              => $biaya_invoice,
                'tanggal'            => $request->input('tgl_masuk'),
                'jatuh_tempo'        => null,
                'tanggal_bayar'      => $request->input('tgl_masuk'),
                'cs_main_project_id' => $cs_main_project->id,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'webhost_id' => $webhost->id_webhost,
                'nama'       => '',
                'jenis'      => $request->input('jenis'),
                'harga'      => $biaya_invoice,
            ]);

            // =============================
            // 10. Pivot customer_cs_main_project & customer_webhost
            // =============================
            if ($customer_id) {
                //jika customer_cs_main_project belum ada, insert
                if (!DB::table('customer_cs_main_project')->where('customer_id', $customer_id)->where('cs_main_project_id', $cs_main_project->id)->exists()) {
                    DB::table('customer_cs_main_project')->insert([
                        'customer_id'       => $customer_id,
                        'cs_main_project_id' => $cs_main_project->id,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                //jika customer_webhost belum ada, insert
                if (!DB::table('customer_webhost')->where('customer_id', $customer_id)->where('webhost_id', $webhost->id_webhost)->exists()) {
                    DB::table('customer_webhost')->insert([
                        'customer_id' => $customer_id,
                        'webhost_id'  => $webhost->id_webhost,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            // =============================
            // 11. Update Kategori Web
            // =============================
            $daftar_kategori_web = Setting::get('kategori_web') ?? ['Yayasan', 'Perusahaan', 'Umum', 'Sekolah', 'Jasa'];
            $input_kategori_web = $request->input('kategori_web');
            //jika input kategori_web tidak ada di daftar maka tambahkan ke array
            if (!in_array($input_kategori_web, $daftar_kategori_web)) {
                array_push($daftar_kategori_web, $input_kategori_web);
                Setting::set('kategori_web', $daftar_kategori_web);
            }

            // =============================
            // 12. Return response
            // =============================
            return response()->json([
                'cs_main_project' => $cs_main_project,
                'webhost'         => $webhost,
                'transaksi_masuk' => $transaksi_masuk,
                'pm_project'      => $pm_project,
                'invoice'         => $invoice,
            ]);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get
        $cs_main_project = CsMainProject::with(
            'webhost',
            'webhost.paket',
            'karyawans',
            'transaksi_masuk',
            'pm_project',
            'wm_project'
        )
            ->find($id);
        return response()->json($cs_main_project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CsMainProjectRequest $request, string $id)
    {
        //get cs_main_project
        $cs_main_project = CsMainProject::find($id);

        //get webhost by id_webhost
        $webhost = Webhost::where('id_webhost', $cs_main_project->id_webhost)->first();

        //jika webhost tidak ditemukan, error
        if (!$webhost) {
            return response()->json([
                'message' => 'Webhost tidak ditemukan',
            ], 404);
        }

        return DB::transaction(function () use ($request, $cs_main_project, $webhost) {

            // =============================
            // 1. Update webhost
            // =============================
            $webhost->update([
                'nama_web'          => str_replace(' ', '', $request->input('nama_web')),
                'id_paket'          => $request->input('paket'),
                'tgl_mulai'         => $request->input('tgl_masuk'),
                // 'id_server'         => '1',
                // 'id_server2'        => '1',
                // 'space'             => '0',
                // 'space_use'         => '0',
                'hp'                => $request->input('hp'),
                'telegram'          => $request->input('telegram'),
                'hpads'             => $request->input('hpads'),
                'wa'                => $request->input('wa'),
                'email'             => $request->input('email'),
                // 'tgl_exp'           => null,
                // 'tgl_update'        => date('Y-m-d'),
                'server_luar'       => $request->input('server') && $request->input('server') == '4' ? '0' : '1',
                'saldo'             => $request->input('saldo'),
                'kategori'          => $request->input('kategori_web'),
                // 'waktu'             => date('Y-m-d H:i:s'),
                // 'via'               => '',
                // 'konfirmasi_order'  => '',
                // 'kata_kunci'        => '',
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

            // =============================
            // 2. Update cs_main_project
            //    simpan data ke tabel cs_main_project
            // =============================
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

            // =============================
            // 3. Update cs_main_project_karyawan
            //    simpan relasi ke cs_main_project_karyawan
            // =============================
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

            // =============================
            // 4. cek perubahan total biaya dan uang yang dibayar,
            //    jika terjadi perubahan pada kolom dibayar maka simpan perubahan di log transaksi masuk
            // =============================
            $transaksi_masuk = '';
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
                $transaksi_masuk = TransaksiMasuk::create([
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
            }

            // =============================
            // 5. PM Project
            //    chek PmProject
            // =============================
            $pm_project = PmProject::where('id', $cs_main_project->id)->first();
            if (!$pm_project) {
                //create
                $pm_project = PmProject::create([
                    'id' => $cs_main_project->id,
                ]);
            }

            //check input 'dikerjakan_oleh' dengan $cs_main_project->raw_dikerjakan
            //jika tidak sama, maka update
            if ($cs_main_project->raw_dikerjakan && $request->input('dikerjakan_oleh') && $cs_main_project->raw_dikerjakan != $request->input('dikerjakan_oleh')) {
                //dapatkan perbedaan
                $diff = array_diff($cs_main_project->raw_dikerjakan, $request->input('dikerjakan_oleh'));
                //loop
                foreach ($diff as $value) {
                    //hapus data WmProject where id = $cs_main_project->id dan karyawan_id = $value
                    WmProject::where('id', $cs_main_project->id)->where('karyawan_id', $value)->delete();
                }
            }

            // =============================
            // 6. Update Kategori Web
            // =============================
            $daftar_kategori_web = Setting::get('kategori_web') ?? ['Yayasan', 'Perusahaan', 'Umum', 'Sekolah', 'Jasa'];
            $input_kategori_web = $request->input('kategori_web');
            //jika input kategori_web tidak ada di daftar maka tambahkan ke array
            if (!in_array($input_kategori_web, $daftar_kategori_web)) {
                array_push($daftar_kategori_web, $input_kategori_web);
                Setting::set('kategori_web', $daftar_kategori_web);
            }


            // =============================
            // 11. Return response
            // =============================

            //get WmProject where id = $cs_main_project->id
            $wm_project = WmProject::where('id', $cs_main_project->id)->get();

            return response()->json([
                'cs_main_project' => $cs_main_project,
                'webhost' => $webhost,
                'pm_project' => $pm_project,
                'transaksi_masuk' => $transaksi_masuk,
                'wm_project' => $wm_project
            ]);
        });
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

    //search by keyword
    public function search(string $keyword, Request $request)
    {
        //jika keyword kosong, atau kurang dari 3 karakter
        if (empty($keyword) || $keyword && strlen($keyword) < 3) {
            return response()->json(['message' => 'Keyword minimal 3 karakter'], 404);
        }

        //query dasar
        $query = CsMainProject::with('webhost:id_webhost,nama_web')
            ->where(function ($q) use ($keyword) {
                $q->where('jenis', 'like', '%' . $keyword . '%')
                    ->orWhere('deskripsi', 'like', '%' . $keyword . '%')
                    ->orWhereHas('webhost', function ($subQ) use ($keyword) {
                        $subQ->where('nama_web', 'like', '%' . $keyword . '%');
                    });
            });

        //filter berdasarkan webhost_id jika ada
        if ($request->has('webhost_id') && $request->input('webhost_id')) {
            $query->where('id_webhost', $request->input('webhost_id'));
        }

        //ambil data dengan limit
        $csMainProjects = $query->select('id', 'jenis', 'deskripsi', 'tgl_masuk', 'status', 'id_webhost')
            ->limit(200)
            ->get();

        //jika kosong
        if ($csMainProjects->isEmpty()) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($csMainProjects);
    }

    //last data
    public function lastdata(Request $request)
    {
        //get 10 last data by CsMainProject.CsMainProjectInfos = created_at
        //urutkan berdasarkan created_at desc
        $cs_main_project = CsMainProject::select('tb_cs_main_project.*')
            ->join('cs_main_project_infos', 'cs_main_project_infos.cs_main_project_id', '=', 'tb_cs_main_project.id')
            ->with('cs_main_project_info', 'webhost:id_webhost,nama_web')
            ->orderBy('cs_main_project_infos.created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($cs_main_project);
    }
}
