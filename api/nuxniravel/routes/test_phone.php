<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/**
 * Test endpoint for phone number login
 * 
 * Usage: POST /api/test-phone-login
 * Body: { "phone": "0938403000", "password": "your_password" }
 */
Route::post('/test-phone-login', function (Request $request) {
    $phone = $request->input('phone', '0938403000');
    $password = $request->input('password', 'password');
    
    $result = [
        'step1_input' => [
            'phone' => $phone,
            'password_provided' => !empty($password),
        ],
        'step2_pattern_check' => [
            'pattern' => '/^0\d{9}$/',
            'matches' => preg_match('/^0\d{9}$/', $phone) ? true : false,
            'detected_as' => 'phone_number',
        ],
        'step3_user_lookup' => [],
        'step4_authentication' => [],
    ];
    
    // Find user
    $user = \App\Models\User::where('phone_number', $phone)->first();
    
    if ($user) {
        $result['step3_user_lookup'] = [
            'found' => true,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'has_password' => !empty($user->password),
        ];
        
        // Try authentication
        try {
            $token = \Auth::guard('api')->attempt([
                'phone_number' => $phone,
                'password' => $password
            ]);
            
            if ($token) {
                $result['step4_authentication'] = [
                    'success' => true,
                    'message' => 'Authentication successful!',
                    'token' => substr($token, 0, 20) . '...',
                ];
                \Auth::guard('api')->logout();
            } else {
                $result['step4_authentication'] = [
                    'success' => false,
                    'message' => 'Wrong password',
                    'hint' => 'The password you provided does not match',
                ];
            }
        } catch (\Exception $e) {
            $result['step4_authentication'] = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    } else {
        $result['step3_user_lookup'] = [
            'found' => false,
            'message' => 'No user found with this phone number',
        ];
        
        // Show all users with phone numbers
        $allUsers = \App\Models\User::whereNotNull('phone_number')
            ->get(['id', 'name', 'phone_number'])
            ->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'phone' => $u->phone_number,
                ];
            });
        
        $result['available_phones'] = $allUsers;
    }
    
    return response()->json($result, 200);
});
