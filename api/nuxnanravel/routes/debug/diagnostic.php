<?php

/**
 * Login Diagnostic Tool
 * 
 * This script helps diagnose login issues by testing different scenarios
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
// Removed unused import

Route::post('/test-login-diagnostic', function (Request $request) {
    $loginInput = $request->input('login');
    $password = $request->input('password', 'test');
    
    // Determine field type
    $loginField = 'name'; // default
    
    if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
        $loginField = 'email';
    } elseif (preg_match('/^0\d{9}$/', $loginInput)) {
        $loginField = 'phone_number';
    } elseif (preg_match('/^\d{8}$/', $loginInput)) {
        $loginField = 'personal_code';
    }
    
    // Find user
    $user = \App\Models\User::where($loginField, $loginInput)->first();
    
    $response = [
        'input' => $loginInput,
        'detected_field' => $loginField,
        'user_found' => $user ? true : false,
    ];
    
    if ($user) {
        $response['user_data'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'personal_code' => $user->personal_code,
            'has_password' => !empty($user->password),
        ];
        
        // Try authentication
        $token = \Auth::guard('api')->attempt([
            $loginField => $loginInput,
            'password' => $password
        ]);
        
        $response['auth_success'] = $token ? true : false;
        if ($token) {
            $response['token'] = $token;
            \Auth::guard('api')->logout();
        }
    }
    
    return response()->json($response, 200);
});
