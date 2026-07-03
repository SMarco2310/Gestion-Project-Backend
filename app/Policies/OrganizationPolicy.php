<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    private function getRole(User $user, Organization $organization): ?string
    {
        $orgUser = $user->organizations()->where('organization_id', $organization->id)->first();
        return $orgUser ? $orgUser->pivot->role : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->getRole($user, $organization) !== null;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Organization $organization): bool
    {
        return in_array($this->getRole($user, $organization), ['admin', 'proprietaire']);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->getRole($user, $organization) === 'proprietaire';
    }
}
