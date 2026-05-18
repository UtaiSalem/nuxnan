<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseAttendance;
use App\Models\AttendanceDetail;
use App\Models\ExamEligibilityOverride;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttendanceEligibilityService
{
    /**
     * Calculate attendance statistics for a course member
     */
    public function calculateAttendanceStats(CourseMember $courseMember): array
    {
        $course = $courseMember->course;
        
        // Get all attendance sessions for this course
        // Filter by group if the member is assigned to one
        $totalSessionsQuery = CourseAttendance::where('course_id', $course->id);
        
        if ($courseMember->group_id) {
            $totalSessionsQuery->where('group_id', $courseMember->group_id);
        }
        
        $totalSessions = $totalSessionsQuery->count();
        
        if ($totalSessions === 0) {
            return [
                'total_sessions' => 0,
                'attended' => 0,
                'absent' => 0,
                'late' => 0,
                'leave' => 0,
                'attendance_rate' => 100,
                'absence_rate' => 0,
                'is_eligible' => true,
                'eligibility_status' => 'eligible',
            ];
        }
        
        // Get attendance details for this student
        $attendanceDetails = AttendanceDetail::where('course_id', $course->id)
            ->where('course_member_id', $courseMember->id)
            ->when($courseMember->group_id, function ($query) use ($courseMember) {
                return $query->where('group_id', $courseMember->group_id);
            })
            ->get();
        
        $stats = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'leave' => 0,
            'excused' => 0,
        ];

        // Status mapping from DB integers: 1=Present, 2=Late, 3=Leave
        $statusMap = [
            1 => 'present',
            2 => 'late',
            3 => 'leave',
        ];
        
        foreach ($attendanceDetails as $detail) {
            $statusKey = $statusMap[$detail->status] ?? 'absent';
            if (isset($stats[$statusKey])) {
                $stats[$statusKey]++;
            }
        }
        
        // Sessions without any record count as absent
        $recordedSessions = $attendanceDetails->count();
        $unrecordedSessions = max(0, $totalSessions - $recordedSessions);
        $stats['absent'] += $unrecordedSessions;
        
        // Calculate rates
        $attendedCount = $stats['present'] + $stats['late']; // Late still counts as attended
        $absenceCount = $stats['absent'];
        $attendanceRate = ($attendedCount / $totalSessions) * 100;
        $absenceRate = ($absenceCount / $totalSessions) * 100;
        
        // Check eligibility against course's max absence
        $maxAbsencePercent = $course->max_absence_percent ?? 20;
        $isEligible = $absenceRate <= $maxAbsencePercent;
        
        // Determine eligibility status
        $eligibilityStatus = $this->determineEligibilityStatus($absenceRate, $maxAbsencePercent, $courseMember);
        
        return [
            'total_sessions' => $totalSessions,
            'attended' => $attendedCount,
            'present' => $stats['present'],
            'absent' => $stats['absent'],
            'late' => $stats['late'],
            'leave' => $stats['leave'],
            'excused' => $stats['excused'],
            'attendance_rate' => round($attendanceRate, 2),
            'absence_rate' => round($absenceRate, 2),
            'max_absence_percent' => $maxAbsencePercent,
            'is_eligible' => $isEligible,
            'eligibility_status' => $eligibilityStatus,
        ];
    }

    /**
     * Determine eligibility status based on absence rate
     */
    protected function determineEligibilityStatus(float $absenceRate, float $maxAbsence, CourseMember $courseMember): string
    {
        // Check if already unlocked
        if ($courseMember->eligibility_status === 'unlocked') {
            return 'unlocked';
        }
        
        if ($absenceRate <= $maxAbsence * 0.5) {
            return 'eligible'; // Safe zone
        } elseif ($absenceRate <= $maxAbsence) {
            return 'at_risk'; // Warning zone
        } else {
            return 'ineligible'; // Over limit
        }
    }

    /**
     * Update eligibility status for a course member
     */
    public function updateEligibilityStatus(CourseMember $courseMember): CourseMember
    {
        $stats = $this->calculateAttendanceStats($courseMember);
        
        // Don't override if already unlocked
        if ($courseMember->eligibility_status !== 'unlocked') {
            $courseMember->update([
                'exam_eligible' => $stats['is_eligible'],
                'eligibility_status' => $stats['eligibility_status'],
                'absence_percent' => $stats['absence_rate'],
            ]);
        }
        
        return $courseMember->fresh();
    }

    /**
     * Update eligibility for all members in a course
     */
    public function updateCourseEligibility(Course $course): array
    {
        $members = $course->members()->get();
        $results = [
            'total' => $members->count(),
            'eligible' => 0,
            'at_risk' => 0,
            'ineligible' => 0,
            'unlocked' => 0,
        ];
        
        foreach ($members as $member) {
            $updated = $this->updateEligibilityStatus($member);
            $results[$updated->eligibility_status]++;
        }
        
        return $results;
    }

    /**
     * Request eligibility unlock by points
     */
    public function requestUnlockByPoints(CourseMember $courseMember): ExamEligibilityOverride
    {
        $course = $courseMember->course;
        
        if (!$course->allow_unlock_by_points) {
            throw new \Exception('วิชานี้ไม่อนุญาตให้ใช้ Points ปลดล็อค');
        }
        
        $stats = $this->calculateAttendanceStats($courseMember);
        
        return ExamEligibilityOverride::create([
            'course_member_id' => $courseMember->id,
            'course_id' => $course->id,
            'student_id' => $courseMember->user_id,
            'unlock_method' => 'points',
            'status' => 'pending',
            'absence_percent_at_unlock' => $stats['absence_rate'],
            'total_sessions_at_unlock' => $stats['total_sessions'],
            'absent_sessions_at_unlock' => $stats['absent'],
        ]);
    }

    /**
     * Process points unlock
     */
    public function processPointsUnlock(ExamEligibilityOverride $override, int $transactionId): ExamEligibilityOverride
    {
        $course = $override->course;
        
        $override->update([
            'points_spent' => $course->unlock_points_cost,
            'points_transaction_id' => $transactionId,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        
        // Update course member
        $override->courseMember->update([
            'exam_eligible' => true,
            'eligibility_status' => 'unlocked',
            'eligibility_unlocked_at' => now(),
            'eligibility_unlock_method' => 'points',
        ]);
        
        return $override->fresh();
    }

    /**
     * Request eligibility unlock by reading
     */
    public function requestUnlockByReading(CourseMember $courseMember): ExamEligibilityOverride
    {
        $course = $courseMember->course;
        
        if (!$course->allow_unlock_by_reading) {
            throw new \Exception('วิชานี้ไม่อนุญาตให้ใช้การอ่านปลดล็อค');
        }
        
        $stats = $this->calculateAttendanceStats($courseMember);
        
        return ExamEligibilityOverride::create([
            'course_member_id' => $courseMember->id,
            'course_id' => $course->id,
            'student_id' => $courseMember->user_id,
            'unlock_method' => 'reading',
            'status' => 'pending',
            'reading_minutes_completed' => 0,
            'absence_percent_at_unlock' => $stats['absence_rate'],
            'total_sessions_at_unlock' => $stats['total_sessions'],
            'absent_sessions_at_unlock' => $stats['absent'],
        ]);
    }

    /**
     * Update reading progress
     */
    public function updateReadingProgress(ExamEligibilityOverride $override, int $minutes, array $proof): ExamEligibilityOverride
    {
        $course = $override->course;
        $newTotal = ($override->reading_minutes_completed ?? 0) + $minutes;
        
        $existingProof = $override->reading_proof ?? [];
        $existingProof[] = $proof;
        
        $override->update([
            'reading_minutes_completed' => $newTotal,
            'reading_proof' => $existingProof,
        ]);
        
        // Check if reading requirement is met
        if ($newTotal >= $course->unlock_reading_minutes) {
            $override->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            
            $override->courseMember->update([
                'exam_eligible' => true,
                'eligibility_status' => 'unlocked',
                'eligibility_unlocked_at' => now(),
                'eligibility_unlock_method' => 'reading',
            ]);
        }
        
        return $override->fresh();
    }

    /**
     * Admin approve unlock request
     */
    public function adminApprove(ExamEligibilityOverride $override, User $admin, string $reason): ExamEligibilityOverride
    {
        $override->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'admin_reason' => $reason,
        ]);
        
        $override->courseMember->update([
            'exam_eligible' => true,
            'eligibility_status' => 'unlocked',
            'eligibility_unlocked_at' => now(),
            'eligibility_unlock_method' => $override->unlock_method,
        ]);
        
        return $override->fresh();
    }

    /**
     * Admin reject unlock request
     */
    public function adminReject(ExamEligibilityOverride $override, User $admin, string $reason): ExamEligibilityOverride
    {
        $override->update([
            'status' => 'rejected',
            'approved_by' => $admin->id,
            'admin_reason' => $reason,
        ]);
        
        return $override->fresh();
    }

    /**
     * Get eligibility summary for a course
     */
    public function getCourseEligibilitySummary(Course $course): array
    {
        // Use courseMembers() which returns CourseMember models, not members() which returns User models
        $members = $course->courseMembers()
            ->select('course_members.*')
            ->get();
        
        $summary = [
            'total' => $members->count(),
            'eligible' => 0,
            'at_risk' => 0,
            'ineligible' => 0,
            'unlocked' => 0,
            'members' => [],
        ];
        
        // Pre-load users in a separate query to avoid accessor issues
        $userIds = $members->pluck('user_id')->unique();
        $users = \App\Models\User::whereIn('id', $userIds)
            ->select('id', 'name', 'profile_photo_path')
            ->get()
            ->keyBy('id');
        
        foreach ($members as $member) {
            $stats = $this->calculateAttendanceStats($member);
            $summary[$stats['eligibility_status']]++;
            
            // Get user data from pre-loaded collection
            $user = $users->get($member->user_id);
            $userData = null;
            if ($user) {
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                ];
            }
            
            $summary['members'][] = [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'user' => $userData,
                'stats' => $stats,
            ];
        }
        
        return $summary;
    }

    /**
     * Check if student can take exam
     */
    public function canTakeExam(CourseMember $courseMember): array
    {
        $stats = $this->calculateAttendanceStats($courseMember);
        
        $canTake = $stats['is_eligible'] || $courseMember->eligibility_status === 'unlocked';
        
        $reasons = [];
        if (!$canTake) {
            $reasons[] = sprintf(
                'ขาดเรียน %.1f%% (เกินกำหนด %.1f%%)',
                $stats['absence_rate'],
                $stats['max_absence_percent']
            );
        }
        
        $unlockOptions = [];
        if (!$canTake) {
            $course = $courseMember->course;
            
            if ($course->allow_unlock_by_points && $course->unlock_points_cost) {
                $unlockOptions[] = [
                    'method' => 'points',
                    'cost' => $course->unlock_points_cost,
                    'label' => sprintf('ใช้ %d Points', $course->unlock_points_cost),
                ];
            }
            
            if ($course->allow_unlock_by_reading && $course->unlock_reading_minutes) {
                $unlockOptions[] = [
                    'method' => 'reading',
                    'minutes' => $course->unlock_reading_minutes,
                    'label' => sprintf('อ่านเพิ่ม %d นาที', $course->unlock_reading_minutes),
                ];
            }
            
            $unlockOptions[] = [
                'method' => 'admin',
                'label' => 'ติดต่อผู้สอน',
            ];
        }
        
        return [
            'can_take_exam' => $canTake,
            'eligibility_status' => $stats['eligibility_status'],
            'attendance_stats' => $stats,
            'reasons' => $reasons,
            'unlock_options' => $unlockOptions,
        ];
    }
}
