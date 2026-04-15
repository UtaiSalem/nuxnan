<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\AcademyMember;
use Illuminate\Support\Facades\DB;

$total = Student::count();
$withUser = Student::whereNotNull('user_id')->where('user_id', '>', 0)->count();
$noUser = Student::where(function($q) { $q->whereNull('user_id')->orWhere('user_id', 0); })->count();

// Check academy_members that have both student_id and user_id
$membersWithBoth = AcademyMember::whereNotNull('student_id')
    ->whereNotNull('user_id')
    ->where('user_id', '>', 0)
    ->count();

// Students without user_id but have academy_member with user_id
$canFillFromMembers = DB::table('students as s')
    ->join('academy_members as am', 'am.student_id', '=', 's.id')
    ->where(function($q) { $q->whereNull('s.user_id')->orWhere('s.user_id', 0); })
    ->whereNotNull('am.user_id')
    ->where('am.user_id', '>', 0)
    ->count();

// Sample: show some students without user_id
$sampleNoUser = DB::table('students as s')
    ->leftJoin('academy_members as am', 'am.student_id', '=', 's.id')
    ->where(function($q) { $q->whereNull('s.user_id')->orWhere('s.user_id', 0); })
    ->select('s.id', 's.student_id', 's.first_name_th', 's.last_name_th', 's.user_id', 'am.user_id as am_user_id', 'am.member_code')
    ->limit(10)
    ->get();

// Also check: students with user_id that matches a real user
$validUserIds = DB::table('students as s')
    ->join('users as u', 'u.id', '=', 's.user_id')
    ->count();

echo "=== Student User ID Status ===\n";
echo "Total students: {$total}\n";
echo "With user_id: {$withUser}\n";
echo "Without user_id: {$noUser}\n";
echo "Valid user_id (exists in users): {$validUserIds}\n";
echo "Academy members with both student+user: {$membersWithBoth}\n";
echo "Can fill from academy_members: {$canFillFromMembers}\n";
echo "\n=== Sample students without user_id ===\n";
foreach ($sampleNoUser as $s) {
    echo "Student #{$s->id} code={$s->student_id} name={$s->first_name_th} {$s->last_name_th} user_id={$s->user_id} am_user_id={$s->am_user_id}\n";
}

// Also check: how many unique users are in academy_members for student records
$uniqueUsers = AcademyMember::whereNotNull('student_id')
    ->whereNotNull('user_id')
    ->where('user_id', '>', 0)
    ->distinct('user_id')
    ->count('user_id');
echo "\nUnique users in academy_members (student records): {$uniqueUsers}\n";
