<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\GradebookScore;
use App\Models\Learn\Academy\SchoolAttendance;
use Illuminate\Http\JsonResponse;

class AcademyDashboardController extends Controller
{
    /**
     * Dashboard action-signals summary (เมนู #24).
     * คืนเฉพาะ signal ที่ผู้ใช้มีสิทธิ์เห็น — การ์ดที่ไม่มีสิทธิ์จะไม่ถูกส่ง (FE ไม่โชว์)
     */
    public function summary(Academy $academy): JsonResponse
    {
        $user = auth()->user();
        $summary = [];

        // ใช้งานวันนี้ (#24) — สมาชิกที่มีกิจกรรมในโรงเรียนวันนี้ (member-level)
        $summary['active_today'] = (int) Activity::whereHasMorph(
            'activityable',
            [AcademyPost::class],
            function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            }
        )
            ->whereDate('created_at', now()->toDateString())
            ->distinct()
            ->count('user_id');

        // บริจาครอตรวจสอบ (#23) — gate finance.view
        if ($academy->userCan($user, 'finance.view')) {
            $summary['donations_pending'] = (int) $academy->donations()
                ->whereNull('deleted_at')
                ->where('status', 'pending')
                ->count();
        }

        // บันทึกการเข้าเรียนวันนี้ (#18) — gate school_attendance.view
        if ($academy->userCan($user, 'school_attendance.view')) {
            $summary['attendance_today'] = (int) SchoolAttendance::where('academy_id', $academy->id)
                ->whereDate('date', now()->toDateString())
                ->count();
        }

        // ผลการเรียนรอตรวจ (#19) — gate grades.view
        if ($academy->userCan($user, 'grades.view')) {
            $summary['grades_pending'] = (int) GradebookScore::where('academy_id', $academy->id)
                ->where('status', 'pending')
                ->count();
        }

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}
