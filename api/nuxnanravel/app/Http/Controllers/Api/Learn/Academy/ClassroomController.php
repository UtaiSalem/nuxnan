<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\Enrollment\ClassroomStudentResource;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AnnualTranscript;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Services\ClassroomService;
use App\Services\MemberService;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    private ClassroomService $classroomService;

    private MemberService $memberService;

    private StudentEnrollmentService $enrollmentService;

    public function __construct(
        ClassroomService $classroomService,
        MemberService $memberService,
        StudentEnrollmentService $enrollmentService
    ) {
        $this->classroomService = $classroomService;
        $this->memberService = $memberService;
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Get all classrooms for academy
     */
    public function index(Request $request, int $academyId): JsonResponse
    {
        $filters = $request->only(['academic_year_id', 'academic_year', 'semester', 'grade_level', 'status']);

        // When no academic-year filter is supplied, default to the academy's current
        // academic year so listings don't mix classrooms across years (which look like
        // duplicate cards). Pass ?all_years=1 to opt out and return every year.
        if (empty($filters['academic_year_id']) && empty($filters['academic_year']) && ! $request->boolean('all_years')) {
            $currentYear = AcademicYear::where('academy_id', $academyId)
                ->where('is_current', true)
                ->first();

            if ($currentYear) {
                $filters['academic_year_id'] = $currentYear->id;
            }
        }

        $withMembers = $request->boolean('include_members', false);

        $classrooms = $this->classroomService->listClassrooms($academyId, $filters, $withMembers);

        return response()->json([
            'success' => true,
            'data' => $classrooms,
            'classrooms' => $classrooms, // backward compat
        ]);
    }

    /**
     * Get single classroom with members and students
     */
    public function show(int $academyId, int $id): JsonResponse
    {
        $classroom = $this->classroomService->getClassroom($academyId, $id);

        // New system: classroom_members (teachers, observers, etc.)
        $members = $this->memberService->listMembers($id);

        // Query students through classroom_students pivot (source of truth)
        $students = Student::whereIn('id', function ($query) use ($id) {
            $query->select('student_id')
                ->from('classroom_students')
                ->where('classroom_id', $id)
                ->where('status', 'active');
        })
            ->select('id', 'student_id', 'title_prefix_th', 'first_name_th', 'last_name_th',
                'nickname', 'gender', 'profile_image', 'status', 'class_level', 'class_section')
            ->orderBy('first_name_th')
            ->get()
            ->map(function ($student) use ($id) {
                // Attach student_number from pivot
                $pivot = ClassroomStudent::where('classroom_id', $id)
                    ->where('student_id', $student->id)
                    ->where('status', 'active')
                    ->first();
                $student->student_number = $pivot?->student_number;
                // Build full profile image URL
                $student->profile_image_url = $student->profile_image_url;

                return $student;
            })
            ->sortBy('student_number')
            ->values();

        // Fallback: if no pivot records, use legacy direct matching
        if ($students->isEmpty()) {
            $students = Student::where('academy_id', $academyId)
                ->where('class_level', $classroom->grade_level)
                ->where('class_section', $classroom->section)
                ->select('id', 'student_id', 'title_prefix_th', 'first_name_th', 'last_name_th',
                    'nickname', 'gender', 'profile_image', 'status', 'class_level', 'class_section')
                ->orderBy('first_name_th')
                ->get()
                ->map(function ($student) {
                    $student->profile_image_url = $student->profile_image_url;

                    return $student;
                });
        }

        // Build classroom data with students and members embedded
        $classroomData = $classroom->toArray();

        // Fix academic_year: ensure string value instead of relationship object
        $classroomData['academic_year'] = $classroom->getRawOriginal('academic_year');
        if ($classroom->relationLoaded('academicYear') && $classroom->getRelation('academicYear')) {
            $classroomData['academic_year_info'] = $classroom->getRelation('academicYear')->toArray();
        }

        // Embed students and members inside the classroom object
        $classroomData['students'] = $students;
        $classroomData['members'] = $members;

        return response()->json([
            'success' => true,
            'classroom' => $classroomData,
        ]);
    }

    /**
     * List classroom enrollments with optional status/year filters.
     */
    public function listEnrollments(Request $request, int $academyId, int $classroomId): JsonResponse
    {
        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($classroomId);

        $validated = $request->validate([
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in(ClassroomStudent::$statuses)],
            'academic_year_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('academy_id', $academyId)),
            ],
        ]);

        $statuses = $validated['status'] ?? [ClassroomStudent::STATUS_ACTIVE];

        $enrollments = ClassroomStudent::query()
            ->where('classroom_id', $classroom->id)
            ->whereIn('status', $statuses)
            ->when(
                isset($validated['academic_year_id']),
                fn ($query) => $query->where('academic_year_id', $validated['academic_year_id'])
            )
            ->with(['student', 'classroom', 'createdBy'])
            ->orderByRaw('CASE WHEN left_at IS NULL THEN 1 ELSE 0 END DESC')
            ->orderByDesc('left_at')
            ->orderBy('student_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ClassroomStudentResource::collection($enrollments),
        ]);
    }

    /**
     * Store new classroom
     */
    public function store(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'academic_year' => 'required_without:academic_year_id|nullable|string|max:10',
            'semester' => 'nullable|integer|in:1,2',
            'grade_level' => 'required|string|max:10',
            'section' => 'required|string|max:10',
            'name' => 'nullable|string|max:50',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'room_location' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $classroom = $this->classroomService->createClassroom($academyId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'สร้างห้องเรียนสำเร็จ',
            'data' => $classroom,
        ], 201);
    }

    /**
     * Update classroom
     */
    public function update(Request $request, int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'academic_year' => 'nullable|string|max:10',
            'grade_level' => 'string|max:10',
            'section' => 'string|max:10',
            'name' => 'nullable|string|max:50',
            'semester' => 'nullable|integer|in:1,2',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'room_location' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'status' => 'in:active,archived',
        ]);

        $classroom = $this->classroomService->updateClassroom($classroom, $validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตห้องเรียนสำเร็จ',
            'data' => $classroom,
        ]);
    }

    /**
     * Delete classroom
     */
    public function destroy(int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);
        $this->classroomService->deleteClassroom($classroom);

        return response()->json([
            'success' => true,
            'message' => 'ลบห้องเรียนสำเร็จ',
        ]);
    }

    /**
     * Archive classroom (soft)
     */
    public function archive(int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);
        $this->classroomService->archiveClassroom($classroom);

        return response()->json([
            'success' => true,
            'message' => 'เก็บห้องเรียนเข้าคลังสำเร็จ',
        ]);
    }

    // ───────────────────────────────────────
    // Member management (new system)
    // ───────────────────────────────────────

    /**
     * List members of a classroom
     */
    public function listMembers(Request $request, int $academyId, int $classroomId): JsonResponse
    {
        $role = $request->query('role');
        $members = $this->memberService->listMembers($classroomId, $role);

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * Add a member to classroom
     */
    public function addMember(Request $request, int $academyId, int $classroomId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:teacher,co_teacher,student,observer',
            'student_no' => 'nullable|integer|min:1',
        ]);

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($classroomId);

        $member = $this->memberService->addMember(
            $classroom,
            $validated['user_id'],
            $validated['role'],
            $validated['student_no'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มสมาชิกสำเร็จ',
            'data' => $member->load('user'),
        ], 201);
    }

    /**
     * Bulk add members
     */
    public function bulkAddMembers(Request $request, int $academyId, int $classroomId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'role' => 'required|in:teacher,co_teacher,student,observer',
        ]);

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($classroomId);
        $added = $this->memberService->bulkAddMembers($classroom, $validated['user_ids'], $validated['role']);

        return response()->json([
            'success' => true,
            'message' => "เพิ่มสมาชิกสำเร็จ {$added} คน",
            'meta' => ['added' => $added],
        ]);
    }

    /**
     * Update a member (role, student_no)
     */
    public function updateMember(Request $request, int $academyId, int $classroomId, int $memberId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'role' => 'in:teacher,co_teacher,student,observer',
            'student_no' => 'nullable|integer|min:1',
        ]);

        $member = $this->memberService->updateMember($memberId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตสมาชิกสำเร็จ',
            'data' => $member,
        ]);
    }

    /**
     * Remove a member from classroom (soft)
     */
    public function removeMember(int $academyId, int $classroomId, int $memberId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $this->memberService->removeMember($classroomId, $memberId);

        return response()->json([
            'success' => true,
            'message' => 'ลบสมาชิกออกจากห้องเรียนสำเร็จ',
        ]);
    }

    /**
     * Transfer a member to another classroom
     */
    public function transferMember(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'member_id' => 'required|exists:classroom_members,id',
            'target_classroom_id' => 'required|exists:classrooms,id',
        ]);

        $newMember = $this->memberService->transferMember(
            $validated['member_id'],
            $validated['target_classroom_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'ย้ายสมาชิกสำเร็จ',
            'data' => $newMember->load('user'),
        ]);
    }

    // ───────────────────────────────────────
    // Legacy student management (backward compat)
    // ───────────────────────────────────────

    /**
     * Add students to classroom (uses enrollment service)
     */
    public function addStudents(Request $request, int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);

        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $added = $this->enrollmentService->bulkEnrollStudents($classroom, $validated['student_ids']);

        return response()->json([
            'success' => true,
            'message' => "เพิ่มนักเรียนสำเร็จ {$added} คน",
            'classroom' => $classroom->fresh(['classroomStudents.student']),
        ]);
    }

    /**
     * Remove student from classroom
     */
    public function removeStudent(int $academyId, int $id, int $studentId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);
        $student = Student::findOrFail($studentId);

        $this->enrollmentService->unenrollStudent($student, $classroom);

        return response()->json([
            'success' => true,
            'message' => 'ลบนักเรียนออกจากห้องเรียนสำเร็จ',
        ]);
    }

    /**
     * Update student number (legacy)
     */
    public function updateStudentNumber(Request $request, int $academyId, int $id, int $studentId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'student_number' => 'required|integer|min:1',
        ]);

        ClassroomStudent::where('classroom_id', $id)
            ->where('student_id', $studentId)
            ->update(['student_number' => $validated['student_number']]);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตเลขที่สำเร็จ',
        ]);
    }

    /**
     * Get grade levels list
     */
    public function getGradeLevels(int $academyId): JsonResponse
    {
        $gradeLevels = Classroom::where('academy_id', $academyId)
            ->where('is_active', true)
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');

        return response()->json([
            'success' => true,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    /**
     * Transfer student between classrooms
     */
    public function transferStudent(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'from_classroom_id' => 'required|exists:classrooms,id',
            'to_classroom_id' => 'required|exists:classrooms,id',
            'reason' => 'nullable|string|max:100',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $fromClassroom = Classroom::where('academy_id', $academyId)->findOrFail($validated['from_classroom_id']);
        $toClassroom = Classroom::where('academy_id', $academyId)->findOrFail($validated['to_classroom_id']);

        $enrollment = $this->enrollmentService->transferStudent(
            $student,
            $fromClassroom,
            $toClassroom,
            $validated['reason'] ?? 'ย้ายห้อง'
        );

        return response()->json([
            'success' => true,
            'message' => 'ย้ายนักเรียนสำเร็จ',
            'data' => $enrollment->load(['student', 'classroom']),
        ]);
    }

    /**
     * Get enrollment history for a student
     */
    public function getStudentEnrollmentHistory(int $academyId, int $studentId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $student = Student::where('academy_id', $academyId)->findOrFail($studentId);
        $history = $this->enrollmentService->getStudentHistory($student);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Promote all students from one classroom to another (new academic year)
     */
    public function promoteClassroom(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'from_classroom_id' => 'required|exists:classrooms,id',
            'to_classroom_id' => 'required|exists:classrooms,id',
        ]);

        $fromClassroom = Classroom::where('academy_id', $academyId)->findOrFail($validated['from_classroom_id']);
        $toClassroom = Classroom::where('academy_id', $academyId)->findOrFail($validated['to_classroom_id']);

        $promoted = $this->enrollmentService->promoteClassroom($fromClassroom, $toClassroom);

        return response()->json([
            'success' => true,
            'message' => "เลื่อนชั้นนักเรียนสำเร็จ {$promoted} คน",
            'meta' => ['promoted' => $promoted],
        ]);
    }

    // Helper Methods
    protected function canManage(Academy $academy): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($academy->user_id === $user->id) {
            return true;
        }

        return $academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'director', 'admin'])
            ->exists();
    }

    /**
     * Get all students in academy with optional filters
     */
    public function getAllStudents(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $query = Student::where('academy_id', $academyId)
            ->with(['user', 'studentCard', 'currentEnrollment.classroom']);

        // Apply filters
        if ($request->filled('classroom_id')) {
            $query->whereHas('classroomStudents', function ($q) use ($request) {
                $q->where('classroom_id', $request->query('classroom_id'))
                    ->where('status', 'active');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Include latest GPA and active classroom metadata without assuming columns exist on students.
        // Note: the alias must be embedded in the raw SQL (…"as current_student_number"). Passing a
        // DB::raw() expression as the value of addSelect([alias => …]) does NOT apply the array-key
        // alias — that shortcut only works for Closure/Builder subqueries — so the column would
        // otherwise be named after the whole expression and no "current_student_number" alias exists.
        // The active-student-number subquery is also reused verbatim in ORDER BY below.
        $studentNumberSubquery = "(SELECT classroom_students.student_number FROM classroom_students WHERE classroom_students.student_id = students.id AND classroom_students.status = 'active' ORDER BY classroom_students.created_at DESC LIMIT 1)";

        $query->select('students.*')
            ->selectRaw("{$studentNumberSubquery} as current_student_number")
            ->selectRaw("(SELECT classroom_students.classroom_id FROM classroom_students WHERE classroom_students.student_id = students.id AND classroom_students.status = 'active' ORDER BY classroom_students.created_at DESC LIMIT 1) as current_classroom_id")
            ->selectRaw('(SELECT gpa FROM semester_transcripts WHERE semester_transcripts.student_id = students.id ORDER BY created_at DESC LIMIT 1) as gpa')
            ->selectRaw('(SELECT gpax FROM annual_transcripts WHERE annual_transcripts.student_id = students.id ORDER BY created_at DESC LIMIT 1) as gpax');

        $perPage = $request->query('per_page', 20);
        $students = $query
            ->orderBy('class_level')
            ->orderBy('class_section')
            ->orderByRaw("{$studentNumberSubquery} IS NULL")
            ->orderByRaw($studentNumberSubquery)
            ->orderBy('student_id')
            ->orderBy('first_name_th')
            ->paginate($perPage);

        $items = $students->getCollection()
            ->map(function (Student $student) {
                $student->student_number = $student->current_student_number;
                $student->classroom_id = $student->current_classroom_id;

                return $student;
            })
            ->values();

        return response()->json([
            'success' => true,
            'students' => $items,
            'data' => $items,
            'pagination' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    /**
     * Get single student details
     */
    public function getStudent(int $academyId, int $studentId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $student = Student::where('academy_id', $academyId)
            ->where('id', $studentId)
            ->with(['user', 'studentCard', 'currentEnrollment.classroom'])
            ->firstOrFail();

        // The Student model has no `classroom` relation. Surface the active enrollment's
        // classroom, student_number and latest GPAX as flat attributes so the frontend
        // (gradebook transcript page reads student.classroom?.name, student.student_number,
        // student.gpax) keeps working — mirroring getAllStudents() above.
        $enrollment = $student->currentEnrollment;
        $student->setAttribute('classroom', $enrollment?->classroom);
        $student->setAttribute('student_number', $enrollment?->student_number);
        $student->setAttribute('gpax', AnnualTranscript::where('student_id', $student->id)
            ->latest('created_at')
            ->value('gpax'));

        return response()->json([
            'success' => true,
            'student' => $student,
        ]);
    }

    /**
     * Get current user's student card
     */
    public function getMyStudentCard(Request $request): JsonResponse
    {
        $user = auth()->user();
        $academyId = $request->query('academy_id');

        $query = Student::where('user_id', $user->id)
            ->with(['activeClassroom', 'user']);

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $student = $query->first();

        if (! $student) {
            return response()->json([
                'success' => true,
                'student' => null,
                'studentCard' => null,
            ]);
        }

        $studentCard = null;
        try {
            $studentCard = $student->studentCard;
        } catch (\Throwable $e) {
            \Log::warning('student card load failed', ['student_id' => $student->id, 'err' => $e->getMessage()]);
            $studentCard = $student->legacy_student_card;
        }

        return response()->json([
            'success' => true,
            'student' => $student,
            'studentCard' => $studentCard,
        ]);
    }

    /**
     * Get classroom statistics
     */
    public function getStatistics(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $academicYear = $request->query('academic_year');

        $query = Classroom::where('academy_id', $academyId)
            ->where('is_active', true)
            ->where('status', 'active');

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        $classrooms = $query->get();
        $classroomIds = $classrooms->pluck('id');

        $totalClassrooms = $classrooms->count();
        $classroomsWithTeacher = $classrooms->whereNotNull('homeroom_teacher_id')->count();

        $totalStudents = ClassroomStudent::whereIn('classroom_id', $classroomIds)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_classrooms' => $totalClassrooms,
                'total_students' => $totalStudents,
                'classrooms_with_teacher' => $classroomsWithTeacher,
            ],
        ]);
    }
}
