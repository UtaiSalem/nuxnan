<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Auth Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
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
                $user = User::create([
                    'email' => $googleUser->email,
                    'username' => $this->generateUniqueUsername($googleUser->name),
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => null, // No password for social login
                    'verified' => true,
                    'email_verified_at' => now(),
                    'referral_code' => User::generatePersonalCode(),
                    'reference_code' => User::generateReferenceCode(),
                ]);

                // Create profile
                $user->profile()->create([
                    'username' => $this->generateUniqueUsername($googleUser->name),
                    'display_name' => $googleUser->name,
                    'profile_image_url' => $googleUser->avatar,
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
     *
     * @param string $name
     * @return string
     */
    protected function generateUniqueUsername(string $name): string
    {
        $username = Str::slug($name);
        $originalUsername = $username;
        $count = 1;

        while (User::whereHas('profile', function ($query) use ($username) {
            $query->where('username', $username);
        })->exists()) {
            $username = "{$originalUsername}{$count}";
            $count++;
        }

        return $username;
    }
}
