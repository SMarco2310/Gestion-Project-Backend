<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    /**
     * Send a password-reset link to the given email address.
     *
     * POST /api/forgot-password
     * Body: { "email": "user@example.com" }
     */
    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            // This will dispatch the ResetPasswordNotification on the User model
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Un lien de réinitialisation a été envoyé à votre adresse e-mail.',
                ], 200);
            }

            // Handles cases like: user not found, throttled, etc.
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error sending reset link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset the user's password using the token from the email.
     *
     * POST /api/reset-password
     * Body: { "token": "...", "email": "...", "password": "...", "password_confirmation": "..." }
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token'    => 'required|string',
                'email'    => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password'       => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Votre mot de passe a été réinitialisé avec succès.',
                ], 200);
            }

            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error resetting password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
