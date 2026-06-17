<?php

namespace App\Http\Controllers;

use App\Models\FormOrder;
use App\Models\User;
use App\Services\TelegramServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FormOrder::query();

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('source', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%")
                    ->orWhere('hp', 'like', "%{$search}%")
                    ->orWhere('kebutuhan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source')->toString());
        }

        if ($request->filled('usia')) {
            $query->where('usia', $request->integer('usia'));
        }

        $allowedOrderBy = [
            'id',
            'source',
            'nama',
            'hp',
            'usia',
            'created_at',
            'updated_at',
        ];
        $orderBy = $request->input('order_by', 'id');

        if (! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'id';
        }

        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        return response()->json(
            $query->orderBy($orderBy, $order)->paginate($perPage)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $formOrder = FormOrder::create($this->validatedData($request));

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $formOrder,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        return response()->json(FormOrder::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $formOrder = FormOrder::findOrFail($id);
        $formOrder->update($this->validatedData($request, true));

        return response()->json([
            'message' => 'Data berhasil diperbarui',
            'data' => $formOrder->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $formOrder = FormOrder::findOrFail($id);
        $formOrder->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus',
        ]);
    }

    private function validatedData(Request $request, bool $isUpdate = false): array
    {
        $presenceRule = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'source' => [$presenceRule, 'string', 'max:255'],
            'nama' => [$presenceRule, 'string', 'max:255'],
            'hp' => [$presenceRule, 'string', 'max:255'],
            'usia' => [$presenceRule, 'integer', 'min:1', 'max:150'],
            'kebutuhan' => [$presenceRule, 'string'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function public_store(Request $request, TelegramServices $telegramServices): JsonResponse
    {
        $users = User::role(['admin'])
            ->whereNotNull('telegram_id')
            ->where('telegram_id', '!=', '')
            ->get();

        $message = "Ada klik form order baru dari {$request->input('nama')} di {$request->input('source')}";

        foreach ($users as $user) {
            $telegramServices->sendMessage($user->telegram_id, $message);
        }

        $formOrder = FormOrder::create($this->validatedData($request));

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $formOrder,
        ], 201);
    }
}
