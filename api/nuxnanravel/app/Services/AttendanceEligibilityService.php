<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseAttendance;
use App\Models\AttendanceDetail;
use App\Models\ExamEligibilityOverride;
use App\Models\EligibilityAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttendanceEligibilityService
{
    public function calculateAttendanceStats(CourseMember $courseMember): array
    {
        $course = $courseMember->course;

        $totalSessionsQuery = CourseAttendance::where('course_id', $course->id);

        if ($courseMember->group_id) {
            $totalSessionsQuery->where('group_id', $courseMember->group_id);
        }

        $totalSessions = $totalSessionsQuery->count();

        $minSessions = $course->min_sessions_for_eligibility_check ?? 3;

        if ($totalSessions === 0 || $totalSessions < $minSessions) {
            return [
                'total_sessions' => $totalSessions,
                'attended' => 0,
                'absent' => 0,
                'late' => 0,
                'leave' => 0,
                'attendance_rate' => 100,
                'absence_rate' => 0,
                'is_eligible' => true,
                'eligibility_status' => 'eligible',
                'skip_reason' => $totalSessions === 0
                    ? 'no_sessions'
                    : 'below_minimum_sessions',
            ];
        }

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

        $recordedSessions = $attendanceDetails->count();
        $unrecordedSessions = max(0, $totalSessions - $recordedSessions);
        $stats['absent'] += $unrecordedSessions;

        $attendedCount = $stats['present'] + $stats['late'];
        $absenceCount = $stats['absent'];
        $attendanceRate = ($attendedCount / $totalSessions) * 100;
        $absenceRate = ($absenceCount / $totalSessions) * 100;

        $maxAbsencePercent = $course->max_absence_percent ?? 20;
        $isEligible = $absenceRate <= $maxAbsencePercent;

        $eligibilityStatus = $this->determineEligibilityStatus($absenceRate, $maxAbsencePercent, $courseMember);

        return [
            'total_sessions' => $totalSessions,
            'attended' => $attendedCount,
            'present' => $stats['present'],
            'absent' => $stats['absent'],
            'late' => $stats['late'],
            'leave' => $stats['leave'],
            'excused' => $stats['excused'],
            'recorded_sessions' => $recordedSessions,
            'unrecorded_sessions' => $unrecordedSessions,
            'attendance_rate' => round($attendanceRate, 2),
            'absence_rate' => round($absenceRate, 2),
            'max_absence_percent' => $maxAbsencePercent,
            'is_eligible' => $isEligible,
            'eligibility_status' => $eligibilityStatus,
        ];
    }

    protected function determineEligibilityStatus(float $absenceRate, float $maxAbsence, CourseMember $courseMember): string
    {
        if ($courseMember->eligibility_status === 'unlocked') {
            return 'unlocked';
        }

        if ($absenceRate <= $maxAbsence * 0.5) {
            return 'eligible';
        } elseif ($absenceRate <= $maxAbsence) {
            return 'at_risk';
        } else {
            return 'ineligible';
        }
    }

    public function updateEligibilityStatus(CourseMember $courseMember): CourseMember
    {
        $stats = $this->calculateAttendanceStats($courseMember);

        if ($courseMember->eligibility_status !== 'unlocked') {
            $oldStatus = $courseMember->eligibility_status;
            $newStatus = $stats['eligibility_status'];

            $courseMember->update([
                'exam_eligible' => $stats['is_eligible'],
                'eligibility_status' => $newStatus,
                'absence_percent' => $stats['absence_rate'],
            ]);

            if ($oldStatus !== $newStatus) {
                EligibilityAuditLog::log(
                    $courseMember,
                    $newStatus,
                    EligibilityAuditLog::TYPE_AUTO_CALC,
                    null,
                    null,
                    ['absence_rate' => $stats['absence_rate'], 'total_sessions' => $stats['total_sessions']]
                );
            }
        }

        return $courseMember->fresh();
    }

    public function updateCourseEligibility(Course $course): array
    {
        $members = $course->courseMembers()->get();
        $results = [
            'total' => $members->count(),
            'eligible' => 0,
            'at_risk' => 0,
            'ineligible' => 0,
            'unlocked' => 0,
        ];

        foreach ($members as $member) {
            $updated = $this->updateEligibilityStatus($member);
            $status = $updated->eligibility_status ?? 'eligible';
            if (isset($results[$status])) {
                $results[$status]++;
            }
        }

        return $results;
    }

    // ─── Duplicate prevention helper ───

    protected function checkExistingOverride(CourseMember $courseMember, string $method): void
    {
        $existing = ExamEligibilityOverride::where('course_member_id', $courseMember->id)
            ->where('unlock_method', $method)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            $label = match($method) {
                'points' => 'Points',
                'reading' => 'การอ่าน',
                'appeal' => 'อุทธรณ์',
                default => $method,
            };
            throw new \Exception("มีคำขอปลดล็อคด้วย{$label}ค้างอยู่แล้ว");
        }
    }

    // ─── Self unlock ───

    public function requestUnlockBySelf(CourseMember $courseMember): ExamEligibilityOverride
    {
        $course = $courseMember->course;

        if (!$course->allow_self_unlock) {
            throw new \Exception('วิชานี้ไม่อนุญาตให้ปลดล็อคด้วยตนเอง');
        }

        $stats = $this->calculateAttendanceStats($courseMember);

        $override = ExamEligibilityOverride::create([
            'course_member_id' => $courseMember->id,
            'course_id' => $course->id,
            'student_id' => $courseMember->user_id,
            'unlock_method' => 'self',
            'status' => 'approved',
            'approved_at' => now(),
            'absence_percent_at_unlock' => $stats['absence_rate'],
            'total_sessions_at_unlock' => $stats['total_sessions'],
            'absent_sessions_at_unlock' => $stats['absent'],
        ]);

        EligibilityAuditLog::log(
            $courseMember,
            'unlocked',
            EligibilityAuditLog::TYPE_SELF_UNLOCK,
            $courseMember->user_id,
            "ปลดล็อคด้วยตนเอง",
            ['override_id' => $override->id]
        );

        $courseMember->update([
            'exam_eligible' => true,
            'eligibility_status' => 'unlocked',
            'eligibility_unlocked_at' => now(),
            'eligibility_unlock_method' => 'self',
        ]);

        return $override->fresh();
    }

    // ─── Points unlock ───

    public function requestUnlockByPoints(CourseMember $courseMember): ExamEligibilityOverride
    {
        $course = $courseMember->course;

        if (!$course->allow_unlock_by_points) {
            throw new \Exception('วิชานี้ไม่อนุญาตให้ใช้ Points ปลดล็อค');
        }

        $this->checkExistingOverride($courseMember, 'points');

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

    public function processPointsUnlock(ExamEligibilityOverride $override, int $transactionId): ExamEligibilityOverride
    {
        $course = $override->course;
        $member = $override->courseMember;

        $override->update([
            'points_spent' => $course->unlock_points_cost,
            'points_transaction_id' => $transactionId,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        EligibilityAuditLog::log(
            $member,
            'unlocked',
            EligibilityAuditLog::TYPE_POINTS_UNLOCK,
            $member->user_id,
            "ใช้ {$course->unlock_points_cost} Points ปลดล็อค",
            ['override_id' => $override->id, 'points_spent' => $course->unlock_points_cost]
        );

        $member->update([
            'exam_eligible' => true,
            'eligibility_status' => 'unlocked',
            'eligibility_unlocked_at' => now(),
            'eligibility_unlock_method' => 'points',
        ]);

        return $override->fresh();
    }

    // ─── Reading unlock ───

    public function requestUnlockByReading(CourseMember $courseMember): ExamEligibilityOverride
    {
        $course = $courseMember->course;

        if (!$course->allow_unlock_by_reading) {
            throw new \Exception('วิชานี้ไม่อนุญาตให้ใช้การอ่านปลดล็อค');
        }

        $this->checkExistingOverride($courseMember, 'reading');

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

        if ($newTotal >= $course->unlock_reading_minutes) {
            $override->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            $member = $override->courseMember;

            EligibilityAuditLog::log(
                $member,
                'unlocked',
                EligibilityAuditLog::TYPE_READING_UNLOCK,
                $member->user_id,
                "อ่านครบ {$newTotal} นาที",
                ['override_id' => $override->id, 'reading_minutes' => $newTotal]
            );

            $member->update([
                'exam_eligible' => true,
                'eligibility_status' => 'unlocked',
                'eligibility_unlocked_at' => now(),
                'eligibility_unlock_method' => 'reading',
            ]);
        }

        return $override->fresh();
    }

    // ─── Appeal unlock ───

    public function requestUnlockByAppeal(CourseMember $courseMember, string $reason, ?array $evidence = null): ExamEligibilityOverride
    {
        $course = $courseMember->course;

        if (!($course->allow_unlock_by_appeal ?? true)) {
            throw new \Exception('วิชานี้ไม่อนุญาตให้อุทธรณ์สิทธิ์สอบ');
        }

        $this->checkExistingOverride($courseMember, 'appeal');

        $stats = $this->calculateAttendanceStats($courseMember);

        return ExamEligibilityOverride::create([
            'course_member_id' => $courseMember->id,
            'course_id' => $course->id,
            'student_id' => $courseMember->user_id,
            'unlock_method' => 'appeal',
            'status' => 'pending',
            'appeal_reason' => $reason,
            'appeal_evidence' => $evidence,
            'absence_percent_at_unlock' => $stats['absence_rate'],
            'total_sessions_at_unlock' => $stats['total_sessions'],
            'absent_sessions_at_unlock' => $stats['absent'],
        ]);
    }

    public function addAppealEvidence(ExamEligibilityOverride $override, array $newEvidence): ExamEligibilityOverride
    {
        $existing = $override->appeal_evidence ?? [];
        $existing[] = $newEvidence;

        $override->update(['appeal_evidence' => $existing]);

        return $override->fresh();
    }

    // ─── Admin approve / reject ───

    public function adminApprove(ExamEligibilityOverride $override, User $admin, string $reason): ExamEligibilityOverride
    {
        $override->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'admin_reason' => $reason,
        ]);

        $member = $override->courseMember;
        $changeType = match($override->unlock_method) {
            'appeal' => EligibilityAuditLog::TYPE_APPEAL_UNLOCK,
            'reading' => EligibilityAuditLog::TYPE_READING_UNLOCK,
            default => EligibilityAuditLog::TYPE_ADMIN_UNLOCK,
        };

        EligibilityAuditLog::log(
            $member,
            'unlocked',
            $changeType,
            $admin->id,
            $reason,
            ['override_id' => $override->id, 'method' => $override->unlock_method]
        );

        $member->update([
            'exam_eligible' => true,
            'eligibility_status' => 'unlocked',
            'eligibility_unlocked_at' => now(),
            'eligibility_unlock_method' => $override->unlock_method,
        ]);

        return $override->fresh();
    }

    public function adminReject(ExamEligibilityOverride $override, User $admin, string $reason): ExamEligibilityOverride
    {
        $override->update([
            'status' => 'rejected',
            'approved_by' => $admin->id,
            'admin_reason' => $reason,
        ]);

        return $override->fresh();
    }

    // ─── Bulk operations ───

    public function bulkUnlock(Course $course, array $memberIds, User $admin, string $reason): array
    {
        $results = ['success' => 0, 'skipped' => 0, 'errors' => []];

        $members = $course->courseMembers()->whereIn('id', $memberIds)->get();

        foreach ($members as $member) {
            if ($member->eligibility_status === 'unlocked') {
                $results['skipped']++;
                continue;
            }

            try {
                $stats = $this->calculateAttendanceStats($member);

                $override = ExamEligibilityOverride::create([
                    'course_member_id' => $member->id,
                    'course_id' => $course->id,
                    'student_id' => $member->user_id,
                    'unlock_method' => 'admin',
                    'status' => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                    'admin_reason' => $reason,
                    'absence_percent_at_unlock' => $stats['absence_rate'],
                    'total_sessions_at_unlock' => $stats['total_sessions'],
                    'absent_sessions_at_unlock' => $stats['absent'],
                ]);

                EligibilityAuditLog::log(
                    $member,
                    'unlocked',
                    EligibilityAuditLog::TYPE_ADMIN_UNLOCK,
                    $admin->id,
                    "Bulk unlock: {$reason}",
                    ['override_id' => $override->id, 'bulk' => true]
                );

                $member->update([
                    'exam_eligible' => true,
                    'eligibility_status' => 'unlocked',
                    'eligibility_unlocked_at' => now(),
                    'eligibility_unlock_method' => 'admin',
                ]);

                $results['success']++;
            } catch (\Exception $e) {
                $results['errors'][] = ['member_id' => $member->id, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function bulkRevoke(Course $course, array $memberIds, User $admin, string $reason): array
    {
        $results = ['success' => 0, 'skipped' => 0];

        $members = $course->courseMembers()->whereIn('id', $memberIds)->get();

        foreach ($members as $member) {
            if ($member->eligibility_status !== 'unlocked') {
                $results['skipped']++;
                continue;
            }

            ExamEligibilityOverride::where('course_member_id', $member->id)
                ->where('status', 'approved')
                ->update(['status' => 'expired', 'notes' => "Revoked by admin: {$reason}"]);

            $stats = $this->calculateAttendanceStats($member);

            EligibilityAuditLog::log(
                $member,
                $stats['is_eligible'] ? 'eligible' : 'ineligible',
                EligibilityAuditLog::TYPE_ADMIN_REVOKE,
                $admin->id,
                $reason,
                ['bulk' => true]
            );

            $member->update([
                'exam_eligible' => $stats['is_eligible'],
                'eligibility_status' => $stats['is_eligible'] ? 'eligible' : 'ineligible',
                'eligibility_unlocked_at' => null,
                'eligibility_unlock_method' => null,
            ]);

            $results['success']++;
        }

        return $results;
    }

    // ─── Remediation auto-unlock ───

    public function unlockByRemediation(CourseMember $member, int $remediationSessionId, User $admin): ExamEligibilityOverride
    {
        $stats = $this->calculateAttendanceStats($member);

        $override = ExamEligibilityOverride::create([
            'course_member_id' => $member->id,
            'course_id' => $member->course_id,
            'student_id' => $member->user_id,
            'unlock_method' => 'admin',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'admin_reason' => 'ผ่านการแก้ตัว (Remediation)',
            'remediation_session_id' => $remediationSessionId,
            'absence_percent_at_unlock' => $stats['absence_rate'],
            'total_sessions_at_unlock' => $stats['total_sessions'],
            'absent_sessions_at_unlock' => $stats['absent'],
        ]);

        EligibilityAuditLog::log(
            $member,
            'unlocked',
            EligibilityAuditLog::TYPE_REMEDIATION_UNLOCK,
            $admin->id,
            'ผ่านการแก้ตัว (Remediation)',
            ['override_id' => $override->id, 'remediation_session_id' => $remediationSessionId]
        );

        $member->update([
            'exam_eligible' => true,
            'eligibility_status' => 'unlocked',
            'eligibility_unlocked_at' => now(),
            'eligibility_unlock_method' => 'admin',
        ]);

        return $override;
    }

    // ─── Summary ───

    public function getCourseEligibilitySummary(Course $course): array
    {
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

        $userIds = $members->pluck('user_id')->unique();
        $users = \App\Models\User::whereIn('id', $userIds)
            ->select('id', 'name', 'profile_photo_path')
            ->get()
            ->keyBy('id');

        $pendingOverrides = ExamEligibilityOverride::where('course_id', $course->id)
            ->where('status', 'pending')
            ->get()
            ->keyBy('course_member_id');

        foreach ($members as $member) {
            $stats = $this->calculateAttendanceStats($member);
            $status = $stats['eligibility_status'];
            if (isset($summary[$status])) {
                $summary[$status]++;
            }

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
                'has_pending_request' => $pendingOverrides->has($member->id),
                'pending_method' => $pendingOverrides->get($member->id)?->unlock_method,
            ];
        }

        return $summary;
    }

    // ─── Audit log retrieval ───

    public function getAuditLog(Course $course, ?int $memberId = null, int $limit = 50): array
    {
        $query = EligibilityAuditLog::where('course_id', $course->id)
            ->with(['changedByUser:id,name'])
            ->orderBy('created_at', 'desc');

        if ($memberId) {
            $query->where('course_member_id', $memberId);
        }

        return $query->limit($limit)->get()->toArray();
    }

    // ─── Can take exam ───

    public function canTakeExam(CourseMember $courseMember): array
    {
        $stats = $this->calculateAttendanceStats($courseMember);

        $hasValidOverride = ExamEligibilityOverride::valid()
            ->where('course_member_id', $courseMember->id)
            ->exists();

        if ($courseMember->eligibility_status === 'unlocked' && !$hasValidOverride) {
            EligibilityAuditLog::log(
                $courseMember,
                $stats['eligibility_status'],
                EligibilityAuditLog::TYPE_EXPIRATION,
                null,
                'Override หมดอายุ'
            );

            $courseMember->update([
                'exam_eligible' => false,
                'eligibility_status' => $stats['eligibility_status'],
                'eligibility_unlocked_at' => null,
                'eligibility_unlock_method' => null,
            ]);
        }

        $canTake = $stats['is_eligible'] || $hasValidOverride;

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

            $existingOverrides = ExamEligibilityOverride::where('course_member_id', $courseMember->id)
                ->whereIn('status', ['pending', 'approved'])
                ->pluck('unlock_method')
                ->toArray();

            if ($course->allow_unlock_by_points && $course->unlock_points_cost && !in_array('points', $existingOverrides)) {
                $unlockOptions[] = [
                    'method' => 'points',
                    'cost' => $course->unlock_points_cost,
                    'label' => sprintf('ใช้ %d Points', $course->unlock_points_cost),
                ];
            }

            if ($course->allow_unlock_by_reading && $course->unlock_reading_minutes && !in_array('reading', $existingOverrides)) {
                $unlockOptions[] = [
                    'method' => 'reading',
                    'minutes' => $course->unlock_reading_minutes,
                    'label' => sprintf('อ่านเพิ่ม %d นาที', $course->unlock_reading_minutes),
                ];
            }

            if (($course->allow_unlock_by_appeal ?? true) && !in_array('appeal', $existingOverrides)) {
                $unlockOptions[] = [
                    'method' => 'appeal',
                    'label' => 'อุทธรณ์สิทธิ์สอบ',
                ];
            }

            $unlockOptions[] = [
                'method' => 'admin',
                'label' => 'ติดต่อผู้สอน',
            ];
        }

        $pendingOverride = null;
        if (!$canTake) {
            $pendingOverride = ExamEligibilityOverride::where('course_member_id', $courseMember->id)
                ->where('status', 'pending')
                ->first();
        }

        return [
            'can_take_exam' => $canTake,
            'eligibility_status' => $hasValidOverride ? 'unlocked' : $stats['eligibility_status'],
            'attendance_stats' => $stats,
            'reasons' => $reasons,
            'unlock_options' => $unlockOptions,
            'pending_override' => $pendingOverride ? [
                'id' => $pendingOverride->id,
                'method' => $pendingOverride->unlock_method,
                'method_label' => $pendingOverride->method_label,
                'created_at' => $pendingOverride->created_at,
            ] : null,
        ];
    }
}
