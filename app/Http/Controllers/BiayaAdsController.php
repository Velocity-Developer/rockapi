<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BiayaAds;

class BiayaAdsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $query = BiayaAds::query();

        //bulan
        $bulan = $request->input('bulan');
        if ($bulan) {
            $query->where('bulan', $bulan);
        }

        //kategori
        $kategori = $request->input('kategori');
        if ($kategori) {
            $query->where('kategori', $kategori);
        }

        $per_page = $request->input('per_page', 20);
        $results = $query->paginate($per_page);

        $results->withPath('/biaya-ads');

        return response()->json($results);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bulan' => 'required|min:3',
            'biaya' => 'required|integer',
            'kategori' => 'required|string|min:2',
        ]);

        // simpan biayaAds
        $biayaAds = BiayaAds::updateOrCreate(
            [
                'bulan' => $request->bulan,
                'kategori' => $request->kategori
            ],
            ['biaya' => $request->biaya]
        );

        return response()->json($biayaAds);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $BiayaAds = BiayaAds::find($id);

        return response()->json($BiayaAds);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'bulan' => 'required|min:3',
            'biaya' => 'required|integer',
            'kategori' => 'required|string|min:2',
        ]);

        $BiayaAds = BiayaAds::find($id);
        $BiayaAds->update([
            'bulan' => $request->bulan,
            'biaya' => $request->biaya,
            'kategori' => $request->kategori,
        ]);

        return response()->json($BiayaAds);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // hapus BiayaAds
        $BiayaAds = BiayaAds::find($id);
        $BiayaAds->delete();
    }
}
