<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\StaffProfile;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * รายการคำขอลา
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = LeaveRequest::whereHas('staffProfile', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->with([
            'staffProfile.user:id,name,profile_photo_path',
            'leaveType:id,name,code',
            'approver:id,name',
        ]);

        // Filter by staff
        if ($request->has('staff_profile_id')) {
            $query->byStaff($request->staff_profile_id);
        }

        // Filter by leave type
        if ($request->has('leave_type_id')) {
            $query->byType($request->leave_type_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Filter pending requests
        if ($request->has('pending_only') && $request->pending_only) {
            $query->pending();
        }

        $leaves = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $leaves,
        ]);
    }

    /**
     * แสดงรายละเอียดคำขอลา
     */
    public function show(Academy $academy, LeaveRequest $leave): JsonResponse
    {
        $this->authorizeLeave($academy, $leave);

        $leave->load([
            'staffProfile.user:id,name,email,profile_photo_path',
            'leaveType',
            'approver:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $leave,
        ]);
    }

    /**
     * ส่งคำขอลา
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'staff_profile_id' => 'required|exists:staff_profiles,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_period' => 'nullable|in:full_day,morning,afternoon',
            'reason' => 'required|string|max:1000',
            'document_path' => 'nullable|string|max:255',
            'contact_during_leave' => 'nullable|string|max:255',
        ]);

        $staffProfile = StaffProfile::findOrFail($validated['staff_profile_id']);

        if ($staffProfile->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        // Calculate total days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // ลาครึ่งวัน (เช้า/บ่าย) นับเป็น 0.5 วัน
        $leavePeriod = $validated['leave_period'] ?? LeaveRequest::PERIOD_FULL_DAY;

        if ($leavePeriod !== LeaveRequest::PERIOD_FULL_DAY) {
            $totalDays = 0.5;
        }

        // Check leave balance — บังคับโควตาเฉพาะประเภทลาที่กำหนดจำนวนวันสูงสุดไว้
        if ($leaveType->max_days_per_year !== null) {
            $usedLeave = LeaveRequest::where('staff_profile_id', $staffProfile->id)
                ->where('leave_type_id', $leaveType->id)
                ->whereYear('start_date', now()->year)
                ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
                ->sum('total_days');

            $availableBalance = $leaveType->max_days_per_year - $usedLeave;

            if ($totalDays > $availableBalance) {
                return response()->json([
                    'success' => false,
                    'message' => "วันลาคงเหลือไม่เพียงพอ (เหลือ {$availableBalance} วัน)",
                ], 400);
            }
        }

        // Check for overlapping leave requests
        $overlapping = LeaveRequest::where('staff_profile_id', $staffProfile->id)
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'มีคำขอลาที่ซ้อนทับกับช่วงเวลานี้อยู่แล้ว',
            ], 400);
        }

        $leave = LeaveRequest::create([
            'staff_profile_id' => $staffProfile->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'leave_period' => $leavePeriod,
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'document_path' => $validated['document_path'] ?? null,
            'contact_during_leave' => $validated['contact_during_leave'] ?? null,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->auditLogService->log(
            action: 'leave.request',
            entity: $leave,
            module: 'academy',
            metadata: [
                'leave_type' => $leaveType->name,
                'days' => $totalDays,
                'dates' => "{$validated['start_date']} - {$validated['end_date']}",
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $leave->load(['leaveType', 'staffProfile.user:id,name']),
            'message' => 'ส่งคำขอลาเรียบร้อยแล้ว',
        ], 201);
    }

    /**
     * อนุมัติคำขอลา
     */
    public function approve(Request $request, Academy $academy, LeaveRequest $leave): JsonResponse
    {
        $this->authorizeLeave($academy, $leave);

        if ($leave->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'คำขอนี้ได้รับการดำเนินการแล้ว',
            ], 400);
        }

        $validated = $request->validate([
            'approver_notes' => 'nullable|string|max:1000',
        ]);

        $leave->approve(auth()->id(), $validated['approver_notes'] ?? null);

        $this->auditLogService->log(
            action: 'leave.approve',
            entity: $leave,
            module: 'academy',
            metadata: ['days' => $leave->total_days]
        );

        return response()->json([
            'success' => true,
            'data' => $leave->fresh(['staffProfile.user:id,name', 'leaveType', 'approver:id,name']),
            'message' => 'อนุมัติคำขอลาเรียบร้อยแล้ว',
        ]);
    }

    /**
     * ปฏิเสธคำขอลา
     */
    public function reject(Request $request, Academy $academy, LeaveRequest $leave): JsonResponse
    {
        $this->authorizeLeave($academy, $leave);

        if ($leave->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'คำขอนี้ได้รับการดำเนินการแล้ว',
            ], 400);
        }

        $validated = $request->validate([
            'approver_notes' => 'required|string|max:1000',
        ]);

        $leave->reject(auth()->id(), $validated['approver_notes']);

        $this->auditLogService->log(
            action: 'leave.reject',
            entity: $leave,
            module: 'academy',
            metadata: ['reason' => $validated['approver_notes']]
        );

        return response()->json([
            'success' => true,
            'data' => $leave->fresh(['staffProfile.user:id,name', 'leaveType', 'approver:id,name']),
            'message' => 'ปฏิเสธคำขอลาเรียบร้อยแล้ว',
        ]);
    }

    /**
     * ยกเลิกคำขอลา
     */
    public function cancel(Academy $academy, LeaveRequest $leave): JsonResponse
    {
        $this->authorizeLeave($academy, $leave);

        if (! in_array($leave->status, [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยกเลิกคำขอนี้ได้',
            ], 400);
        }

        $leave->cancel();

        $this->auditLogService->log(
            action: 'leave.cancel',
            entity: $leave,
            module: 'academy',
            metadata: []
        );

        return response()->json([
            'success' => true,
            'data' => $leave->fresh(),
            'message' => 'ยกเลิกคำขอลาเรียบร้อยแล้ว',
        ]);
    }

    /**
     * ประเภทการลา
     */
    public function leaveTypes(Academy $academy): JsonResponse
    {
        $types = LeaveType::byAcademy($academy->id)
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * สร้างประเภทการลา
     */
    public function storeLeaveType(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:leave_types,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_days_per_year' => 'nullable|integer|min:0',
            'is_paid' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'requires_document' => 'nullable|boolean',
            'advance_notice_days' => 'nullable|integer|min:0',
            'applicable_to' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['academy_id'] = $academy->id;

        $type = LeaveType::create($validated);

        $this->auditLogService->log(
            action: 'leave_type.create',
            entity: $type,
            module: 'academy',
            metadata: ['name' => $type->name]
        );

        return response()->json([
            'success' => true,
            'data' => $type,
            'message' => 'สร้างประเภทการลาเรียบร้อยแล้ว',
        ], 201);
    }

    /**
     * อัปเดตประเภทการลา
     */
    public function updateLeaveType(Request $request, Academy $academy, LeaveType $leaveType): JsonResponse
    {
        if ($leaveType->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Leave type not found'], 404);
        }

        $validated = $request->validate([
            'code' => 'sometimes|string|max:10|unique:leave_types,code,'.$leaveType->id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'max_days_per_year' => 'nullable|integer|min:0',
            'is_paid' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'requires_document' => 'nullable|boolean',
            'advance_notice_days' => 'nullable|integer|min:0',
            'applicable_to' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $leaveType->update($validated);

        return response()->json([
            'success' => true,
            'data' => $leaveType->fresh(),
            'message' => 'อัปเดตประเภทการลาเรียบร้อยแล้ว',
        ]);
    }

    /**
     * สรุปวันลารายบุคคล
     */
    public function staffLeaveBalance(Academy $academy, StaffProfile $staff): JsonResponse
    {
        $this->authorizeStaff($academy, $staff);

        $leaveTypes = LeaveType::byAcademy($academy->id)->active()->get();

        $balances = $leaveTypes->map(function ($type) use ($staff) {
            $used = LeaveRequest::where('staff_profile_id', $staff->id)
                ->where('leave_type_id', $type->id)
                ->whereYear('start_date', now()->year)
                ->approved()
                ->sum('total_days');

            $pending = LeaveRequest::where('staff_profile_id', $staff->id)
                ->where('leave_type_id', $type->id)
                ->whereYear('start_date', now()->year)
                ->pending()
                ->sum('total_days');

            return [
                'leave_type' => [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                ],
                'entitlement' => $type->max_days_per_year,
                'used' => $used,
                'pending' => $pending,
                'available' => $type->max_days_per_year !== null
                    ? $type->max_days_per_year - $used - $pending
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'year' => now()->year,
                'staff' => $staff->only(['id', 'employee_id']),
                'balances' => $balances,
            ],
        ]);
    }

    /**
     * สรุปการลาของ Academy
     */
    public function summary(Request $request, Academy $academy): JsonResponse
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month');

        $query = LeaveRequest::whereHas('staffProfile', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->whereYear('start_date', $year);

        if ($month) {
            $query->whereMonth('start_date', $month);
        }

        // By status
        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as count, SUM(total_days) as total_days')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // By leave type
        $byType = (clone $query)
            ->with('leaveType:id,name')
            ->selectRaw('leave_type_id, COUNT(*) as count, SUM(total_days) as total_days')
            ->groupBy('leave_type_id')
            ->get()
            ->map(fn ($item) => [
                'leave_type' => $item->leaveType?->name ?? 'ไม่ระบุ',
                'count' => $item->count,
                'total_days' => $item->total_days,
            ]);

        // Pending count for quick reference
        $pendingCount = LeaveRequest::whereHas('staffProfile', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->pending()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'pending_count' => $pendingCount,
                'by_status' => $byStatus,
                'by_type' => $byType,
            ],
        ]);
    }

    // Helper methods
    protected function authorizeLeave(Academy $academy, LeaveRequest $leave): void
    {
        if ($leave->staffProfile->academy_id !== $academy->id) {
            abort(404, 'Leave request not found');
        }
    }

    protected function authorizeStaff(Academy $academy, StaffProfile $staff): void
    {
        if ($staff->academy_id !== $academy->id) {
            abort(404, 'Staff not found');
        }
    }
}
