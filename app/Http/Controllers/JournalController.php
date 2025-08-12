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
    public function index(Request $request): JsonResponse
    {
        $query = Journal::with(['user:id,name,avatar', 'journalCategory'])
            ->orderBy('created_at', 'desc');

        //filter role
        if ($request->input('role')) {
            $query->where('role', $request->input('role'));
        }

        //filter user_id
        if ($request->input('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        //filter journal_category_id
        if ($request->input('journal_category_id')) {
            $query->where('journal_category_id', $request->input('journal_category_id'));
        }

        //filter status
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        //filter priority
        if ($request->input('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        //filter search
        if ($request->input('search')) {
            $query->where('title', 'like', '%' . $request->input('search') . '%');
        }

        //filter start
        if ($request->input('start')) {
            $query->where('start', 'like', '%' . $request->input('start') . '%');
        }

        //filter end
        if ($request->input('end')) {
            $query->where('end', 'like', '%' . $request->input('end') . '%');
        }

        //pagination
        $per_page = $request->input('per_page', 100);
        $journals = $query->paginate($per_page);

        return response()->json($journals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'                 => 'required|string',
            'description'           => 'nullable|string',
            'start'                 => 'required|date',
            'end'                   => 'nullable|date|after:start',
            'status'                => 'required|string',
            'priority'              => 'nullable|string',
            'user_id'               => 'nullable|exists:users,id',
            'webhost_id'            => 'nullable',
            'cs_main_project_id'    => 'nullable',
            'journal_category_id'   => 'nullable|exists:journal_categories,id',
        ]);

        if (!$request->input('user_id')) {
            $user_id = auth()->user()->id;
            $request->merge(['user_id' => $user_id]);
        }

        $journal = Journal::create([
            'title'                 => $request->title,
            'description'           => $request->description,
            'start'                 => $request->start,
            'end'                   => $request->end,
            'status'                => $request->status,
            'priority'              => $request->priority,
            'user_id'               => $request->user_id,
            'webhost_id'            => $request->webhost_id,
            'cs_main_project_id'    => $request->cs_main_project_id,
            'journal_category_id'   => $request->journal_category_id,
        ]);

        return response()->json($journal);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $journal = Journal::with(['user', 'journalCategory'])->findOrFail($id);
        return response()->json($journal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'title'                 => 'required|string',
            'description'           => 'nullable|string',
            'start'                 => 'required|date',
            'end'                   => 'nullable|date|after:start',
            'status'                => 'required|string',
            'priority'              => 'nullable|string',
            'user_id'               => 'required|exists:users,id',
            'webhost_id'            => 'nullable',
            'cs_main_project_id'    => 'nullable',
            'journal_category_id'   => 'nullable|exists:journal_categories,id',
        ]);

        $journal = Journal::findOrFail($id);

        $journal->update([
            'title'                 => $request->title,
            'description'           => $request->description,
            'start'                 => $request->start,
            'end'                   => $request->end,
            'status'                => $request->status,
            'priority'              => $request->priority,
            'webhost_id'            => $request->webhost_id,
            'cs_main_project_id'    => $request->cs_main_project_id,
            'journal_category_id'   => $request->journal_category_id,
        ]);

        return response()->json($journal);
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
