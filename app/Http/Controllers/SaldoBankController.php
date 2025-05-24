<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaldoBank;
use Illuminate\Support\Facades\Redis;

class SaldoBankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //get data saldo bank, by bulan dan bank
        $saldo_bank = SaldoBank::where('bulan', $request->bulan)
            ->where('bank', $request->bank)
            ->first();

        //return json
        return response()->json($saldo_bank);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bank'      => 'required',
            'bulan'     => 'required',
            'nominal'   => 'required',
        ]);

        //saveorupdate by bulan dan bank
        $saldo_bank = SaldoBank::updateOrCreate(
            [
                'bank'      => $request->bank,
                'bulan'     => $request->bulan,
            ],
            [
                'nominal'   => $request->nominal,
            ]
        );

        //return json
        return response()->json($saldo_bank);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get data saldo bank
        $saldo_bank = SaldoBank::find($id);

        //return json
        return response()->json($saldo_bank);
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
        //
    }
}
