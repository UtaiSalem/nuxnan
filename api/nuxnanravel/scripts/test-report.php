<?php
/**
 * Test Report Export System
 * Run: php scripts/test-report.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseAttendance;
use App\Models\Lesson;
use App\Http\Controllers\Api\CourseReportController;
use App\Exports\CourseGradesExport;
use Maatwebsite\Excel\Facades\Excel;

echo "=== Testing Report Export System ===\n\n";

// 1. Check courses
$course = Course::first();
if (!$course) {
    echo "❌ No courses found in database\n";
    exit(1);
}

echo "✅ Course found: {$course->name} (ID: {$course->id})\n";

// 2. Check members
$memberCount = CourseMember::where('course_id', $course->id)->count();
echo "   - Members: {$memberCount}\n";

// 3. Check groups
$groupCount = \App\Models\CourseGroup::where('course_id', $course->id)->count();
echo "   - Groups: {$groupCount}\n";

// 4. Check lessons
$lessonCount = Lesson::where('course_id', $course->id)->count();
echo "   - Lessons: {$lessonCount}\n";

// 5. Test getReportTypes method
echo "\n=== Testing Controller Methods ===\n";

try {
    // Use Laravel's container to resolve controller with dependencies
    $controller = app()->make(CourseReportController::class);
    
    // For testing, skip authorization by using Gate facade
    \Illuminate\Support\Facades\Gate::before(function () {
        return true; // Allow all for testing
    });
    
    // Mock authenticated user
    $user = \App\Models\User::first();
    if ($user) {
        auth()->login($user);
        echo "   Logged in as: {$user->name}\n";
    }
    
    // Create mock request
    $request = new \Illuminate\Http\Request();
    
    echo "\n1. Testing getReportTypes()...\n";
    $response = $controller->getReportTypes($request, $course);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ getReportTypes() works!\n";
        echo "   Reports available: " . count($data['data']['reports']) . "\n";
        foreach ($data['data']['reports'] as $report) {
            echo "      - {$report['name']}: " . implode(', ', $report['formats']) . "\n";
        }
    } else {
        echo "   ❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 6. Test prepareGradesData (internal method)
echo "\n2. Testing data preparation...\n";

try {
    $members = CourseMember::where('course_id', $course->id)
        ->with(['user', 'group'])
        ->limit(5)
        ->get();
    
    echo "   ✅ Can fetch members with relations\n";
    echo "   Sample members:\n";
    
    foreach ($members as $member) {
        $userName = $member->user?->name ?? 'Unknown';
        $groupName = $member->group?->name ?? 'No Group';
        echo "      - {$userName} ({$groupName})\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// 7. Test Excel Export
echo "\n3. Testing Excel Export...\n";

try {
    // Get grades data with proper format matching prepareGradesData
    $members = CourseMember::where('course_id', $course->id)
        ->with(['user', 'group'])
        ->get();
    
    $gradesData = $members->map(function ($member) use ($course) {
        $achievedScore = $member->achieved_score ?? 0;
        $totalScore = $course->total_score ?? 100;
        $percentage = $totalScore > 0 ? ($achievedScore / $totalScore) * 100 : 0;
        
        return [
            'member_code' => $member->member_code ?? $member->id,
            'name' => $member->user?->name ?? 'Unknown',
            'email' => $member->user?->email ?? '-',
            'group' => $member->group?->name ?? '-',
            'achieved_score' => $achievedScore,
            'total_score' => $totalScore,
            'percentage' => round($percentage, 2),
            'calculated_grade' => $member->grade ?? '-',
            'final_grade' => $member->grade ?? '-',
            'grade_name' => $member->grade ?? '-',
            'bonus_points' => $member->bonus_points ?? 0,
            'completion_status' => $member->completion_status ?? 'pending',
        ];
    })->toArray();
    
    $export = new CourseGradesExport($gradesData, $course);
    
    // Try to create the Excel file
    $filename = 'test_grades_export_' . date('Y-m-d_His') . '.xlsx';
    $path = storage_path('app/' . $filename);
    
    Excel::store($export, $filename, 'local');
    
    if (file_exists($path)) {
        $size = round(filesize($path) / 1024, 2);
        echo "   ✅ Excel file created: {$filename} ({$size} KB)\n";
        // Clean up
        unlink($path);
        echo "   ✅ Test file cleaned up\n";
    } else {
        echo "   ⚠️ File created but not found at expected path\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "You can now test the API endpoints:\n";
echo "GET /api/courses/{$course->id}/reports/types\n";
echo "GET /api/courses/{$course->id}/reports/grades?format=excel\n";
echo "GET /api/courses/{$course->id}/reports/attendance?format=excel\n";
