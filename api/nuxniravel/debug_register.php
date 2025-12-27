<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    echo "Creating User...\n";
    $user = User::create([
        'email' => 'manual_' . time() . '@example.com',
        'name' => 'manual_' . substr(time(), -4),
        'username' => 'manual_' . substr(time(), -4),
        'referral_code' => 'REF' . time(),
        'reference_code' => 'REF_CODE' . time(),
        'password' => Hash::make('password'),
    ]);
    
    echo "User created. ID: " . json_encode($user->id) . "\n";
    
    if (!$user->id) {
        echo "Error: User ID is null/empty!\n";
        exit(1);
    }

    echo "Creating Profile...\n";
    $profile = $user->profile()->create([
        'first_name' => 'Test',
    ]);
    
    echo "Profile created. ID: " . $profile->id . "\n";

} catch (\Exception $e) {
    file_put_contents('error.txt', $e->getMessage());
    echo "Error written to error.txt\n";
}
