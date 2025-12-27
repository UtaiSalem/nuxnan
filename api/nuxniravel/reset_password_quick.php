<?php

echo "=== Quick Password Reset ===\n\n";

$email = 'utaisalem@gmail.com';
$newPassword = 'password123';

$user = \App\Models\User::where('email', $email)->first();

if ($user) {
    $user->password = bcrypt($newPassword);
    $user->save();
    
    echo "✅ Password updated successfully!\n\n";
    echo "User: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "New Password: {$newPassword}\n\n";
    echo "You can now login with:\n";
    echo "- Email: {$user->email}\n";
    echo "- Phone: {$user->phone_number}\n";
    echo "- Personal Code: {$user->personal_code}\n";
    echo "- Username: {$user->name}\n";
    echo "\nPassword: {$newPassword}\n";
} else {
    echo "❌ User not found\n";
}
