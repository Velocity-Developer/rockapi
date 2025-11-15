<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\SaldoBank;
use App\Models\BankSorting;
use App\Models\CsMainProject;
use App\Models\TransaksiKeluar;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BankTransaksiController extends Controller
{
    /*
     * INDEX
     * tampilkan halaman 'bank_transaksi'
     */
    public function index(Request $request)
    {
        //request
        $req_bulan = $request->input("bulan") ?? date("Y-m");
        $req_bank = $request->input("bank");

        //get data sorting, by bank dan bulan
        $sorting = BankSorting::where("bulan", "like", "%" . $req_bulan . "%")
            ->where("bank", $req_bank)
            ->get();

        //get saldo bank, berdasarkan bulan dan bank
        $saldo_bank = SaldoBank::where("bulan", $req_bulan)
            ->where("bank", $req_bank)
            ->first();

        //jika tidak ada data saldo, set default 0
        if (!$saldo_bank) {
            $saldo_bank = [
                "bank" => $req_bank,
                "bulan" => $req_bulan,
                "nominal" => (int) 0,
            ];
        }

        //get data bank, berdasarkan LIKE tahun-bulan di tgl
        $banks = Bank::orderBy("tgl", "asc")
            ->where("tgl", "like", "%" . $req_bulan . "%")
            ->where("bank", $req_bank)
            ->with(
                "TransaksiKeluar",
                "TransaksiKeluar.bank",
                "CsMainProject.invoices:id,nomor,cs_main_project_id",
                "CsMainProject.bank",
                "CsMainProject.Webhost",
                "CsMainProject.Webhost.Paket",
            )
            ->orderBy("id", "asc")
            ->get();

        $saldo = $saldo_bank->nominal ?? 0;
        $total_masuk = 0;
        $total_keluar = 0;

        $saldo_saatini = 0;

        // tambahkan total nominal saldo di banks
        if ($banks) {
            foreach ($banks as $key => $bank) {
                $nomor = str_replace("-", "", $bank->tgl);
                $bank->nomor = $nomor . $bank->id;

                //jika jenis transaksi adalah 'masuk'
                if ($bank->jenis_transaksi == "masuk") {
                    $saldo += $bank->nominal;
                    $total_masuk += $bank->nominal;
                } else {
                    $saldo -= $bank->nominal;
                    $total_keluar += $bank->nominal;
                }

                $bank->saldo = $saldo;

                //saldo ini
                // if $key == last
                if ($key === count($banks) - 1) {
                    $saldo_saatini = $bank->saldo;
                }
            }
        }

        return response()->json([
            "sorting" => $sorting,
            "data" => $banks,
            "saldo" => $saldo_bank,
            "total_masuk" => $total_masuk,
            "total_keluar" => $total_keluar,
            "saldo_saatini" => $saldo_saatini,
        ]);
    }

    /**
     * Store data baru di Bank.
     */
    public function store(Request $request)
    {
        $request->validate([
            "bank" => "required",
            "tgl" => "required",
            "jenis" => "nullable",
            "jenis_transaksi" => "required",
            "nominal" => "required",
            "keterangan_bank" => "nullable",
        ]);

        $bank_jenis = "";
        //jika ada input'newjenis_array'
        if ($request->has("newjenis_array")) {
            $jenis_array = $request->newjenis_array;

            //serialize array
            $jenis_array = serialize($jenis_array);

            //set jenis
            $bank_jenis = $jenis_array;
        }

        //create
        $bank = Bank::create([
            "bank" => $request->bank,
            "tgl" => $request->tgl,
            "jenis" => $bank_jenis,
            "jenis_transaksi" => $request->jenis_transaksi,
            "nominal" => $request->nominal,
            "keterangan_bank" => $request->keterangan_bank,
            "id_webhost" => 0,
            "status" => "",
        ]);

        return response()->json($bank);
    }

    /**
     * Edit data baru di Bank.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            "bank" => "required",
            "tgl" => "required",
            "jenis" => "nullable",
            "jenis_transaksi" => "required",
            "nominal" => "required",
            "keterangan_bank" => "nullable",
        ]);

        //get by id
        $bank = Bank::find($request->id);

        //jika ada input'newjenis_array'
        if ($request->has("newjenis_array")) {
            $jenis_array = $request->newjenis_array;
            //serialize array
            $jenis_array = serialize($jenis_array);
            //set jenis
            $bank->jenis = $jenis_array;
        }

        //update
        $bank->update([
            "bank" => $request->bank,
            "tgl" => $request->tgl,
            "jenis" => $bank->jenis,
            "jenis_transaksi" => $request->jenis_transaksi,
            "nominal" => $request->nominal,
            "keterangan_bank" => $request->keterangan_bank,
        ]);

        return response()->json($bank);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //get by id
        $bank = Bank::find($id);
        $bank->delete();
        return response()->json($bank);
    }

    //get Transaksi Keluar dan CsMainProject 20 terakhir
    public function get_last_transaksi()
    {
        //get tanggal sekarang, format Y-m-d
        $tgl_sekarang = Carbon::now()->format("Y-m-d");
        //get 30 hari terakhir
        $tgl_30_hari_terakhir = Carbon::now()->subDays(30)->format("Y-m-d");

        //search TransaksiKeluar: jenis by keyword, 30 hari terakhir
        $transaksi_keluar = TransaksiKeluar::with("bank")
            ->where("tgl", ">=", $tgl_30_hari_terakhir)
            ->orderBy("tgl", "desc")
            ->limit(20)
            ->get();

        //search CsMainProject with webhost: webhost.nama_web by keyword, limit 10
        $cs_main_project = CsMainProject::with("Webhost", "bank", "invoices:id,cs_main_project_id,total,nomor")
            ->where("tgl_masuk", ">=", date("Y-m-d", strtotime("-30 days")))
            ->orderBy("tgl_masuk", "desc")
            ->limit(20)
            ->get();

        $gabungan = $cs_main_project
            ->merge($transaksi_keluar)
            ->sortByDesc("tanggal")
            ->values();

        return response()->json([
            // 'transaksi_keluar' => $transaksi_keluar,
            // 'cs_main_project'  => $cs_main_project,
            "data" => $gabungan,
        ]);
    }

    /**
     * Search data di Transaksi Keluar dan CsMainProject.
     */
    public function search_jenis(string $keyword)
    {
        //get 1 tahun terakhir
        $tgl_1_tahun_terakhir = Carbon::now()->subDays(365)->format("Y-m-d");

        //search TransaksiKeluar: jenis by keyword, 1 tahun terakhir
        $transaksi_keluar = TransaksiKeluar::with("bank")
            ->where("jenis", "like", "%" . $keyword . "%")
            // ->where('tgl', '>=', $tgl_1_tahun_terakhir)
            ->orderBy("tgl", "desc")
            ->limit(30)
            ->get();

        //search CsMainProject with webhost: webhost.nama_web by keyword, limit 10
        $cs_main_project = CsMainProject::with("Webhost", "bank")
            ->whereHas("Webhost", function ($query) use ($keyword) {
                $query->where("nama_web", "like", "%" . $keyword . "%");
            })
            // ->where('tgl_masuk', '>=', $tgl_1_tahun_terakhir)
            ->orderBy("tgl_masuk", "desc")
            ->limit(30)
            ->get();

        $gabungan = $cs_main_project
            ->merge($transaksi_keluar)
            ->sortByDesc("tanggal")
            ->values();

        return response()->json([
            // 'transaksi_keluar' => $transaksi_keluar,
            // 'cs_main_project'  => $cs_main_project,
            "data" => $gabungan,
        ]);
    }

    /*
     * json export data bank
     */
    public function export(Request $request)
    {
        //request
        $req_bulan = $request->input("bulan") ?? date("Y-m");
        $req_bank = $request->input("bank");

        //get saldo bank, berdasarkan bulan dan bank
        $saldo_bank = SaldoBank::where("bulan", $req_bulan)
            ->where("bank", $req_bank)
            ->first();

        //jika tidak ada data saldo, set default 0
        if (!$saldo_bank) {
            $saldo_bank = [
                "bank" => $req_bank,
                "bulan" => $req_bulan,
                "nominal" => (int) 0,
            ];
        }

        //get data bank, berdasarkan LIKE tahun-bulan di tgl
        $banks = Bank::orderBy("tgl", "asc")
            ->where("tgl", "like", "%" . $req_bulan . "%")
            ->where("bank", $req_bank)
            ->with(
                "TransaksiKeluar",
                "TransaksiKeluar.bank",
                "CsMainProject",
                "CsMainProject.bank",
                "CsMainProject.Webhost",
                "CsMainProject.Webhost.Paket",
            )
            ->get();

        $saldo = $saldo_bank->nominal ?? 0;
        $total_masuk = 0;
        $total_keluar = 0;

        //susun ulang data untuk export

        if ($banks) {
            $results = [];
            foreach ($banks as $key => $bank) {
                //jika jenis transaksi adalah 'masuk'
                if ($bank->jenis_transaksi == "masuk") {
                    $saldo += $bank->nominal;
                    $total_masuk += $bank->nominal;
                } else {
                    $saldo -= $bank->nominal;
                    $total_keluar += $bank->nominal;
                }

                //buat keterangan jenis dari loop transaksi_keluar dan cs_main_project
                $ket_jenis = "";

                if ($bank->CsMainProject) {
                    foreach ($bank->CsMainProject as $key => $value) {
                        $ket_jenis .= $value->tgl_masuk . " - ";
                        $ket_jenis .= $value->jenis . " - ";
                        $ket_jenis .= $value->webhost->nama_web . " - ";
                        $ket_jenis .= $value->dibayar;
                    }
                }
                if ($bank->TransaksiKeluar) {
                    foreach ($bank->TransaksiKeluar as $key => $value) {
                        $ket_jenis .= $value->tgl . " - ";
                        $ket_jenis .= $value->jenis . " - ";
                        $ket_jenis .= $value->jml;
                    }
                }

                $results[] = [
                    "No" => $key + 1,
                    "Tanggal" => $bank->tgl,
                    "Bank" => $bank->bank,
                    "Jenis" => $ket_jenis,
                    "Keterangan" => $bank->keterangan_bank,
                    "Masuk" =>
                    $bank->jenis_transaksi == "masuk"
                        ? number_format($bank->nominal, 2, ",", ".")
                        : "",
                    "Keluar" =>
                    $bank->jenis_transaksi == "keluar"
                        ? number_format($bank->nominal, 2, ",", ".")
                        : "",
                    "Saldo" => "Rp " . number_format($saldo, 2, ",", "."),
                ];
            }
        }

        return response()->json($results);
    }
}
