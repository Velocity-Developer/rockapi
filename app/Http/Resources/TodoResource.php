<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'category_id' => $this->category_id,
            'is_private' => $this->is_private,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Status and priority information
            'status_label' => $this->getStatusLabel(),
            'priority_label' => $this->getPriorityLabel(),
            'priority_color' => $this->getPriorityColor(),
            'is_overdue' => $this->due_date && $this->due_date->isPast() && $this->status !== 'completed',

            // Completion information
            'completion_percentage' => $this->getCompletionPercentage(),
            'total_assignments' => $this->when(isset($this->assignments_count), $this->assignments_count),
            'completed_assignments' => $this->when(isset($this->completed_assignments_count), $this->completed_assignments_count),

            // Dates formatting
            'formatted_created_at' => $this->created_at->format('Y-m-d H:i'),
            'formatted_updated_at' => $this->updated_at->format('Y-m-d H:i'),
            'formatted_due_date' => $this->when($this->due_date, fn () => $this->due_date->format('Y-m-d')),
            'due_date_days_left' => $this->when($this->due_date, function () {
                if ($this->due_date->isFuture()) {
                    return $this->due_date->diffInDays(now());
                }

                return $this->due_date->isPast() ? -1 : 0;
            }),

            // Creator information
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'avatar' => $this->creator->avatar,
                ];
            }),

            // Category information
            'category' => $this->when($this->relationLoaded('category'), function () {
                return $this->category ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'color' => $this->category->color,
                    'icon' => $this->category->icon,
                ] : null;
            }),

            // Assignments information
            'assignments' => $this->when($this->relationLoaded('assignments'), function () {
                return TodoAssignmentResource::collection($this->assignments);
            }),

            'assignments_summary' => $this->when($this->relationLoaded('assignments'), function () {
                $assignments = $this->assignments;
                $users = $assignments->where('assignable_type', 'App\Models\User');
                $roles = $assignments->where('assignable_type', 'Spatie\Permission\Models\Role');

                return [
                    'total_count' => $assignments->count(),
                    'user_count' => $users->count(),
                    'role_count' => $roles->count(),
                    'completed_count' => $assignments->where('status', 'completed')->count(),
                    'in_progress_count' => $assignments->where('status', 'in_progress')->count(),
                    'assigned_count' => $assignments->where('status', 'assigned')->count(),
                    'declined_count' => $assignments->where('status', 'declined')->count(),
                    'users' => $users->map(function ($assignment) {
                        return [
                            'id' => $assignment->assignable->id,
                            'name' => $assignment->assignable->name,
                            'avatar' => $assignment->assignable->avatar,
                            'status' => $assignment->status,
                            'status_label' => $assignment->getStatusLabel(),
                        ];
                    }),
                    'roles' => $roles->map(function ($assignment) {
                        return [
                            'id' => $assignment->assignable->id,
                            'name' => $assignment->assignable->name,
                            'status' => $assignment->status,
                            'status_label' => $assignment->getStatusLabel(),
                            'user_count' => $assignment->assignable->users_count ?? 0,
                        ];
                    }),
                ];
            }),

            // Permissions for current user
            'can' => $this->when($user = $request->user(), function () use ($user) {
                return [
                    'update' => $this->created_by === $user->id,
                    'delete' => $this->created_by === $user->id,
                    'manage_assignments' => $this->created_by === $user->id,
                    'update_status' => $this->created_by === $user->id || $this->isAssignedToUser($user),
                ];
            }),
        ];
    }

    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'declined' => 'Declined',
            default => 'Unknown'
        };
    }

    private function getPriorityLabel(): string
    {
        return match ($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Medium'
        };
    }

    private function getPriorityColor(): string
    {
        return match ($this->priority) {
            'low' => '#6b7280',     // gray
            'medium' => '#3b82f6',  // blue
            'high' => '#f59e0b',    // amber
            'urgent' => '#ef4444',  // red
            default => '#6b7280'     // gray
        };
    }
}
