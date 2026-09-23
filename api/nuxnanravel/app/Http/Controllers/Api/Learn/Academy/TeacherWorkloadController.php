<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Exports\TeacherWorkloadExport;
use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\ClassSchedule;
use App\Models\Semester;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class TeacherWorkloadController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    private function resolveSemester(Request $request, Academy $academy): Semester|JsonResponse|null
    {
        $validator = Validator::make($request->all(), [
            'semester_id' => 'nullable|exists:semesters,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('semester_id')) {
            $semester = Semester::with('academicYear')->find($request->semester_id);
            if ($semester?->academicYear?->academy_id !== $academy->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => ['semester_id' => ['The selected semester id is invalid.']],
                ], 422);
            }

            return $semester;
        }

        return Semester::currentForAcademy($academy->id);
    }

    private function buildRows(Academy $academy, ?Semester $semester): array
    {
        $members = $academy->academyMembers()->where('status', 2)
            ->where(function ($q) {
                $q->where('role', 'teacher')->orWhereHas('academyRole', fn ($r) => $r->where('name', 'teacher'));
            })
            ->with('user:id,name,profile_photo_path')->get();

        if ($semester) {
            $schedules = ClassSchedule::byAcademy($academy->id)
                ->active()
                ->where('entry_type', '!=', 'break')
                ->bySemester($semester->id)
                ->get(['id', 'teacher_id', 'classroom_id', 'course_id', 'day_of_week', 'start_time', 'end_time']);
        } else {
            $schedules = collect();
        }

        $memberTeacherIds = $members->pluck('user_id');
        $scheduleTeacherIds = $schedules->pluck('teacher_id')->unique();
        $missingTeacherIds = $scheduleTeacherIds->diff($memberTeacherIds);

        $missingTeachers = User::whereIn('id', $missingTeacherIds)->get(['id', 'name', 'profile_photo_path']);

        $allTeachers = collect();
        foreach ($members as $member) {
            if ($member->user) {
                $allTeachers->put($member->user->id, [
                    'user' => $member->user,
                    'is_member_teacher' => true,
                ]);
            }
        }
        foreach ($missingTeachers as $teacher) {
            $allTeachers->put($teacher->id, [
                'user' => $teacher,
                'is_member_teacher' => false,
            ]);
        }

        $schedulesByTeacher = $schedules->groupBy('teacher_id');

        $rows = [];
        foreach ($allTeachers as $teacherId => $teacherData) {
            $user = $teacherData['user'];
            $teacherSchedules = $schedulesByTeacher->get($teacherId, collect());

            $periods_per_week = $teacherSchedules->count();

            $minutes_per_week = 0;
            foreach ($teacherSchedules as $s) {
                $minutes_per_week += $s->start_time->diffInMinutes($s->end_time);
            }
            // Carbon 3 คืน diffInMinutes เป็น float — ปัดเป็น int ไม่งั้น JSON ออกมาเป็น 170.0
            $minutes_per_week = (int) round($minutes_per_week);

            $course_count = $teacherSchedules->whereNotNull('course_id')->pluck('course_id')->unique()->count();
            $classroom_count = $teacherSchedules->pluck('classroom_id')->unique()->count();
            $teaching_days = $teacherSchedules->pluck('day_of_week')->unique()->count();

            $max_periods_per_day = 0;
            if ($periods_per_week > 0) {
                $max_periods_per_day = $teacherSchedules->groupBy('day_of_week')->map->count()->max();
            }

            $rows[] = [
                'teacher_id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ?? null,
                'is_member_teacher' => $teacherData['is_member_teacher'],
                'periods_per_week' => $periods_per_week,
                'minutes_per_week' => $minutes_per_week,
                'hours_per_week' => round($minutes_per_week / 60, 1),
                'course_count' => $course_count,
                'classroom_count' => $classroom_count,
                'teaching_days' => $teaching_days,
                'max_periods_per_day' => $max_periods_per_day,
            ];
        }

        return collect($rows)
            ->sortBy([
                ['periods_per_week', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->all();
    }

    public function index(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        $semester = $this->resolveSemester($request, $academy);

        if ($semester instanceof JsonResponse) {
            return $semester;
        }

        $rows = $this->buildRows($academy, $semester);
        $collection = collect($rows);

        $teacher_count = $collection->count();
        $teachers_without_periods = $collection->where('periods_per_week', 0)->count();
        $total_periods = $collection->sum('periods_per_week');
        $average_periods = $teacher_count > 0 ? round($total_periods / $teacher_count, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'semester' => $semester ? ['id' => $semester->id, 'name' => $semester->name] : null,
                'summary' => [
                    'teacher_count' => $teacher_count,
                    'teachers_without_periods' => $teachers_without_periods,
                    'total_periods' => $total_periods,
                    'average_periods' => $average_periods,
                ],
                'teachers' => $rows,
            ],
        ]);
    }

    public function export(Request $request, $academyId)
    {
        $academy = Academy::findOrFail($academyId);
        $semester = $this->resolveSemester($request, $academy);

        if ($semester instanceof JsonResponse) {
            return $semester;
        }

        $this->auditLogService->logExport('teacher_workload', ['semester_id' => $semester?->id], 'schedules');

        $rows = $this->buildRows($academy, $semester);

        return Excel::download(
            new TeacherWorkloadExport($rows, 'ภาระงานสอน'),
            'teacher-workload-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}
