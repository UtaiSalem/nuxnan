<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check academic_years table
if (Schema::hasTable('academic_years')) {
    echo "=== academic_years ===\n";
    $years = DB::table('academic_years')->get();
    foreach ($years as $y) {
        echo json_encode($y) . "\n";
    }
} else {
    echo "academic_years table NOT FOUND\n";
    // Check for semesters
    if (Schema::hasTable('semesters')) {
        echo "\n=== semesters ===\n";
        $cols = Schema::getColumnListing('semesters');
        echo "Columns: " . implode(', ', $cols) . "\n";
        $sems = DB::table('semesters')->get();
        foreach ($sems as $s) {
            echo json_encode($s) . "\n";
        }
    }
}

// Check classrooms academic_year field
echo "\n=== classrooms academic_year/academic_year_id ===\n";
$sample = DB::table('classrooms')->select('id', 'name', 'academic_year', 'academic_year_id')->limit(5)->get();
foreach ($sample as $c) {
    echo "id={$c->id} name={$c->name} year={$c->academic_year} year_id={$c->academic_year_id}\n";
}

// Check student enrollment_date distribution
echo "\n=== students enrollment_date ===\n";
$dates = DB::table('students')
    ->selectRaw('enrollment_date, count(*) as cnt')
    ->groupBy('enrollment_date')
    ->orderBy('enrollment_date')
    ->get();
foreach ($dates as $d) {
    echo "date={$d->enrollment_date} count={$d->cnt}\n";
}
