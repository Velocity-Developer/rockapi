<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodoUser extends Model
{
    protected $fillable = [
        'user_id',
        'todo_id',
        'journal_id',
        'taken_at',
        'completed_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNotNull('taken_at')->whereNull('completed_at');
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('taken_at');
    }

    public function markAsTaken(): void
    {
        $this->update(['taken_at' => now()]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function isPending(): bool
    {
        return !is_null($this->taken_at) && is_null($this->completed_at);
    }
}
