<?php

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;

class ProjetPolicy
{
    private function hasAccess(User $user, Projet $projet): bool
    {
        if ($projet->user_id === $user->id) {
            return true;
        }

        if ($projet->teams()->whereHas('members', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->exists()) {
            return true;
        }

        if ($projet->users()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($projet->organization_id && $user->organizations()->where('organizations.id', $projet->organization_id)->exists()) {
            return true;
        }

        if ($projet->workspace_id && $user->workspaces()->where('workspaces.id', $projet->workspace_id)->exists()) {
            return true;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Projet $projet): bool
    {
        return $this->hasAccess($user, $projet);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Projet $projet): bool
    {
        return $this->hasAccess($user, $projet);
    }

    public function delete(User $user, Projet $projet): bool
    {
        return $projet->user_id === $user->id; // Only owner can delete
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