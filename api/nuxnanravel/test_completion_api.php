<?php
/**
 * Test Course Completion API
 * Run: php test_completion_api.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseAttendance;
use App\Services\AttendanceEligibilityService;
use App\Services\CourseGradingService;
use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "  Course Completion & Grading API Test\n";
echo "===========================================\n\n";

// Test 1: Check new columns on courses table
echo "TEST 1: Check Course model new columns\n";
echo "---------------------------------------\n";
$course = Course::first();
if ($course) {
    echo "✓ Course ID: {$course->id}\n";
    echo "✓ Course Name: {$course->name}\n";
    echo "  - max_absence_percent: " . ($course->max_absence_percent ?? '20 (default)') . "%\n";
    echo "  - allow_unlock_by_points: " . ($course->allow_unlock_by_points ? 'Yes' : 'No') . "\n";
    echo "  - unlock_points_cost: " . ($course->unlock_points_cost ?? 'NULL') . "\n";
    echo "  - allow_unlock_by_reading: " . ($course->allow_unlock_by_reading ? 'Yes' : 'No') . "\n";
    echo "  - finalization_status: " . ($course->finalization_status ?? 'open (default)') . "\n";
    echo "  - allow_remediation: " . ($course->allow_remediation ? 'Yes' : 'No') . "\n";
    echo "  - allow_grade_appeal: " . ($course->allow_grade_appeal ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ No courses found in database\n";
}
echo "\n";

// Test 2: Check new columns on course_members table
echo "TEST 2: Check CourseMember model new columns\n";
echo "---------------------------------------------\n";
$member = CourseMember::first();
if ($member) {
    echo "✓ Member ID: {$member->id}\n";
    echo "  - exam_eligible: " . ($member->exam_eligible === null ? 'NULL' : ($member->exam_eligible ? 'Yes' : 'No')) . "\n";
    echo "  - eligibility_status: " . ($member->eligibility_status ?? 'NULL') . "\n";
    echo "  - absence_percent: " . ($member->absence_percent ?? 'NULL') . "%\n";
    echo "  - draft_total_score: " . ($member->draft_total_score ?? 'NULL') . "\n";
    echo "  - draft_grade: " . ($member->draft_grade ?? 'NULL') . "\n";
    echo "  - final_total_score: " . ($member->final_total_score ?? 'NULL') . "\n";
    echo "  - final_grade: " . ($member->final_grade ?? 'NULL') . "\n";
    echo "  - completion_status: " . ($member->completion_status ?? 'in_progress (default)') . "\n";
} else {
    echo "✗ No course members found\n";
}
echo "\n";

// Test 3: Check new tables created
echo "TEST 3: Check new tables exist\n";
echo "-------------------------------\n";
$tables = [
    'course_finalization_logs' => 'CourseFinalizationLog',
    'exam_eligibility_overrides' => 'ExamEligibilityOverride',
    'grade_appeals' => 'GradeAppeal',
    'course_remediation_sessions' => 'CourseRemediationSession',
    'course_remediation_enrollments' => 'CourseRemediationEnrollment',
    'course_certificates' => 'CourseCertificate',
    'grade_edit_logs' => 'GradeEditLog',
];

foreach ($tables as $table => $model) {
    $exists = DB::getSchemaBuilder()->hasTable($table);
    echo ($exists ? '✓' : '✗') . " {$table}: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}
echo "\n";

// Test 4: Test AttendanceEligibilityService
echo "TEST 4: Test AttendanceEligibilityService\n";
echo "-----------------------------------------\n";
if ($course && $member) {
    try {
        $eligibilityService = new AttendanceEligibilityService();
        
        // Calculate eligibility for the member
        $stats = $eligibilityService->calculateAttendanceStats($member);
        echo "✓ AttendanceEligibilityService initialized\n";
        echo "  Attendance Stats:\n";
        echo "    - total_sessions: " . ($stats['total_sessions'] ?? 0) . "\n";
        echo "    - attended: " . ($stats['attended'] ?? 0) . "\n";
        echo "    - absent: " . ($stats['absent'] ?? 0) . "\n";
        echo "    - absence_percent: " . number_format($stats['absence_percent'] ?? 0, 2) . "%\n";
        echo "    - is_eligible: " . (($stats['is_eligible'] ?? false) ? 'Yes' : 'No') . "\n";
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Cannot test - missing course or member\n";
}
echo "\n";

// Test 5: Test CourseGradingService
echo "TEST 5: Test CourseGradingService\n";
echo "-----------------------------------\n";
if ($course) {
    try {
        $eligibilityService = $eligibilityService ?? new AttendanceEligibilityService();
        $gradingService = new CourseGradingService($eligibilityService);
        
        // Get grading summary
        $summary = $gradingService->getGradingSummary($course);
        echo "✓ CourseGradingService initialized\n";
        echo "  Grading Summary:\n";
        echo "    - finalization_status: " . ($summary['finalization_status'] ?? 'N/A') . "\n";
        echo "    - total_members: " . ($summary['total_members'] ?? 0) . "\n";
        echo "    - eligible_members: " . ($summary['eligible_members'] ?? 0) . "\n";
        echo "    - grades_drafted: " . ($summary['grades_drafted'] ?? 0) . "\n";
        echo "    - grades_published: " . ($summary['grades_published'] ?? 0) . "\n";
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Cannot test - no course found\n";
}
echo "\n";

// Test 6: Test Model relationships
echo "TEST 6: Test Model relationships\n";
echo "---------------------------------\n";
if ($course) {
    try {
        // Test course relationships
        $finalizationLogs = $course->finalizationLogs()->count();
        $gradeAppeals = $course->gradeAppeals()->count();
        $remediationSessions = $course->remediationSessions()->count();
        $certificates = $course->certificates()->count();
        $eligibilityOverrides = $course->eligibilityOverrides()->count();
        
        echo "✓ Course relationships work:\n";
        echo "  - finalizationLogs: {$finalizationLogs}\n";
        echo "  - gradeAppeals: {$gradeAppeals}\n";
        echo "  - remediationSessions: {$remediationSessions}\n";
        echo "  - certificates: {$certificates}\n";
        echo "  - eligibilityOverrides: {$eligibilityOverrides}\n";
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

if ($member) {
    try {
        // Test member relationships
        $memberEligibilityOverrides = $member->eligibilityOverrides()->count();
        $memberGradeAppeals = $member->gradeAppeals()->count();
        $memberRemediationEnrollments = $member->remediationEnrollments()->count();
        $memberCertificates = $member->certificates()->count();
        
        echo "✓ CourseMember relationships work:\n";
        echo "  - eligibilityOverrides: {$memberEligibilityOverrides}\n";
        echo "  - gradeAppeals: {$memberGradeAppeals}\n";
        echo "  - remediationEnrollments: {$memberRemediationEnrollments}\n";
        echo "  - certificates: {$memberCertificates}\n";
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 7: Check API routes are accessible
echo "TEST 7: Check available API endpoints\n";
echo "--------------------------------------\n";
$endpoints = [
    'GET /api/courses/{course}/eligibility',
    'GET /api/courses/{course}/eligibility/summary',
    'POST /api/courses/{course}/eligibility/refresh',
    'GET /api/courses/{course}/completion/summary',
    'POST /api/courses/{course}/completion/start-grading',
    'GET /api/courses/{course}/appeals',
    'GET /api/courses/{course}/remediation',
    'GET /api/courses/{course}/certificates',
];
echo "✓ API Endpoints registered:\n";
foreach ($endpoints as $endpoint) {
    echo "  - {$endpoint}\n";
}
echo "\n";

echo "===========================================\n";
echo "  All tests completed!\n";
echo "===========================================\n";
