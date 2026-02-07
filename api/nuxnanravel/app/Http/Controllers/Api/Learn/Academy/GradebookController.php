<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\GradebookAssessment;
use App\Models\GradebookScore;
use App\Models\CourseGrade;
use App\Models\Course;
use App\Models\Student;
use App\Models\GradeScale;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GradebookController extends Controller
{
    /**
     * Get gradebook for a course
     */
    public function index(Request $request, int $courseId): JsonResponse
    {
        $course = Course::with(['academy', 'courseMembers.user'])->findOrFail($courseId);
        
        // Check permission
        if (!$this->canAccessGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $semesterId = $request->query('semester_id');
        $categoryId = $request->query('category_id');

        $assessments = GradebookAssessment::where('course_id', $courseId)
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->with(['category', 'scores.student', 'assignment', 'quiz'])
            ->orderBy('created_at')
            ->get();

        // Get students in the course
        $students = $this->getCourseStudents($course);

        // Format gradebook data
        $gradebook = $this->formatGradebookData($assessments, $students);

        return response()->json([
            'success' => true,
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
            ],
            'assessments' => $assessments,
            'students' => $students,
            'gradebook' => $gradebook,
        ]);
    }

    /**
     * Create a new assessment
     */
    public function storeAssessment(Request $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        
        if (!$this->canManageGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:assessment_categories,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'max_score' => 'required|numeric|min:0|max:9999',
            'weight' => 'required|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'is_included_in_grade' => 'boolean',
            'assignment_id' => 'nullable|exists:assignments,id',
            'quiz_id' => 'nullable|exists:course_quizzes,id',
        ]);

        $validated['course_id'] = $courseId;
        $validated['created_by'] = auth()->id();

        $assessment = GradebookAssessment::create($validated);

        // Create score records for all students
        $students = $this->getCourseStudents($course);
        foreach ($students as $student) {
            GradebookScore::create([
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'user_id' => $student->user_id,
                'status' => 'pending',
            ]);
        }

        // If linked to assignment or quiz, sync scores
        if ($assessment->assignment_id || $assessment->quiz_id) {
            $assessment->syncScoresFromSource();
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้างการประเมินสำเร็จ',
            'assessment' => $assessment->load('category', 'scores'),
        ], 201);
    }

    /**
     * Update an assessment
     */
    public function updateAssessment(Request $request, int $courseId, int $assessmentId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $assessment = GradebookAssessment::where('course_id', $courseId)->findOrFail($assessmentId);
        
        if (!$this->canManageGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'name' => 'string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:assessment_categories,id',
            'max_score' => 'numeric|min:0|max:9999',
            'weight' => 'numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'is_published' => 'boolean',
            'is_included_in_grade' => 'boolean',
        ]);

        $assessment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตการประเมินสำเร็จ',
            'assessment' => $assessment->fresh(['category', 'scores']),
        ]);
    }

    /**
     * Delete an assessment
     */
    public function destroyAssessment(int $courseId, int $assessmentId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $assessment = GradebookAssessment::where('course_id', $courseId)->findOrFail($assessmentId);
        
        if (!$this->canManageGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $assessment->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบการประเมินสำเร็จ',
        ]);
    }

    /**
     * Get scores for an assessment
     */
    public function getScores(int $courseId, int $assessmentId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $assessment = GradebookAssessment::where('course_id', $courseId)
            ->with(['scores.student', 'scores.grader'])
            ->findOrFail($assessmentId);
        
        if (!$this->canAccessGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        return response()->json([
            'success' => true,
            'assessment' => $assessment,
            'scores' => $assessment->scores,
            'stats' => [
                'total' => $assessment->scores->count(),
                'graded' => $assessment->scores->where('status', 'graded')->count(),
                'pending' => $assessment->scores->where('status', 'pending')->count(),
                'average' => $assessment->average_score,
                'max' => $assessment->scores->max('score'),
                'min' => $assessment->scores->where('status', 'graded')->min('score'),
            ],
        ]);
    }

    /**
     * Update scores (batch)
     */
    public function updateScores(Request $request, int $courseId, int $assessmentId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $assessment = GradebookAssessment::where('course_id', $courseId)->findOrFail($assessmentId);
        
        if (!$this->canManageGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:students,id',
            'scores.*.score' => 'nullable|numeric|min:0',
            'scores.*.feedback' => 'nullable|string',
            'scores.*.status' => 'nullable|in:pending,graded,excused,missing',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['scores'] as $scoreData) {
                $score = GradebookScore::where('assessment_id', $assessmentId)
                    ->where('student_id', $scoreData['student_id'])
                    ->first();

                if (!$score) continue;

                if (isset($scoreData['score']) && $scoreData['score'] !== null) {
                    $score->grade(
                        $scoreData['score'],
                        $scoreData['feedback'] ?? null,
                        auth()->id()
                    );
                } elseif (isset($scoreData['status'])) {
                    if ($scoreData['status'] === 'missing') {
                        $score->markAsMissing(auth()->id());
                    } elseif ($scoreData['status'] === 'excused') {
                        $score->markAsExcused($scoreData['feedback'] ?? null, auth()->id());
                    }
                }
            }
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'บันทึกคะแนนสำเร็จ',
                'scores' => $assessment->fresh()->scores,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update single score
     */
    public function updateScore(Request $request, int $courseId, int $assessmentId, int $studentId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $assessment = GradebookAssessment::where('course_id', $courseId)->findOrFail($assessmentId);
        
        if (!$this->canManageGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'score' => 'nullable|numeric|min:0|max:' . $assessment->max_score,
            'feedback' => 'nullable|string',
            'status' => 'nullable|in:pending,graded,excused,missing',
        ]);

        $score = GradebookScore::where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        if (isset($validated['score']) && $validated['score'] !== null) {
            $score->grade($validated['score'], $validated['feedback'] ?? null, auth()->id());
        } elseif (isset($validated['status'])) {
            if ($validated['status'] === 'missing') {
                $score->markAsMissing(auth()->id());
            } elseif ($validated['status'] === 'excused') {
                $score->markAsExcused($validated['feedback'] ?? null, auth()->id());
            } else {
                $score->update($validated);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกคะแนนสำเร็จ',
            'score' => $score->fresh(['student', 'grader']),
        ]);
    }

    /**
     * Calculate and get course grades
     */
    public function getCourseGrades(Request $request, int $courseId): JsonResponse
    {
        $course = Course::with('academy')->findOrFail($courseId);
        
        if (!$this->canAccessGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $semesterId = $request->query('semester_id');
        $students = $this->getCourseStudents($course);

        $grades = [];
        foreach ($students as $student) {
            $grade = CourseGrade::firstOrCreate(
                [
                    'course_id' => $courseId,
                    'student_id' => $student->id,
                    'semester_id' => $semesterId,
                ],
                [
                    'user_id' => $student->user_id,
                    'status' => 'in_progress',
                ]
            );

            // Calculate if requested
            if ($request->query('calculate')) {
                $grade->calculateFromScores();
                $grade->assignLetterGrade();
            }

            $grades[] = $grade->load('student');
        }

        return response()->json([
            'success' => true,
            'course' => $course,
            'grades' => $grades,
        ]);
    }

    /**
     * Publish course grades
     */
    public function publishGrades(Request $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        
        if (!$this->canManageGradebook($course)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'semester_id' => 'nullable|exists:semesters,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $query = CourseGrade::where('course_id', $courseId);
        
        if (!empty($validated['semester_id'])) {
            $query->where('semester_id', $validated['semester_id']);
        }

        if (!empty($validated['student_ids'])) {
            $query->whereIn('student_id', $validated['student_ids']);
        }

        $grades = $query->get();

        foreach ($grades as $grade) {
            $grade->markAsCompleted();
            $grade->publish(auth()->id());
        }

        return response()->json([
            'success' => true,
            'message' => 'เผยแพร่เกรดสำเร็จ ' . $grades->count() . ' รายการ',
        ]);
    }

    /**
     * Get student's gradebook view
     */
    public function getStudentGrades(Request $request, int $courseId): JsonResponse
    {
        $user = auth()->user();
        $course = Course::findOrFail($courseId);

        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน'], 404);
        }

        $assessments = GradebookAssessment::where('course_id', $courseId)
            ->where('is_published', true)
            ->with(['category', 'scores' => function($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->get();

        $courseGrade = CourseGrade::where('course_id', $courseId)
            ->where('student_id', $student->id)
            ->where('is_published', true)
            ->first();

        return response()->json([
            'success' => true,
            'course' => $course,
            'assessments' => $assessments,
            'courseGrade' => $courseGrade,
        ]);
    }

    // Helper Methods
    protected function canAccessGradebook(Course $course): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Owner or instructor
        if ($course->user_id === $user->id || $course->instructor_id === $user->id) {
            return true;
        }

        // Course admin
        $isAdmin = $course->courseMembers()
            ->where('user_id', $user->id)
            ->whereIn('role', ['admin', 'teacher', 'instructor'])
            ->exists();

        return $isAdmin;
    }

    protected function canManageGradebook(Course $course): bool
    {
        return $this->canAccessGradebook($course);
    }

    protected function getCourseStudents(Course $course)
    {
        return Student::whereHas('user', function($q) use ($course) {
            $q->whereHas('courseMembers', function($q2) use ($course) {
                $q2->where('course_id', $course->id)
                   ->where('course_member_status', 1);
            });
        })->with('user')->get();
    }

    protected function formatGradebookData($assessments, $students): array
    {
        $gradebook = [];

        foreach ($students as $student) {
            $row = [
                'student_id' => $student->id,
                'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                'student_number' => $student->student_id,
                'scores' => [],
                'total' => 0,
                'percentage' => 0,
            ];

            $totalWeight = 0;
            $weightedSum = 0;

            foreach ($assessments as $assessment) {
                $score = $assessment->scores->where('student_id', $student->id)->first();
                
                $row['scores'][$assessment->id] = [
                    'score' => $score?->score,
                    'status' => $score?->status ?? 'pending',
                    'percentage' => $score?->percentage,
                ];

                if ($score && $score->status === 'graded' && $assessment->max_score > 0) {
                    $pct = ($score->score / $assessment->max_score) * 100;
                    $weightedSum += $pct * ($assessment->weight / 100);
                    $totalWeight += $assessment->weight;
                }
            }

            $row['total'] = round($weightedSum, 2);
            $row['percentage'] = $totalWeight > 0 ? round(($weightedSum / $totalWeight) * 100, 2) : 0;

            $gradebook[] = $row;
        }

        return $gradebook;
    }
}
