<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ClassroomStudent;
use App\Models\Learn\Academy\SchoolAttendance;
use App\Models\Learn\Academy\SchoolAttendanceRecord;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use App\Services\PointsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolAttendanceController extends Controller
{
    // GET /academies/{academy}/school-attendances
    public function index(Academy $academy, Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:open,closed',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = SchoolAttendance::where('academy_id', $academy->id)
            ->withCount('records')
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        if ($request->date) {
            $query->whereDate('date', $request->date);
        }
        if ($request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $attendances = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $attendances,
        ]);
    }

    // POST /academies/{academy}/school-attendances  — สร้าง session + generate QR
    public function store(Academy $academy, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'title' => 'nullable|string|max:200',
            'start_time' => 'nullable|date_format:H:i',
            'late_minutes' => 'nullable|integer|min:0|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendance = SchoolAttendance::create([
            'academy_id' => $academy->id,
            'date' => $validated['date'],
            'title' => $validated['title'] ?? 'เช็คชื่อการมาโรงเรียน',
            'start_time' => $validated['start_time'] ?? null,
            'late_minutes' => $validated['late_minutes'] ?? 15,
            'notes' => $validated['notes'] ?? null,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        $qrToken = $attendance->generateQrToken(); // no TTL — valid for full session

        return response()->json([
            'success' => true,
            'message' => 'สร้างการเช็คชื่อสำเร็จ',
            'data' => $attendance,
            'qr_token' => $qrToken,
            'qr_content' => "CHECKIN:SCHOOL:{$academy->id}:{$attendance->id}:{$qrToken}",
        ], 201);
    }

    // POST /academies/{academy}/school-attendances/{attendance}/refresh-qr
    public function refreshQr(Academy $academy, SchoolAttendance $attendance): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        if (! $attendance->isOpen()) {
            return response()->json(['success' => false, 'message' => 'การเช็คชื่อนี้ปิดแล้ว ไม่สามารถต่ออายุ QR ได้'], 422);
        }

        // No TTL — token valid for entire session (session open/closed is the security gate)
        $token = $attendance->generateQrToken();

        return response()->json([
            'success' => true,
            'qr_token' => $token,
            'qr_content' => "CHECKIN:SCHOOL:{$academy->id}:{$attendance->id}:{$token}",
            'expires_at' => null,
        ]);
    }

    // GET /academies/{academy}/school-attendances/{attendance}
    public function show(Academy $academy, SchoolAttendance $attendance): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        $attendance->load([
            'records.student:id,name,profile_photo_path',
            'creator:id,name',
        ]);

        $this->enrichRecordsWithClassroomInfo($attendance->records, $academy->id);

        $summary = [
            'total' => $attendance->records->count(),
            'present' => $attendance->records->where('status', 'present')->count(),
            'absent' => $attendance->records->where('status', 'absent')->count(),
            'late' => $attendance->records->where('status', 'late')->count(),
            'leave' => $attendance->records->where('status', 'leave')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'summary' => $summary,
        ]);
    }

    // POST /academies/{academy}/school-attendances/{attendance}/check-in  — นักเรียน scan QR
    public function checkIn(Academy $academy, SchoolAttendance $attendance, Request $request): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        $validated = $request->validate([
            'qr_token' => 'required|string',
        ]);

        if (! $attendance->isOpen()) {
            return response()->json(['success' => false, 'message' => 'การเช็คชื่อนี้ปิดแล้ว'], 422);
        }

        if (! $attendance->isQrTokenValid($validated['qr_token'])) {
            return response()->json(['success' => false, 'message' => 'QR Code หมดอายุหรือไม่ถูกต้อง กรุณาสแกนใหม่'], 422);
        }

        $student = auth()->user();
        $alreadyChecked = SchoolAttendanceRecord::where('attendance_id', $attendance->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($alreadyChecked) {
            return response()->json(['success' => false, 'message' => 'คุณเช็คชื่อแล้ว'], 422);
        }

        // Determine status: present or late
        $status = 'present';
        if ($attendance->start_time) {
            $startTime = Carbon::parse($attendance->date->format('Y-m-d').' '.$attendance->start_time);
            $lateDeadline = $startTime->copy()->addMinutes($attendance->late_minutes);
            if (now()->gt($lateDeadline)) {
                $status = 'late';
            }
        }

        DB::transaction(function () use ($attendance, $student, $status, $academy) {
            SchoolAttendanceRecord::create([
                'attendance_id' => $attendance->id,
                'academy_id' => $academy->id,
                'student_id' => $student->id,
                'status' => $status,
                'check_in_method' => 'qr',
                'checked_in_at' => now(),
                'recorded_by' => $student->id,
            ]);

            app(PointsService::class)->awardByRule(
                $student,
                'school_attendance',
                $attendance->id,
                "เช็คชื่อมาโรงเรียนวันที่ {$attendance->date->format('d/m/Y')}",
                ['academy_id' => $academy->id]
            );

            // Phase M XP / Points
            if ($status !== 'absent') {
                app(\App\Services\Gamification\XpService::class)->award(
                    $academy,
                    'attendance.recorded',
                    config('xp_rates.school.attendance.recorded', 5),
                    $student->id,
                    null,
                    ['attendance_id' => $attendance->id, 'type' => 'school']
                );

                $classroom = \App\Models\AcademyGroup::where('academy_id', $academy->id)
                    ->where('type', 'classroom')
                    ->whereHas('members', function ($query) use ($student) {
                        $query->where('user_id', $student->id);
                    })
                    ->first();

                if ($classroom) {
                    app(\App\Services\Gamification\ClassroomPointsService::class)->award(
                        $classroom,
                        'attendance.checkin',
                        config('xp_rates.classroom.attendance.checkin', 1),
                        $student->id,
                        ['attendance_id' => $attendance->id]
                    );
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $status === 'late' ? 'บันทึกการมาสาย' : 'เช็คชื่อสำเร็จ',
            'status' => $status,
        ]);
    }

    // POST /academies/{academy}/school-attendances/{attendance}/records  — ครูบันทึกแบบ bulk
    public function storeRecords(Academy $academy, SchoolAttendance $attendance, Request $request): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        $validated = $request->validate([
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:users,id',
            'records.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'leave'])],
            'records.*.remark' => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($attendance, $academy, $validated) {
            $pointsService = app(PointsService::class);

            foreach ($validated['records'] as $record) {
                SchoolAttendanceRecord::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'student_id' => $record['student_id'],
                    ],
                    [
                        'academy_id' => $academy->id,
                        'status' => $record['status'],
                        'check_in_method' => 'manual',
                        'checked_in_at' => $record['status'] !== 'absent' ? now() : null,
                        'remark' => $record['remark'] ?? null,
                        'recorded_by' => auth()->id(),
                    ]
                );

                if (in_array($record['status'], ['present', 'late'])) {
                    $student = User::find($record['student_id']);
                    if ($student) {
                        if ($record['status'] === 'present') {
                            $pointsService->awardByRule(
                                $student,
                                'school_attendance',
                                $attendance->id,
                                "เช็คชื่อมาโรงเรียนวันที่ {$attendance->date->format('d/m/Y')}",
                                ['academy_id' => $academy->id]
                            );
                        }

                        // Phase M XP / Points
                        app(\App\Services\Gamification\XpService::class)->award(
                            $academy,
                            'attendance.recorded',
                            config('xp_rates.school.attendance.recorded', 5),
                            $student->id,
                            null,
                            ['attendance_id' => $attendance->id, 'type' => 'school']
                        );

                        $classroom = \App\Models\AcademyGroup::where('academy_id', $academy->id)
                            ->where('type', 'classroom')
                            ->whereHas('members', function ($query) use ($student) {
                                $query->where('user_id', $student->id);
                            })
                            ->first();

                        if ($classroom) {
                            app(\App\Services\Gamification\ClassroomPointsService::class)->award(
                                $classroom,
                                'attendance.checkin',
                                config('xp_rates.classroom.attendance.checkin', 1),
                                $student->id,
                                ['attendance_id' => $attendance->id]
                            );
                        }
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการเช็คชื่อสำเร็จ',
        ]);
    }

    // POST /academies/{academy}/school-attendances/{attendance}/scan-student
    public function scanStudent(Academy $academy, SchoolAttendance $attendance, Request $request): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        $validated = $request->validate([
            'identifier' => 'required|string|max:50',
            'scan_method' => 'nullable|string|in:qr,manual',
        ]);

        if (! $attendance->isOpen()) {
            return response()->json(['success' => false, 'message' => 'การเช็คชื่อนี้ปิดแล้ว'], 422);
        }

        $identifier = trim($validated['identifier']);
        $scanMethod = $validated['scan_method'] ?? 'manual';
        $userId = null;
        $studentName = null;
        $studentPhoto = null;

        // --- Strategy 1: member_code (numeric) ---
        if (is_numeric($identifier)) {
            $member = AcademyMember::where('academy_id', $academy->id)
                ->where('member_code', (int) $identifier)
                ->with('user:id,name,profile_photo_path')
                ->first();

            if ($member && $member->user_id) {
                $userId = $member->user_id;
                $studentName = $member->user?->name;
                $studentPhoto = $member->user?->profile_photo_path;
            }
        }

        // --- Strategy 2: student_number from student_cards ---
        if (! $userId) {
            $card = StudentCard::where('academy_id', $academy->id)
                ->where('student_number', $identifier)
                ->first();

            if ($card) {
                $student = Student::where('student_id', $card->student_number)
                    ->orWhere('citizen_id', $card->national_id)
                    ->first();

                if ($student) {
                    $member = AcademyMember::where('academy_id', $academy->id)
                        ->where('student_id', $student->id)
                        ->with('user:id,name,profile_photo_path')
                        ->first();

                    if ($member && $member->user_id) {
                        $userId = $member->user_id;
                        $studentName = $member->user?->name ?? $card->full_name_thai;
                        $studentPhoto = $member->user?->profile_photo_path;
                    }
                }

                // Fallback: name from card even without user link
                if (! $userId) {
                    $studentName = $card->full_name_thai;
                }
            }
        }

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบนักเรียนจากรหัส "'.$identifier.'" ในโรงเรียนนี้',
                'identifier' => $identifier,
            ], 404);
        }

        // Check already recorded
        $existing = SchoolAttendanceRecord::where('attendance_id', $attendance->id)
            ->where('student_id', $userId)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'already_checked' => true,
                'message' => ($studentName ?? 'นักเรียน').' เช็คชื่อแล้ว ('.$existing->status.')',
                'record' => $existing,
                'student_name' => $studentName,
                'student_photo' => $studentPhoto,
            ], 422);
        }

        // Determine status: present or late
        $status = 'present';
        if ($attendance->start_time) {
            $startTime = Carbon::parse($attendance->date->format('Y-m-d').' '.$attendance->start_time);
            $lateDeadline = $startTime->copy()->addMinutes($attendance->late_minutes);
            if (now()->gt($lateDeadline)) {
                $status = 'late';
            }
        }

        $record = DB::transaction(function () use ($attendance, $academy, $userId, $status, $scanMethod) {
            $rec = SchoolAttendanceRecord::create([
                'attendance_id' => $attendance->id,
                'academy_id' => $academy->id,
                'student_id' => $userId,
                'status' => $status,
                'check_in_method' => $scanMethod,
                'checked_in_at' => now(),
                'recorded_by' => auth()->id(),
            ]);

            $student = User::find($userId);
            if ($student) {
                app(PointsService::class)->awardByRule(
                    $student,
                    'school_attendance',
                    $attendance->id,
                    "เช็คชื่อมาโรงเรียนวันที่ {$attendance->date->format('d/m/Y')}",
                    ['academy_id' => $academy->id]
                );
            }

            return $rec;
        });

        [$studentNumber, $classroomName] = $this->resolveStudentClassroomInfo($userId, $academy->id);

        return response()->json([
            'success' => true,
            'message' => ($studentName ?? 'นักเรียน').' — '.($status === 'late' ? 'มาสาย' : 'มาเรียน'),
            'status' => $status,
            'record' => $record,
            'student_name' => $studentName,
            'student_photo' => $studentPhoto,
            'student_number' => $studentNumber,
            'classroom_name' => $classroomName,
        ]);
    }

    // POST /academies/{academy}/school-attendances/{attendance}/close
    public function close(Academy $academy, SchoolAttendance $attendance): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        if (! $attendance->isOpen()) {
            return response()->json(['success' => false, 'message' => 'ปิดการเช็คชื่อนี้แล้ว'], 422);
        }

        $attendance->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ปิดการเช็คชื่อเรียบร้อย',
        ]);
    }

    // GET /academies/{academy}/school-attendances/student/{student}
    public function studentHistory(Academy $academy, User $student, Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = SchoolAttendanceRecord::where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->with('attendance:id,date,title,status')
            ->orderByDesc('created_at');

        if ($request->date_from) {
            $query->whereHas('attendance', fn ($q) => $q->whereDate('date', '>=', $request->date_from));
        }
        if ($request->date_to) {
            $query->whereHas('attendance', fn ($q) => $q->whereDate('date', '<=', $request->date_to));
        }

        $records = $query->paginate($request->per_page ?? 30);
        $summary = SchoolAttendanceRecord::where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => $records,
            'summary' => $summary,
        ]);
    }

    private function authorizeAttendance(Academy $academy, SchoolAttendance $attendance): void
    {
        abort_if($attendance->academy_id !== $academy->id, 404);
    }

    // Enrich a collection of SchoolAttendanceRecord with student_number and classroom_name.
    // Uses 2 batched queries (no N+1): AcademyMember → ClassroomStudent → Classroom.
    private function enrichRecordsWithClassroomInfo(\Illuminate\Support\Collection $records, int $academyId): void
    {
        $userIds = $records->pluck('student_id')->filter()->unique()->values();
        if ($userIds->isEmpty()) {
            return;
        }

        // user_id → member_code + student FK
        $memberMap = AcademyMember::where('academy_id', $academyId)
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'member_code', 'student_id'])
            ->keyBy('user_id');

        // student FK → student_number + classroom name
        $studentFkIds = $memberMap->pluck('student_id')->filter()->unique();
        $classroomMap = collect();
        if ($studentFkIds->isNotEmpty()) {
            $classroomMap = ClassroomStudent::where('academy_id', $academyId)
                ->whereIn('student_id', $studentFkIds)
                ->where('status', ClassroomStudent::STATUS_ACTIVE)
                ->with('classroom:id,name')
                ->get(['student_id', 'classroom_id', 'student_number'])
                ->keyBy('student_id');
        }

        $records->each(function ($record) use ($memberMap, $classroomMap) {
            $member = $memberMap->get($record->student_id);
            $record->member_code = $member?->member_code;

            $cs = $member?->student_id ? $classroomMap->get($member->student_id) : null;
            $record->student_number = $cs?->student_number;
            $record->classroom_name = $cs?->classroom?->name;
        });
    }

    // Resolve classroom info for a single user — used in real-time scan responses.
    private function resolveStudentClassroomInfo(int $userId, int $academyId): array
    {
        $member = AcademyMember::where('academy_id', $academyId)
            ->where('user_id', $userId)
            ->value('student_id');

        if (! $member) {
            return [null, null];
        }

        $cs = ClassroomStudent::where('academy_id', $academyId)
            ->where('student_id', $member)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->with('classroom:id,name')
            ->first(['student_id', 'classroom_id', 'student_number']);

        return [$cs?->student_number, $cs?->classroom?->name];
    }
}
