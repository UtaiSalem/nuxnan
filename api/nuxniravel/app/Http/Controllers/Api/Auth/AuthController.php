<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\UserResource;

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
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Generate JWT token
        $token = auth('api')->login($user);

        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Server Error',
                'message' => 'Failed to generate token'
            ], 500);
        }

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

            // Validate reference code: must be '11111111' (admin) or existing personal_code
            if ($request->reference_code !== '11111111') {
                $referrerExists = User::where('personal_code', $request->reference_code)->exists();
                if (!$referrerExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid referral code. Please check and try again.'
                    ], 422);
                }
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'personal_code' => User::generateReferralCode(),
                'reference_code' => $request->reference_code,
                'no_of_ref' => 0,
                'pp' => 0,
                'wallet' => 0,
                'verified' => false,
            ]);

            // Update referrer's reference count (skip for admin code '11111111')
            if ($request->reference_code !== '11111111') {
                $referrer = User::where('personal_code', $request->reference_code)->first();
                if ($referrer) {
                    $referrer->increment('no_of_ref');
                }
            }

            // Assign STUDENT role if it exists
            // $studentRole = \App\Models\Role::where('name', 'STUDENT')->first();
            // if ($studentRole) {
            //     $user->assignRole('STUDENT');
            // }

            $token = auth('api')->login($user);

            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
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
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user)
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
            'message' => 'Successfully logged out'
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

        // Admin codes are always valid (both '18' and '11111111')
        if ($referenceCode === '11111111') {
            return response()->json([
                'success' => true,
                'message' => 'Admin referral code verified',
                'is_admin' => true,
            ]);
        }

        // Check if referral code exists
        $referrer = User::where('personal_code', $referenceCode)->first();

        if ($referrer) {
            // Generate avatar URL
            $avatarUrl = $referrer->profile_photo_path 
                ? (filter_var($referrer->profile_photo_path, FILTER_VALIDATE_URL) 
                    ? $referrer->profile_photo_path 
                    : \Storage::url($referrer->profile_photo_path))
                : 'https://ui-avatars.com/api/?name=' . urlencode($referrer->name) . '&color=7F9CF5&background=EBF4FF';

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
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth('api')->user();
        $user->load('roles'); // Load relationships for resource
        
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => new UserResource($user)
        ]);
    }
}
