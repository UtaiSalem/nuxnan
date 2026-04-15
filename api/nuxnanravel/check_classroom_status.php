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
    ->filter(fn($t) => str_contains($t, 'classroom'))
    ->values();

echo "=== ALL Classroom Tables Status ===\n\n";
foreach ($allTables as $table) {
    $count = DB::table($table)->count();
    $cols = Schema::getColumnListing($table);
    echo "--- {$table} ({$count} records) ---\n";
    echo "Columns: " . implode(', ', $cols) . "\n";
    
    // Check NULLs for key columns
    $keyCols = ['academy_id', 'student_id', 'user_id', 'classroom_id', 'academic_year_id', 'enrolled_at', 'student_number'];
    foreach ($keyCols as $col) {
        if (in_array($col, $cols)) {
            $nullCount = DB::table($table)->whereNull($col)->count();
            $filledCount = DB::table($table)->whereNotNull($col)->count();
            echo "  {$col}: filled={$filledCount}, null={$nullCount}\n";
        }
    }
    
    if ($count > 0 && $count <= 5) {
        echo "  All records:\n";
        foreach (DB::table($table)->get() as $r) {
            echo "  " . json_encode($r) . "\n";
        }
    } elseif ($count > 0) {
        echo "  Sample (3):\n";
        foreach (DB::table($table)->limit(3)->get() as $r) {
            echo "  " . json_encode($r) . "\n";
        }
    }
    echo "\n";
}

// Cross-check: students with class_level but NOT in classroom_students
$studentsNotInPivot = DB::table('students as s')
    ->whereNotNull('s.class_level')
    ->where('s.class_level', '!=', '')
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))->from('classroom_students as cs')->whereColumn('cs.student_id', 's.id');
    })
    ->count();
echo "=== Cross-checks ===\n";
echo "Students with class but NOT in classroom_students: {$studentsNotInPivot}\n";

// Students in classroom_students but class_level doesn't match classroom
$mismatch = DB::table('classroom_students as cs')
    ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
    ->join('students as s', 's.id', '=', 'cs.student_id')
    ->whereRaw("c.grade_level != CONCAT('ม.', s.class_level)")
    ->count();
echo "Grade mismatch (pivot vs student): {$mismatch}\n";

// Section mismatch
$secMismatch = DB::table('classroom_students as cs')
    ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
    ->join('students as s', 's.id', '=', 'cs.student_id')
    ->whereColumn('c.section', '!=', 's.class_section')
    ->count();
echo "Section mismatch (pivot vs student): {$secMismatch}\n";

// Students without class_level
$noClass = DB::table('students')->where(function($q) { $q->whereNull('class_level')->orWhere('class_level', ''); })->count();
echo "Students without class_level: {$noClass}\n";

// classroom_students orphans (student not in students)
$orphanPivot = DB::table('classroom_students as cs')
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'cs.student_id');
    })
    ->count();
echo "classroom_students orphan records: {$orphanPivot}\n";

// student_academic_info classroom_id consistency
$academicNoClassroom = DB::table('student_academic_info')->whereNull('classroom_id')->count();
$academicWithClassroom = DB::table('student_academic_info')->whereNotNull('classroom_id')->count();
echo "\nstudent_academic_info: with classroom_id={$academicWithClassroom}, without={$academicNoClassroom}\n";

// Verify classroom_id in academic_info matches actual classroom
$academicBadClassroom = DB::table('student_academic_info as sa')
    ->whereNotNull('sa.classroom_id')
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))->from('classrooms as c')->whereColumn('c.id', 'sa.classroom_id');
    })
    ->count();
echo "student_academic_info with invalid classroom_id: {$academicBadClassroom}\n";
