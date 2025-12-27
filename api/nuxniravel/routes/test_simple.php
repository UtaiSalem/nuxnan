<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Route::post('/test-login-simple', function (Request $request) {
    $loginInput = $request->input('login', '0938403000');
    $password = $request->input('password', 'password');
    
    // Step 1: Find user
    $user = User::where('email', $loginInput)
        ->orWhere('phone_number', $loginInput)
        ->orWhere('personal_code', $loginInput)
        ->orWhere('name', $loginInput)
        ->first();
    
    $result = [
        'input' => $loginInput,
        'user_found' => $user ? true : false,
    ];
    
    if ($user) {
        $result['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'personal_code' => $user->personal_code,
            'has_password' => !empty($user->password),
            'password_hash' => substr($user->password, 0, 20) . '...',
        ];
        
        // Step 2: Check password
        $passwordMatch = Hash::check($password, $user->password);
        $result['password_check'] = [
            'input_password' => $password,
            'matches' => $passwordMatch,
        ];
        
        // Step 3: Try to generate token
        if ($passwordMatch) {
            try {
                $token = \Auth::guard('api')->login($user);
                $result['token_generation'] = [
                    'success' => $token ? true : false,
                    'token' => $token ? substr($token, 0, 30) . '...' : null,
                ];
                
                if ($token) {
                    \Auth::guard('api')->logout();
                }
            } catch (\Exception $e) {
                $result['token_generation'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }
    
    return response()->json($result, 200);
});
