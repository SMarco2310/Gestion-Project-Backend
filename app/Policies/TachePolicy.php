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
        return $user->can('view', $tache->projet);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tache $tache): bool
    {
        return $user->can('update', $tache->projet);
    }

    public function delete(User $user, Tache $tache): bool
    {
        return $user->can('update', $tache->projet);
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