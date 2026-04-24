<?php

namespace App\Http\Controllers;

use App\Models\AbsensiShift;
use Illuminate\Http\Request;

class AbsensiShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = AbsensiShift::query();

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->input('nama') . '%');
        }

        if ($request->filled('aktif')) {
            $query->where('aktif', filter_var($request->input('aktif'), FILTER_VALIDATE_BOOLEAN));
        }

        $orderBy = $request->input('order_by', 'nama');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->input('per_page', 50);
        $pagination = filter_var($request->query('pagination', true), FILTER_VALIDATE_BOOLEAN);

        $allowedOrderBy = ['id', 'nama', 'masuk', 'pulang', 'aktif', 'created_at', 'updated_at'];

        if (! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'nama';
        }

        $query->orderBy($orderBy, $order)->orderBy('id');

        if ($pagination) {
            $data = $query->paginate($perPage);
        } else {
            $data = [
                'data' => $query->limit($perPage)->get(),
            ];
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'masuk' => ['required', 'date_format:H:i:s'],
            'pulang' => ['required', 'date_format:H:i:s'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $shift = AbsensiShift::create($validated);

        return response()->json($shift, 201);
    }

    public function show(string $id)
    {
        $shift = AbsensiShift::findOrFail($id);

        return response()->json($shift);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'masuk' => ['required', 'date_format:H:i:s'],
            'pulang' => ['required', 'date_format:H:i:s'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $shift = AbsensiShift::findOrFail($id);
        $shift->update($validated);

        return response()->json($shift);
    }

    public function destroy(string $id)
    {
        $shift = AbsensiShift::findOrFail($id);

        if ($shift->absensi()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete shift that is already used by absensi data',
            ], 400);
        }

        $shift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Absensi shift deleted successfully',
        ]);
    }
}
