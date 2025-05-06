<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\SaldoBank;
use App\Models\BankSorting;

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
}
