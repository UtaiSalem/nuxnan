<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\AcademyMember;
use App\Models\Academy;

$total = Student::count();
$withAcademy = Student::whereNotNull('academy_id')->count();
$withAcademy1 = Student::where('academy_id', 1)->count();
$noAcademy = Student::whereNull('academy_id')->count();
$hasMember = AcademyMember::whereNotNull('student_id')->count();
$noMember = Student::whereDoesntHave('academyMember')->count();
$academy = Academy::find(1);

// Check academy_members distribution
$membersByAcademy = AcademyMember::whereNotNull('student_id')
    ->selectRaw('academy_id, count(*) as cnt')
    ->groupBy('academy_id')
    ->pluck('cnt', 'academy_id')
    ->toArray();

echo "=== Student-Academy Status ===\n";
echo "Total students: {$total}\n";
echo "With academy_id: {$withAcademy}\n";
echo "academy_id = 1: {$withAcademy1}\n";
echo "No academy_id: {$noAcademy}\n";
echo "Has academy_member record: {$hasMember}\n";
echo "No academy_member record: {$noMember}\n";
echo "Academy #1 exists: " . ($academy ? 'YES' : 'NO') . "\n";
echo "academy_members by academy: " . json_encode($membersByAcademy) . "\n";

// Check academies count
echo "Total academies: " . Academy::count() . "\n";
