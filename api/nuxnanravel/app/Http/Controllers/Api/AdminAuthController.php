<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    /**
     * Admin Login - Only users with admin roles can login
     *
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string', // email, phone, or username
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $loginInput = $request->input('login');
        $password = $request->input('password');

        // Find user by email, phone_number, personal_code, or name
        $user = User::where('email', $loginInput)
            ->orWhere('phone_number', $loginInput)
            ->orWhere('personal_code', $loginInput)
            ->orWhere('name', $loginInput)
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบบัญชีผู้ใช้นี้ในระบบ',
            ], 401);
        }

        // Check if user has admin role
        $adminRoles = ['SUPER_ADMIN', 'ADMIN', 'MODERATOR', 'INSTRUCTOR'];
        if (! $user->hasAnyRole($adminRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เข้าใช้งานระบบ Admin',
            ], 403);
        }

        // Attempt to authenticate
        if (! Auth::guard('api')->attempt(['email' => $user->email, 'password' => $password])) {
            return response()->json([
                'success' => false,
                'message' => 'รหัสผ่านไม่ถูกต้อง',
            ], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions(),
                    'is_super_admin' => $user->isSuperAdmin(),
                    'is_plearnd_admin' => $user->is_plearnd_admin ?? false,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    /**
     * Logout
     *
     * @return JsonResponse
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'ออกจากระบบสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถออกจากระบบได้',
            ], 500);
        }
    }

    /**
     * Refresh token
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถ refresh token ได้',
            ], 401);
        }
    }

    /**
     * Get current admin user info
     *
     * @return JsonResponse
     */
    public function me()
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions(),
                'is_super_admin' => $user->isSuperAdmin(),
                'is_plearnd_admin' => $user->is_plearnd_admin ?? false,
            ],
        ]);
    }
}
