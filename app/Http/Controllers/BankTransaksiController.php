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

        //get data sorting
        $sorting = BankSorting::all();

        //get data bank, berdasarkan LIKE tahun-bulan di tgl
        $banks = Bank::orderBy('tgl', 'desc')
            ->where('tgl', 'like', '%' . $req_bulan . '%')
            ->where('bank', $req_bank)
            ->with('CsMainProject', 'CsMainProject.Webhost', 'CsMainProject.Webhost.Paket')
            ->get();

        //get saldo bank, berdasarkan bulan dan bank
        $saldo_bank = SaldoBank::where('bulan', $req_bulan)
            ->where('bank', $req_bank)
            ->first();

        return response()->json([
            'sorting'   => $sorting,
            'data'      => $banks,
            'saldo'     => $saldo_bank,
        ]);
    }
}
