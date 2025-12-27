<?php

/**
 * Fix existing OAuth users with spaces in their usernames
 * Run this script once to clean up existing data
 */

use App\Models\User;

// Find all users with spaces in their name
$usersWithSpaces = User::where('name', 'LIKE', '% %')->get();

echo "Found " . $usersWithSpaces->count() . " users with spaces in their username\n\n";

foreach ($usersWithSpaces as $user) {
    $oldName = $user->name;
    
    // Generate new username from email
    if ($user->email) {
        $newUsername = strtolower(explode('@', $user->email)[0]);
    } else {
        // Fallback: remove spaces and convert to lowercase
        $newUsername = strtolower(str_replace(' ', '', $user->name));
    }
    
    // Ensure uniqueness
    $baseUsername = $newUsername;
    $counter = 1;
    while (User::where('name', $newUsername)->where('id', '!=', $user->id)->exists()) {
        $newUsername = $baseUsername . $counter;
        $counter++;
    }
    
    // Update user
    $user->name = $newUsername;
    $user->save();
    
    echo "Updated user ID {$user->id}: '{$oldName}' -> '{$newUsername}'\n";
}

echo "\nDone! Updated " . $usersWithSpaces->count() . " users.\n";
