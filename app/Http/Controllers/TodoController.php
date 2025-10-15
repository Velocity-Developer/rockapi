<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\TodoAssignment;
use App\Models\TodoCategory;
use App\Models\User;
use App\Http\Resources\TodoResource;
use App\Http\Resources\TodoAssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TodoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TodoList::with([
            'creator:id,name,avatar',
            'category:id,name,color,icon',
            'assignments' => function ($q) {
                $q->with(['assignable']);
            }
        ]);

        // Filter by status
        if ($request->input('status')) {
            $query->byStatus($request->input('status'));
        }

        // Filter by priority
        if ($request->input('priority')) {
            $query->byPriority($request->input('priority'));
        }

        // Filter by category
        if ($request->input('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter by created_by
        if ($request->input('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        // Search by title/description
        if ($request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by date range
        if ($request->input('date_start') && $request->input('date_end')) {
            $query->whereBetween('due_date', [
                $request->input('date_start'),
                $request->input('date_end')
            ]);
        }

        // Order by
        $orderBy = $request->input('order_by', 'created_at');
        $order = $request->input('order', 'desc');
        $query->orderBy($orderBy, $order);

        // Pagination
        $pagination = $request->input('pagination', 'true');
        if ($pagination == 'true') {
            $perPage = $request->input('per_page', 10);
            $todos = $query->paginate($perPage);
        } else {
            $todos = [
                'data' => $query->get(),
            ];
        }

        // Transform with resources
        if (isset($todos['data'])) {
            $todos['data'] = TodoResource::collection($todos['data']);
        }

        return response()->json($todos);
    }

    public function myTodos(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = TodoList::with([
            'creator:id,name,avatar',
            'category:id,name,color,icon',
            'assignments' => function ($q) use ($user) {
                $q->where(function ($subQuery) use ($user) {
                    $subQuery->where('assignable_type', 'user')
                        ->where('assignable_id', $user->id);
                })->orWhere(function ($subQuery) use ($user) {
                    $userRoleIds = $user->roles->pluck('id');
                    $subQuery->where('assignable_type', 'role')
                        ->whereIn('assignable_id', $userRoleIds);
                });
            }
        ])->forUser($user);

        // Apply same filters as index
        if ($request->input('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->input('priority')) {
            $query->byPriority($request->input('priority'));
        }

        if ($request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy('created_at', 'desc');

        $pagination = $request->input('pagination', 'true');
        if ($pagination == 'true') {
            $perPage = $request->input('per_page', 10);
            $todos = $query->paginate($perPage);
        } else {
            $todos = [
                'data' => $query->get(),
            ];
        }

        return response()->json($todos);
    }

    public function createdTodos(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = TodoList::with([
            'creator:id,name,avatar',
            'category:id,name,color,icon',
            'assignments' => function ($q) {
                $q->with(['assignable', 'assignedBy']);
            }
        ])->createdBy($user);

        // Apply filters
        if ($request->input('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->input('priority')) {
            $query->byPriority($request->input('priority'));
        }

        if ($request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy('created_at', 'desc');

        $pagination = $request->input('pagination', 'true');
        if ($pagination == 'true') {
            $perPage = $request->input('per_page', 10);
            $todos = $query->paginate($perPage);
        } else {
            $todos = [
                'data' => $query->get(),
            ];
        }

        return response()->json($todos);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date' => 'nullable|date|after_or_equal:today',
            'category_id' => 'nullable|exists:todo_categories,id',
            'is_private' => 'boolean',
            'notes' => 'nullable|string',
            'assignments' => 'required|array|min:1',
            'assignments.*.type' => 'required|in:user,role',
            'assignments.*.id' => 'required|integer|min:1'
        ]);

        return DB::transaction(function () use ($request) {
            $todo = TodoList::create([
                'title' => $request->title,
                'description' => $request->description,
                'created_by' => Auth::id(),
                'status' => TodoList::STATUS_ASSIGNED,
                'priority' => $request->priority ?? TodoList::PRIORITY_MEDIUM,
                'due_date' => $request->due_date,
                'category_id' => $request->category_id,
                'is_private' => $request->boolean('is_private', false),
                'notes' => $request->notes
            ]);

            // Create assignments
            foreach ($request->assignments as $assignment) {
                TodoAssignment::create([
                    'todo_id' => $todo->id,
                    'assignable_type' => $assignment['type'],
                    'assignable_id' => $assignment['id'],
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                    'status' => TodoAssignment::STATUS_ASSIGNED
                ]);
            }

            // Load relationships for response
            $todo->load([
                'creator:id,name,avatar',
                'category:id,name,color,icon',
                'assignments' => function ($q) {
                    $q->with(['assignable', 'assignedBy']);
                }
            ]);

            return response()->json([
                'success' => true,
                'data' => $todo,
                'message' => 'Todo created successfully'
            ], 201);
        });
    }

    public function show(TodoList $todo): JsonResponse
    {
        $todo->load([
            'creator:id,name,avatar',
            'category:id,name,color,icon',
            'assignments' => function ($q) {
                $q->with(['assignable', 'assignedBy']);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $todo
        ]);
    }

    public function update(Request $request, TodoList $todo): JsonResponse
    {
        // Check if user can update this todo
        if ($todo->created_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this todo'
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['nullable', Rule::in(['assigned', 'in_progress', 'completed', 'declined'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date' => 'nullable|date|after_or_equal:today',
            'category_id' => 'nullable|exists:todo_categories,id',
            'is_private' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        $todo->update([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? $todo->status,
            'priority' => $request->priority ?? $todo->priority,
            'due_date' => $request->due_date,
            'category_id' => $request->category_id,
            'is_private' => $request->boolean('is_private', $todo->is_private),
            'notes' => $request->notes
        ]);

        $todo->load([
            'creator:id,name,avatar',
            'category:id,name,color,icon',
            'assignments' => function ($q) {
                $q->with(['assignable', 'assignedBy']);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $todo,
            'message' => 'Todo updated successfully'
        ]);
    }

    public function destroy(TodoList $todo): JsonResponse
    {
        // Check if user can delete this todo
        if ($todo->created_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this todo'
            ], 403);
        }

        return DB::transaction(function () use ($todo) {
            // Delete assignments first
            $todo->assignments()->delete();

            // Delete todo
            $todo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Todo deleted successfully'
            ]);
        });
    }

    public function assign(Request $request, TodoList $todo): JsonResponse
    {
        // Check if user can manage assignments for this todo
        if ($todo->created_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage assignments for this todo'
            ], 403);
        }

        $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.type' => 'required|in:user,role',
            'assignments.*.id' => 'required|integer|min:1'
        ]);

        return DB::transaction(function () use ($request, $todo) {
            // Remove existing assignments
            $todo->assignments()->delete();

            // Create new assignments
            foreach ($request->assignments as $assignment) {
                TodoAssignment::create([
                    'todo_id' => $todo->id,
                    'assignable_type' => $assignment['type'],
                    'assignable_id' => $assignment['id'],
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                    'status' => TodoAssignment::STATUS_ASSIGNED
                ]);
            }

            $todo->load([
                'creator:id,name,avatar',
                'category:id,name,color,icon',
                'assignments' => function ($q) {
                    $q->with(['assignable', 'assignedBy']);
                }
            ]);

            return response()->json([
                'success' => true,
                'data' => $todo,
                'message' => 'Assignments updated successfully'
            ]);
        });
    }

    public function assignments(TodoList $todo): JsonResponse
    {
        // Check if user can view assignments for this todo
        $user = Auth::user();
        if ($todo->created_by !== $user->id && !$todo->isAssignedToUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view assignments for this todo'
            ], 403);
        }

        $assignments = $todo->assignments()
            ->with(['assignable', 'assignedBy'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    public function updateAssignmentStatus(Request $request, TodoList $todo, int $assignmentId): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['assigned', 'in_progress', 'completed', 'declined'])]
        ]);

        $user = Auth::user();

        // Find assignment
        $assignment = $todo->assignments()->findOrFail($assignmentId);

        // Check if user can update this assignment
        $canUpdate = false;

        // User can update if they created the todo
        if ($todo->created_by === $user->id) {
            $canUpdate = true;
        }

        // User can update if they are assigned (direct or via role)
        if ($assignment->assignable_type === 'user' && $assignment->assignable_id === $user->id) {
            $canUpdate = true;
        } elseif ($assignment->assignable_type === 'role') {
            $userRoleIds = $user->roles->pluck('id');
            if ($userRoleIds->contains($assignment->assignable_id)) {
                $canUpdate = true;
            }
        }

        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this assignment'
            ], 403);
        }

        // Update assignment status
        $assignment->update([
            'status' => $request->status,
            'completed_at' => $request->status === TodoAssignment::STATUS_COMPLETED ? now() : null
        ]);

        // Update todo status based on assignments
        $this->updateTodoStatus($todo);

        $assignment->load(['assignable', 'assignedBy']);

        return response()->json([
            'success' => true,
            'data' => $assignment,
            'message' => 'Assignment status updated successfully'
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Basic statistics
        $stats = [
            'my_total' => TodoList::forUser($user)->count(),
            'my_completed' => TodoList::forUser($user)->byStatus(TodoList::STATUS_COMPLETED)->count(),
            'my_pending' => TodoList::forUser($user)->whereNot('status', TodoList::STATUS_COMPLETED)->count(),
            'created_total' => TodoList::createdBy($user)->count(),
            'created_completed' => TodoList::createdBy($user)->byStatus(TodoList::STATUS_COMPLETED)->count(),
        ];

        // Priority breakdown
        $stats['my_by_priority'] = [
            'low' => TodoList::forUser($user)->byPriority(TodoList::PRIORITY_LOW)->count(),
            'medium' => TodoList::forUser($user)->byPriority(TodoList::PRIORITY_MEDIUM)->count(),
            'high' => TodoList::forUser($user)->byPriority(TodoList::PRIORITY_HIGH)->count(),
            'urgent' => TodoList::forUser($user)->byPriority(TodoList::PRIORITY_URGENT)->count(),
        ];

        // Category breakdown
        $stats['my_by_category'] = TodoCategory::withCount(['todos' => function ($q) use ($user) {
            $q->forUser($user);
        }])->active()->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'icon' => $category->icon,
                'count' => $category->todos_count
            ];
        });

        // Recent activity
        $stats['recent_activity'] = TodoList::with(['creator:id,name'])
            ->forUser($user)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'updated_at', 'created_by']);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    private function updateTodoStatus(TodoList $todo): void
    {
        $assignments = $todo->assignments;
        $completedCount = $assignments->where('status', TodoAssignment::STATUS_COMPLETED)->count();
        $totalCount = $assignments->count();

        if ($completedCount === $totalCount) {
            $todo->update(['status' => TodoList::STATUS_COMPLETED]);
        } elseif ($completedCount > 0) {
            $todo->update(['status' => TodoList::STATUS_IN_PROGRESS]);
        }
    }
}
