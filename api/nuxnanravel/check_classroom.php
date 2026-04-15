<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('student_academic_info');
echo "=== student_academic_info columns ===\n";
echo implode(', ', $columns) . "\n\n";

$total = DB::table('student_academic_info')->count();
$hasClassroom = DB::table('student_academic_info')->whereNotNull('classroom_id')->where('classroom_id', '>', 0)->count();
$noClassroom = DB::table('student_academic_info')->where(function($q) { $q->whereNull('classroom_id')->orWhere('classroom_id', 0); })->count();

echo "Total records: {$total}\n";
echo "With classroom_id: {$hasClassroom}\n";
echo "Without classroom_id: {$noClassroom}\n\n";

// Check classrooms table
$classroomCount = DB::table('classrooms')->count();
echo "Total classrooms: {$classroomCount}\n";

// Check classroom_students pivot
$pivotCount = DB::table('classroom_students')->count();
echo "classroom_students records: {$pivotCount}\n\n";

// Check if we can match via class_level + class_section
$sampleAcademic = DB::table('student_academic_info')
    ->select('id', 'student_id', 'classroom_id', 'current_class', 'current_grade', 'education_level', 'classroom_full')
    ->limit(10)
    ->get();
echo "=== Sample student_academic_info ===\n";
foreach ($sampleAcademic as $r) {
    echo "id={$r->id} student_id={$r->student_id} classroom_id={$r->classroom_id} current_class={$r->current_class} current_grade={$r->current_grade} edu={$r->education_level} full={$r->classroom_full}\n";
}

// Check classrooms structure
echo "\n=== Sample classrooms ===\n";
$classroomCols = Schema::getColumnListing('classrooms');
echo "Columns: " . implode(', ', $classroomCols) . "\n";
$sampleClassrooms = DB::table('classrooms')->select('id', 'academy_id', 'name', 'grade_level', 'section', 'academic_year')->limit(15)->get();
foreach ($sampleClassrooms as $c) {
    echo "id={$c->id} academy={$c->academy_id} name={$c->name} grade={$c->grade_level} section={$c->section} year={$c->academic_year}\n";
}

// Check students class_level/class_section
echo "\n=== Sample students class info ===\n";
$sampleStudents = DB::table('students')->select('id', 'student_id', 'class_level', 'class_section')->whereNotNull('class_level')->limit(10)->get();
foreach ($sampleStudents as $s) {
    echo "id={$s->id} code={$s->student_id} level={$s->class_level} section={$s->class_section}\n";
}

// How many students have class_level?
$withLevel = DB::table('students')->whereNotNull('class_level')->where('class_level', '!=', '')->count();
echo "\nStudents with class_level: {$withLevel}\n";

// Can we match students to classrooms via grade_level + section?
$matchable = DB::table('students as s')
    ->join('classrooms as c', function($j) {
        $j->on('s.class_level', '=', 'c.grade_level')
          ->on('s.class_section', '=', 'c.section');
    })
    ->count();
echo "Students matchable to classrooms (level+section): {$matchable}\n";

// Unique grade+section combos from students
echo "\n=== Unique class_level/class_section from students ===\n";
$combos = DB::table('students')
    ->whereNotNull('class_level')->where('class_level', '!=', '')
    ->selectRaw('class_level, class_section, count(*) as cnt')
    ->groupBy('class_level', 'class_section')
    ->orderBy('class_level')->orderBy('class_section')
    ->get();
foreach ($combos as $c) {
    echo "level={$c->class_level} section={$c->class_section} count={$c->cnt}\n";
}

// Unique grade+class combos from academic_info
echo "\n=== Unique current_grade/current_class from student_academic_info ===\n";
$combos2 = DB::table('student_academic_info')
    ->selectRaw('current_grade, current_class, classroom_full, education_level, count(*) as cnt')
    ->groupBy('current_grade', 'current_class', 'classroom_full', 'education_level')
    ->orderBy('current_grade')->orderBy('current_class')
    ->get();
foreach ($combos2 as $c) {
    echo "grade={$c->current_grade} class={$c->current_class} full={$c->classroom_full} edu={$c->education_level} count={$c->cnt}\n";
}

// All classrooms with full info
echo "\n=== ALL classrooms ===\n";
$allClassrooms = DB::table('classrooms')->get();
foreach ($allClassrooms as $c) {
    echo "id={$c->id} name={$c->name} grade_level={$c->grade_level} section={$c->section} academy={$c->academy_id}\n";
}
