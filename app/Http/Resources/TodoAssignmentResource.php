<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoAssignmentResource extends JsonResource
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
            'todo_id' => $this->todo_id,
            'assignable_type' => $this->assignable_type,
            'assignable_id' => $this->assignable_id,
            'assigned_by' => $this->assigned_by,
            'assigned_at' => $this->assigned_at,
            'completed_at' => $this->completed_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Status information
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'is_completed' => $this->isCompleted(),
            'is_in_progress' => $this->isInProgress(),
            'is_assigned' => $this->isAssigned(),
            'is_declined' => $this->isDeclined(),

            // Assignable information
            'assignable' => $this->when($this->relationLoaded('assignable'), function () {
                if ($this->assignable_type === 'App\Models\User') {
                    return [
                        'id' => $this->assignable->id,
                        'name' => $this->assignable->name,
                        'avatar' => $this->assignable->avatar,
                        'type' => 'user',
                    ];
                } elseif ($this->assignable_type === 'Spatie\Permission\Models\Role') {
                    return [
                        'id' => $this->assignable->id,
                        'name' => $this->assignable->name,
                        'type' => 'role',
                        'user_count' => $this->assignable->users_count ?? 0,
                    ];
                }

                return null;
            }),

            // Assigned by user information
            'assigned_by_user' => $this->when($this->relationLoaded('assignedBy'), function () {
                return [
                    'id' => $this->assignedBy->id,
                    'name' => $this->assignedBy->name,
                    'avatar' => $this->assignedBy->avatar,
                ];
            }),

            // Assignment duration
            'duration_hours' => $this->when($this->completed_at, function () {
                return $this->assigned_at->diffInHours($this->completed_at);
            }),
        ];
    }
}
