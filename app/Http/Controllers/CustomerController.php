<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage   = (int) ($request->input('per_page', 20));
        $orderBy   = $request->input('order_by', 'created_at');
        $order     = $request->input('order', 'desc');
        $search    = $request->input('q');

        $query = Customer::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('hp', 'like', "%{$search}%");
            });
        }

        // Simple whitelist for order_by
        if (!in_array($orderBy, ['nama', 'email', 'hp', 'created_at', 'updated_at'])) {
            $orderBy = 'created_at';
        }
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($orderBy, $order);

        $customers = $query->paginate($perPage);
        return response()->json($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'   => 'required|string|min:2',
            'email'  => 'nullable|email',
            'hp'     => 'nullable|string',
            'alamat' => 'nullable|string',
            'telegram' => 'nullable|string',
            'hpads' => 'nullable|string',
            'saldo' => 'nullable|decimal:2',
            'jenis_kelamin' => 'nullable|string',
            'usia' => 'nullable|integer',
        ]);

        $customer = Customer::create($validated);
        return response()->json($customer, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }
        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'nama'   => 'required|string|min:2',
            'email'  => 'nullable|email',
            'hp'     => 'nullable|string',
            'alamat' => 'nullable|string',
            'telegram' => 'nullable|string',
            'hpads' => 'nullable|string',
            'saldo' => 'nullable|decimal:2',
            'jenis_kelamin' => 'nullable|string',
            'usia' => 'nullable|integer',
        ]);

        $customer->update($validated);
        return response()->json($customer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }
        $customer->delete();
        return response()->json(['message' => 'Customer berhasil dihapus']);
    }
}
