<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class OAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $redirectUrl = Socialite::driver('custom_app')->stateless()->redirect()->getTargetUrl();
        
        $response = response()->json(['url' => $redirectUrl]);

        if ($request->has('invitation_token')) {
            // Store the invitation token in a cookie that expires in 30 minutes
            $response->cookie('invitation_token', $request->invitation_token, 30);
        }

        return $response;
    }

    public function callback(Request $request)
    {
        try {
            $socialUser = Socialite::driver('custom_app')->stateless()->user();
        } catch (\Exception $e) {
            return redirect(config('app.frontend_url') . '/auth/login?error=oauth_failed');
        }

        $email = $socialUser->getEmail();
        $user = User::where('email', $email)->first();

        if ($user) {
            // User exists, check if they have a provider_id linked
            if (!$user->provider_id) {
                // Must link account, we generate a temporary linking token
                $linkToken = \Illuminate\Support\Str::random(40);
                \Illuminate\Support\Facades\Cache::put('link_token_'.$linkToken, [
                    'email' => $email,
                    'provider_id' => $socialUser->getId()
                ], now()->addMinutes(15));
                return redirect(config('app.frontend_url') . '/auth/link-account?token=' . $linkToken);
            }
        } else {
            // Create user
            $user = User::create([
                'email' => $email,
                'first_name' => $socialUser->user['first_name'] ?? $socialUser->getName() ?? 'Unknown',
                'last_name' => $socialUser->user['last_name'] ?? '',
                'phone' => $socialUser->user['phone'] ?? null,
                'provider_id' => $socialUser->getId(),
                'password' => null,
            ]);
        }

        return $this->loginAndHandleInvitation($user, $request);
    }

    public function linkAccount(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string',
        ]);

        $data = \Illuminate\Support\Facades\Cache::get('link_token_'.$request->token);
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Token expired or invalid'], 400);
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid password'], 401);
        }

        $user->update([
            'provider_id' => $data['provider_id']
        ]);

        \Illuminate\Support\Facades\Cache::forget('link_token_'.$request->token);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user
        ]);
    }

    private function loginAndHandleInvitation(User $user, Request $request)
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        // Check for invitation
        $invitationToken = $request->cookie('invitation_token');
        if ($invitationToken) {
            $invitation = Invitation::where('token', $invitationToken)->first();
            if ($invitation && $invitation->status === 'pending' && !$invitation->expires_at->isPast() && $invitation->email === $user->email) {
                // Add to organization
                if (!$user->organizations()->where('organization_id', $invitation->organization_id)->exists()) {
                    $orgRole = $invitation->role === 'admin' ? 'admin' : 'membre';
                    if ($invitation->role === 'owner') $orgRole = 'proprietaire';
                    $user->organizations()->attach($invitation->organization_id, ['role' => $orgRole, 'joined_at' => now()]);
                }
                if ($invitation->team_id && !$user->teams()->where('team_id', $invitation->team_id)->exists()) {
                    $user->teams()->attach($invitation->team_id, ['joined_at' => now()]);
                }
                if ($invitation->projet_id && !$user->projets_collaborated()->where('projet_id', $invitation->projet_id)->exists()) {
                    $user->projets_collaborated()->attach($invitation->projet_id, ['joined_at' => now()]);
                }
                $invitation->update(['status' => 'accepted']);
            }
        }

        // Redirect to frontend with token
        $url = config('app.frontend_url') . '/auth/oauth-callback?token=' . $token;
        return redirect($url)->withoutCookie('invitation_token');
    }
}
