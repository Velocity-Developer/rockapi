<?php

namespace App\Http\Controllers;

use App\Models\CekServerTimSupport;
use Illuminate\Http\Request;

class CekServerTimSupportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CekServerTimSupport::with('server:id,name');

        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        if ($request->filled('cek_error_idrac')) {
            $query->where('cek_error_idrac', filter_var($request->cek_error_idrac, FILTER_VALIDATE_BOOLEAN));
        }

        $orderBy = $request->input('order_by', 'id');
        $allowedOrderBy = [
            'id',
            'server_id',
            'hapus_backup_admin',
            'kapasitas_ssh',
            'cek_error_idrac',
            'created_at',
            'updated_at',
        ];

        if (! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'id';
        }

        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderBy, $order);

        $perPage = (int) $request->input('per_page', 20);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'server_id' => 'nullable|exists:servers,id',
            'hapus_backup_admin' => 'nullable|date',
            'kapasitas_ssh' => 'nullable|string|max:255',
            'cek_error_idrac' => 'nullable|boolean',
            'error_idrac' => 'nullable|string',
        ]);

        $cekServer = CekServerTimSupport::create($data);
        $cekServer->load('server:id,name');

        return response()->json($cekServer, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cekServer = CekServerTimSupport::with('server:id,name')->findOrFail($id);

        return response()->json($cekServer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'server_id' => 'nullable|exists:servers,id',
            'hapus_backup_admin' => 'nullable|date',
            'kapasitas_ssh' => 'nullable|string|max:255',
            'cek_error_idrac' => 'nullable|boolean',
            'error_idrac' => 'nullable|string',
        ]);

        $cekServer = CekServerTimSupport::findOrFail($id);
        $cekServer->update($data);
        $cekServer->load('server:id,name');

        return response()->json($cekServer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cekServer = CekServerTimSupport::findOrFail($id);
        $cekServer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cek server tim support deleted successfully',
        ]);
    }
}
