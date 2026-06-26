<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\ActivityAttendance;
use App\Models\ActivityEnrollment;
use App\Models\ActivitySession;
use App\Models\SchoolEvent;
use App\Services\StudentIdentifierResolver;
use App\Traits\ManagesEventPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Check-in for ActivitySession (semester/recurring activities — e.g. ชมรม, ลูกเสือ,
 * เช็คชื่อละหมาดรายวัน). For one-time events use SchoolEventController::markAttendance().
 */
class ActivitySessionController extends Controller
{
    use ManagesEventPermissions;

    // GET /academies/{academy}/events/{event}/sessions/{session}
    public function show(Request $request, Academy $academy, SchoolEvent $event, ActivitySession $session): JsonResponse
    {
        $this->authorizeSession($event, $session);
        $this->authorizeManager($request, $academy, $event);

        $session->load('event:id,title,attendance_pattern');

        $summary = [
            'total' => $session->attendances()->count(),
            'present' => $session->attendances()->where('status', 'present')->count(),
            'absent' => $session->attendances()->where('status', 'absent')->count(),
            'late' => $session->attendances()->where('status', 'late')->count(),
        ];

        return response()->json(['success' => true, 'data' => $session, 'summary' => $summary]);
    }

    // POST /academies/{academy}/events/{event}/sessions/{session}/refresh-qr
    public function refreshQr(Request $request, Academy $academy, SchoolEvent $event, ActivitySession $session): JsonResponse
    {
        $this->authorizeSession($event, $session);
        $this->authorizeManager($request, $academy, $event);

        $token = $session->generateQrToken();

        return response()->json([
            'success' => true,
            'qr_token' => $token,
            'qr_content' => "CHECKIN:ACTIVITY:{$academy->id}:{$session->id}:{$token}",
        ]);
    }

    // POST /academies/{academy}/events/{event}/sessions/{session}/check-in — นักเรียนสแกน QR ด้วยตนเอง
    public function checkIn(Academy $academy, SchoolEvent $event, ActivitySession $session, Request $request): JsonResponse
    {
        $this->authorizeSession($event, $session);

        $validated = $request->validate([
            'qr_token' => 'required|string',
        ]);

        if (! $session->isQrTokenValid($validated['qr_token'])) {
            return response()->json(['success' => false, 'message' => 'QR Code หมดอายุหรือไม่ถูกต้อง กรุณาสแกนใหม่'], 422);
        }

        $user = $request->user();
        $enrollment = $this->resolveEnrollment($event, $user->id);

        if (! $enrollment) {
            return response()->json(['success' => false, 'message' => 'คุณไม่ได้ลงทะเบียนกิจกรรมนี้'], 422);
        }

        return $this->recordAttendance($session, $enrollment, $user->id, 'present', 'qr_code', $user->id);
    }

