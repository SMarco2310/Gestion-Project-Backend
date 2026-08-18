<?php

namespace App\Http\Middleware;

use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeature
{
    public function __construct(protected FeatureGate $gate)
    {
    }

    public function handle(Request $request, Closure $next, string $featureCode): Response
    {
        $organization = $request->route('organization');
        $organization = is_object($organization) ? $organization : \App\Models\Organization::find($organization);

        if (! $organization) {
            return response()->json(['message' => 'Organization not found in route.'], 400);
        }

        if (! $this->gate->has($organization, $featureCode)) {
            return response()->json([
                'success' => false,
                'upgrade_required' => true,
                'feature' => $featureCode,
                'message' => 'This feature requires a plan upgrade.',
            ], 403);
        }

        return $next($request);
    }
}
