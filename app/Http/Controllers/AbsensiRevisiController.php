<?php

namespace App\Http\Controllers;

use App\Models\AbsensiRevisi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsensiRevisiController extends Controller
{
    public function index(Request $request)
    {
        $query = AbsensiRevisi::with(['user', 'approver']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('approve_user_id')) {
            $query->where('approve_user_id', $request->input('approve_user_id'));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->input('tanggal'));
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }

        if ($request->filled('sumber')) {
            $query->where('sumber', 'like', '%' . $request->input('sumber') . '%');
        }

        $orderBy = $request->input('order_by', 'tanggal');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->input('per_page', 50);
        $pagination = filter_var($request->query('pagination', true), FILTER_VALIDATE_BOOLEAN);

        $allowedOrderBy = [
            'id',
            'user_id',
            'approve_user_id',
            'tanggal',
            'detik',
            'jenis',
            'created_at',
            'updated_at',
        ];

        if (! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'tanggal';
        }

        $query->orderBy($orderBy, $order)->orderBy('id', 'desc');

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
        $validated = $request->validate($this->rules());

        $revisi = AbsensiRevisi::create($validated)->load(['user', 'approver']);

        return response()->json($revisi, 201);
    }

    public function show(string $id)
    {
        $revisi = AbsensiRevisi::with(['user', 'approver'])->findOrFail($id);

        return response()->json($revisi);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate($this->rules());

        $revisi = AbsensiRevisi::findOrFail($id);
        $revisi->update($validated);

        return response()->json($revisi->load(['user', 'approver']));
    }

    public function destroy(string $id)
    {
        $revisi = AbsensiRevisi::findOrFail($id);
        $revisi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Absensi revisi deleted successfully',
        ]);
    }

    private function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'tanggal' => ['required', 'date'],
            'detik' => ['nullable', 'integer'],
            'jenis' => [
                'required',
                'string',
                Rule::in([
                    AbsensiRevisi::JENIS_TAMBAH,
                    AbsensiRevisi::JENIS_KURANG,
                ]),
            ],
            'sumber' => ['nullable', 'string', 'max:255'],
            'approve_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
