<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\StaffAttendance;
use App\Models\StaffProfile;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffAttendanceController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * รายการลงเวลาทำงาน
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = StaffAttendance::whereHas('staffProfile', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->with(['staffProfile.user:id,name,avatar']);

        // Filter by staff
        if ($request->has('staff_profile_id')) {
            $query->byStaff($request->staff_profile_id);
        }

        // Filter by date range
        if ($request->has('date')) {
            $query->byDate($request->date);
        } elseif ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $attendances,
        ]);
    }

    /**
     * แสดงรายละเอียดการลงเวลา
     */
    public function show(Academy $academy, StaffAttendance $attendance): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        $attendance->load(['staffProfile.user:id,name,avatar']);

        return response()->json([
            'success' => true,
            'data' => $attendance,
        ]);
    }

    /**
     * Check In - ลงเวลาเข้า
     */
    public function checkIn(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'staff_profile_id' => 'required|exists:staff_profiles,id',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_in_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $staffProfile = StaffProfile::findOrFail($validated['staff_profile_id']);

        if ($staffProfile->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }

        $today = Carbon::today()->toDateString();

        // Check if already checked in today
        $existing = StaffAttendance::where('staff_profile_id', $staffProfile->id)
            ->where('attendance_date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'ลงเวลาเข้างานวันนี้แล้ว',
            ], 400);
        }

        $checkInTime = $validated['check_in_time']
            ? Carbon::createFromFormat('H:i', $validated['check_in_time'])
            : now();

        $attendance = StaffAttendance::create([
            'staff_profile_id' => $staffProfile->id,
            'attendance_date' => $today,
            'check_in_time' => $checkInTime->format('H:i:s'),
            'check_in_location' => $validated['check_in_location'] ?? null,
            'status' => 'present',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->auditLogService->log(
            'attendance.check_in',
            $attendance,
            $academy->id,
            'academy',
            ['check_in_time' => $checkInTime->format('H:i')]
        );

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'ลงเวลาเข้างานเรียบร้อยแล้ว',
        ], 201);
    }

    /**
     * Check Out - ลงเวลาออก
     */
    public function checkOut(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'staff_profile_id' => 'required|exists:staff_profiles,id',
            'check_out_time' => 'nullable|date_format:H:i',
            'check_out_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $staffProfile = StaffProfile::findOrFail($validated['staff_profile_id']);

        if ($staffProfile->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }

        $today = Carbon::today()->toDateString();

        $attendance = StaffAttendance::where('staff_profile_id', $staffProfile->id)
            ->where('attendance_date', $today)
            ->first();

        if (! $attendance) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบการลงเวลาเข้างานวันนี้',
            ], 400);
        }

        if ($attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'ลงเวลาออกงานวันนี้แล้ว',
            ], 400);
        }

        $checkOutTime = $validated['check_out_time']
            ? Carbon::createFromFormat('H:i', $validated['check_out_time'])
            : now();

        $attendance->update([
            'check_out_time' => $checkOutTime->format('H:i:s'),
            'check_out_location' => $validated['check_out_location'] ?? null,
            'notes' => $attendance->notes
                ? $attendance->notes."\n".($validated['notes'] ?? '')
                : ($validated['notes'] ?? null),
        ]);

        // Calculate work hours
        $attendance->calculateWorkHours();

        $this->auditLogService->log(
            'attendance.check_out',
            $attendance,
            $academy->id,
            'academy',
            [
                'check_out_time' => $checkOutTime->format('H:i'),
                'work_hours' => $attendance->work_hours,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $attendance->fresh(),
            'message' => 'ลงเวลาออกงานเรียบร้อยแล้ว',
        ]);
    }

    /**
     * บันทึกการลงเวลาแบบ manual
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'staff_profile_id' => 'required|exists:staff_profiles,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,absent,late,half_day,work_from_home,on_leave',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $staffProfile = StaffProfile::findOrFail($validated['staff_profile_id']);

        if ($staffProfile->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }

        // Check if attendance already exists
        $existing = StaffAttendance::where('staff_profile_id', $staffProfile->id)
            ->where('attendance_date', $validated['attendance_date'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'มีการลงเวลาวันนี้แล้ว',
            ], 400);
        }

        $attendance = StaffAttendance::create([
            'staff_profile_id' => $staffProfile->id,
            'attendance_date' => $validated['attendance_date'],
            'status' => $validated['status'],
            'check_in_time' => $validated['check_in_time'] ?? null,
            'check_out_time' => $validated['check_out_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($attendance->check_in_time && $attendance->check_out_time) {
            $attendance->calculateWorkHours();
        }

        $this->auditLogService->log(
            'attendance.create',
            $attendance,
            $academy->id,
            'academy',
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'บันทึกการลงเวลาเรียบร้อยแล้ว',
        ], 201);
    }

    /**
     * แก้ไขการลงเวลา
     */
    public function update(Request $request, Academy $academy, StaffAttendance $attendance): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        $validated = $request->validate([
            'status' => 'sometimes|in:present,absent,late,half_day,work_from_home,on_leave',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $attendance->update($validated);

        if ($attendance->check_in_time && $attendance->check_out_time) {
            $attendance->calculateWorkHours();
        }

        $this->auditLogService->log(
            'attendance.update',
            $attendance,
            $academy->id,
            'academy',
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $attendance->fresh(),
            'message' => 'แก้ไขการลงเวลาเรียบร้อยแล้ว',
        ]);
    }

    /**
     * ลบการลงเวลา
     */
    public function destroy(Academy $academy, StaffAttendance $attendance): JsonResponse
    {
        $this->authorizeAttendance($academy, $attendance);

        $attendance->delete();

        $this->auditLogService->log(
            'attendance.delete',
            $attendance,
            $academy->id,
            'academy',
            ['date' => $attendance->attendance_date]
        );

        return response()->json([
            'success' => true,
            'message' => 'ลบการลงเวลาเรียบร้อยแล้ว',
        ]);
    }

    /**
     * รายงานการลงเวลาประจำวัน
     */
    public function dailyReport(Request $request, Academy $academy): JsonResponse
    {
        $date = $request->get('date', Carbon::today()->toDateString());

        $allStaff = StaffProfile::where('academy_id', $academy->id)
            ->active()
            ->with('user:id,name,avatar')
            ->get();

        $attendances = StaffAttendance::whereHas('staffProfile', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->where('attendance_date', $date)->get()->keyBy('staff_profile_id');

        $report = $allStaff->map(function ($staff) use ($attendances) {
            $attendance = $attendances->get($staff->id);

            return [
                'staff' => [
                    'id' => $staff->id,
                    'employee_id' => $staff->employee_id,
                    'name' => $staff->user?->name,
                    'avatar' => $staff->user?->avatar,
                ],
                'attendance' => $attendance ? [
                    'status' => $attendance->status,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'work_hours' => $attendance->work_hours,
                ] : null,
            ];
        });

        $summary = [
            'date' => $date,
            'total_staff' => $allStaff->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $allStaff->count() - $attendances->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'on_leave' => $attendances->where('status', 'on_leave')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'staff' => $report,
            ],
        ]);
    }

    /**
     * รายงานการลงเวลารายบุคคล
     */
    public function staffReport(Request $request, Academy $academy, StaffProfile $staff): JsonResponse
    {
        $this->authorizeStaff($academy, $staff);

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $attendances = StaffAttendance::where('staff_profile_id', $staff->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date')
            ->get();

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'on_leave' => $attendances->where('status', 'on_leave')->count(),
            'work_from_home' => $attendances->where('status', 'work_from_home')->count(),
            'total_work_hours' => $attendances->sum('work_hours'),
            'average_work_hours' => $attendances->avg('work_hours') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'staff' => $staff->only(['id', 'employee_id']),
                'summary' => $summary,
                'attendances' => $attendances,
            ],
        ]);
    }

    // Helper methods
    protected function authorizeAttendance(Academy $academy, StaffAttendance $attendance): void
    {
        if ($attendance->staffProfile->academy_id !== $academy->id) {
            abort(404, 'Attendance not found');
        }
    }

    protected function authorizeStaff(Academy $academy, StaffProfile $staff): void
    {
        if ($staff->academy_id !== $academy->id) {
            abort(404, 'Staff not found');
        }
    }
}
