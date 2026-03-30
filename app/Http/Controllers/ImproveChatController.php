<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ImproveChat;
use Illuminate\Support\Facades\Validator;

class ImproveChatController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $user_roles = $user->getRoleNames();

        $listKategori = ImproveChat::KATEGORI;
        if ($user_roles && in_array('customer_service', json_decode($user_roles, true))) {
            $listKategori = ['CS'];
        }

        $query = ImproveChat::query();

        // Search functionality
        $search = $request->input('q');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nohp', 'like', "%{$search}%")
                    ->orWhere('kategori', 'like', "%{$search}%")
                    ->orWhere('masukkan', 'like', "%{$search}%");
            });
        }

        //filter kategori        
        $kategori = $request->input('kategori');
        if ($kategori) {
            $query->where('kategori', $kategori);
        } else {
            $query->whereIn('kategori', $listKategori);
        }

        // Sorting
        $orderBy = $request->input('order_by', 'id');
        $order = $request->input('order', 'desc');
        $query->orderBy($orderBy, $order);

        // Pagination
        $perPage = (int) ($request->input('per_page', 100));
        $results = $query->paginate($perPage);


        return response()->json([
            ...$results->toArray(),
            'kategori' => $listKategori
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nohp' => 'nullable|string',
            'kategori' => 'required|string',
            'masukkan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ImproveChat = ImproveChat::create([
            'nohp' => $request->input('nohp'),
            'kategori' => $request->input('kategori'),
            'masukkan' => $request->input('masukkan'),
            // user_id is handled in model's booted method
        ]);

        return response()->json($ImproveChat, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ImproveChat = ImproveChat::find($id);
        if (!$ImproveChat) {
            return response()->json(['message' => 'Improve Chat not found'], 404);
        }

        return response()->json($ImproveChat);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'nohp' => 'nullable|string',
            'kategori' => 'required|string',
            'masukkan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ImproveChat = ImproveChat::find($id);
        if (!$ImproveChat) {
            return response()->json(['message' => 'Improve Chat not found'], 404);
        }

        $ImproveChat->update([
            'nohp' => $request->input('nohp'),
            'kategori' => $request->input('kategori'),
            'masukkan' => $request->input('masukkan'),
        ]);

        return response()->json($ImproveChat);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ImproveChat = ImproveChat::find($id);
        if (!$ImproveChat) {
            return response()->json(['message' => 'Improve Chat not found'], 404);
        }

        $ImproveChat->delete();

        return response()->json(['message' => 'Improve Chat deleted successfully']);
    }
}
