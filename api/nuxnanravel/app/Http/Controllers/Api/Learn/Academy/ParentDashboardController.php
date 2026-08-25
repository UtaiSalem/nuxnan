<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Announcement;
use App\Models\CourseMember;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\GuardianAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Parent Dashboard Controller - ระบบดูข้อมูลบุตรหลานสำหรับผู้ปกครอง
 */
class ParentDashboardController extends Controller
{
    /**
     * Get list of children for the authenticated parent
     */
    public function getMyChildren(Academy $academy, Request $request)
    {
        $user = $request->user();

        $studentIds = app(GuardianAccessService::class)->guardianStudentIds($user, $academy);

        $students = Student::whereIn('id', $studentIds)
            ->with(['currentClassroom.classroom'])
            ->get();

        return response()->json([
            'success' => true,
            'children' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->first_name_th.' '.$student->last_name_th,
                    'name_en' => $student->first_name_en.' '.$student->last_name_en,
                    'photo' => $student->photo_url,
                    'classroom' => $student->currentClassroom?->classroom?->name ?? 'ไม่มีห้องเรียน',
                    'grade_level' => $student->currentClassroom?->classroom?->grade_level ?? '-',
                    'student_number' => $student->currentClassroom?->student_number ?? '-',
                    'birth_date' => $student->birth_date,
                    'status' => $student->status,
                ];
            }),
        ], 200);
    }

    /**
     * Get detailed info for a specific child
     */
    public function getChildDetail(Academy $academy, Student $student, Request $request)
    {
        // Verify parent has access to this student
        if (! $this->canAccessStudent($request->user(), $student)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้',
            ], 403);
        }

        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน',
            ], 404);
        }

        $student->load([
            'currentClassroom.classroom',
            'guardianLinks.guardian.contacts',
        ]);

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->first_name_th.' '.$student->last_name_th,
                'name_en' => $student->first_name_en.' '.$student->last_name_en,
                'nickname' => $student->nickname,
                'photo' => $student->photo_url,
                'birth_date' => $student->birth_date,
                'age' => $student->birth_date ? now()->diffInYears($student->birth_date) : null,
                'blood_type' => $student->blood_type,
                'nationality' => $student->nationality,
                'religion' => $student->religion,
                'classroom' => $student->currentClassroom?->classroom ? [
                    'id' => $student->currentClassroom->classroom->id,
                    'name' => $student->currentClassroom->classroom->name,
                    'grade_level' => $student->currentClassroom->classroom->grade_level,
                    'student_number' => $student->currentClassroom->student_number,
                ] : null,
                'guardians' => $student->guardianLinks->map(function ($link) {
                    return [
                        'id' => $link->id,
                        'guardian_id' => $link->guardian_id,
                        'type' => $link->guardian_type,
                        'full_name' => $link->full_name,
                        'relationship' => $link->relationship,
                        'is_primary' => $link->is_primary_contact,
                        'phone' => $link->primary_phone,
                    ];
                })->values(),
            ],
        ], 200);
    }

    /**
     * Get grades for a child
     */
    public function getChildGrades(Academy $academy, Student $student, Request $request)
    {
        if (! $this->canAccessStudent($request->user(), $student)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้',
            ], 403);
        }

        // Get course enrollments and grades
        $enrollments = CourseMember::where('user_id', $student->user_id)
            ->whereHas('course', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })
            ->with(['course:id,name,code', 'grade'])
            ->get();

        return response()->json([
            'success' => true,
            'grades' => $enrollments->map(function ($e) {
                return [
                    'course_id' => $e->course_id,
                    'course_name' => $e->course?->name ?? 'ไม่ทราบ',
                    'course_code' => $e->course?->code ?? '-',
                    'grade' => $e->grade?->letter_grade ?? '-',
                    'score' => $e->grade?->final_score ?? null,
                    'status' => $e->status,
                ];
            }),
        ], 200);
    }

    /**
     * Get attendance for a child
     */
    public function getChildAttendance(Academy $academy, Student $student, Request $request)
    {
        if (! $this->canAccessStudent($request->user(), $student)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้',
            ], 403);
        }

        $month = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get student attendance from classroom
        $attendances = StudentAttendance::where('student_id', $student->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $summary = [
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'leave' => $attendances->whereIn('status', ['sick', 'leave'])->count(),
            'total_days' => $attendances->count(),
        ];

        return response()->json([
            'success' => true,
            'month' => $month,
            'summary' => $summary,
            'attendance' => $attendances->map(function ($a) {
                return [
                    'date' => $a->date,
                    'day_name' => $a->date ? Carbon::parse($a->date)->locale('th')->dayName : '',
                    'status' => $a->status,
                    'note' => $a->note,
                    'check_in' => $a->check_in_time,
                    'check_out' => $a->check_out_time,
                ];
            }),
        ], 200);
    }

    /**
     * Get announcements/news for parent
     */
    public function getAnnouncements(Academy $academy, Request $request)
    {
        $announcements = Announcement::where('academy_id', $academy->id)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('target_audience')
                    ->orWhere('target_audience', 'all')
                    ->orWhere('target_audience', 'LIKE', '%parent%');
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'announcements' => $announcements->map(function ($a) {
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'content' => $a->content,
                    'excerpt' => \Str::limit(strip_tags($a->content), 150),
                    'is_pinned' => $a->is_pinned,
                    'category' => $a->category,
                    'published_at' => $a->published_at,
                    'author' => $a->author?->name ?? 'ฝ่ายบริหาร',
                ];
            }),
        ], 200);
    }

    /**
     * Get upcoming events for parent
     */
    public function getUpcomingEvents(Academy $academy, Request $request)
    {
        $events = SchoolEvent::where('academy_id', $academy->id)
            ->where('start_date', '>=', now())
            ->where('status', 'active')
            ->orderBy('start_date')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'events' => $events->map(function ($e) {
                return [
                    'id' => $e->id,
                    'title' => $e->title,
                    'description' => $e->description,
                    'start_date' => $e->start_date,
                    'end_date' => $e->end_date,
                    'location' => $e->location,
                    'category' => $e->category,
                    'is_holiday' => $e->is_holiday,
                ];
            }),
        ], 200);
    }

    /**
     * Get tuition fees status for children
     */
    public function getFeeStatus(Academy $academy, Request $request)
    {
        $user = $request->user();
        $children = $this->getStudentIdsForParent($user, $academy);

        $fees = StudentFee::whereIn('student_id', $children)
            ->with(['feeStructure', 'payments'])
            ->orderBy('due_date')
            ->get();

        $summary = [
            'total_due' => $fees->where('status', 'pending')->sum('amount'),
            'total_paid' => $fees->where('status', 'paid')->sum('amount'),
            'overdue_count' => $fees->where('status', 'overdue')->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'fees' => $fees->map(function ($f) {
                return [
                    'id' => $f->id,
                    'description' => $f->feeStructure?->name ?? $f->description,
                    'amount' => $f->amount,
                    'due_date' => $f->due_date,
                    'status' => $f->status,
                    'paid_date' => $f->paid_date,
                    'student_id' => $f->student_id,
                ];
            }),
        ], 200);
    }

    /**
     * Check if parent can access student
     */
    private function canAccessStudent(User $user, Student $student): bool
    {
        return app(GuardianAccessService::class)->isGuardianOf($user, $student);
    }

    /**
     * Get student IDs for parent
     */
    private function getStudentIdsForParent(User $user, Academy $academy): array
    {
        return app(GuardianAccessService::class)->guardianStudentIds($user, $academy);
    }
}
