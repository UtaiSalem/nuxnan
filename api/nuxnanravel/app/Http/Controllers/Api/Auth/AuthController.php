<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     * Supports multi-field login: email, phone, username, or member ID
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        $loginInput = $request->login;
        $password = $request->password;

        // Search for user across all possible login fields
        $user = User::where('email', $loginInput)
            ->orWhere('phone_number', $loginInput)
            ->orWhere('personal_code', $loginInput)
            ->orWhere('name', $loginInput)
            ->first();

        // Check if user exists
        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Verify password
        if (! Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Block unverified (pending) users
        if (is_null($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'error' => 'AccountPending',
                'message' => 'บัญชีของคุณยังไม่ได้รับการอนุมัติจากผู้ดูแล กรุณารอการตรวจสอบ',
            ], 403);
        }

        // Generate JWT token
        $token = auth('api')->login($user);

        if (! $token) {
            return response()->json([
                'success' => false,
                'error' => 'Server Error',
                'message' => 'Failed to generate token',
            ], 500);
        }

        // Fire gamification event
        \App\Services\UsageEventService::fire($user, \App\Enums\UsageEventType::LOGIN->value);

        return $this->respondWithToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone_number' => 'nullable|string|max:20',
                'reference_code' => 'required|string',
            ]);

            $user = DB::transaction(function () use ($request) {
                $referrer = null;
                $suggesterCode = $request->reference_code;

                // Validate suggester code: admin fallback is unlimited, normal suggesters are capped.
                if ($suggesterCode !== User::ADMIN_SUGGESTER_CODE) {
                    $referrer = User::where('personal_code', $suggesterCode)->lockForUpdate()->first();

                    if (! $referrer) {
                        throw ValidationException::withMessages([
                            'reference_code' => ['Invalid referral code. Please check and try again.'],
                        ]);
                    }

                    if (! $referrer->canAcceptReferral()) {
                        throw ValidationException::withMessages([
                            'reference_code' => ['Suggester has reached the maximum number of referrals.'],
                        ]);
                    }
                }

                $user = User::create([
                    'name' => $request->name,
                    'username' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'phone_number' => $request->phone_number,
                    'personal_code' => User::generateReferralCode(),
                    'reference_code' => User::generateReferenceCode(),
                    'suggester_code' => $suggesterCode,
                    'no_of_ref' => 0,
                    'pp' => 0,
                    'wallet' => 0,
                    'verified' => false,
                ]);

                if ($referrer) {
                    $referrer->increment('no_of_ref');
                }

                return $user;
            });

            // Assign STUDENT role if it exists
            // $studentRole = \App\Models\Role::where('name', 'STUDENT')->first();
            // if ($studentRole) {
            //     $user->assignRole('STUDENT');
            // }

            $token = auth('api')->login($user);

            return $this->respondWithToken($token);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Registration error: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth('api')->user();
        $user->load('roles'); // Load relationships for resource
        $user->loadCount(['posts', 'followers', 'following', 'userAchievements']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Validate referral code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateReferralCode(Request $request)
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $referenceCode = $request->reference_code;

        // Admin fallback code is always valid and is not capped.
        if ($referenceCode === User::ADMIN_SUGGESTER_CODE) {
            return response()->json([
                'success' => true,
                'message' => 'Admin referral code verified',
                'is_admin' => true,
            ]);
        }

        // Check if referral code exists
        $referrer = User::where('personal_code', $referenceCode)->first();

        if ($referrer) {
            if (! $referrer->canAcceptReferral()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggester has reached the maximum number of referrals.',
                ], 422);
            }

            // Generate avatar URL
            $avatarUrl = $referrer->profile_photo_path
                ? (filter_var($referrer->profile_photo_path, FILTER_VALIDATE_URL)
                    ? $referrer->profile_photo_path
                    : \Storage::url($referrer->profile_photo_path))
                : 'https://ui-avatars.com/api/?name='.urlencode($referrer->name).'&color=7F9CF5&background=EBF4FF';

            return response()->json([
                'success' => true,
                'message' => 'Valid referral code',
                'is_admin' => false,
                'referrer' => [
                    'username' => $referrer->name,
                    'personal_code' => $referrer->personal_code,
                    'avatar' => $avatarUrl,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid referral code. Please check and try again.',
        ], 422);
    }

    /**
     * Get the token array structure.
     *
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth('api')->user();
        $user->load('roles'); // Load relationships for resource
        $user->loadCount(['posts', 'followers', 'following', 'userAchievements']);

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => new UserResource($user),
        ]);
    }
}
