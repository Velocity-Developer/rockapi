<?php

namespace App\Http\Controllers;

use App\Models\CekServerTimSupport;
use App\Models\Server;
use Illuminate\Http\Request;

class CekServerTimSupportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CekServerTimSupport::with('server:id,name,hostname', 'user:id,name,username');

        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('cek_error_idrac')) {
            $query->where('cek_error_idrac', filter_var($request->cek_error_idrac, FILTER_VALIDATE_BOOLEAN));
        }

        $orderBy = $request->input('order_by', 'id');
        $allowedOrderBy = [
            'id',
            'server_id',
            'user_id',
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

        $data['user_id'] = $request->user()->id;

        $cekServer = CekServerTimSupport::create($data);
        $cekServer->load('server:id,name', 'user:id,name,username');

        return response()->json($cekServer, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cekServer = CekServerTimSupport::with('server:id,name', 'user:id,name,username')->findOrFail($id);

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

        if (
            array_key_exists('kapasitas_ssh', $data)
            && $cekServer->kapasitas_ssh !== $data['kapasitas_ssh']
        ) {
            $data['tanggal_update_kapasitas_ssh'] = now();
        }

        if (array_key_exists('cek_error_idrac', $data)) {
            $currentCekErrorIdrac = is_null($cekServer->cek_error_idrac)
                ? null
                : (bool) $cekServer->cek_error_idrac;
            $newCekErrorIdrac = is_null($data['cek_error_idrac'])
                ? null
                : (bool) $data['cek_error_idrac'];

            if ($currentCekErrorIdrac !== $newCekErrorIdrac) {
                $data['tanggal_update_cek_error_idrac'] = now();
            }
        }

        $cekServer->update($data);
        $cekServer->load('server:id,name', 'user:id,name,username');

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

    // server dan riwayat cek terakhir
    public function latestServerCheck(Request $request)
    {
        $query = Server::query()
            ->select('id', 'name', 'type', 'ip_address', 'hostname', 'is_active')
            ->with([
                'cek_server_tim_support_latest' => function ($query) {
                    $query->with('user:id,name,username');
                },
            ]);

        if ($request->filled('server_id')) {
            $query->where('id', $request->server_id);
        }

        if ($request->filled('cek_error_idrac')) {
            $query->whereHas('cek_server_tim_support_latest', function ($query) use ($request) {
                $query->where('cek_error_idrac', filter_var($request->cek_error_idrac, FILTER_VALIDATE_BOOLEAN));
            });
        }

        $orderBy = $request->input('order_by', 'id');
        $allowedOrderBy = [
            'id',
            'name',
            'type',
            'ip_address',
            'hostname',
            'is_active',
        ];

        if (! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'id';
        }

        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderBy, $order);

        $perPage = (int) $request->input('per_page', 25);

        return response()->json($query->paginate($perPage));
    }
}
