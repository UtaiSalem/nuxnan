<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class ProviderAuthController extends Controller
{
    /**
     * Redirect to OAuth provider for authentication
     * RFC 6749 compliant with proper error handling
     */
    public function redirectToProvider($provider)
    {
        try {
            // Store reference code in session for use in callback
            $referenceCode = request('reference_code');
            if ($referenceCode) {
                session(['oauth_reference_code' => $referenceCode]);
            }

            // Enable state parameter for CSRF protection (RFC 6749)
            $state = Str::random(40);
            session(['oauth_state' => $state, 'oauth_provider' => $provider]);

            return Socialite::driver($provider)
                ->with(['state' => $state])
                ->redirect();

        } catch (\Exception $e) {
            Log::error('OAuth redirect error', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return redirect(
                env('FRONTEND_URL', 'http://localhost:3000') .
                '/?error=server_error&error_description=' . urlencode('Failed to initiate authentication')
            );
        }
    }

    /**
     * Handle OAuth callback
     * RFC 6749 compliant with proper error handling
     */
    public function handleGoogleCallback($provider)
    {
        try {
            // Validate state parameter (CSRF protection)
            $state = request('state');
            $sessionState = session('oauth_state');
            $sessionProvider = session('oauth_provider');

            if (!$state || !$sessionState || $state !== $sessionState) {
                Log::warning('OAuth state mismatch detected', [
                    'provider' => $provider,
                    'ip' => request()->ip()
                ]);

                session()->forget(['oauth_state', 'oauth_provider']);

                return redirect(
                    env('FRONTEND_URL', 'http://localhost:3000') .
                    '/?error=invalid_state&error_description=' . urlencode('Security validation failed')
                );
            }

            // Clear state from session
            session()->forget(['oauth_state', 'oauth_provider']);

            // Get user from provider
            $socialUser = Socialite::driver($provider)->user();

            // Find or create user
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'email_verified_at' => now(),
                    'personal_code' => User::generateReferralCode(),
                    'reference_code' => User::generateReferenceCode(),
                    'suggester_code' => session('oauth_reference_code'),
                    'no_of_ref' => 0,
                    'verified' => true,
                ]);
            }

            // Update provider info if not set
            if (!$user->provider) {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }

            // Create JWT token
            $token = auth()->login($user);

            return redirect(
                env('FRONTEND_URL', 'http://localhost:3000') .
                '/oauth/callback?token=' . $token .
                '&reference_code=' . session('oauth_reference_code', '')
            );

        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return redirect(
                env('FRONTEND_URL', 'http://localhost:3000') .
                '/?error=authentication_failed&error_description=' . urlencode($e->getMessage())
            );
        }
    }
}
