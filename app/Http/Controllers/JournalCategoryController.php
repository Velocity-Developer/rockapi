<?php

namespace App\Http\Controllers;

use App\Models\JournalCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JournalCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = JournalCategory::orderBy('name');

        //filter
        if ($request->input('role') && $request->input('role') !== 'admin') {
            $query->where('role', $request->input('role'));
        }

        //search
        if ($request->input('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        //pagination
        $per_page = $request->input('per_page', 10);
        $categories = $query->paginate($per_page);

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'icon'          => 'nullable|string',
            'role'          => 'nullable|string',
        ]);

        $category = JournalCategory::create([
            'name'          => $request->name,
            'description'   => $request->description,
            'icon'          => $request->icon,
            'role'          => $request->role,
        ]);

        return response()->json($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $category = JournalCategory::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'icon'          => 'nullable|string',
            'role'          => 'nullable|string',
        ]);

        $category = JournalCategory::findOrFail($id);
        $category->update([
            'name'          => $request->name,
            'description'   => $request->description,
            'icon'          => $request->icon,
            'role'          => $request->role,
        ]);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {

        try {
            $category = JournalCategory::findOrFail($id);

            // Check if category is being used by any journals
            if ($category->journals()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete journal category that is being used by journals'
                ], 400);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Journal category deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete journal category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function option_by_role(Request $request): JsonResponse
    {
        $query = JournalCategory::orderBy('name');

        //filter
        if ($request->input('role')) {
            $query->where('role', $request->input('role'));
        }

        $categories = $query->get();

        return response()->json($categories);
    }
}
