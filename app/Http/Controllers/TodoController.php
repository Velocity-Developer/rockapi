<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\TodoAssignment;
use App\Models\TodoUser;
use App\Models\TodoCategory;
use App\Models\User;
use App\Models\Journal;
use App\Http\Resources\TodoResource;
use App\Http\Resources\TodoAssignmentResource;
use App\Notifications\TodoAssignedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

        // Order by - prioritize incomplete todos with higher priority first
        $orderBy = $request->input('order_by');
        $order = $request->input('order');

        if ($orderBy && $order) {
            $query->orderBy($orderBy, $order);
        } else {
            // Default ordering: prioritize incomplete todos with higher priority
            $query->orderByRaw("
                CASE
                    WHEN status = 'completed' THEN 4
                    WHEN status = 'declined' THEN 3
                    WHEN status = 'in_progress' THEN 2
                    WHEN status = 'assigned' THEN 1
                    WHEN status = 'pending' THEN 0
                    ELSE 5
                END ASC,
                CASE
                    WHEN priority = 'urgent' THEN 0
                    WHEN priority = 'high' THEN 1
                    WHEN priority = 'medium' THEN 2
                    WHEN priority = 'low' THEN 3
                    ELSE 4
                END ASC,
                created_at DESC
            ");
        }

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
            'assignments' => function ($q) {
                $q->with(['assignable', 'assignedBy']);
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

        // Order by - prioritize incomplete todos with higher priority first
        $query->orderByRaw("
            CASE
                WHEN status = 'completed' THEN 4
                WHEN status = 'declined' THEN 3
                WHEN status = 'in_progress' THEN 2
                WHEN status = 'assigned' THEN 1
                WHEN status = 'pending' THEN 0
                ELSE 5
            END ASC,
            CASE
                WHEN priority = 'urgent' THEN 0
                WHEN priority = 'high' THEN 1
                WHEN priority = 'medium' THEN 2
                WHEN priority = 'low' THEN 3
                ELSE 4
            END ASC,
            created_at DESC
        ");

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

        // Order by - prioritize incomplete todos with higher priority first
        $query->orderByRaw("
            CASE
                WHEN status = 'completed' THEN 4
                WHEN status = 'declined' THEN 3
                WHEN status = 'in_progress' THEN 2
                WHEN status = 'assigned' THEN 1
                WHEN status = 'pending' THEN 0
                ELSE 5
            END ASC,
            CASE
                WHEN priority = 'urgent' THEN 0
                WHEN priority = 'high' THEN 1
                WHEN priority = 'medium' THEN 2
                WHEN priority = 'low' THEN 3
                ELSE 4
            END ASC,
            created_at DESC
        ");

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
            $usersToNotify = [];
            foreach ($request->assignments as $assignment) {
                $assignableClass = $assignment['type'] === 'user'
                    ? 'App\Models\User'
                    : 'Spatie\Permission\Models\Role';

                TodoAssignment::create([
                    'todo_id' => $todo->id,
                    'assignable_type' => $assignableClass,
                    'assignable_id' => $assignment['id'],
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                    'status' => TodoAssignment::STATUS_ASSIGNED
                ]);

                // Collect users to notify
                if ($assignment['type'] === 'user') {
                    $user = User::find($assignment['id']);
                    if ($user && $user->id !== Auth::id()) {
                        $usersToNotify[] = $user;
                    }
                } else {
                    // If assignment is to a role, get all users with that role
                    $role = \Spatie\Permission\Models\Role::find($assignment['id']);
                    if ($role) {
                        $roleUsers = $role->users()->where('users.id', '!=', Auth::id())->get();
                        $usersToNotify = array_merge($usersToNotify, $roleUsers->toArray());
                    }
                }
            }

            // Send notifications to assigned users
            if (!empty($usersToNotify)) {
                $assignedBy = Auth::user();
                foreach ($usersToNotify as $user) {
                    $userModel = is_array($user) ? User::find($user['id']) : $user;
                    if ($userModel) {
                        $userModel->notify(new TodoAssignedNotification($todo, $assignedBy));
                    }
                }
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

    public function update(string $id, Request $request): JsonResponse
    {
        // Check if user can update this todo
        $todo = TodoList::findOrFail($id);
        $creator_id = $todo->created_by ? intval($todo->created_by) : null;

        if ($creator_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this todo',
                'todo' => $todo,
                'user_id' => Auth::id()
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['nullable', Rule::in(['pending', 'assigned', 'in_progress', 'completed', 'declined'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date' => 'nullable|date|after_or_equal:today',
            'category_id' => 'nullable|exists:todo_categories,id',
            'is_private' => 'boolean',
            'notes' => 'nullable|string',
            'assignments' => 'nullable|array|min:1',
            'assignments.*.type' => 'required_with:assignments|in:user,role',
            'assignments.*.id' => 'required_with:assignments|integer|min:1'
        ]);

        return DB::transaction(function () use ($request, $todo) {
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

            // Handle assignments if provided
            if ($request->has('assignments')) {
                // Remove existing assignments
                $todo->assignments()->delete();

                // Create new assignments
                foreach ($request->assignments as $assignment) {
                    $assignableClass = $assignment['type'] === 'user'
                        ? 'App\Models\User'
                        : 'Spatie\Permission\Models\Role';

                    TodoAssignment::create([
                        'todo_id' => $todo->id,
                        'assignable_type' => $assignableClass,
                        'assignable_id' => $assignment['id'],
                        'assigned_by' => Auth::id(),
                        'assigned_at' => now(),
                        'status' => TodoAssignment::STATUS_ASSIGNED
                    ]);
                }
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
                'message' => 'Todo updated successfully'
            ]);
        });
    }

    public function destroy(string $id): JsonResponse
    {
        // Check if user can delete this todo
        $todo = TodoList::findOrFail($id);
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
                $assignableClass = $assignment['type'] === 'user'
                    ? 'App\Models\User'
                    : 'Spatie\Permission\Models\Role';

                TodoAssignment::create([
                    'todo_id' => $todo->id,
                    'assignable_type' => $assignableClass,
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
            'status' => ['required', Rule::in(['pending', 'assigned', 'in_progress', 'completed', 'declined'])]
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
        if ($assignment->assignable_type === 'App\Models\User' && $assignment->assignable_id === $user->id) {
            $canUpdate = true;
        } elseif ($assignment->assignable_type === 'Spatie\Permission\Models\Role') {
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

    public function updateStatus(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'assigned', 'in_progress', 'completed', 'declined'])]
        ]);

        $todo = TodoList::findOrFail($id);
        $user = Auth::user();
        $oldStatus = $todo->status;
        $newStatus = $request->status;

        $todo->update($request->only(['status']));

        // Handle TodoUser pivot operations
        if ($oldStatus === TodoList::STATUS_ASSIGNED && $newStatus === TodoList::STATUS_IN_PROGRESS && $user) {
            // Create journal entry first
            $journal = Journal::create([
                'title' => 'Pengerjaan ' . $todo->title,
                'description' => $todo->description,
                'start' => now(),
                'end' => null,
                'status' => 'ongoing',
                'priority' => 'medium',
                'user_id' => $user->id,
                'role' => $user->roles->first()->name ?? null,
                'journal_category_id' => null
            ]);

            // Create or update TodoUser record with journal_id
            TodoUser::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'todo_id' => $todo->id,
                ],
                [
                    'journal_id' => $journal->id,
                    'taken_at' => now(),
                    'completed_at' => null,
                ]
            );
        } elseif ($oldStatus === TodoList::STATUS_IN_PROGRESS && $newStatus === TodoList::STATUS_COMPLETED && $user) {
            // Mark as completed in TodoUser if exists and update journal
            $todoUser = TodoUser::where('user_id', $user->id)
                ->where('todo_id', $todo->id)
                ->first();

            if ($todoUser) {
                $todoUser->markAsCompleted();

                // Update journal end time and status to completed
                if ($todoUser->journal) {
                    $todoUser->journal->update([
                        'end' => now(),
                        'status' => 'completed'
                    ]);
                }
            }
        } elseif ($oldStatus === TodoList::STATUS_IN_PROGRESS && $newStatus === TodoList::STATUS_ASSIGNED && $user) {
            // Cancel work: delete TodoUser record and associated journal
            $todoUser = TodoUser::where('user_id', $user->id)
                ->where('todo_id', $todo->id)
                ->first();

            if ($todoUser) {
                // Delete the associated journal if it exists
                if ($todoUser->journal) {
                    $todoUser->journal->delete();
                }

                // Delete the TodoUser record
                $todoUser->delete();
            }
        }

        //update status semua assignment, jika status == 'completed' isi juga completed_at jika tidak kosongkan
        if ($todo->status === TodoList::STATUS_COMPLETED) {
            $todo->assignments()->update([
                'status' => $todo->status,
                'completed_at' => now()
            ]);
        } else {
            $todo->assignments()->update([
                'status' => $todo->status,
                'completed_at' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $todo,
            'status' => $todo->status
        ]);
    }
}
