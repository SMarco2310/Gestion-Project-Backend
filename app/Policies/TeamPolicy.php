<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $user->can('view', $team->organization);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        if ($user->can('update', $team->organization)) {
            return true;
        }

        // Check if the user is a team_lead
        $teamUser = $team->members()->where('user_id', $user->id)->first();
        return $teamUser && $teamUser->pivot->role === 'team_lead';
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->can('update', $team->organization);
    }
}
