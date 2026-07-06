<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Google Auth Error: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json(['error' => 'Google authentication failed', 'message' => $e->getMessage()], 401);
        }

        $user = User::where('google_id', $googleUser->id)->first();

        if (! $user) {
            // Check if user exists with same email
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Link Google account to existing user
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
            } else {
                // Create new user
                $generatedUsername = $this->generateUniqueUsername($googleUser->name);
                $user = User::create([
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'username' => $generatedUsername,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => null, // No password for social login
                    'verified' => true,
                    'email_verified_at' => now(),
                    'referral_code' => User::generatePersonalCode(),
                    'reference_code' => User::generateReferenceCode(),
                ]);

                // Create profile with compatible fields
                $user->profile()->create([
                    'first_name' => $googleUser->user['given_name'] ?? null,
                    'last_name' => $googleUser->user['family_name'] ?? null,
                    'profile_picture' => $googleUser->avatar,
                ]);

                // Assign default role
                $this->authService->assignDefaultRole($user); // Assuming this method is public or accessible
            }
        }

        // Login user
        $token = Auth::guard('api')->login($user);

        // Redirect to frontend with token
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        return redirect()->to("{$frontendUrl}/auth/callback?token={$token}");
    }

    /**
     * Generate a unique username from the name.
     */
    protected function generateUniqueUsername(string $name): string
    {
        return User::generateUniqueUsername($name);
    }
}
