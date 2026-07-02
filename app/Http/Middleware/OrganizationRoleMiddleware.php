<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizationRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $organization = $request->route('organization');

        if (!$organization) {
            return response()->json(['message' => 'Organization not found in route.'], 400);
        }

        // If the route binding provides an ID instead of a model, extract it
        $organizationId = is_object($organization) ? $organization->id : $organization;

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Find the user's role in this specific organization
        $orgUser = $user->organizations()->where('organizations.id', $organizationId)->first();

        if (!$orgUser) {
            return response()->json(['message' => 'You are not a member of this organization.'], 403);
        }
        

        $userRole = $orgUser->pivot->role;

        // If specific roles are required, check if the user has one of them
        if (!empty($roles) && !in_array($userRole, $roles)) {
            return response()->json(['message' => 'You do not have the required permissions in this organization.'], 403);
        }

        return $next($request);
    }
}
