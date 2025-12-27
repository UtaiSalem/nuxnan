<?php

use App\Models\VisitorCounter;
use App\Models\User;
use App\Models\Donate;
use App\Http\Resources\Earn\DonateResource;
use App\Http\Resources\UserResource;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Checking VisitorCounter...\n";
    $vc = VisitorCounter::first();
    if ($vc) {
        echo "Found VisitorCounter. ID: " . $vc->id . "\n";
        // Check attributes
        print_r($vc->toArray());
    } else {
        echo "No VisitorCounter found.\n";
    }

    echo "\nChecking Users...\n";
    $userCount = User::count();
    echo "User Count: $userCount\n";

    echo "\nChecking Donates...\n";
    $donates = Donate::whereNotIn('status', [2])->orderBy('remaining_points', 'DESC')->latest()->take(1)->get();
    echo "Found " . $donates->count() . " donates.\n";
    if ($donates->count() > 0) {
        $d = $donates->first();
        echo "Donate ID: " . $d->id . "\n";
        // Try resource
        $resource = new DonateResource($d);
        echo "Resource created successfully.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
