<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TodoCategory extends Model
{
    protected $fillable = [
        'name',
        'color',
        'icon',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function todos(): HasMany
    {
        return $this->hasMany(TodoList::class, 'category_id');
    }

    public function activeTodos(): HasMany
    {
        return $this->todos()->where('status', '!=', TodoList::STATUS_COMPLETED);
    }

    public function completedTodos(): HasMany
    {
        return $this->todos()->where('status', TodoList::STATUS_COMPLETED);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function getTodoCount(): int
    {
        return $this->todos()->count();
    }

    public function getActiveTodoCount(): int
    {
        return $this->activeTodos()->count();
    }

    public function getCompletedTodoCount(): int
    {
        return $this->completedTodos()->count();
    }
}
