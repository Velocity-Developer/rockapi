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
        $req_bulan = $request->input('bulan') ?? date('Y-m');
        $req_bank = $request->input('bank');

        //get data sorting, by bank dan bulan
        $sorting = BankSorting::where('bulan', 'like', '%' . $req_bulan . '%')
            ->where('bank', $req_bank)->get();

        //get saldo bank, berdasarkan bulan dan bank
        $saldo_bank = SaldoBank::where('bulan', $req_bulan)
            ->where('bank', $req_bank)
            ->first();

        //jika tidak ada data saldo, set default 0
        if (!$saldo_bank) {
            $saldo_bank = [
                'bank'      => $req_bank,
                'bulan'     => $req_bulan,
                'nominal'   => (int) 0,
            ];
        };

        //get data bank, berdasarkan LIKE tahun-bulan di tgl
        $banks = Bank::orderBy('tgl', 'asc')
            ->where('tgl', 'like', '%' . $req_bulan . '%')
            ->where('bank', $req_bank)
            ->with(
                'TransaksiKeluar',
                'TransaksiKeluar.bank',
                'CsMainProject',
                'CsMainProject.bank',
                'CsMainProject.Webhost',
                'CsMainProject.Webhost.Paket'
            )
            ->get();

        $saldo = $saldo_bank->nominal ?? 0;
        $total_masuk = 0;
        $total_keluar = 0;

        // tambahkan total nominal saldo di banks
        if ($banks) {
            foreach ($banks as $key => $bank) {

                $bank->nomor = $bank->tgl . '-' . $key;

                //jika jenis transaksi adalah 'masuk'
                if ($bank->jenis_transaksi == 'masuk') {
                    $saldo += $bank->nominal;
                    $total_masuk += $bank->nominal;
                } else {
                    $saldo -= $bank->nominal;
                    $total_keluar += $bank->nominal;
                }

                $bank->saldo = $saldo;
            }
        }

        return response()->json([
            'sorting'       => $sorting,
            'data'          => $banks,
            'saldo'         => $saldo_bank,
            'total_masuk'   => $total_masuk,
            'total_keluar'  => $total_keluar,
        ]);
    }

    /**
     * Store data baru di Bank.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bank'              => 'required',
            'tgl'               => 'required',
            'jenis'             => 'nullable',
            'jenis_transaksi'   => 'required',
            'nominal'           => 'required',
            'keterangan_bank'   => 'nullable',
        ]);

        //create
        $bank = Bank::create($request->all());

        return response()->json($bank);
    }

    /**
     * Edit data baru di Bank.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'bank'              => 'required',
            'tgl'               => 'required',
            'jenis'             => 'nullable',
            'jenis_transaksi'   => 'required',
            'nominal'           => 'required',
            'keterangan_bank'   => 'nullable',
        ]);

        //get by id
        $bank = Bank::find($request->id);
        //update
        $bank->update($request->all());

        return response()->json($bank);
    }

    /**
     * Search data di Transaksi Keluar dan CsMainProject.
     */
    public function search_jenis(string $keyword)
    {
        //get tanggal sekarang, format Y-m-d
        $tgl_sekarang = Carbon::now()->format('Y-m-d');
        //get 30 hari terakhir
        $tgl_30_hari_terakhir = Carbon::now()->subDays(30)->format('Y-m-d');

        //search TransaksiKeluar: jenis by keyword, 30 hari terakhir
        $transaksi_keluar = TransaksiKeluar::where('jenis', 'like', '%' . $keyword . '%')
            ->where('tgl', '>=', $tgl_30_hari_terakhir)
            ->orderBy('tgl', 'desc')
            ->limit(30)
            ->get();

        //search CsMainProject with webhost: webhost.nama_web by keyword, limit 10
        $cs_main_project = CsMainProject::with('Webhost')
            ->whereHas('Webhost', function ($query) use ($keyword) {
                $query->where('nama_web', 'like', '%' . $keyword . '%');
            })
            ->where('tgl_masuk', '>=', date('Y-m-d', strtotime('-30 days')))
            ->orderBy('tgl_masuk', 'desc')
            ->limit(30)
            ->get();

        $gabungan = $cs_main_project->merge($transaksi_keluar)->sortByDesc('tanggal')->values();

        return response()->json([
            // 'transaksi_keluar' => $transaksi_keluar,
            // 'cs_main_project'  => $cs_main_project,
            'data'             => $gabungan,
        ]);
    }
}
