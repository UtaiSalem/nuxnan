<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Create a referrer
$referrer = User::create([
    'username' => 'referrer_user_' . time(),
    'email' => 'referrer_' . time() . '@example.com',
    'password' => Hash::make('password'),
    'referral_code' => User::generateReferralCode(),
]);

echo "Referrer created: {$referrer->username}, Code: {$referrer->referral_code}\n";

// 2. Register a new user with the referral code
$data = [
    'username' => 'new_user_' . time(),
    'email' => 'new_user_' . time() . '@example.com',
    'password' => 'password',
    'referral_code' => $referrer->referral_code, // Input referral code
];

$authService = new \App\Services\AuthService();
try {
    $newUser = $authService->register($data);
    echo "New User created: {$newUser->username}\n";
    echo "New User Referrer Code: {$newUser->referrer_code}\n";
    echo "New User Own Referral Code: {$newUser->referral_code}\n";

    if ($newUser->referrer_code === $referrer->referral_code) {
        echo "SUCCESS: Referrer code matches!\n";
    } else {
        echo "FAILURE: Referrer code mismatch!\n";
    }

    if (!empty($newUser->referral_code)) {
        echo "SUCCESS: New user has a referral code!\n";
    } else {
        echo "FAILURE: New user missing referral code!\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
