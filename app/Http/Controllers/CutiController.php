<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use Illuminate\Http\Request;

class CutiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cuti::query();

        if ($request->input('nama')) {
            $query->where('nama', 'like', '%' . $request->input('nama') . '%');
        }

        if ($request->input('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }

        if ($request->input('tipe')) {
            $query->where('tipe', $request->input('tipe'));
        }

        if ($request->input('tanggal')) {
            $query->whereDate('tanggal', $request->input('tanggal'));
        }

        if ($request->input('tanggal_from')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_from'));
        }

        if ($request->input('tanggal_to')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_to'));
        }

        $orderBy = $request->input('order_by', 'tanggal');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($orderBy, $order);

        $perPage = (int) $request->input('per_page', 50);

        $pagination = filter_var(
            $request->query('pagination', true),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($pagination) {
            $data = $query->paginate($perPage);
        } else {
            $data = [
                'data' => $query->limit($perPage)->get(),
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
            'nama' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jenis' => 'nullable|string|max:255',
            'time' => 'nullable|string|max:50',
            'tipe' => 'nullable|string|max:50',
            'detail' => 'nullable|string',
        ]);

        $cuti = new Cuti();
        $cuti->nama = $request->nama;
        $cuti->tanggal = $request->tanggal;
        $cuti->jenis = $request->jenis;
        $cuti->time = $request->time;
        $cuti->tipe = $request->tipe;
        $cuti->detail = $request->detail;
        $cuti->save();

        return response()->json($cuti, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cuti = Cuti::findOrFail($id);

        return response()->json($cuti);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jenis' => 'nullable|string|max:255',
            'time' => 'nullable|string|max:50',
            'tipe' => 'nullable|string|max:50',
            'detail' => 'nullable|string',
        ]);

        $cuti = Cuti::findOrFail($id);
        $cuti->nama = $request->nama;
        $cuti->tanggal = $request->tanggal;
        $cuti->jenis = $request->jenis;
        $cuti->time = $request->time;
        $cuti->tipe = $request->tipe;
        $cuti->detail = $request->detail;
        $cuti->save();

        return response()->json($cuti);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cuti = Cuti::findOrFail($id);
        $cuti->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuti deleted successfully',
        ]);
    }
}
