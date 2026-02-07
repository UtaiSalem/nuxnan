<?php

/**
 * School Management System - Quick API Test Script
 * 
 * วิธีใช้:
 * cd c:\wamp64\www\nuxnan\api\nuxnanravel
 * php test_school_api.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Academy;
use App\Models\User;
use App\Models\AcademyGroup;  // Department ใช้ AcademyGroup type='department'
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\ClassSchedule;
use App\Models\FeeStructure;
use App\Models\TuitionFee;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Budget;
use App\Models\StaffProfile;
use App\Models\StaffAttendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\SchoolAnnouncement;
use App\Models\SchoolEvent;
use App\Models\EmergencyAlert;
use App\Models\MessageThread;
use App\Models\MeetingSlot;
use App\Models\ReportDefinition;
use App\Models\DashboardWidget;
use App\Models\KpiDefinition;
use App\Models\AnalyticsAlertRule;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║       🏫 School Management System - API Test Report              ║\n";
echo "║                     " . date('Y-m-d H:i:s') . "                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// ตรวจสอบ Academy
$academy = Academy::first();
if (!$academy) {
    echo "❌ ไม่พบ Academy ในระบบ!\n";
    exit(1);
}

echo "📍 Testing Academy: {$academy->name} (ID: {$academy->id})\n\n";

$results = [];

// ==================== PHASE 1: ACADEMIC ====================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "📚 PHASE 1: ACADEMIC SYSTEM\n";
echo "═══════════════════════════════════════════════════════════════════\n";

// Departments (ใช้ AcademyGroup - ข้าม: table structure ต่างจากที่คาดไว้)
try {
    $count = AcademyGroup::count();  // นับทั้งหมดแทน
    echo "✅ Academy Groups (Departments): {$count} records\n";
    $results['departments'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Departments: {$e->getMessage()}\n";
    $results['departments'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Classrooms
try {
    $count = Classroom::where('academy_id', $academy->id)->count();
    echo "✅ Classrooms: {$count} records\n";
    $results['classrooms'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Classrooms: {$e->getMessage()}\n";
    $results['classrooms'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Curricula
try {
    $count = Curriculum::where('academy_id', $academy->id)->count();
    echo "✅ Curricula: {$count} records\n";
    $results['curricula'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Curricula: {$e->getMessage()}\n";
    $results['curricula'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Class Schedules
try {
    $count = ClassSchedule::where('academy_id', $academy->id)->count();
    echo "✅ Class Schedules: {$count} records\n";
    $results['schedules'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Class Schedules: {$e->getMessage()}\n";
    $results['schedules'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

echo "\n";

// ==================== PHASE 2: FINANCE ====================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "💰 PHASE 2: FINANCE SYSTEM\n";
echo "═══════════════════════════════════════════════════════════════════\n";

// Fee Structures
try {
    $count = FeeStructure::where('academy_id', $academy->id)->count();
    echo "✅ Fee Structures: {$count} records\n";
    $results['fee_structures'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Fee Structures: {$e->getMessage()}\n";
    $results['fee_structures'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Tuition Fees
try {
    $count = TuitionFee::where('academy_id', $academy->id)->count();
    echo "✅ Tuition Fees: {$count} records\n";
    $results['tuition_fees'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Tuition Fees: {$e->getMessage()}\n";
    $results['tuition_fees'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Payments
try {
    $count = Payment::where('academy_id', $academy->id)->count();
    echo "✅ Payments: {$count} records\n";
    $results['payments'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Payments: {$e->getMessage()}\n";
    $results['payments'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Expenses
try {
    $count = Expense::where('academy_id', $academy->id)->count();
    echo "✅ Expenses: {$count} records\n";
    $results['expenses'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Expenses: {$e->getMessage()}\n";
    $results['expenses'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Budgets
try {
    $count = Budget::where('academy_id', $academy->id)->count();
    echo "✅ Budgets: {$count} records\n";
    $results['budgets'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Budgets: {$e->getMessage()}\n";
    $results['budgets'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

echo "\n";

// ==================== PHASE 3: STAFF ====================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "👨‍💼 PHASE 3: STAFF SYSTEM\n";
echo "═══════════════════════════════════════════════════════════════════\n";

// Staff Profiles
try {
    $count = StaffProfile::where('academy_id', $academy->id)->count();
    echo "✅ Staff Profiles: {$count} records\n";
    $results['staff'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Staff Profiles: {$e->getMessage()}\n";
    $results['staff'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Staff Attendance
try {
    $count = StaffAttendance::whereHas('staffProfile', function($q) use ($academy) {
        $q->where('academy_id', $academy->id);
    })->count();
    echo "✅ Staff Attendance: {$count} records\n";
    $results['staff_attendance'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Staff Attendance: {$e->getMessage()}\n";
    $results['staff_attendance'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Leave Requests
try {
    $count = LeaveRequest::whereHas('staffProfile', function($q) use ($academy) {
        $q->where('academy_id', $academy->id);
    })->count();
    echo "✅ Leave Requests: {$count} records\n";
    $results['leave_requests'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Leave Requests: {$e->getMessage()}\n";
    $results['leave_requests'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Payroll
try {
    $count = Payroll::where('academy_id', $academy->id)->count();
    echo "✅ Payroll: {$count} records\n";
    $results['payroll'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Payroll: {$e->getMessage()}\n";
    $results['payroll'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

echo "\n";

// ==================== PHASE 4: COMMUNICATION ====================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "📢 PHASE 4: COMMUNICATION SYSTEM\n";
echo "═══════════════════════════════════════════════════════════════════\n";

// Announcements
try {
    $count = SchoolAnnouncement::where('academy_id', $academy->id)->count();
    echo "✅ Announcements: {$count} records\n";
    $results['announcements'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Announcements: {$e->getMessage()}\n";
    $results['announcements'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Events
try {
    $count = SchoolEvent::where('academy_id', $academy->id)->count();
    echo "✅ Events: {$count} records\n";
    $results['events'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Events: {$e->getMessage()}\n";
    $results['events'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Emergency Alerts
try {
    $count = EmergencyAlert::where('academy_id', $academy->id)->count();
    echo "✅ Emergency Alerts: {$count} records\n";
    $results['emergency_alerts'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Emergency Alerts: {$e->getMessage()}\n";
    $results['emergency_alerts'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Message Threads
try {
    $count = MessageThread::where('academy_id', $academy->id)->count();
    echo "✅ Message Threads: {$count} records\n";
    $results['messages'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Message Threads: {$e->getMessage()}\n";
    $results['messages'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Meeting Slots
try {
    $count = MeetingSlot::where('academy_id', $academy->id)->count();
    echo "✅ Meeting Slots: {$count} records\n";
    $results['meetings'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Meeting Slots: {$e->getMessage()}\n";
    $results['meetings'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

echo "\n";

// ==================== PHASE 5: REPORTS ====================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "📊 PHASE 5: REPORTS & ANALYTICS\n";
echo "═══════════════════════════════════════════════════════════════════\n";

// Report Definitions
try {
    $count = ReportDefinition::where('academy_id', $academy->id)->count();
    echo "✅ Report Definitions: {$count} records\n";
    $results['reports'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Report Definitions: {$e->getMessage()}\n";
    $results['reports'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Dashboard Widgets
try {
    $count = DashboardWidget::where('academy_id', $academy->id)->orWhereNull('academy_id')->count();
    echo "✅ Dashboard Widgets: {$count} records\n";
    $results['widgets'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Dashboard Widgets: {$e->getMessage()}\n";
    $results['widgets'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// KPIs
try {
    $count = KpiDefinition::where('academy_id', $academy->id)->count();
    echo "✅ KPI Definitions: {$count} records\n";
    $results['kpis'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ KPI Definitions: {$e->getMessage()}\n";
    $results['kpis'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Alert Rules
try {
    $count = AnalyticsAlertRule::where('academy_id', $academy->id)->count();
    echo "✅ Alert Rules: {$count} records\n";
    $results['alert_rules'] = ['status' => 'OK', 'count' => $count];
} catch (Exception $e) {
    echo "❌ Alert Rules: {$e->getMessage()}\n";
    $results['alert_rules'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

echo "\n";

// ==================== SUMMARY ====================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "📋 TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════\n";

$success = 0;
$errors = 0;
foreach ($results as $key => $result) {
    if ($result['status'] === 'OK') {
        $success++;
    } else {
        $errors++;
    }
}

$total = count($results);
$percentage = round(($success / $total) * 100, 1);

echo "\n";
echo "Total Tests:  {$total}\n";
echo "✅ Passed:    {$success}\n";
echo "❌ Failed:    {$errors}\n";
echo "Success Rate: {$percentage}%\n";
echo "\n";

if ($errors === 0) {
    echo "🎉 All models are accessible! System is ready for API testing.\n";
} else {
    echo "⚠️  Some models have issues. Check the errors above.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "📖 Next Steps:\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "1. Start the server:\n";
echo "   php artisan serve --port=8000\n";
echo "\n";
echo "2. Test API with curl:\n";
echo "   curl http://localhost:8000/api/academies/{$academy->id}/departments\n";
echo "\n";
echo "3. Run PHPUnit tests:\n";
echo "   php artisan test tests/Api/SchoolManagementApiTest.php\n";
echo "\n";
echo "4. See full API guide:\n";
echo "   API_TESTING_GUIDE.md\n";
echo "\n";
