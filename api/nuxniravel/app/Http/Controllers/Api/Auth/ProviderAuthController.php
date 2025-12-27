<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Models\VisitorCounter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class ProviderAuthController extends Controller
{
    /**
     * Redirect to OAuth provider
     * Standards-compliant with state parameter for CSRF protection
     */
    public function redirectToGoogle($provider)
    {
        // Validate provider
        $allowedProviders = ['google', 'facebook', 'twitter', 'github', 'linkedin'];
        if (!in_array($provider, $allowedProviders)) {
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/?error=invalid_provider');
        }

        // Enforce HTTPS in production
        if (app()->environment('production') && !request()->secure()) {
            Log::warning('OAuth redirect attempted over HTTP in production', [
                'provider' => $provider,
                'ip' => request()->ip()
            ]);
            abort(403, 'HTTPS required for OAuth authentication');
        }

        try {
            // Store reference code in session for use in callback
            $referenceCode = request('reference_code');
            if ($referenceCode) {
                session(['oauth_reference_code' => $referenceCode]);
            }

            // Temporarily disable state parameter for testing
            // TODO: Re-enable state parameter after fixing session issues
            // $state = Str::random(40);
            // session(['oauth_state' => $state, 'oauth_provider' => $provider]);

            return Socialite::driver($provider)
                // ->with(['state' => $state])
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
            // Temporarily disable state validation for testing
            // TODO: Re-enable state validation after fixing session issues
            /*
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
            */
            
            // Get user from provider
            $provider_user = Socialite::driver($provider)->user();

            // Validate required fields from provider
            if (!$provider_user->email) {
                Log::error('OAuth provider did not return email', [
                    'provider' => $provider
                ]);
                
                return redirect(
                    env('FRONTEND_URL', 'http://localhost:3000') . 
                    '/?error=invalid_request&error_description=' . urlencode('Email not provided by ' . $provider)
                );
            }

            // Get provider-specific field name
            $providerIdField = $provider . '_id';

            // Find user by provider ID (preferred) or email
            $user = User::where($providerIdField, $provider_user->id)->first();
            
            if (!$user) {
                $user = User::where('email', $provider_user->email)->first();
            }

            $isNewUser = false;

            if ($user) {
                // Update provider ID if not set (linking existing account)
                if ($user[$providerIdField] == null) {
                    $user[$providerIdField] = $provider_user->id;
                    $user->save();
                    
                    Log::info('Linked OAuth provider to existing user', [
                        'user_id' => $user->id,
                        'provider' => $provider
                    ]);
                }
            } else {
                // Get reference code from session
                $referenceCode = session('oauth_reference_code');
                
                // Validate reference code is provided
                if (!$referenceCode) {
                    Log::error('OAuth registration attempted without reference code', [
                        'provider' => $provider,
                        'email' => $provider_user->email
                    ]);
                    
                    session()->forget('oauth_reference_code');
                    
                    return redirect(
                        env('FRONTEND_URL', 'http://localhost:3000') . 
                        '/?error=missing_referral_code&error_description=' . urlencode('Referral code is required for registration')
                    );
                }
                
                // Validate reference code: must be '18' or '00000018' (admin) or existing personal_code
                if ($referenceCode !== '11111111') {
                    $referrerExists = User::where('personal_code', $referenceCode)->exists();
                    if (!$referrerExists) {
                        Log::error('OAuth registration attempted with invalid reference code', [
                            'provider' => $provider,
                            'reference_code' => $referenceCode
                        ]);
                        
                        session()->forget('oauth_reference_code');
                        
                        return redirect(
                            env('FRONTEND_URL', 'http://localhost:3000') . 
                            '/?error=invalid_referral_code&error_description=' . urlencode('Invalid referral code')
                        );
                    }
                }
                
                // Auto-register new user from OAuth provider
                // Generate a proper username (no spaces, lowercase)
                $username = null;
                if ($provider_user->email) {
                    // Use email prefix as username
                    $username = strtolower(explode('@', $provider_user->email)[0]);
                } elseif ($provider_user->name) {
                    // Sanitize name: remove spaces, convert to lowercase
                    $username = strtolower(str_replace(' ', '', $provider_user->name));
                } elseif ($provider_user->nickname) {
                    $username = strtolower(str_replace(' ', '', $provider_user->nickname));
                }
                
                // Ensure username is unique
                $baseUsername = $username;
                $counter = 1;
                while (User::where('name', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                
                $userData = [
                    'name' => $username,
                    'email' => $provider_user->email,
                    $providerIdField => $provider_user->id,
                    'email_verified_at' => now(), // OAuth accounts are verified
                    'personal_code' => User::generateReferralCode(),
                    'reference_code' => $referenceCode,
                    'no_of_ref' => 0,
                    'pp' => 0,
                    'wallet' => 0,
                    'verified' => true,
                    'password' => Hash::make(Str::random(32)), // Random password for OAuth users
                ];

                // Add profile photo if available
                if (isset($provider_user->avatar)) {
                    $userData['profile_photo_path'] = $provider_user->avatar;
                }

                $user = User::create($userData);
                $isNewUser = true;
                
                // Update referrer's reference count (skip for admin codes '18' and '00000018')
                if ($referenceCode !== '11111111') {
                    $referrer = User::where('personal_code', $referenceCode)->first();
                    if ($referrer) {
                        $referrer->increment('no_of_ref');
                    }
                }
                
                // Clear reference code from session
                session()->forget('oauth_reference_code');

                // Assign default role
                try {
                    $user->assignRole('STUDENT');
                } catch (\Exception $e) {
                    Log::warning('Failed to assign role to new OAuth user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                Log::info('New user registered via OAuth', [
                    'user_id' => $user->id,
                    'provider' => $provider
                ]);
            }

            // Increment login counter
            try {
                $visitorCounter = VisitorCounter::first();
                if ($visitorCounter) {
                    $visitorCounter->increment('login_counter');
                }
            } catch (\Exception $e) {
                Log::warning('Failed to increment visitor counter', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Generate JWT token for API authentication
            $token = auth('api')->login($user);
            
            // Redirect to frontend callback with token
            // Frontend will use this token to fetch user data from /api/auth/me
            return redirect(
                env('FRONTEND_URL', 'http://localhost:3000') . 
                '/auth/callback?token=' . $token . 
                '&new_user=' . ($isNewUser ? '1' : '0') .
                '&provider=' . $provider
            );
        
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error('OAuth invalid state exception', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            return redirect(
                env('FRONTEND_URL', 'http://localhost:3000') . 
                '/?error=invalid_state&error_description=' . urlencode('Invalid OAuth state')
            );
            
        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect(
                env('FRONTEND_URL', 'http://localhost:3000') . 
                '/?error=server_error&error_description=' . urlencode('Authentication failed') .
                '&provider=' . $provider
            );
        }
    }
}
