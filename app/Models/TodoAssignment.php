<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TodoAssignment extends Model
{
    protected $fillable = [
        'todo_id',
        'assignable_type',
        'assignable_id',
        'assigned_by',
        'assigned_at',
        'completed_at',
        'status'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    //append
    protected $appends = ['tipe'];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DECLINED = 'declined';

    public function todo(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }

    public function markAsInProgress(): bool
    {
        return $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'completed_at' => null
        ]);
    }

    public function markAsDeclined(): bool
    {
        return $this->update([
            'status' => self::STATUS_DECLINED,
            'completed_at' => null
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getAssignableName(): string
    {
        if ($this->assignable_type === 'App\Models\User') {
            return $this->assignable->name ?? 'Unknown User';
        }

        if ($this->assignable_type === 'Spatie\Permission\Models\Role') {
            return $this->assignable->name ?? 'Unknown Role';
        }

        return 'Unknown';
    }

    public function getAssignableTypeLabel(): string
    {
        return match ($this->assignable_type) {
            'App\Models\User' => 'User',
            'Spatie\Permission\Models\Role' => 'Role',
            default => 'Unknown'
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DECLINED => 'Declined',
            default => 'Unknown'
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'orange',
            self::STATUS_ASSIGNED => 'gray',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_DECLINED => 'red',
            default => 'gray'
        };
    }

    //attribute
    public function getTipeAttribute(): string
    {
        return $this->getAssignableTypeLabel();
    }
}
