<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Include counts if loaded
            'todos_count' => $this->when(isset($this->todos_count), $this->todos_count),
            'active_todos_count' => $this->when(isset($this->active_todos_count), $this->active_todos_count),
            'completed_todos_count' => $this->when(isset($this->completed_todos_count), $this->completed_todos_count),
        ];
    }
}
