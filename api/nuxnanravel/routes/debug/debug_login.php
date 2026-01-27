<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::post('/debug-login', function (Request $request) {
    $loginInput = $request->input('login');
    $password = $request->input('password');
    
    $result = [];
    
    // Step 1: Determine field
    $loginField = 'name';
    if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
        $loginField = 'email';
    } elseif (preg_match('/^0\d{9}$/', $loginInput)) {
        $loginField = 'phone_number';
    } elseif (preg_match('/^\d{8}$/', $loginInput)) {
        $loginField = 'personal_code';
    }
    
    $result['step1_detection'] = [
        'input' => $loginInput,
        'detected_field' => $loginField,
    ];
    
    // Step 2: Find user
    $user = \App\Models\User::where($loginField, $loginInput)->first();
    $result['step2_user_lookup'] = [
        'found' => $user ? true : false,
        'user_id' => $user ? $user->id : null,
        'user_name' => $user ? $user->name : null,
    ];
    
    if (!$user) {
        return response()->json($result, 404);
    }
    
    // Step 3: Try authentication
    try {
        $token = Auth::guard('api')->attempt([
            $loginField => $loginInput,
            'password' => $password
        ]);
        
        $result['step3_authentication'] = [
            'success' => $token ? true : false,
            'token' => $token ? substr($token, 0, 30) . '...' : null,
            'error' => $token ? null : 'Password mismatch or auth failed',
        ];
        
        if ($token) {
            Auth::guard('api')->logout();
        }
    } catch (\Exception $e) {
        $result['step3_authentication'] = [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
    
    return response()->json($result, 200);
});
