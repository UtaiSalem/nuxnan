<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseCertificate;
use App\Models\GradeAppeal;
use App\Models\Notification;
use App\Models\RemediationSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service สำหรับส่ง Notifications เกี่ยวกับระบบเกรด
 */
class GradingNotificationService
{
    /**
     * แจ้งเตือนเมื่อประกาศเกรด (Publish Draft Grades)
     */
    public function notifyGradePublished(Course $course, User $performer): int
    {
        $members = $course->courseMembers()
            ->where('status', 1)
            ->whereNotNull('draft_grade')
            ->with('user')
            ->get();

        $notifications = [];
        $now = now();

        foreach ($members as $member) {
            if (!$member->user) continue;

            $notifications[] = [
                'user_id' => $member->user->id,
                'type' => Notification::TYPE_GRADE_PUBLISHED,
                'content' => "เกรดของคุณในรายวิชา \"{$course->name}\" ได้ประกาศแล้ว คุณได้รับเกรด {$member->draft_grade}",
                'action_url' => "/courses/{$course->name}/gradebook/my-grade",
                'sender_id' => $performer->id,
                'related_id' => $course->id,
                'metadata' => json_encode([
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'grade' => $member->draft_grade,
                    'score' => $member->draft_total_score,
                ]),
                'read_status' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($notifications)) {
            Notification::insert($notifications);
        }

        return count($notifications);
    }

    /**
     * แจ้งเตือนเมื่อ Finalize เกรด
     */
    public function notifyGradeFinalized(Course $course, User $performer): int
    {
        $members = $course->courseMembers()
            ->where('status', 1)
            ->whereNotNull('final_grade')
            ->with('user')
            ->get();

        $notifications = [];
        $now = now();

        foreach ($members as $member) {
            if (!$member->user) continue;

            $passed = $member->final_grade !== 'F';
            $message = $passed
                ? "🎉 ยินดีด้วย! คุณผ่านรายวิชา \"{$course->name}\" ด้วยเกรด {$member->final_grade}"
                : "ผลการเรียนรายวิชา \"{$course->name}\" - คุณได้รับเกรด {$member->final_grade}";

            $notifications[] = [
                'user_id' => $member->user->id,
                'type' => Notification::TYPE_GRADE_FINALIZED,
                'content' => $message,
                'action_url' => "/courses/{$course->name}/gradebook/my-grade",
                'sender_id' => $performer->id,
                'related_id' => $course->id,
                'metadata' => json_encode([
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'grade' => $member->final_grade,
                    'passed' => $passed,
                ]),
                'read_status' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($notifications)) {
            Notification::insert($notifications);
        }

        return count($notifications);
    }

    /**
     * แจ้งเตือนผู้สอนเมื่อมีคำร้องอุทธรณ์ใหม่
     */
    public function notifyAppealSubmitted(GradeAppeal $appeal): void
    {
        $course = $appeal->course;
        $student = $appeal->member->user;

        // Notify course owner and admins
        $admins = $this->getCourseAdmins($course);

        $notifications = [];
        $now = now();

        foreach ($admins as $admin) {
            $notifications[] = [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_APPEAL_SUBMITTED,
                'content' => "📝 {$student->name} ส่งคำร้องอุทธรณ์เกรดในรายวิชา \"{$course->name}\"",
                'action_url' => "/courses/{$course->name}/gradebook/appeals",
                'sender_id' => $student->id,
                'related_id' => $appeal->id,
                'metadata' => json_encode([
                    'appeal_id' => $appeal->id,
                    'course_id' => $course->id,
                    'student_name' => $student->name,
                    'appeal_type' => $appeal->appeal_type,
                    'current_grade' => $appeal->current_grade,
                    'requested_grade' => $appeal->requested_grade,
                ]),
                'read_status' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($notifications)) {
            Notification::insert($notifications);
        }
    }

    /**
     * แจ้งเตือนนักเรียนเมื่อได้รับการตอบกลับอุทธรณ์
     */
    public function notifyAppealResponded(GradeAppeal $appeal): void
    {
        $course = $appeal->course;
        $student = $appeal->member->user;

        $statusText = match ($appeal->status) {
            'approved' => '✅ อนุมัติแล้ว',
            'rejected' => '❌ ไม่อนุมัติ',
            'withdrawn' => 'ถูกยกเลิก',
            default => 'มีการอัปเดต',
        };

        $message = "คำร้องอุทธรณ์ของคุณในรายวิชา \"{$course->name}\" {$statusText}";

        if ($appeal->status === 'approved' && $appeal->new_grade) {
            $message .= " - เกรดใหม่: {$appeal->new_grade}";
        }

        Notification::create([
            'user_id' => $student->id,
            'type' => Notification::TYPE_APPEAL_RESPONDED,
            'content' => $message,
            'action_url' => "/courses/{$course->name}/gradebook/my-grade",
            'sender_id' => $appeal->reviewed_by,
            'related_id' => $appeal->id,
            'metadata' => [
                'appeal_id' => $appeal->id,
                'course_id' => $course->id,
                'status' => $appeal->status,
                'new_grade' => $appeal->new_grade,
                'response_notes' => $appeal->response_notes,
            ],
            'read_status' => false,
        ]);
    }

    /**
     * แจ้งเตือนเมื่อออกใบประกาศนียบัตร
     */
    public function notifyCertificateIssued(CourseCertificate $certificate): void
    {
        $course = $certificate->course;
        $student = $certificate->member->user;

        Notification::create([
            'user_id' => $student->id,
            'type' => Notification::TYPE_CERTIFICATE_ISSUED,
            'content' => "🏆 ใบประกาศนียบัตรของคุณสำหรับรายวิชา \"{$course->name}\" พร้อมดาวน์โหลดแล้ว!",
            'action_url' => "/courses/{$course->name}/gradebook/my-grade",
            'sender_id' => $certificate->issued_by,
            'related_id' => $certificate->id,
            'metadata' => [
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'grade' => $certificate->final_grade,
            ],
            'read_status' => false,
        ]);
    }

    /**
     * แจ้งเตือนเมื่อออกใบประกาศหลายคน (Bulk)
     */
    public function notifyBulkCertificatesIssued(Collection $certificates, User $performer): int
    {
        $notifications = [];
        $now = now();

        foreach ($certificates as $certificate) {
            $course = $certificate->course;
            $student = $certificate->member->user;

            if (!$student) continue;

            $notifications[] = [
                'user_id' => $student->id,
                'type' => Notification::TYPE_CERTIFICATE_ISSUED,
                'content' => "🏆 ใบประกาศนียบัตรของคุณสำหรับรายวิชา \"{$course->name}\" พร้อมดาวน์โหลดแล้ว!",
                'action_url' => "/courses/{$course->name}/gradebook/my-grade",
                'sender_id' => $performer->id,
                'related_id' => $certificate->id,
                'metadata' => json_encode([
                    'certificate_id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                ]),
                'read_status' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($notifications)) {
            Notification::insert($notifications);
        }

        return count($notifications);
    }

    /**
     * แจ้งเตือนเมื่อปลดล็อคสิทธิ์สอบ
     */
    public function notifyEligibilityUnlocked(CourseMember $member, string $unlockMethod): void
    {
        $course = $member->course;
        $student = $member->user;

        $methodText = match ($unlockMethod) {
            'admin' => 'โดยผู้สอน',
            'points' => 'ด้วยแต้ม',
            'reading' => 'โดยอ่านบทเรียนเพิ่มเติม',
            default => '',
        };

        Notification::create([
            'user_id' => $student->id,
            'type' => Notification::TYPE_ELIGIBILITY_UNLOCKED,
            'content' => "🔓 สิทธิ์สอบของคุณในรายวิชา \"{$course->name}\" ได้รับการปลดล็อค{$methodText}แล้ว!",
            'action_url' => "/courses/{$course->name}/gradebook/eligibility",
            'related_id' => $course->id,
            'metadata' => [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'unlock_method' => $unlockMethod,
            ],
            'read_status' => false,
        ]);
    }

    /**
     * แจ้งเตือนเมื่อเปิด Remediation Session
     */
    public function notifyRemediationOpened(RemediationSession $session): int
    {
        $course = $session->course;
        
        // Get eligible students (failed or need remediation)
        $eligibleMembers = $course->courseMembers()
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('final_grade', 'F')
                    ->orWhere('completion_status', 'failed');
            })
            ->with('user')
            ->get();

        $notifications = [];
        $now = now();

        foreach ($eligibleMembers as $member) {
            if (!$member->user) continue;

            $notifications[] = [
                'user_id' => $member->user->id,
                'type' => Notification::TYPE_REMEDIATION_OPENED,
                'content' => "📚 มีการเปิดสอนซ่อมเสริมรายวิชา \"{$course->name}\" - \"{$session->title}\"",
                'action_url' => "/courses/{$course->name}/gradebook/remediation",
                'sender_id' => $session->created_by,
                'related_id' => $session->id,
                'metadata' => json_encode([
                    'session_id' => $session->id,
                    'session_title' => $session->title,
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'start_date' => $session->start_date?->toDateString(),
                    'end_date' => $session->end_date?->toDateString(),
                ]),
                'read_status' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($notifications)) {
            Notification::insert($notifications);
        }

        return count($notifications);
    }

    /**
     * แจ้งเตือนเมื่อผ่าน Remediation
     */
    public function notifyRemediationCompleted(CourseMember $member, RemediationSession $session): void
    {
        $course = $session->course;
        $student = $member->user;

        Notification::create([
            'user_id' => $student->id,
            'type' => Notification::TYPE_REMEDIATION_COMPLETED,
            'content' => "✅ คุณผ่านการซ่อมเสริมรายวิชา \"{$course->name}\" แล้ว!",
            'action_url' => "/courses/{$course->name}/gradebook/my-grade",
            'related_id' => $session->id,
            'metadata' => [
                'session_id' => $session->id,
                'course_id' => $course->id,
                'course_name' => $course->name,
            ],
            'read_status' => false,
        ]);
    }

    /**
     * Get course admins (owner + members with manage permission)
     */
    private function getCourseAdmins(Course $course): Collection
    {
        $adminIds = collect([$course->user_id]);

        // Get members with manage permission
        $adminMembers = $course->courseMembers()
            ->where('status', 1)
            ->where('role', 'admin')
            ->pluck('user_id');

        $adminIds = $adminIds->merge($adminMembers)->unique();

        return User::whereIn('id', $adminIds)->get();
    }
}
