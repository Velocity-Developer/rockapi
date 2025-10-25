<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

class TodoList extends Model
{
    protected $fillable = [
        'title',
        'description',
        'created_by',
        'status',
        'priority',
        'due_date',
        'category_id',
        'is_private',
        'notes'
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'is_private' => 'boolean',
        'completed_at' => 'datetime:Y-m-d H:i:s'
    ];

    //appends due_date_days_left
    protected $appends = ['due_date_days_left', 'is_overdue'];

    // Status constants
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DECLINED = 'declined';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TodoCategory::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TodoAssignment::class, 'todo_id');
    }

    public function userAssignments(): HasMany
    {
        return $this->assignments()->where('assignable_type', 'App\Models\User');
    }

    public function roleAssignments(): HasMany
    {
        return $this->assignments()->where('assignable_type', 'Spatie\Permission\Models\Role');
    }

    public function assignedUsers()
    {
        $userIds = $this->userAssignments()->pluck('assignable_id');
        return User::whereIn('id', $userIds)->get();
    }

    public function assignedRoles()
    {
        $roleIds = $this->roleAssignments()->pluck('assignable_id');
        return \Spatie\Permission\Models\Role::whereIn('id', $roleIds)->get();
    }

    public function getAllAssignedUsers()
    {
        $users = $this->assignedUsers();

        // Add users from role assignments
        foreach ($this->assignedRoles() as $role) {
            $users = $users->merge($role->users);
        }

        return $users->unique('id');
    }

    public function isAssignedToUser(User $user): bool
    {
        // Check direct assignment
        if ($this->assignments()->where('assignable_type', 'App\Models\User')->where('assignable_id', $user->id)->exists()) {
            return true;
        }

        // Check role assignment
        $userRoleIds = $user->roles->pluck('id');
        return $this->assignments()->where('assignable_type', 'Spatie\Permission\Models\Role')->whereIn('assignable_id', $userRoleIds)->exists();
    }

    public function getAssignmentStatusForUser(User $user): ?string
    {
        // Check direct assignment status
        $directAssignment = $this->assignments()
            ->where('assignable_type', 'App\Models\User')
            ->where('assignable_id', $user->id)
            ->first();

        if ($directAssignment) {
            return $directAssignment->status;
        }

        // If assigned via role, return default assigned status
        $userRoleIds = $user->roles->pluck('id');
        $roleAssignment = $this->assignments()
            ->where('assignable_type', 'Spatie\Permission\Models\Role')
            ->whereIn('assignable_id', $userRoleIds)
            ->first();

        return $roleAssignment ? self::STATUS_ASSIGNED : null;
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('assignments', function ($q) use ($user) {
            $q->where(function ($subQuery) use ($user) {
                $subQuery->where('assignable_type', 'App\Models\User')
                    ->where('assignable_id', $user->id);
            })->orWhere(function ($subQuery) use ($user) {
                $userRoleIds = $user->roles->pluck('id');
                $subQuery->where('assignable_type', 'Spatie\Permission\Models\Role')
                    ->whereIn('assignable_id', $userRoleIds);
            });
        });
    }

    public function scopeCreatedBy($query, User $user)
    {
        return $query->where('created_by', $user->id);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function getCompletionPercentage(): int
    {
        $totalAssignments = $this->assignments()->count();
        if ($totalAssignments === 0) {
            return 0;
        }

        $completedAssignments = $this->assignments()
            ->where('status', self::STATUS_COMPLETED)
            ->count();

        return round(($completedAssignments / $totalAssignments) * 100);
    }

    //attribute due_date_days_left
    public function getDueDateDaysLeftAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        $dueDate = Carbon::parse($this->due_date);
        $now = now();

        // hasil positif jika due_date di masa depan, negatif jika sudah lewat
        return $now->diffInDays($dueDate, false);
    }

    //attribute is_overdue
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date_days_left < 0;
    }
}
