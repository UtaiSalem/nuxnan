<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\CourseMember;
use App\Models\SchoolAnnouncement;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\TuitionFee;
use App\Models\User;
use App\Services\GuardianAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->with(['currentEnrollment.classroom'])
            ->get();

        return response()->json([
            'success' => true,
            'children' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->first_name_th.' '.$student->last_name_th,
                    'name_en' => $student->first_name_en.' '.$student->last_name_en,
                    'photo' => $student->profile_image_url,
                    'classroom' => $student->currentEnrollment?->classroom?->name ?? 'ไม่มีห้องเรียน',
                    'grade_level' => $student->currentEnrollment?->classroom?->grade_level ?? '-',
                    'student_number' => $student->currentEnrollment?->student_number ?? '-',
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
            'currentEnrollment.classroom',
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
                'photo' => $student->profile_image_url,
                'birth_date' => $student->birth_date,
                'age' => $student->birth_date ? now()->diffInYears($student->birth_date) : null,
                'blood_type' => $student->blood_type,
                'nationality' => $student->nationality,
                'religion' => $student->religion,
                'classroom' => $student->currentEnrollment?->classroom ? [
                    'id' => $student->currentEnrollment->classroom->id,
                    'name' => $student->currentEnrollment->classroom->name,
                    'grade_level' => $student->currentEnrollment->classroom->grade_level,
                    'student_number' => $student->currentEnrollment->student_number,
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
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->format('Y-m-d');

        // Get student attendance from DB join
        $attendances = DB::table('school_attendance_records')
            ->join('school_attendances', 'school_attendance_records.attendance_id', '=', 'school_attendances.id')
            ->where('school_attendance_records.student_id', $student->id)
            ->where('school_attendance_records.academy_id', $academy->id)
            ->whereBetween('school_attendances.date', [$startDate, $endDate])
            ->select(
                'school_attendances.date',
                'school_attendances.title',
                'school_attendance_records.status as status',
                'school_attendance_records.checked_in_at',
                'school_attendance_records.remarks'
            )
            ->orderBy('school_attendances.date')
            ->get();

        $summary = $attendances->groupBy('status')->map->count()->toArray();
        $summary['total'] = $attendances->count();

        return response()->json([
            'success' => true,
            'month' => $month,
            'summary' => $summary,
            'attendance' => $attendances->map(function ($a) {
                return [
                    'date' => $a->date,
                    'title' => $a->title,
                    'status' => $a->status,
                    'checked_in_at' => $a->checked_in_at,
                    'remarks' => $a->remarks,
                ];
            }),
        ], 200);
    }

    /**
     * Get announcements/news for parent
     */
    public function getAnnouncements(Academy $academy, Request $request)
    {
        $announcements = SchoolAnnouncement::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereJsonContains('target_audience', 'all')
                    ->orWhereJsonContains('target_audience', 'parents')
                    ->orWhereJsonContains('target_audience', 'parent');
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'announcements' => $announcements->map(function ($a) {
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'content' => $a->content,
                    'announcement_type' => $a->announcement_type,
                    'priority' => $a->priority,
                    'is_pinned' => $a->is_pinned,
                    'published_at' => $a->published_at,
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
            ->where('start_datetime', '>=', now())
            ->where('status', 'published')
            ->orderBy('start_datetime')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'events' => $events->map(function ($e) {
                return [
                    'id' => $e->id,
                    'title' => $e->title,
                    'description' => $e->description,
                    'event_type' => $e->event_type,
                    'category' => $e->category,
                    'location' => $e->location,
                    'start_datetime' => $e->start_datetime,
                    'end_datetime' => $e->end_datetime,
                    'is_all_day' => $e->is_all_day,
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

        $fees = TuitionFee::whereIn('student_id', $children)
            ->with(['feeStructure', 'payments'])
            ->orderBy('due_date')
            ->get();

        $summary = [
            'total_due' => $fees->where('status', '!=', 'paid')->sum('balance_amount'),
            'total_paid' => $fees->sum('paid_amount'),
            'overdue_count' => $fees->filter(function ($fee) {
                return $fee->due_date < now() && $fee->balance_amount > 0;
            })->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'fees' => $fees->map(function ($f) {
                return [
                    'id' => $f->id,
                    'invoice_number' => $f->invoice_number,
                    'total_amount' => $f->total_amount,
                    'discount_amount' => $f->discount_amount,
                    'net_amount' => $f->net_amount,
                    'paid_amount' => $f->paid_amount,
                    'balance_amount' => $f->balance_amount,
                    'due_date' => $f->due_date,
                    'paid_date' => $f->paid_date,
                    'status' => $f->status,
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
