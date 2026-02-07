<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Academy;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassroomController extends Controller
{
    /**
     * Get all classrooms for academy
     */
    public function index(Request $request, int $academyId): JsonResponse
    {
        $academicYearId = $request->query('academic_year_id');
        $gradeLevel = $request->query('grade_level');

        $query = Classroom::where('academy_id', $academyId)
            ->with(['academicYear', 'homeroomTeacher', 'activeStudents'])
            ->withCount('activeStudents');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($gradeLevel) {
            $query->where('grade_level', $gradeLevel);
        }

        $classrooms = $query->orderBy('grade_level')
            ->orderBy('section')
            ->get();

        return response()->json([
            'success' => true,
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Get single classroom with students
     */
    public function show(int $academyId, int $id): JsonResponse
    {
        $classroom = Classroom::where('academy_id', $academyId)
            ->with(['academicYear', 'homeroomTeacher', 'classroomStudents.student.user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'classroom' => $classroom,
        ]);
    }

    /**
     * Store new classroom
     */
    public function store(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_level' => 'required|string|max:10',
            'section' => 'required|string|max:10',
            'name' => 'nullable|string|max:50',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'room_location' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $validated['academy_id'] = $academyId;
        $validated['name'] = $validated['name'] ?? "{$validated['grade_level']}/{$validated['section']}";

        $classroom = Classroom::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'สร้างห้องเรียนสำเร็จ',
            'classroom' => $classroom->load('academicYear', 'homeroomTeacher'),
        ], 201);
    }

    /**
     * Update classroom
     */
    public function update(Request $request, int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);

        $validated = $request->validate([
            'grade_level' => 'string|max:10',
            'section' => 'string|max:10',
            'name' => 'nullable|string|max:50',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'room_location' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $classroom->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตห้องเรียนสำเร็จ',
            'classroom' => $classroom->fresh(['academicYear', 'homeroomTeacher']),
        ]);
    }

    /**
     * Delete classroom
     */
    public function destroy(int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);
        $classroom->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบห้องเรียนสำเร็จ',
        ]);
    }

    /**
     * Add students to classroom
     */
    public function addStudents(Request $request, int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);

        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $added = 0;
        foreach ($validated['student_ids'] as $studentId) {
            $student = Student::find($studentId);
            if ($student) {
                $classroom->addStudent($student);
                $added++;
            }
        }

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
        
        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $classroom = Classroom::where('academy_id', $academyId)->findOrFail($id);
        $student = Student::findOrFail($studentId);
        
        $classroom->removeStudent($student);

        return response()->json([
            'success' => true,
            'message' => 'ลบนักเรียนออกจากห้องเรียนสำเร็จ',
        ]);
    }

    /**
     * Update student number
     */
    public function updateStudentNumber(Request $request, int $academyId, int $id, int $studentId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManage($academy)) {
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

    // Helper Methods
    protected function canManage(Academy $academy): bool
    {
        $user = auth()->user();
        if (!$user) return false;

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

        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $query = Student::where('academy_id', $academyId)
            ->with(['user', 'classroom', 'studentCard']);

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

        // Include latest GPA
        $query->addSelect([
            'gpa' => \DB::raw("(SELECT gpa FROM semester_transcripts WHERE semester_transcripts.student_id = students.id ORDER BY created_at DESC LIMIT 1)"),
            'gpax' => \DB::raw("(SELECT gpax FROM annual_transcripts WHERE annual_transcripts.student_id = students.id ORDER BY created_at DESC LIMIT 1)")
        ]);

        $perPage = $request->query('per_page', 20);
        $students = $query->orderBy('student_number')->paginate($perPage);

        return response()->json([
            'success' => true,
            'students' => $students,
        ]);
    }

    /**
     * Get single student details
     */
    public function getStudent(int $academyId, int $studentId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $student = Student::where('academy_id', $academyId)
            ->where('id', $studentId)
            ->with(['user', 'classroom', 'studentCard'])
            ->firstOrFail();

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
            ->with(['studentCard', 'classroom', 'user']);

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $student = $query->first();

        if (!$student) {
            return response()->json([
                'success' => true,
                'student' => null,
                'studentCard' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'student' => $student,
            'studentCard' => $student->studentCard,
        ]);
    }
}
