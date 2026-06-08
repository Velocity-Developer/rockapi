<?php

namespace App\Http\Controllers;

use App\Models\ReminderCS;
use Illuminate\Http\Request;

class ReminderCSController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ReminderCS::query()
            ->with('user:id,name,username');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('jam')) {
            $query->where('jam', $request->jam);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('keterangan', 'like', "%{$search}%");
        }

        $orderBy = $request->input('order_by', 'id');
        $allowedOrderBy = [
            'id',
            'jam',
            'user_id',
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
        $data = $this->validatedData($request);
        $data['user_id'] = $request->user()?->id;

        $reminder = ReminderCS::create($data);
        $reminder->load('user:id,name,username');

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $reminder,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reminder = ReminderCS::with('user:id,name,username')->findOrFail($id);

        return response()->json($reminder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $reminder = ReminderCS::findOrFail($id);
        $data = $this->validatedData($request);
        $data['user_id'] = $request->user()?->id;

        $reminder->update($data);
        $reminder->load('user:id,name,username');

        return response()->json([
            'message' => 'Data berhasil diperbarui',
            'data' => $reminder,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $reminder = ReminderCS::findOrFail($id);
        $reminder->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus',
        ]);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'jam' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        if (array_key_exists('jam', $data)) {
            $data['jam'] = $this->formatJam($data['jam']);
        }

        return $data;
    }

    private function formatJam(?string $jam): ?string
    {
        if ($jam === null) {
            return null;
        }

        $jam = trim($jam);

        if ($jam === '') {
            return $jam;
        }

        $normalizedJam = str_replace('.', ':', $jam);

        if (preg_match('/^(\d{1,2})(?::(\d{1,2}))?(?::\d{1,2})?$/', $normalizedJam, $matches)) {
            $hour = (int) $matches[1];
            $minute = isset($matches[2]) ? (int) $matches[2] : 0;

            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        }

        $timestamp = strtotime($normalizedJam);

        if ($timestamp !== false) {
            return date('H:i', $timestamp);
        }

        return $jam;
    }
}
