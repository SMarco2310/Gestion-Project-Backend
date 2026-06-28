<?php

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;

class ProjetPolicy
{
    /**
     * viewAny -> controls index() (the list endpoint).
     * Any logged-in user can see A list of their own projects —
     * the controller itself (->projets()) already scopes it to theirs,
     * so there's no extra restriction needed here.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * view -> controls show().
     * Only allow it if THIS project actually belongs to THIS user.
     */
    public function view(User $user, Projet $projet): bool
    {
        return $projet->user_id === $user->id;
    }

    /**
     * create -> controls store().
     * Any logged-in user can create a project — ownership doesn't apply
     * yet, since the project doesn't exist until after this check passes.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * update -> controls update().
     */
    public function update(User $user, Projet $projet): bool
    {
        // return $projet->user_id === $user->id;
        return true;
    }

    /**
     * delete -> controls destroy().
     */
    public function delete(User $user, Projet $projet): bool
    {
        return $projet->user_id === $user->id;
    }

    public function restore(User $user, Projet $projet): bool
    {
        return $projet->user_id === $user->id;
    }

    public function forceDelete(User $user, Projet $projet): bool
    {
        return $projet->user_id === $user->id;
    }
}