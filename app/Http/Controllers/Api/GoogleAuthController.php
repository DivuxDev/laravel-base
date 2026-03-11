<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Handle the Google OAuth callback.
     *
     * Finds or creates a user, generates a Sanctum token,
     * and redirects the frontend with the token in the query string.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

            return redirect()->away("{$frontendUrl}/auth/callback?error=google_auth_failed");
        }

        // Find existing user by google_id or email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Update google_id and avatar if not already set
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar() ?? $user->avatar,
            ]);
        } else {
            // Create a new user
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password'          => null,
            ]);
        }

        // Revoke previous tokens and issue a fresh one
        $user->tokens()->delete();
        $token = $user->createToken('google_auth_token')->plainTextToken;

        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        return redirect()->away("{$frontendUrl}/auth/callback?token={$token}");
    }
}
