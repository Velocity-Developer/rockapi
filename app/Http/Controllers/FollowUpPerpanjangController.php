<?php

namespace App\Http\Controllers;

use App\Models\FollowUpPerpanjang;
use Illuminate\Http\Request;

class FollowUpPerpanjangController extends Controller
{
    public function index(Request $request)
    {
        $query = FollowUpPerpanjang::query()
            ->with('user:id,name')
            ->orderByDesc('tanggal');

        $perPage = (int) $request->input('per_page', 100);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['user_id'] = $request->user()?->id;

        $data = FollowUpPerpanjang::create($validated);

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $data,
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(FollowUpPerpanjang::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $data = FollowUpPerpanjang::findOrFail($id);
        $validated = $this->validatedData($request);
        $validated['user_id'] = $request->user()?->id;

        $data->update($validated);

        return response()->json([
            'message' => 'Data berhasil diperbarui',
            'data' => $data->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $data = FollowUpPerpanjang::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'status' => 'nullable|boolean',
            'tanggal' => 'required|date',
            'whmcs_user_id' => 'nullable|integer',
            'whmcs_domain_id' => 'nullable|integer',
            'whmcs_hosting_id' => 'nullable|integer',
            'webhost_id' => 'nullable|integer',
            'keterangan' => 'nullable|string',
            'alasan' => 'nullable|string',
        ]);
    }
}
