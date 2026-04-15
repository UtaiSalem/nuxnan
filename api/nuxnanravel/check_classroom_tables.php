<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Find all classroom-related tables
$allTables = collect(DB::select('SHOW TABLES'))
    ->map(fn($t) => array_values((array)$t)[0])
    ->filter(fn($t) => str_contains($t, 'classroom') || str_contains($t, 'group_member'))
    ->values();

echo "=== Classroom-Related Tables ===\n";
foreach ($allTables as $table) {
    $count = DB::table($table)->count();
    $cols = Schema::getColumnListing($table);
    $flags = [];
    if (in_array('student_id', $cols)) $flags[] = 'student_id';
    if (in_array('user_id', $cols)) $flags[] = 'user_id';
    if (in_array('academy_id', $cols)) $flags[] = 'academy_id';
    if (in_array('classroom_id', $cols)) $flags[] = 'classroom_id';
    if (in_array('member_id', $cols)) $flags[] = 'member_id';
    echo "{$table}: {$count} records | keys: " . implode(', ', $flags) . "\n";
    echo "  columns: " . implode(', ', $cols) . "\n";
}

// classroom_students detail
echo "\n=== classroom_students ===\n";
$csCount = DB::table('classroom_students')->count();
echo "Total: {$csCount}\n";
if ($csCount > 0) {
    $csCols = Schema::getColumnListing('classroom_students');
    $hasAcademyId = in_array('academy_id', $csCols);
    echo "Has academy_id: " . ($hasAcademyId ? 'YES' : 'NO') . "\n";
    
    if ($hasAcademyId) {
        $noAcademy = DB::table('classroom_students')->where(function($q) { $q->whereNull('academy_id')->orWhere('academy_id', 0); })->count();
        echo "Without academy_id: {$noAcademy}\n";
    }
    
    // Check academic_year_id
    if (in_array('academic_year_id', $csCols)) {
        $noYear = DB::table('classroom_students')->whereNull('academic_year_id')->count();
        echo "Without academic_year_id: {$noYear}\n";
    }
    
    $sample = DB::table('classroom_students')->limit(5)->get();
    echo "\nSample records:\n";
    foreach ($sample as $r) {
        echo json_encode($r) . "\n";
    }
}

// classroom_members detail
echo "\n=== classroom_members ===\n";
$cmCount = DB::table('classroom_members')->count();
echo "Total: {$cmCount}\n";
if ($cmCount > 0) {
    $sample = DB::table('classroom_members')->limit(5)->get();
    foreach ($sample as $r) {
        echo json_encode($r) . "\n";
    }
}

// classroom_groups detail
echo "\n=== classroom_groups ===\n";
if (Schema::hasTable('classroom_groups')) {
    $cgCount = DB::table('classroom_groups')->count();
    echo "Total: {$cgCount}\n";
} else {
    echo "TABLE NOT FOUND\n";
}

// classroom_invitations detail
echo "\n=== classroom_invitations ===\n";
if (Schema::hasTable('classroom_invitations')) {
    $ciCount = DB::table('classroom_invitations')->count();
    echo "Total: {$ciCount}\n";
} else {
    echo "TABLE NOT FOUND\n";
}

// classrooms detail - check consistency
echo "\n=== classrooms consistency ===\n";
$classrooms = DB::table('classrooms')->get();
echo "Total classrooms: " . $classrooms->count() . "\n";
foreach ($classrooms as $c) {
    $studentCount = DB::table('classroom_students')->where('classroom_id', $c->id)->count();
    $academicCount = DB::table('student_academic_info')->where('classroom_id', $c->id)->count();
    echo "id={$c->id} name={$c->name} grade={$c->grade_level} section={$c->section} academy={$c->academy_id} | pivot={$studentCount} academic={$academicCount}\n";
}

// Check: students in classroom_students but not matching classroom academy
echo "\n=== Cross-check classroom_students with students ===\n";
$mismatchAcademy = DB::table('classroom_students as cs')
    ->join('students as s', 's.id', '=', 'cs.student_id')
    ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
    ->whereColumn('s.academy_id', '!=', 'c.academy_id')
    ->count();
echo "Academy mismatch (student vs classroom): {$mismatchAcademy}\n";

// Check classroom_students.academy_id status  
$csCols = Schema::getColumnListing('classroom_students');
if (in_array('academy_id', $csCols)) {
    $csNoAcademy = DB::table('classroom_students')->where(function($q) { $q->whereNull('academy_id')->orWhere('academy_id', 0); })->count();
    $csWithAcademy = DB::table('classroom_students')->whereNotNull('academy_id')->where('academy_id', '>', 0)->count();
    echo "classroom_students with academy_id: {$csWithAcademy}\n";
    echo "classroom_students without academy_id: {$csNoAcademy}\n";
}