    // POST /academies/{academy}/events/{event}/sessions/{session}/scan — สแกนบัตรนักเรียน หรือป้อนเลขประจำตัว (ครู/แอดมิน)
    public function scanStudent(Academy $academy, SchoolEvent $event, ActivitySession $session, Request $request): JsonResponse
    {
        $this->authorizeSession($event, $session);
        $this->authorizeManager($request, $academy, $event);

        $validated = $request->validate([
            'identifier' => 'required|string|max:50',
            'scan_method' => 'nullable|string|in:qr,manual',
        ]);

        $identifier = trim($validated['identifier']);
        $scanMethod = $validated['scan_method'] ?? 'manual';

        $resolved = app(StudentIdentifierResolver::class)->resolve($academy->id, $identifier);

        if (! $resolved['user_id']) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบนักเรียนจากรหัส "'.$identifier.'" ในโรงเรียนนี้',
            ], 404);
        }

        $enrollment = $this->resolveEnrollment($event, $resolved['user_id']);

        if (! $enrollment) {
            return response()->json([
                'success' => false,
                'message' => ($resolved['student_name'] ?? 'นักเรียน').' ไม่ได้ลงทะเบียนกิจกรรมนี้',
            ], 422);
        }

        return $this->recordAttendance(
            $session,
            $enrollment,
            $resolved['user_id'],
            'present',
            $scanMethod === 'qr' ? 'qr_code' : 'manual',
            auth()->id(),
            $resolved['student_name'],
            $resolved['student_photo'],
        );
    }

    // POST /academies/{academy}/events/{event}/sessions/{session}/records — ครูบันทึกแบบ bulk
    public function storeRecords(Academy $academy, SchoolEvent $event, ActivitySession $session, Request $request): JsonResponse
    {
        $this->authorizeSession($event, $session);
        $this->authorizeManager($request, $academy, $event);

        $validated = $request->validate([
            'records' => 'required|array|min:1',
            'records.*.user_id' => 'required|exists:users,id',
            'records.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'leave', 'activity_leave'])],
            'records.*.remarks' => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($validated, $event, $session) {
            foreach ($validated['records'] as $record) {
                $enrollment = $this->resolveEnrollment($event, $record['user_id']);
                if (! $enrollment) {
                    continue;
                }

                $this->upsertAttendance(
                    $session,
                    $enrollment,
                    $record['user_id'],
                    $record['status'],
                    'manual',
                    auth()->id(),
                    $record['remarks'] ?? null,
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'บันทึกการเช็คชื่อสำเร็จ']);
    }

    // GET /academies/{academy}/events/{event}/enrollments/{enrollment}/attendance-summary
    // สรุปการเข้าร่วม รายวัน/สัปดาห์/เดือน/ภาคเรียน ของผู้ลงทะเบียนคนหนึ่ง
    public function attendanceSummary(Academy $academy, SchoolEvent $event, ActivityEnrollment $enrollment, Request $request): JsonResponse
    {
        abort_if($enrollment->event_id !== $event->id, 404);

        // The enrolled student may view their own summary; otherwise must manage the event.
        if ($enrollment->user_id !== $request->user()->id) {
            $this->authorizeManager($request, $academy, $event);
        }

        $period = $request->get('period', 'monthly'); // daily, weekly, monthly, semester

        $groupExpr = match ($period) {
            'daily' => 'DATE(activity_sessions.start_datetime)',
            'weekly' => "CONCAT(YEAR(activity_sessions.start_datetime), '-W', WEEK(activity_sessions.start_datetime))",
            'semester' => 'enrollment_id', // single bucket — whole semester totals
            default => "DATE_FORMAT(activity_sessions.start_datetime, '%Y-%m')",
        };

        $rows = ActivityAttendance::query()
            ->join('activity_sessions', 'activity_sessions.id', '=', 'activity_attendances.session_id')
            ->where('activity_attendances.enrollment_id', $enrollment->id)
            ->selectRaw("{$groupExpr} as period_key, status, COUNT(*) as count")
            ->groupBy('period_key', 'status')
            ->orderBy('period_key')
            ->get();

        $buckets = [];
        foreach ($rows as $row) {
            $buckets[$row->period_key][$row->status] = $row->count;
        }

        return response()->json([
            'success' => true,
            'period' => $period,
            'data' => $buckets,
            'totals' => [
                'attendance_count' => $enrollment->attendance_count,
                'absence_count' => $enrollment->absence_count,
            ],
        ]);
    }

    private function resolveEnrollment(SchoolEvent $event, int $userId): ?ActivityEnrollment
    {
        return ActivityEnrollment::where('event_id', $event->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }

    private function recordAttendance(
        ActivitySession $session,
        ActivityEnrollment $enrollment,
        int $userId,
        string $status,
        string $method,
        ?int $recordedBy,
        ?string $studentName = null,
        ?string $studentPhoto = null,
    ): JsonResponse {
        $existing = ActivityAttendance::where('session_id', $session->id)
            ->where('enrollment_id', $enrollment->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'already_checked' => true,
                'message' => ($studentName ?? 'นักเรียน').' เช็คชื่อแล้ว ('.$existing->status.')',
                'record' => $existing,
            ], 422);
        }

        $record = $this->upsertAttendance($session, $enrollment, $userId, $status, $method, $recordedBy);

        return response()->json([
            'success' => true,
            'message' => ($studentName ?? 'นักเรียน').' — เช็คชื่อสำเร็จ',
            'record' => $record,
            'student_name' => $studentName,
            'student_photo' => $studentPhoto,
        ]);
    }

    private function upsertAttendance(
        ActivitySession $session,
        ActivityEnrollment $enrollment,
        int $userId,
        string $status,
        string $method,
        ?int $recordedBy,
        ?string $remarks = null,
    ): ActivityAttendance {
        return DB::transaction(function () use ($session, $enrollment, $userId, $status, $method, $recordedBy, $remarks) {
            $record = ActivityAttendance::updateOrCreate(
                ['session_id' => $session->id, 'enrollment_id' => $enrollment->id],
                [
                    'user_id' => $userId,
                    'status' => $status,
                    'check_in_time' => in_array($status, ['present', 'late']) ? now() : null,
                    'check_in_method' => $method,
                    'remarks' => $remarks,
                    'recorded_by' => $recordedBy,
                ]
            );

            $enrollment->update([
                'attendance_count' => $enrollment->attendances()->whereIn('status', ['present', 'late'])->count(),
                'absence_count' => $enrollment->attendances()->where('status', 'absent')->count(),
            ]);

            return $record;
        });
    }

    private function authorizeSession(SchoolEvent $event, ActivitySession $session): void
    {
        abort_if($session->event_id !== $event->id, 404);
    }

    private function authorizeManager(Request $request, Academy $academy, SchoolEvent $event): void
    {
        abort_unless($this->canManageEvent($request->user(), $academy, $event->group_id), 403, 'Unauthorized');
    }
}
