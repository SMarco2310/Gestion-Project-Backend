<?php

namespace App\Policies;

use App\Models\Tache;
use App\Models\User;

class TachePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A Tache has no user_id column of its own — ownership has to be
     * checked through the Tache's related Projet (Tache belongsTo Projet,
     * Projet belongsTo User). Same logic as the whereHas() pattern we
     * used earlier in TaskController, just expressed as a Policy instead.
     */
    public function view(User $user, Tache $tache): bool
    {
        return $tache->projet()->user_id() === $user->id();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tache $tache): bool
    {
        return $tache->projet->user_id === $user->id;
    }

    public function delete(User $user, Tache $tache): bool
    {
        return $tache->projet()->user_id() === $user->id();
    }

    public function restore(User $user, Tache $tache): bool
    {
        return false;
    }

    public function forceDelete(User $user, Tache $tache): bool
    {
        return $tache->projet()->user_id() === $user->id();
    }
}