<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
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
        
        // Find students linked to this parent
        // Method 1: Via AcademyMember with parent role
        // Method 2: Via StudentGuardian with linked user_id
        
        // Get students where this user is a guardian
        $studentIds = StudentGuardian::whereHas('student', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })
        ->where(function ($q) use ($user) {
            // Match by citizen_id if available
            if ($user->citizen_id) {
                $q->where('citizen_id', $user->citizen_id);
            }
            // Or match by phone
            $q->orWhereHas('contacts', function ($cq) use ($user) {
                $cq->where('contact_type', 'phone')
                   ->where('contact_value', $user->phone);
            });
            // Or match by email
            $q->orWhereHas('contacts', function ($cq) use ($user) {
                $cq->where('contact_type', 'email')
                   ->where('contact_value', $user->email);
            });
        })
        ->pluck('student_id');

        $students = Student::whereIn('id', $studentIds)
            ->with(['currentClassroom.classroom'])
            ->get();

        return response()->json([
            'success' => true,
            'children' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->first_name_th . ' ' . $student->last_name_th,
                    'name_en' => $student->first_name_en . ' ' . $student->last_name_en,
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
        if (!$this->canAccessStudent($request->user(), $student)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้'
            ], 403);
        }

        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ], 404);
        }

        $student->load([
            'currentClassroom.classroom',
            'guardians.primaryContact',
        ]);

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->first_name_th . ' ' . $student->last_name_th,
                'name_en' => $student->first_name_en . ' ' . $student->last_name_en,
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
                'guardians' => $student->guardians->map(function ($g) {
                    return [
                        'id' => $g->id,
                        'type' => $g->guardian_type,
                        'full_name' => $g->full_name,
                        'relationship' => $g->relationship,
                        'is_primary' => $g->is_primary_contact,
                        'phone' => $g->primaryContact?->contact_value,
                    ];
                }),
            ],
        ], 200);
    }

    /**
     * Get grades for a child
     */
    public function getChildGrades(Academy $academy, Student $student, Request $request)
    {
        if (!$this->canAccessStudent($request->user(), $student)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้'
            ], 403);
        }

        // Get course enrollments and grades
        $enrollments = \App\Models\CourseMember::where('user_id', $student->user_id)
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
        if (!$this->canAccessStudent($request->user(), $student)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้'
            ], 403);
        }

        $month = $request->get('month', now()->format('Y-m'));
        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get student attendance from classroom
        $attendances = \App\Models\StudentAttendance::where('student_id', $student->id)
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
                    'day_name' => $a->date ? \Carbon\Carbon::parse($a->date)->locale('th')->dayName : '',
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
        $announcements = \App\Models\Announcement::where('academy_id', $academy->id)
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
        $events = \App\Models\SchoolEvent::where('academy_id', $academy->id)
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

        $fees = \App\Models\StudentFee::whereIn('student_id', $children)
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
        // Check if user is guardian of this student
        $isGuardian = StudentGuardian::where('student_id', $student->id)
            ->where(function ($q) use ($user) {
                if ($user->citizen_id) {
                    $q->where('citizen_id', $user->citizen_id);
                }
                $q->orWhereHas('contacts', function ($cq) use ($user) {
                    $cq->where('contact_value', $user->phone)
                       ->orWhere('contact_value', $user->email);
                });
            })
            ->exists();

        return $isGuardian;
    }

    /**
     * Get student IDs for parent
     */
    private function getStudentIdsForParent(User $user, Academy $academy): array
    {
        return StudentGuardian::whereHas('student', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })
        ->where(function ($q) use ($user) {
            if ($user->citizen_id) {
                $q->where('citizen_id', $user->citizen_id);
            }
            $q->orWhereHas('contacts', function ($cq) use ($user) {
                $cq->where('contact_value', $user->phone)
                   ->orWhere('contact_value', $user->email);
            });
        })
        ->pluck('student_id')
        ->toArray();
    }
}
