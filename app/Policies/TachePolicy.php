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

    public function view(User $user, Tache $tache): bool
    {
        if ($tache->assignee_id === $user->id) {
            return true;
        }
        if ($tache->projet) {
            return $user->can('view', $tache->projet);
        }
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tache $tache): bool
    {
        if ($tache->assignee_id === $user->id) {
            return true;
        }
        if ($tache->projet) {
            return $user->can('update', $tache->projet);
        }
        return true;
    }

    public function delete(User $user, Tache $tache): bool
    {
        if ($tache->assignee_id === $user->id) {
            return true;
        }
        if ($tache->projet) {
            return $user->can('update', $tache->projet);
        }
        return true;
    }

    public function restore(User $user, Tache $tache): bool
    {
        return false;
    }

    public function forceDelete(User $user, Tache $tache): bool
    {
        return $user->can('delete', $tache->projet);
    }
}