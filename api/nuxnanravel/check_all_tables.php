<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// List all student-related tables and their record counts
$tables = [
    'students',
    'student_academic_info',
    'student_addresses',
    'student_contacts',
    'student_documents',
    'student_guardians',
    'student_health_info',
    'student_health_records',
    'student_home_visits',
    'student_cards',
    'classroom_students',
    'academy_members',
    'academy_group_members',
    'gradebook_scores',
    'course_grades',
];

echo "=== Student-Related Tables Status ===\n\n";

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        echo "{$table}: TABLE NOT FOUND\n";
        continue;
    }
    
    $count = DB::table($table)->count();
    $columns = Schema::getColumnListing($table);
    
    $hasStudentId = in_array('student_id', $columns);
    $hasUserId = in_array('user_id', $columns);
    $hasAcademyId = in_array('academy_id', $columns);
    
    $flags = [];
    if ($hasStudentId) $flags[] = 'student_id';
    if ($hasUserId) $flags[] = 'user_id';
    if ($hasAcademyId) $flags[] = 'academy_id';
    
    echo "{$table}: {$count} records | columns: " . implode(', ', $flags) . "\n";
    
    // Check orphaned records (student_id not in students table)
    if ($hasStudentId && $count > 0 && $table !== 'students') {
        $orphaned = DB::table($table)
            ->whereNotNull('student_id')
            ->whereNotExists(function($q) use ($table) {
                $q->select(DB::raw(1))
                  ->from('students')
                  ->whereColumn('students.id', $table . '.student_id');
            })
            ->count();
        if ($orphaned > 0) {
            echo "  -> WARNING: {$orphaned} orphaned records (student_id not in students)\n";
        }
        
        // Check if user_id needs syncing
        if ($hasUserId) {
            $nullUserId = DB::table($table)
                ->whereNotNull('student_id')
                ->where(function($q) { $q->whereNull('user_id')->orWhere('user_id', 0); })
                ->count();
            $canSync = DB::table("{$table} as t")
                ->join('students as s', 's.id', '=', 't.student_id')
                ->where(function($q) { $q->whereNull('t.user_id')->orWhere('t.user_id', 0); })
                ->whereNotNull('s.user_id')
                ->where('s.user_id', '>', 0)
                ->count();
            if ($nullUserId > 0) {
                echo "  -> NEEDS UPDATE: {$nullUserId} records without user_id ({$canSync} can sync from students)\n";
            }
        }
        
        // Check if academy_id needs syncing
        if ($hasAcademyId) {
            $nullAcademyId = DB::table($table)
                ->whereNotNull('student_id')
                ->where(function($q) { $q->whereNull('academy_id')->orWhere('academy_id', 0); })
                ->count();
            $canSyncAcademy = DB::table("{$table} as t")
                ->join('students as s', 's.id', '=', 't.student_id')
                ->where(function($q) { $q->whereNull('t.academy_id')->orWhere('t.academy_id', 0); })
                ->whereNotNull('s.academy_id')
                ->count();
            if ($nullAcademyId > 0) {
                echo "  -> NEEDS UPDATE: {$nullAcademyId} records without academy_id ({$canSyncAcademy} can sync from students)\n";
            }
        }
    }
}
