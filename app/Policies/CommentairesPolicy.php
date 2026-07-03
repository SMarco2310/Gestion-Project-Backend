<?php

namespace App\Policies;

use App\Models\Commentaires;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentairesPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Commentaires $commentaires): bool
    {
        return $commentaires->user_id === $user->id;

    }

    public function delete(User $user, Commentaires $commentaires): bool
    {
        return $commentaires->user_id === $user->id;
    }

    public function restore(User $user, Commentaires $commentaires): bool
    {
        return false;
    }

    public function forceDelete(User $user, Commentaires $commentaires): bool
    {
        return $commentaires->user_id === $user->id;
    }
}
