<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $journals = Journal::with(['user', 'journalCategory'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Journals retrieved successfully',
                'data' => $journals
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve journals',
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
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start' => 'required|date',
                'end' => 'nullable|date|after:start',
                'status' => 'in:ongoing,completed,cancelled,archived',
                'priority' => 'in:low,medium,high',
                'journal_category_id' => 'required|exists:journal_categories,id',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $journal = Journal::create($validator->validated());
            $journal->load(['user', 'journalCategory']);

            return response()->json([
                'success' => true,
                'message' => 'Journal created successfully',
                'data' => $journal
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal',
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
            $journal = Journal::with(['user', 'journalCategory'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Journal retrieved successfully',
                'data' => $journal
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve journal',
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
            $journal = Journal::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'start' => 'sometimes|required|date',
                'end' => 'nullable|date|after:start',
                'status' => 'in:ongoing,completed,cancelled,archived',
                'priority' => 'in:low,medium,high',
                'journal_category_id' => 'sometimes|required|exists:journal_categories,id',
                'user_id' => 'sometimes|required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $journal->update($validator->validated());
            $journal->load(['user', 'journalCategory']);

            return response()->json([
                'success' => true,
                'message' => 'Journal updated successfully',
                'data' => $journal
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update journal',
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
            $journal = Journal::findOrFail($id);
            $journal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Journal deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete journal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
