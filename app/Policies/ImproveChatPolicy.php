<?php

namespace App\Policies;

use App\Models\ImproveChat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImproveChatPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImproveChat $ImproveChat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImproveChat $ImproveChat): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $ImproveChat->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImproveChat $ImproveChat): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $ImproveChat->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ImproveChat $ImproveChat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ImproveChat $ImproveChat): bool
    {
        return false;
    }
}
