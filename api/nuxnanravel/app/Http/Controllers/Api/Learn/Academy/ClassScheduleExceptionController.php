<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\ClassSchedule;
use App\Models\ClassScheduleException;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ClassScheduleExceptionController extends Controller
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    private function validationErrorResponse(array $errors)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $errors,
        ], 422);
    }

    private function payload(ClassScheduleException $e): array
    {
        $e->loadMissing([
            'schedule.classroom:id,name',
            'schedule.course:id,name,code,academy_id',
            'schedule.teacher:id,name',
            'substituteTeacher:id,name',
        ]);

        $s = $e->schedule;

        return $e->toOverlayArray() + [
            'schedule' => [
                'id' => $s->id,
                'day_of_week' => $s->day_of_week,
                'day_name' => $s->day_name,
                'start_time' => $s->start_time->format('H:i'),
                'end_time' => $s->end_time->format('H:i'),
                'period_number' => $s->period_number,
                'display_title' => $s->display_title,
                'room' => $s->room,
                'classroom' => $s->classroom ? ['id' => $s->classroom->id, 'name' => $s->classroom->name] : null,
                'teacher' => $s->teacher ? ['id' => $s->teacher->id, 'name' => $s->teacher->name] : null,
                'course' => $s->course ? ['id' => $s->course->id, 'code' => $s->course->code, 'name' => $s->course->name] : null,
            ],
        ];
    }

    private function loadSchedule(Academy $academy, $scheduleId): ?ClassSchedule
    {
        return ClassSchedule::with('semester')
            ->where('academy_id', $academy->id)
            ->where('status', ClassSchedule::STATUS_ACTIVE)
            ->find($scheduleId);
    }

    private function dateErrors(ClassSchedule $s, string $date): ?array
    {
        $parsedDate = Carbon::parse($date);

        if ($parsedDate->dayOfWeekIso !== $s->day_of_week) {
            return ['date' => ['วันที่ที่เลือกไม่ใช่'.$s->day_name]];
        }

        if ($s->semester && $s->semester->start_date && $s->semester->end_date) {
            $dateOnly = substr($date, 0, 10);
            $startOnly = substr((string) $s->semester->start_date, 0, 10);
            $endOnly = substr((string) $s->semester->end_date, 0, 10);

            if ($dateOnly < $startOnly || $dateOnly > $endOnly) {
                return ['date' => ['วันที่อยู่นอกช่วงภาคเรียนของคาบนี้']];
            }
        }

        return null;
    }

    private function substituteErrors(Academy $academy, ClassSchedule $s, string $date, $teacherId): ?array
    {
        if ((int) $teacherId === (int) $s->teacher_id) {
            return ['substitute_teacher_id' => ['ครูผู้สอนแทนต้องไม่ใช่ครูประจำคาบ']];
        }

        $u = User::find($teacherId);
        if (! $u || (! $academy->isApprovedMember($u) && ! $academy->isAdmin($u))) {
            return ['substitute_teacher_id' => ['ครูผู้สอนแทนไม่ได้อยู่ในโรงเรียนนี้']];
        }

        $busyIds = ClassSchedule::busyTeacherIdsOn(
            $date,
            $s->semester_id,
            $s->day_of_week,
            $s->start_time->format('H:i'),
            $s->end_time->format('H:i'),
            $s->id
        );

        if (in_array((int) $teacherId, $busyIds, true)) {
            return ['substitute_teacher_id' => ['ครูผู้สอนแทนมีคาบอื่นในเวลานี้']];
        }

        return null;
    }

    public function index(Request $request, $academyId)
    {
        $academy = Academy::findOrFail($academyId);

        $validator = Validator::make($request->all(), [
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            'teacher_id' => 'nullable|integer',
            'classroom_id' => 'nullable|integer',
            'type' => 'nullable|in:cancelled,substitute,room_change',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $from = $request->from;
        $to = $request->to;

        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 62) {
            return $this->validationErrorResponse(['to' => ['ช่วงวันที่ต้องไม่เกิน 62 วัน']]);
        }

        $query = ClassScheduleException::where('academy_id', $academy->id)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->with([
                'schedule.classroom:id,name',
                'schedule.course:id,name,code,academy_id',
                'schedule.teacher:id,name',
                'substituteTeacher:id,name',
            ]);

        if ($request->filled('teacher_id')) {
            $tid = $request->teacher_id;
            $query->where(function ($q) use ($tid) {
                $q->where('substitute_teacher_id', $tid)
                    ->orWhereHas('schedule', function ($sq) use ($tid) {
                        $sq->where('teacher_id', $tid);
                    });
            });
        }

        if ($request->filled('classroom_id')) {
            $cid = $request->classroom_id;
            $query->whereHas('schedule', function ($sq) use ($cid) {
                $sq->where('classroom_id', $cid);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $exceptions = $query->get()->sortBy([
            ['date', 'asc'],
            [fn ($a) => $a->schedule->start_time->format('H:i:s'), 'asc'],
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $exceptions->map(fn ($e) => $this->payload($e)),
        ]);
    }

    public function availableTeachers(Request $request, $academyId)
    {
        $academy = Academy::findOrFail($academyId);

        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $s = $this->loadSchedule($academy, $request->schedule_id);
        if (! $s) {
            return $this->validationErrorResponse(['schedule_id' => ['ไม่พบคาบนี้ในโรงเรียน']]);
        }

        if ($errors = $this->dateErrors($s, $request->date)) {
            return $this->validationErrorResponse($errors);
        }

        $teachers = $academy->academyMembers()->where('status', 2)
            ->where(function ($q) {
                $q->where('role', 'teacher')
                    ->orWhereHas('academyRole', function ($r) {
                        $r->where('name', 'teacher');
                    });
            })
            ->with('user:id,name,profile_photo_path')
            ->get()
            ->map(fn ($m) => $m->user);

        $busyIds = ClassSchedule::busyTeacherIdsOn(
            $request->date,
            $s->semester_id,
            $s->day_of_week,
            $s->start_time->format('H:i'),
            $s->end_time->format('H:i'),
            $s->id
        );
        $busyIds[] = (int) $s->teacher_id;

        $availableTeachers = $teachers->filter(function ($t) use ($busyIds) {
            return ! in_array((int) $t->id, $busyIds, true);
        })->map(function ($t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'avatar' => $t->profile_photo_path,
            ];
        })->sortBy('name')->values();

        $busyCount = reset($teachers) ? ($teachers->count() - $availableTeachers->count()) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'teachers' => $availableTeachers,
                'busy_count' => $busyCount,
            ],
        ]);
    }

    public function store(Request $request, $academyId)
    {
        $academy = Academy::findOrFail($academyId);

        $validator = Validator::make($request->all(), [
            'class_schedule_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'type' => 'required|in:cancelled,substitute,room_change',
            'substitute_teacher_id' => 'nullable|integer|required_if:type,substitute',
            'room' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $s = $this->loadSchedule($academy, $request->class_schedule_id);
        if (! $s) {
            return $this->validationErrorResponse(['class_schedule_id' => ['ไม่พบคาบนี้ในโรงเรียน']]);
        }

        if ($errors = $this->dateErrors($s, $request->date)) {
            return $this->validationErrorResponse($errors);
        }

        $exists = ClassScheduleException::where('class_schedule_id', $s->id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return $this->validationErrorResponse(['date' => ['คาบนี้มีรายการของวันที่นี้อยู่แล้ว — แก้ไขหรือลบรายการเดิม']]);
        }

        if ($request->type === ClassScheduleException::TYPE_SUBSTITUTE) {
            if ($errors = $this->substituteErrors($academy, $s, $request->date, $request->substitute_teacher_id)) {
                return $this->validationErrorResponse($errors);
            }
        }

        $room = ClassSchedule::normalizeRoom($request->room);
        $substituteTeacherId = $request->substitute_teacher_id;

        if ($request->type === ClassScheduleException::TYPE_CANCELLED) {
            $substituteTeacherId = null;
            $room = null;
        } elseif ($request->type === ClassScheduleException::TYPE_ROOM_CHANGE) {
            $substituteTeacherId = null;
            if ($room === null) {
                return $this->validationErrorResponse(['room' => ['ต้องระบุห้องใหม่']]);
            }
        }

        $exception = ClassScheduleException::create([
            'academy_id' => $academy->id,
            'class_schedule_id' => $s->id,
            'date' => $request->date,
            'type' => $request->type,
            'substitute_teacher_id' => $substituteTeacherId,
            'room' => $room,
            'reason' => $request->reason,
            'created_by' => $request->user()->id,
        ]);

        $this->auditLogService->logCreate($exception, 'schedules');

        return response()->json([
            'success' => true,
            'message' => 'บันทึกสำเร็จ',
            'data' => $this->payload($exception),
        ], 201);
    }

    public function update(Request $request, $academyId, $exceptionId)
    {
        $academy = Academy::findOrFail($academyId);

        $exception = ClassScheduleException::where('academy_id', $academy->id)->find($exceptionId);
        if (! $exception) {
            return response()->json(['success' => false, 'message' => 'ไม่พบรายการ'], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:cancelled,substitute,room_change',
            'substitute_teacher_id' => 'nullable|integer|required_if:type,substitute',
            'room' => 'nullable|string|max:50|required_if:type,room_change',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $s = $this->loadSchedule($academy, $exception->class_schedule_id);

        if ($request->type === ClassScheduleException::TYPE_SUBSTITUTE) {
            if ($errors = $this->substituteErrors($academy, $s, $exception->dateString(), $request->substitute_teacher_id)) {
                return $this->validationErrorResponse($errors);
            }
        }

        $room = ClassSchedule::normalizeRoom($request->room);
        $substituteTeacherId = $request->substitute_teacher_id;

        if ($request->type === ClassScheduleException::TYPE_CANCELLED) {
            $substituteTeacherId = null;
            $room = null;
        } elseif ($request->type === ClassScheduleException::TYPE_ROOM_CHANGE) {
            $substituteTeacherId = null;
            if ($room === null) {
                return $this->validationErrorResponse(['room' => ['ต้องระบุห้องใหม่']]);
            }
        }

        $old = $exception->getOriginal();

        $exception->update([
            'type' => $request->type,
            'substitute_teacher_id' => $substituteTeacherId,
            'room' => $room,
            'reason' => $request->reason,
        ]);

        $this->auditLogService->logUpdate($exception, $old, 'schedules');

        return response()->json([
            'success' => true,
            'message' => 'บันทึกสำเร็จ',
            'data' => $this->payload($exception),
        ]);
    }

    public function destroy(Request $request, $academyId, $exceptionId)
    {
        $academy = Academy::findOrFail($academyId);

        $exception = ClassScheduleException::where('academy_id', $academy->id)->find($exceptionId);
        if (! $exception) {
            return response()->json(['success' => false, 'message' => 'ไม่พบรายการ'], 404);
        }

        $this->auditLogService->logDelete($exception, 'schedules');
        $exception->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบรายการแล้ว',
        ]);
    }
}
