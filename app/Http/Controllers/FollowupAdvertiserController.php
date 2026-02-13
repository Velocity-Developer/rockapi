<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FollowupAdvertiser;

class FollowupAdvertiserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $query = FollowupAdvertiser::query();

        $orderBy = $request->query('order_by', 'id');
        $order = $request->query('order', 'desc');
        $query->orderBy($orderBy, $order);

        $perPage = (int) ($request->query('per_page', 20));
        $datas = $query->paginate($perPage);

        return response()->json($datas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'required|string',
            'biaya' => 'required|numeric',
        ]);

        $data = FollowupAdvertiser::create($validated);

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $data
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = FollowupAdvertiser::findOrFail($id);
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = FollowupAdvertiser::findOrFail($id);

        $validated = $request->validate([
            'bulan' => 'required|string',
            'biaya' => 'required|numeric',
        ]);

        $data->update($validated);

        return response()->json([
            'message' => 'Data berhasil diperbarui',
            'data' => $data
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = FollowupAdvertiser::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus'
        ]);
    }
}
