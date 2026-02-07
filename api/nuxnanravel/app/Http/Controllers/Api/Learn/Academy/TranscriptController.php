<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\SemesterTranscript;
use App\Models\AnnualTranscript;
use App\Models\CourseGrade;
use App\Models\Student;
use App\Models\Academy;
use App\Models\Semester;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TranscriptController extends Controller
{
    /**
     * Get student's transcript (semester)
     */
    public function getSemesterTranscript(Request $request, int $studentId): JsonResponse
    {
        $student = Student::with(['academy', 'user'])->findOrFail($studentId);
        
        if (!$this->canViewTranscript($student)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $semesterId = $request->query('semester_id');

        $query = SemesterTranscript::where('student_id', $studentId)
            ->with(['semester.academicYear', 'classroom', 'items.course', 'approver']);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        $transcripts = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'student' => $student,
            'transcripts' => $transcripts,
        ]);
    }

    /**
     * Get student's annual transcript (GPAX)
     */
    public function getAnnualTranscript(Request $request, int $studentId): JsonResponse
    {
        $student = Student::with(['academy', 'user'])->findOrFail($studentId);
        
        if (!$this->canViewTranscript($student)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $academicYearId = $request->query('academic_year_id');

        $query = AnnualTranscript::where('student_id', $studentId)
            ->with('academicYear');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $transcripts = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'student' => $student,
            'transcripts' => $transcripts,
        ]);
    }

    /**
     * Generate/Calculate semester transcript
     */
    public function generateSemesterTranscript(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManageTranscripts($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);

        // Get students to process
        $studentQuery = Student::where('academy_id', $academyId);
        
        if (!empty($validated['classroom_id'])) {
            $studentQuery->whereHas('classroomStudents', function($q) use ($validated) {
                $q->where('classroom_id', $validated['classroom_id'])
                  ->where('status', 'active');
            });
        }

        if (!empty($validated['student_ids'])) {
            $studentQuery->whereIn('id', $validated['student_ids']);
        }

        $students = $studentQuery->get();
        $processed = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // Get or create transcript
                $transcript = SemesterTranscript::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                    ],
                    [
                        'academy_id' => $academyId,
                        'classroom_id' => $validated['classroom_id'] ?? null,
                        'status' => 'draft',
                    ]
                );

                // Calculate
                $transcript->calculate();
                $processed++;
            }

            // Calculate rankings
            $transcripts = SemesterTranscript::where('semester_id', $semester->id)
                ->where('academy_id', $academyId)
                ->get();

            foreach ($transcripts as $transcript) {
                $transcript->calculateRanking();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "คำนวณผลการเรียนสำเร็จ {$processed} รายการ",
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
     * Publish semester transcripts
     */
    public function publishSemesterTranscripts(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManageTranscripts($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'transcript_ids' => 'nullable|array',
            'transcript_ids.*' => 'exists:semester_transcripts,id',
        ]);

        $query = SemesterTranscript::where('academy_id', $academyId)
            ->where('semester_id', $validated['semester_id'])
            ->where('status', 'draft');

        if (!empty($validated['transcript_ids'])) {
            $query->whereIn('id', $validated['transcript_ids']);
        }

        $count = $query->update(['status' => 'published']);

        return response()->json([
            'success' => true,
            'message' => "เผยแพร่ผลการเรียนสำเร็จ {$count} รายการ",
        ]);
    }

    /**
     * Generate annual transcript
     */
    public function generateAnnualTranscript(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManageTranscripts($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);

        // Get students to process
        $studentQuery = Student::where('academy_id', $academyId);

        if (!empty($validated['student_ids'])) {
            $studentQuery->whereIn('id', $validated['student_ids']);
        }

        $students = $studentQuery->get();
        $processed = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // Get grade level from latest classroom
                $gradeLevel = $student->classroomStudents()
                    ->whereHas('classroom', function($q) use ($academicYear) {
                        $q->where('academic_year_id', $academicYear->id);
                    })
                    ->with('classroom')
                    ->first()?->classroom?->grade_level ?? $student->class_level;

                // Get or create annual transcript
                $transcript = AnnualTranscript::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    [
                        'academy_id' => $academyId,
                        'grade_level' => $gradeLevel,
                        'status' => 'draft',
                    ]
                );

                // Calculate
                $transcript->calculate();
                $transcript->determinePromotionStatus();
                $processed++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "คำนวณผลการเรียนประจำปีสำเร็จ {$processed} รายการ",
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
     * Download transcript as PDF
     */
    public function downloadTranscriptPdf(Request $request, int $studentId, int $transcriptId): mixed
    {
        $student = Student::with(['academy', 'user'])->findOrFail($studentId);
        
        if (!$this->canViewTranscript($student)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $transcript = SemesterTranscript::where('student_id', $studentId)
            ->with(['semester.academicYear', 'classroom', 'items.course', 'academy'])
            ->findOrFail($transcriptId);

        $pdf = Pdf::loadView('pdf.transcript', [
            'student' => $student,
            'transcript' => $transcript,
        ]);

        $filename = "transcript_{$student->student_id}_{$transcript->semester->name}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Get transcript overview for academy
     */
    public function getAcademyTranscriptOverview(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        
        if (!$this->canManageTranscripts($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $semesterId = $request->query('semester_id');
        $classroomId = $request->query('classroom_id');

        $query = SemesterTranscript::where('academy_id', $academyId)
            ->with(['student', 'semester', 'classroom']);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        $transcripts = $query->get();

        // Calculate stats
        $stats = [
            'total' => $transcripts->count(),
            'draft' => $transcripts->where('status', 'draft')->count(),
            'published' => $transcripts->where('status', 'published')->count(),
            'approved' => $transcripts->where('status', 'approved')->count(),
            'avg_gpa' => round($transcripts->avg('gpa'), 2),
            'max_gpa' => $transcripts->max('gpa'),
            'min_gpa' => $transcripts->min('gpa'),
        ];

        // GPA distribution
        $distribution = [
            'excellent' => $transcripts->where('gpa', '>=', 3.5)->count(),
            'good' => $transcripts->whereBetween('gpa', [3.0, 3.49])->count(),
            'fair' => $transcripts->whereBetween('gpa', [2.5, 2.99])->count(),
            'pass' => $transcripts->whereBetween('gpa', [2.0, 2.49])->count(),
            'below' => $transcripts->where('gpa', '<', 2.0)->count(),
        ];

        return response()->json([
            'success' => true,
            'transcripts' => $transcripts,
            'stats' => $stats,
            'distribution' => $distribution,
        ]);
    }

    // Helper Methods
    protected function canViewTranscript(Student $student): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Student viewing own transcript
        if ($student->user_id === $user->id) {
            return true;
        }

        // Parent viewing child's transcript
        // (Add guardian logic if needed)

        // Academy admin/teacher
        $isMember = $student->academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'director', 'admin', 'teacher'])
            ->exists();

        return $isMember;
    }

    protected function canManageTranscripts(Academy $academy): bool
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
     * Get current user's transcripts
     */
    public function getMyTranscripts(Request $request): JsonResponse
    {
        $user = auth()->user();
        $academyId = $request->query('academy_id');

        $query = Student::where('user_id', $user->id);
        
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $student = $query->first();

        if (!$student) {
            return response()->json([
                'success' => true,
                'transcripts' => [],
            ]);
        }

        $transcripts = SemesterTranscript::where('student_id', $student->id)
            ->where('status', 'published')
            ->with(['semester.academicYear', 'classroom', 'items.course'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($transcript) {
                return [
                    'id' => $transcript->id,
                    'academic_year_name' => $transcript->semester->academicYear->name ?? '',
                    'semester_number' => $transcript->semester->semester_number ?? 1,
                    'gpa' => $transcript->gpa,
                    'total_credits' => $transcript->total_credits,
                    'class_rank' => $transcript->class_rank,
                    'grade_rank' => $transcript->grade_rank,
                    'gpax' => $transcript->gpax,
                    'total_accumulated_credits' => $transcript->total_accumulated_credits,
                    'items' => $transcript->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'subject_code' => $item->course->code ?? '',
                            'subject_name_th' => $item->course->name ?? '',
                            'subject_name_en' => $item->course->name_en ?? '',
                            'credits' => $item->credits,
                            'letter_grade' => $item->letter_grade,
                        ];
                    }),
                    'remarks' => $transcript->remarks,
                ];
            });

        return response()->json([
            'success' => true,
            'transcripts' => $transcripts,
        ]);
    }

    /**
     * Download current user's transcript PDF
     */
    public function downloadMyTranscriptPdf(int $transcriptId)
    {
        $user = auth()->user();
        
        $transcript = SemesterTranscript::with(['student.user', 'student.academy', 'semester.academicYear', 'classroom', 'items.course'])
            ->findOrFail($transcriptId);

        // Check if this transcript belongs to the current user
        if ($transcript->student->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        if ($transcript->status !== 'published') {
            return response()->json(['success' => false, 'message' => 'ใบ Transcript ยังไม่ได้เผยแพร่'], 400);
        }

        $pdf = Pdf::loadView('pdf.transcript', [
            'transcript' => $transcript,
            'student' => $transcript->student,
            'academy' => $transcript->student->academy,
        ]);

        return $pdf->download("transcript-{$transcript->student->student_number}.pdf");
    }
}
