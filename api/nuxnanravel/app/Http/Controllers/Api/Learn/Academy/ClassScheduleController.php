<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Semester;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassScheduleController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Get all schedules for an academy's semester
     */
    public function index(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        $query = ClassSchedule::with([
            'course:id,name,code,academy_id',
            'teacher:id,name,profile_photo_path',
            'classroom:id,name,grade_level,section',
        ])
            ->byAcademy($academy->id)
            ->active();

        // Filter by semester
        if ($request->filled('semester_id')) {
            $query->bySemester($request->semester_id);
        } else {
            // Default to current semester
            $currentSemester = Semester::currentForAcademy($academy->id);
            if ($currentSemester) {
                $query->bySemester($currentSemester->id);
            }
        }

        // Filter by classroom
        if ($request->filled('classroom_id')) {
            $query->byClassroom($request->classroom_id);
        }

        // Filter by teacher
        if ($request->filled('teacher_id')) {
            $query->byTeacher($request->teacher_id);
        }

        // Filter by day
        if ($request->filled('day_of_week')) {
            $query->byDay($request->day_of_week);
        }

        // Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'day_name' => $schedule->day_name,
                    'day_name_short' => $schedule->day_name_short,
                    'start_time' => $schedule->start_time->format('H:i'),
                    'end_time' => $schedule->end_time->format('H:i'),
                    'time_range' => $schedule->time_range,
                    'period_number' => $schedule->period_number,
                    'room' => $schedule->room,
                    'status' => $schedule->status,
                    'entry_type' => $schedule->entry_type,
                    // `title` คือค่าที่เก็บจริงในคอลัมน์ (อาจว่าง) ส่วน `display_title` คือค่าที่พร้อมแสดง
                    // แยกกันเพื่อไม่ให้ฟอร์มแก้ไขเผลอคัดลอกชื่อคอร์สลงคอลัมน์ title
                    'title' => $schedule->title,
                    'display_title' => $schedule->display_title,
                    'course' => $schedule->course ? [
                        'id' => $schedule->course->id,
                        'code' => $schedule->course->code,
                        'name' => $schedule->course->name,
                    ] : null,
                    'teacher' => $schedule->teacher ? [
                        'id' => $schedule->teacher->id,
                        'name' => $schedule->teacher->name,
                        'avatar' => $schedule->teacher->avatar,
                    ] : null,
                    'classroom' => $schedule->classroom,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    /**
     * Get timetable view (grouped by day and time)
     */
    public function timetable(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        $request->validate([
            'classroom_id' => [
                'required_without:teacher_id',
                Rule::exists('classrooms', 'id')->where('academy_id', $academy->id),
            ],
            'teacher_id' => 'required_without:classroom_id|exists:users,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ]);

        $query = ClassSchedule::with([
            'course:id,name,code,academy_id',
            'teacher:id,name,profile_photo_path',
            'classroom:id,name,grade_level,section',
        ])
            ->byAcademy($academy->id)
            ->active();

        // Semester filter
        if ($request->filled('semester_id')) {
            $query->bySemester($request->semester_id);
        } else {
            $currentSemester = Semester::currentForAcademy($academy->id);
            if ($currentSemester) {
                $query->bySemester($currentSemester->id);
            }
        }

        // By classroom or teacher
        if ($request->filled('classroom_id')) {
            $query->byClassroom($request->classroom_id);
            $viewType = 'classroom';
            $viewEntity = Classroom::find($request->classroom_id);
        } else {
            $query->byTeacher($request->teacher_id);
            $viewType = 'teacher';
            $viewEntity = User::find($request->teacher_id);
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Group by day
        $timetable = [];
        for ($day = 1; $day <= 7; $day++) {
            $daySchedules = $schedules->where('day_of_week', $day)->values();
            if ($daySchedules->isNotEmpty()) {
                $timetable[$day] = [
                    'day' => $day,
                    'day_name' => ClassSchedule::DAYS[$day],
                    'day_name_short' => ClassSchedule::DAYS_SHORT[$day],
                    'schedules' => $daySchedules->map(function ($s) use ($viewType) {
                        $data = [
                            'id' => $s->id,
                            'start_time' => $s->start_time->format('H:i'),
                            'end_time' => $s->end_time->format('H:i'),
                            'period_number' => $s->period_number,
                            'room' => $s->room,
                            'entry_type' => $s->entry_type,
                            'title' => $s->title,
                            'display_title' => $s->display_title,
                            'course' => $s->course ? [
                                'id' => $s->course->id,
                                'code' => $s->course->code,
                                'name' => $s->course->name,
                            ] : null,
                        ];

                        // Include teacher or classroom based on view type
                        if ($viewType === 'classroom') {
                            $data['teacher'] = $s->teacher ? [
                                'id' => $s->teacher->id,
                                'name' => $s->teacher->name,
                            ] : null;
                        } else {
                            $data['classroom'] = $s->classroom ? [
                                'id' => $s->classroom->id,
                                'name' => $s->classroom->name,
                            ] : null;
                        }

                        return $data;
                    }),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'view_type' => $viewType,
                'view_entity' => $viewEntity ? [
                    'id' => $viewEntity->id,
                    'name' => $viewEntity->name ?? $viewEntity->first_name.' '.$viewEntity->last_name,
                ] : null,
                'timetable' => array_values($timetable),
            ],
        ]);
    }

    /**
     * Create a new schedule
     */
    public function store(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        $validator = Validator::make($request->all(), [
            'semester_id' => 'required|exists:semesters,id',
            'classroom_id' => [
                'required',
                Rule::exists('classrooms', 'id')->where('academy_id', $academy->id),
            ],
            'course_id' => ['nullable', Rule::exists('courses', 'id')->where('academy_id', $academy->id)],
            'title' => ['nullable', 'string', 'max:255', 'required_without:course_id'],
            'entry_type' => ['nullable', Rule::in(ClassSchedule::ENTRY_TYPES)],
            'period_id' => 'nullable|integer|min:1',
            'teacher_id' => 'required|exists:users,id',
            'day_of_week' => 'required|integer|between:1,7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'period_number' => 'nullable|integer|min:1',
            'room' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $semester = Semester::with('academicYear')->find($request->semester_id);
        if ($semester?->academicYear?->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['semester_id' => ['The selected semester id is invalid.']],
            ], 422);
        }

        $teacher = User::find($request->teacher_id);
        if (! $academy->isApprovedMember($teacher) && ! $academy->isAdmin($teacher)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['teacher_id' => ['ครูผู้สอนไม่ได้อยู่ในโรงเรียนนี้']],
            ], 422);
        }

        // Check for teacher conflict
        if (ClassSchedule::hasTeacherConflict(
            $request->teacher_id,
            $request->semester_id,
            $request->day_of_week,
            $request->start_time,
            $request->end_time
        )) {
            return response()->json([
                'success' => false,
                'message' => 'ครูผู้สอนมีตารางสอนซ้ำซ้อนในเวลานี้',
            ], 422);
        }

        // Check for classroom conflict
        if (ClassSchedule::hasClassroomConflict(
            $request->classroom_id,
            $request->semester_id,
            $request->day_of_week,
            $request->start_time,
            $request->end_time
        )) {
            return response()->json([
                'success' => false,
                'message' => 'ห้องเรียนมีตารางเรียนซ้ำซ้อนในเวลานี้',
            ], 422);
        }

        // Get academic year from semester
        $semester = Semester::find($request->semester_id);

        $schedule = ClassSchedule::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $semester->academic_year_id,
            'semester_id' => $request->semester_id,
            'classroom_id' => $request->classroom_id,
            'course_id' => $request->course_id,
            'title' => $request->title,
            'entry_type' => $request->entry_type ?: ClassSchedule::ENTRY_TYPE_COURSE,
            'period_id' => $request->period_id,
            'teacher_id' => $request->teacher_id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'period_number' => $request->period_number,
            'room' => $request->room,
            'notes' => $request->notes,
            'created_by' => $request->user()->id,
        ]);

        $schedule->load(['course', 'teacher', 'classroom']);

        $this->auditLogService->logCreate($schedule, 'schedules');

        return response()->json([
            'success' => true,
            'message' => 'สร้างตารางเรียนสำเร็จ',
            'data' => $schedule,
        ], 201);
    }

    /**
     * Update a schedule
     */
    public function update(Request $request, $academyId, $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        $schedule = ClassSchedule::where('academy_id', $academy->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'classroom_id' => [
                'sometimes',
                Rule::exists('classrooms', 'id')->where('academy_id', $academy->id),
            ],
            'course_id' => [
                'sometimes',
                'nullable',
                Rule::exists('courses', 'id')->where('academy_id', $academy->id),
            ],
            'title' => 'sometimes|nullable|string|max:255',
            'entry_type' => ['sometimes', Rule::in(ClassSchedule::ENTRY_TYPES)],
            'period_id' => 'nullable|integer|min:1',
            'teacher_id' => 'sometimes|exists:users,id',
            'day_of_week' => 'sometimes|integer|between:1,7',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'period_number' => 'nullable|integer|min:1',
            'room' => 'nullable|string|max:50',
            'status' => 'sometimes|in:active,cancelled,temporary',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('teacher_id')) {
            $teacher = User::find($request->teacher_id);
            if (! $academy->isApprovedMember($teacher) && ! $academy->isAdmin($teacher)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => ['teacher_id' => ['ครูผู้สอนไม่ได้อยู่ในโรงเรียนนี้']],
                ], 422);
            }
        }

        $oldValues = $schedule->toArray();

        // Check conflicts if time-related fields are being updated
        $teacherId = $request->teacher_id ?? $schedule->teacher_id;
        $classroomId = $request->classroom_id ?? $schedule->classroom_id;
        $dayOfWeek = $request->day_of_week ?? $schedule->day_of_week;
        $startTime = $request->start_time ?? $schedule->start_time->format('H:i');
        $endTime = $request->end_time ?? $schedule->end_time->format('H:i');

        if ($request->hasAny(['teacher_id', 'day_of_week', 'start_time', 'end_time'])) {
            if (ClassSchedule::hasTeacherConflict(
                $teacherId,
                $schedule->semester_id,
                $dayOfWeek,
                $startTime,
                $endTime,
                $schedule->id
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'ครูผู้สอนมีตารางสอนซ้ำซ้อนในเวลานี้',
                ], 422);
            }
        }

        if ($request->hasAny(['classroom_id', 'day_of_week', 'start_time', 'end_time'])) {
            if (ClassSchedule::hasClassroomConflict(
                $classroomId,
                $schedule->semester_id,
                $dayOfWeek,
                $startTime,
                $endTime,
                $schedule->id
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'ห้องเรียนมีตารางเรียนซ้ำซ้อนในเวลานี้',
                ], 422);
            }
        }

        $schedule->update($request->only([
            'classroom_id',
            'course_id',
            'title',
            'entry_type',
            'period_id',
            'teacher_id',
            'day_of_week',
            'start_time',
            'end_time',
            'period_number',
            'room',
            'status',
            'notes',
        ]));

        $schedule->load(['course', 'teacher', 'classroom']);

        $this->auditLogService->logUpdate($schedule, $oldValues, 'schedules');

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทตารางเรียนสำเร็จ',
            'data' => $schedule,
        ]);
    }

    /**
     * Delete a schedule
     */
    public function destroy(Request $request, $academyId, $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        $schedule = ClassSchedule::where('academy_id', $academy->id)->findOrFail($id);

        $this->auditLogService->logDelete($schedule, 'schedules');

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบตารางเรียนสำเร็จ',
        ]);
    }

    /**
     * Bulk create schedules
     */
    public function bulkStore(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        $validator = Validator::make($request->all(), [
            'schedules' => 'required|array|min:1',
            'schedules.*.semester_id' => 'required|exists:semesters,id',
            'schedules.*.classroom_id' => [
                'required',
                Rule::exists('classrooms', 'id')->where('academy_id', $academy->id),
            ],
            'schedules.*.course_id' => [
                'nullable',
                Rule::exists('courses', 'id')->where('academy_id', $academy->id),
            ],
            'schedules.*.entry_type' => ['nullable', Rule::in(ClassSchedule::ENTRY_TYPES)],
            'schedules.*.period_id' => 'nullable|integer|min:1',
            'schedules.*.teacher_id' => 'required|exists:users,id',
            'schedules.*.day_of_week' => 'required|integer|between:1,7',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $created = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->schedules as $index => $scheduleData) {
                if (empty($scheduleData['course_id']) && empty($scheduleData['title'])) {
                    $errors[] = [
                        'index' => $index,
                        'message' => 'The title field is required when course id is not present.',
                    ];

                    continue;
                }

                $semester = Semester::with('academicYear')->find($scheduleData['semester_id']);
                if ($semester?->academicYear?->academy_id !== $academy->id) {
                    $errors[] = [
                        'index' => $index,
                        'message' => 'The selected semester id is invalid.',
                    ];

                    continue;
                }

                $teacher = User::find($scheduleData['teacher_id']);
                if (! $academy->isApprovedMember($teacher) && ! $academy->isAdmin($teacher)) {
                    $errors[] = [
                        'index' => $index,
                        'message' => 'ครูผู้สอนไม่ได้อยู่ในโรงเรียนนี้',
                    ];

                    continue;
                }

                // Check for conflicts
                if (ClassSchedule::hasTeacherConflict(
                    $scheduleData['teacher_id'],
                    $scheduleData['semester_id'],
                    $scheduleData['day_of_week'],
                    $scheduleData['start_time'],
                    $scheduleData['end_time']
                )) {
                    $errors[] = [
                        'index' => $index,
                        'message' => 'ครูผู้สอนมีตารางสอนซ้ำซ้อน',
                    ];

                    continue;
                }

                if (ClassSchedule::hasClassroomConflict(
                    $scheduleData['classroom_id'],
                    $scheduleData['semester_id'],
                    $scheduleData['day_of_week'],
                    $scheduleData['start_time'],
                    $scheduleData['end_time']
                )) {
                    $errors[] = [
                        'index' => $index,
                        'message' => 'ห้องเรียนมีตารางเรียนซ้ำซ้อน',
                    ];

                    continue;
                }

                $semester = Semester::find($scheduleData['semester_id']);

                $schedule = ClassSchedule::create([
                    'academy_id' => $academy->id,
                    'academic_year_id' => $semester->academic_year_id,
                    'semester_id' => $scheduleData['semester_id'],
                    'classroom_id' => $scheduleData['classroom_id'],
                    'course_id' => $scheduleData['course_id'] ?? null,
                    'title' => $scheduleData['title'] ?? null,
                    'entry_type' => $scheduleData['entry_type'] ?? ClassSchedule::ENTRY_TYPE_COURSE,
                    'period_id' => $scheduleData['period_id'] ?? null,
                    'teacher_id' => $scheduleData['teacher_id'],
                    'day_of_week' => $scheduleData['day_of_week'],
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'period_number' => $scheduleData['period_number'] ?? null,
                    'room' => $scheduleData['room'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                $created[] = $schedule;
            }

            if (empty($created) && ! empty($errors)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถสร้างตารางเรียนได้เนื่องจากมีการซ้ำซ้อน',
                    'errors' => $errors,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'สร้างตารางเรียนสำเร็จ '.count($created).' รายการ',
                'data' => [
                    'created_count' => count($created),
                    'error_count' => count($errors),
                    'errors' => $errors,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's schedule for a classroom or teacher
     */
    public function today(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        $query = ClassSchedule::with([
            'course:id,name,code,academy_id',
            'teacher:id,name,profile_photo_path',
            'classroom:id,name,grade_level,section',
        ])
            ->byAcademy($academy->id)
            ->active()
            ->today();

        // Current semester
        $currentSemester = Semester::currentForAcademy($academy->id);
        if ($currentSemester) {
            $query->bySemester($currentSemester->id);
        }

        // Filter
        if ($request->filled('classroom_id')) {
            $query->byClassroom($request->classroom_id);
        }
        if ($request->filled('teacher_id')) {
            $query->byTeacher($request->teacher_id);
        }

        $schedules = $query->orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => now()->toDateString(),
                'day_name' => ClassSchedule::DAYS[now()->dayOfWeek ?: 7],
                'schedules' => $schedules,
            ],
        ]);
    }

    /**
     * Check availability for a specific time slot
     */
    public function checkAvailability(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'day_of_week' => 'required|integer|between:1,7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'teacher_id' => 'nullable|exists:users,id',
            'classroom_id' => [
                'nullable',
                Rule::exists('classrooms', 'id')->where('academy_id', $academy->id),
            ],
        ]);

        $semester = Semester::with('academicYear')->find($request->semester_id);
        if ($semester?->academicYear?->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['semester_id' => ['The selected semester id is invalid.']],
            ], 422);
        }

        if ($request->filled('teacher_id')) {
            $teacher = User::find($request->teacher_id);
            if (! $academy->isApprovedMember($teacher) && ! $academy->isAdmin($teacher)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => ['teacher_id' => ['ครูผู้สอนไม่ได้อยู่ในโรงเรียนนี้']],
                ], 422);
            }
        }

        $result = [
            'teacher_available' => true,
            'classroom_available' => true,
            'conflicts' => [],
        ];

        if ($request->filled('teacher_id')) {
            if (ClassSchedule::hasTeacherConflict(
                $request->teacher_id,
                $request->semester_id,
                $request->day_of_week,
                $request->start_time,
                $request->end_time
            )) {
                $result['teacher_available'] = false;
                $result['conflicts'][] = 'ครูผู้สอนไม่ว่างในเวลานี้';
            }
        }

        if ($request->filled('classroom_id')) {
            if (ClassSchedule::hasClassroomConflict(
                $request->classroom_id,
                $request->semester_id,
                $request->day_of_week,
                $request->start_time,
                $request->end_time
            )) {
                $result['classroom_available'] = false;
                $result['conflicts'][] = 'ห้องเรียนไม่ว่างในเวลานี้';
            }
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
