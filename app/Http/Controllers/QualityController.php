<?php

namespace App\Http\Controllers;

use App\Models\Quality;
use Illuminate\Http\Request;

class QualityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Quality::query();

        if ($request->input('detail')) {
            $query->where('detail', 'like', '%' . $request->input('detail') . '%');
        }

        $orderBy = $request->input('order_by', 'id');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';

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
            'detail' => 'required|string|max:255',
        ]);

        $quality = new Quality();
        $quality->detail = $request->detail;
        $quality->save();

        return response()->json($quality, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $quality = Quality::findOrFail($id);

        return response()->json($quality);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'detail' => 'required|string|max:255',
        ]);

        $quality = Quality::findOrFail($id);
        $quality->detail = $request->detail;
        $quality->save();

        return response()->json($quality);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $quality = Quality::findOrFail($id);
        $quality->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quality deleted successfully',
        ]);
    }
}
