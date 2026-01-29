<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodoCategoryResource;
use App\Models\TodoCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodoCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = TodoCategory::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => TodoCategoryResource::collection($categories),
        ]);
    }

    public function active(): JsonResponse
    {
        $categories = TodoCategory::active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TodoCategoryResource::collection($categories),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:todo_categories',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = TodoCategory::create([
            'name' => $request->name,
            'color' => $request->color ?? '#6b7280',
            'icon' => $request->icon,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'data' => new TodoCategoryResource($category),
            'message' => 'Category created successfully',
        ], 201);
    }

    public function show(TodoCategory $todoCategory): JsonResponse
    {
        $todoCategory->loadCount(['todos', 'activeTodos', 'completedTodos']);

        return response()->json([
            'success' => true,
            'data' => new TodoCategoryResource($todoCategory),
        ]);
    }

    public function update(Request $request, TodoCategory $todoCategory): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:todo_categories,name,'.$todoCategory->id,
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $todoCategory->update([
            'name' => $request->name,
            'color' => $request->color ?? $todoCategory->color,
            'icon' => $request->icon,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', $todoCategory->is_active),
        ]);

        return response()->json([
            'success' => true,
            'data' => new TodoCategoryResource($todoCategory),
            'message' => 'Category updated successfully',
        ]);
    }

    public function destroy(TodoCategory $todoCategory): JsonResponse
    {
        // Check if category has todos
        if ($todoCategory->todos()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing todos',
            ], 422);
        }

        $todoCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}
