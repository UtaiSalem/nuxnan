<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\EligibilityAuditLog;
use App\Models\ExamEligibilityOverride;
use App\Services\AttendanceEligibilityService;
use App\Services\PointsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamEligibilityController extends Controller
{
    use AuthorizesRequests;

    protected AttendanceEligibilityService $eligibilityService;

    protected PointsService $pointsService;

    public function __construct(AttendanceEligibilityService $eligibilityService, PointsService $pointsService)
    {
        $this->eligibilityService = $eligibilityService;
        $this->pointsService = $pointsService;
    }

    public function getStatus(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $member = $course->courseMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        $result = $this->eligibilityService->canTakeExam($member);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function getMyStatus(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $member = $course->courseMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 403);
        }

        // Sync status with DB to keep cached values accurate
        $this->eligibilityService->updateEligibilityStatus($member);
        $member->refresh();

        $result = $this->eligibilityService->canTakeExam($member);

        return response()->json([
            'success' => true,
            'data' => [
                'can_take_exam' => $result['can_take_exam'],
                'status' => $result['eligibility_status'],
                'reason' => implode(', ', $result['reasons']),
                'absence_percent' => $result['attendance_stats']['absence_rate'] ?? null,
                'unlock_options' => $result['unlock_options'],
            ],
        ]);
    }

    public function readingProgress(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $member = $course->courseMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 403);
        }

        $progress = $this->eligibilityService->getReadingLessonProgress($member);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    public function getCourseSummary(Request $request, Course $course): JsonResponse
    {
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $summary = $this->eligibilityService->getCourseEligibilitySummary($course);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'ไม่สามารถโหลดข้อมูลสรุปสิทธิ์สอบได้',
                'error' => config('app.debug')
                    ? [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                    : null,
            ], 500);
        }
    }

    public function refreshCourseEligibility(Request $request, Course $course): JsonResponse
    {
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $results = $this->eligibilityService->updateCourseEligibility($course);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสถานะสิทธิ์สอบเรียบร้อย',
            'data' => $results,
        ]);
    }

    // ─── Self unlock ───

    public function requestSelfUnlock(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $member = $course->courseMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        // Sync status with DB before checking to avoid stale eligibility state
        $this->eligibilityService->updateEligibilityStatus($member);
        $member->refresh();

        if ($member->eligibility_status === 'unlocked' || $member->exam_eligible) {
            return response()->json([
                'success' => false,
                'message' => 'คุณมีสิทธิ์สอบอยู่แล้ว',
            ], 400);
        }

        try {
            $override = $this->eligibilityService->requestUnlockBySelf($member);

            return response()->json([
                'success' => true,
                'message' => 'ปลดล็อคสิทธิ์สอบสำเร็จ',
                'data' => $override,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // ─── Points unlock ───

    public function requestPointsUnlock(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $member = $course->courseMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        // Sync status with DB before checking to avoid stale eligibility state
        $this->eligibilityService->updateEligibilityStatus($member);
        $member->refresh();

        if ($member->eligibility_status === 'unlocked' || $member->exam_eligible) {
            return response()->json([
                'success' => false,
                'message' => 'คุณมีสิทธิ์สอบอยู่แล้ว',
            ], 400);
        }

        $pointsCost = $course->unlock_points_cost ?? 0;

        if ($pointsCost > 0) {
            $balance = $this->pointsService->getBalance($user);
            if ($balance['current_points'] < $pointsCost) {
                return response()->json([
                    'success' => false,
                    'message' => 'คะแนนสะสมไม่เพียงพอสำหรับการปลดล็อค',
                    'data' => [
                        'required' => $pointsCost,
                        'current_balance' => $balance['current_points'],
                    ],
                ], 400);
            }
        }

        try {
            return DB::transaction(function () use ($member, $course, $user, $pointsCost) {
                $override = $this->eligibilityService->requestUnlockByPoints($member);

                if ($pointsCost > 0) {
                    $transaction = $this->pointsService->spend(
                        $user,
                        $pointsCost,
                        'course_exam_unlock',
                        $course->id,
                        "ปลดล็อคสิทธิ์สอบวิชา {$course->name}"
                    );

                    if (! $transaction) {
                        throw new \Exception('ไม่สามารถตัดคะแนนสะสมได้');
                    }

                    $override = $this->eligibilityService->processPointsUnlock($override, $transaction->id);
                } else {
                    $override = $this->eligibilityService->processPointsUnlock($override, 0);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'ปลดล็อคสิทธิ์สอบสำเร็จ',
                    'data' => $override,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // ─── Reading unlock ───

    public function requestReadingUnlock(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $member = $course->courseMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        try {
            $override = $this->eligibilityService->requestUnlockByReading($member);

            return response()->json([
                'success' => true,
                'message' => 'เริ่มการปลดล็อคด้วยการอ่าน',
                'data' => [
                    'override' => $override,
                    'required_minutes' => $course->unlock_reading_minutes,
                    'completed_minutes' => 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function updateReadingProgress(Request $request, ExamEligibilityOverride $override): JsonResponse
    {
        $request->validate([
            'minutes' => 'required|integer|min:1|max:60',
            'content_type' => 'required|string',
            'content_id' => 'required',
        ]);

        if ($override->student_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์',
            ], 403);
        }

        if ($override->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'คำขอนี้ไม่อยู่ในสถานะรอดำเนินการ',
            ], 400);
        }

        $proof = [
            'content_type' => $request->content_type,
            'content_id' => $request->content_id,
            'minutes' => $request->minutes,
            'timestamp' => now()->toIso8601String(),
        ];

        $override = $this->eligibilityService->updateReadingProgress(
            $override,
            $request->minutes,
            $proof
        );

        $course = $override->course;

        return response()->json([
            'success' => true,
            'message' => $override->status === 'approved' ? 'ปลดล็อคสิทธิ์สอบสำเร็จ!' : 'บันทึกความก้าวหน้าแล้ว',
            'data' => [
                'override' => $override,
                'required_minutes' => $course->unlock_reading_minutes,
                'completed_minutes' => $override->reading_minutes_completed,
                'is_completed' => $override->status === 'approved',
            ],
        ]);
    }

    // ─── Appeal unlock ───

    public function requestAppealUnlock(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'evidence' => 'nullable|array',
            'evidence.*.type' => 'required_with:evidence|string',
            'evidence.*.description' => 'required_with:evidence|string',
            'evidence.*.url' => 'nullable|string',
        ]);

        $user = $request->user();

        $member = $course->courseMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        // Sync status with DB before checking to avoid stale eligibility state
        $this->eligibilityService->updateEligibilityStatus($member);
        $member->refresh();

        if ($member->eligibility_status === 'unlocked' || $member->exam_eligible) {
            return response()->json([
                'success' => false,
                'message' => 'คุณมีสิทธิ์สอบอยู่แล้ว',
            ], 400);
        }

        try {
            $override = $this->eligibilityService->requestUnlockByAppeal(
                $member,
                $request->reason,
                $request->evidence
            );

            return response()->json([
                'success' => true,
                'message' => 'ส่งคำขออุทธรณ์สิทธิ์สอบเรียบร้อย กรุณารอผู้สอนพิจารณา',
                'data' => $override,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function addAppealEvidence(Request $request, ExamEligibilityOverride $override): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'description' => 'required|string|max:500',
            'url' => 'nullable|string',
        ]);

        if ($override->student_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403);
        }

        if ($override->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'คำขอนี้ไม่อยู่ในสถานะรอดำเนินการ'], 400);
        }

        $override = $this->eligibilityService->addAppealEvidence($override, [
            'type' => $request->type,
            'description' => $request->description,
            'url' => $request->url,
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มหลักฐานเรียบร้อย',
            'data' => $override,
        ]);
    }

    // ─── Admin: Pending requests ───

    public function getPendingRequests(Request $request, Course $course): JsonResponse
    {
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $requests = ExamEligibilityOverride::where('course_id', $course->id)
            ->where('status', 'pending')
            ->with(['student:id,name,profile_photo_path,email', 'courseMember'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    // ─── Admin: Approve / Reject ───

    public function approveRequest(Request $request, ExamEligibilityOverride $override): JsonResponse
    {
        $course = $override->course;
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $override = $this->eligibilityService->adminApprove(
            $override,
            $request->user(),
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'อนุมัติการปลดล็อคสำเร็จ',
            'data' => $override,
        ]);
    }

    public function rejectRequest(Request $request, ExamEligibilityOverride $override): JsonResponse
    {
        $course = $override->course;
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $override = $this->eligibilityService->adminReject(
            $override,
            $request->user(),
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคำขอปลดล็อค',
            'data' => $override,
        ]);
    }

    // ─── Admin: Direct unlock ───

    public function adminUnlock(Request $request, Course $course, CourseMember $member): JsonResponse
    {
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        abort_if($member->course_id !== $course->id, 403, 'นักเรียนคนนี้ไม่ได้อยู่ในรายวิชานี้');

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $stats = $this->eligibilityService->calculateAttendanceStats($member);
        $reason = $request->reason ?? 'ปลดล็อคโดยผู้สอน';

        $override = ExamEligibilityOverride::create([
            'course_member_id' => $member->id,
            'course_id' => $course->id,
            'student_id' => $member->user_id,
            'unlock_method' => 'admin',
            'status' => 'approved',
            'approved_by' => $request->user()->id,
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
            $request->user()->id,
            $reason,
            ['override_id' => $override->id]
        );

        $member->update([
            'exam_eligible' => true,
            'eligibility_status' => 'unlocked',
            'eligibility_unlocked_at' => now(),
            'eligibility_unlock_method' => 'admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ปลดล็อคสิทธิ์สอบสำเร็จ',
            'data' => $override,
        ]);
    }

    // ─── Bulk operations ───

    public function bulkUnlock(Request $request, Course $course): JsonResponse
    {
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:course_members,id',
            'group_id' => 'nullable|integer|exists:course_groups,id',
            'only_ineligible' => 'nullable|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $query = CourseMember::where('course_id', $course->id)->whereIn('role', [1, 2]);

        // Resolve target members from group_id / member_ids / whole course
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        } elseif ($request->has('member_ids')) {
            $requestedIds = array_filter((array) $request->input('member_ids', []));

            if (empty($requestedIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบสมาชิกที่ต้องการปลดล็อค',
                ], 422);
            }

            $query->whereIn('id', $requestedIds);
        } elseif (! $request->boolean('only_ineligible')) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิกที่ต้องการปลดล็อค',
            ], 422);
        }

        $members = $query->get();

        if ($members->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิกที่ต้องการปลดล็อค',
            ], 422);
        }

        // Filter to only ineligible members when requested.
        // ต้องคำนวณสถานะใหม่ก่อน เพราะคอลัมน์ eligibility_status ใน DB
        // ค้างค่าเก่าได้ (default 'eligible') ทำให้กรองคนที่หมดสิทธิ์จริงหลุดออกไปเงียบๆ
        if ($request->boolean('only_ineligible')) {
            $stats = $this->eligibilityService->syncEligibilityForMembers($course, $members);

            $members = $members->filter(
                fn (CourseMember $member) => ($stats[$member->id]['eligibility_status'] ?? null) === 'ineligible'
            );

            if ($members->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ไม่มีสมาชิกที่หมดสิทธิ์สอบในรายการนี้',
                    'data' => ['success' => 0, 'skipped' => 0, 'errors' => []],
                ]);
            }
        }

        $reason = $request->reason ?? 'ปลดล็อคโดยผู้สอน (รายกลุ่ม)';

        $results = $this->eligibilityService->bulkUnlock(
            $course,
            $members->pluck('id')->all(),
            $request->user(),
            $reason
        );

        $message = sprintf('ปลดล็อคสำเร็จ %d คน, ข้าม %d คน', $results['success'], $results['skipped']);

        if (! empty($results['errors'])) {
            $message .= sprintf(', ผิดพลาด %d คน', count($results['errors']));
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $results,
        ]);
    }

    public function bulkRevoke(Request $request, Course $course): JsonResponse
    {
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'integer|exists:course_members,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $reason = $request->reason ?? 'ยกเลิกการปลดล็อคโดยผู้สอน';

        $results = $this->eligibilityService->bulkRevoke(
            $course,
            $request->member_ids,
            $request->user(),
            $reason
        );

        return response()->json([
            'success' => true,
            'message' => sprintf('เพิกถอนสิทธิ์สำเร็จ %d คน, ข้าม %d คน', $results['success'], $results['skipped']),
            'data' => $results,
        ]);
    }

    // ─── Audit log ───

    public function getAuditLog(Request $request, Course $course): JsonResponse
    {
        if (! $course->isAdmin($request->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $memberId = $request->query('member_id');
        $limit = min((int) ($request->query('limit', 50)), 200);

        $logs = $this->eligibilityService->getAuditLog($course, $memberId, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
