<?php

namespace App\Http\Controllers;

use App\Models\HargaDomain;
use Illuminate\Http\Request;

class HargaDomainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = HargaDomain::orderBy('bulan', 'desc');

        if ($request->input('bulan')) {
            $query->where('bulan', 'like', '%' . $request->input('bulan') . '%');
        }

        $per_page = $request->input('per_page', 10);

        $pagination = filter_var(
            $request->query('pagination', true),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($pagination) {
            $data = $query->paginate($per_page);
        } else {
            $data = [
                'data' => $query->limit($per_page)->get(),
            ];
        }

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bulan' => 'required|string|max:255',
            'biaya' => 'required',
        ]);

        $hargaDomain = HargaDomain::create([
            'bulan' => $request->bulan,
            'biaya' => $request->biaya,
        ]);

        return response()->json($hargaDomain);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $hargaDomain = HargaDomain::findOrFail($id);

        return response()->json($hargaDomain);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'bulan' => 'required|string|max:255',
            'biaya' => 'required',
        ]);

        $hargaDomain = HargaDomain::findOrFail($id);

        $hargaDomain->update([
            'bulan' => $request->bulan,
            'biaya' => $request->biaya,
        ]);

        return response()->json($hargaDomain);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $hargaDomain = HargaDomain::findOrFail($id);

        $hargaDomain->delete();

        return response()->json([
            'success' => true,
            'message' => 'Harga domain deleted successfully',
        ]);
    }
}
