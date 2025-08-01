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
    public function index(): JsonResponse
    {
        try {
            $categories = JournalCategory::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'message' => 'Journal categories retrieved successfully',
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve journal categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:journal_categories,name',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $category = JournalCategory::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Journal category created successfully',
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $category = JournalCategory::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Journal category retrieved successfully',
                'data' => $category
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve journal category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $category = JournalCategory::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255|unique:journal_categories,name,' . $id,
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $category->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Journal category updated successfully',
                'data' => $category
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update journal category',
                'error' => $e->getMessage()
            ], 500);
        }
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
}
