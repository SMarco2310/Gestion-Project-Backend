<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(Request $request, $id, $hash)
    {
        try {
            $user = User::findOrFail($id);

            if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification link.'
                ], 403);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email already verified.'
                ], 200);
            }

            if ($user->markEmailAsVerified()) {
                event(new \Illuminate\Auth\Events\Verified($user));
            }

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully.'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify email.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email is already verified.'
                ], 400);
            }

            $request->user()->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Verification link sent.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Resend verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification email.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
