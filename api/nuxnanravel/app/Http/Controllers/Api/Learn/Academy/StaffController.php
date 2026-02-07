<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\StaffProfile;
use App\Models\Position;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * แสดงรายชื่อบุคลากร
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = StaffProfile::where('academy_id', $academy->id)
            ->with(['user:id,name,avatar', 'position:id,name', 'department:id,name']);

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by employment type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->byDepartment($request->department_id);
        }

        // Filter by position
        if ($request->has('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $staff = $query->orderBy('employee_id')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $staff
        ]);
    }

    /**
     * แสดงรายละเอียดบุคลากร
     */
    public function show(Academy $academy, StaffProfile $staff): JsonResponse
    {
        $this->authorizeStaff($academy, $staff);

        $staff->load([
            'user:id,name,email,avatar',
            'position',
            'department',
            'supervisor.user:id,name',
            'attendances' => fn($q) => $q->latest()->limit(30),
            'leaveRequests' => fn($q) => $q->latest()->limit(10),
            'performanceReviews' => fn($q) => $q->latest()->limit(5),
        ]);

        return response()->json([
            'success' => true,
            'data' => $staff
        ]);
    }

    /**
     * สร้างบุคลากรใหม่
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'supervisor_id' => 'nullable|exists:staff_profiles,id',
            'employment_type' => 'required|in:full_time,part_time,contract,temporary',
            'hire_date' => 'required|date',
            'probation_end_date' => 'nullable|date|after:hire_date',
            'contract_end_date' => 'nullable|date|after:hire_date',
            'base_salary' => 'nullable|numeric|min:0',
            'work_location' => 'nullable|string|max:255',
            'phone_extension' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|array',
            'education_history' => 'nullable|array',
            'work_history' => 'nullable|array',
            'certifications' => 'nullable|array',
        ]);

        $validated['academy_id'] = $academy->id;
        $validated['status'] = StaffProfile::STATUS_ACTIVE;
        
        // Generate employee ID
        $validated['employee_id'] = $this->generateEmployeeId($academy);

        $staff = StaffProfile::create($validated);

        $this->auditLogService->log(
            'staff.create',
            $staff,
            $academy->id,
            'academy',
            ['employee_id' => $staff->employee_id]
        );

        return response()->json([
            'success' => true,
            'data' => $staff->load(['user', 'position', 'department']),
            'message' => 'สร้างข้อมูลบุคลากรเรียบร้อยแล้ว'
        ], 201);
    }

    /**
     * อัปเดตข้อมูลบุคลากร
     */
    public function update(Request $request, Academy $academy, StaffProfile $staff): JsonResponse
    {
        $this->authorizeStaff($academy, $staff);

        $validated = $request->validate([
            'position_id' => 'sometimes|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'supervisor_id' => 'nullable|exists:staff_profiles,id',
            'employment_type' => 'sometimes|in:full_time,part_time,contract,temporary',
            'hire_date' => 'sometimes|date',
            'probation_end_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'base_salary' => 'nullable|numeric|min:0',
            'work_location' => 'nullable|string|max:255',
            'phone_extension' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|array',
            'education_history' => 'nullable|array',
            'work_history' => 'nullable|array',
            'certifications' => 'nullable|array',
        ]);

        $staff->update($validated);

        $this->auditLogService->log(
            'staff.update',
            $staff,
            $academy->id,
            'academy',
            ['changes' => $validated]
        );

        return response()->json([
            'success' => true,
            'data' => $staff->fresh(['user', 'position', 'department']),
            'message' => 'อัปเดตข้อมูลบุคลากรเรียบร้อยแล้ว'
        ]);
    }

    /**
     * เปลี่ยนสถานะบุคลากร
     */
    public function updateStatus(Request $request, Academy $academy, StaffProfile $staff): JsonResponse
    {
        $this->authorizeStaff($academy, $staff);

        $validated = $request->validate([
            'status' => 'required|in:active,on_leave,suspended,resigned,terminated',
            'effective_date' => 'nullable|date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $staff->status;

        if (in_array($validated['status'], [StaffProfile::STATUS_RESIGNED, StaffProfile::STATUS_TERMINATED])) {
            $staff->termination_date = $validated['effective_date'] ?? now();
            $staff->termination_reason = $validated['reason'] ?? null;
        }

        $staff->status = $validated['status'];
        $staff->save();

        $this->auditLogService->log(
            'staff.status_change',
            $staff,
            $academy->id,
            'academy',
            [
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'reason' => $validated['reason'] ?? null
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $staff->fresh(),
            'message' => 'เปลี่ยนสถานะบุคลากรเรียบร้อยแล้ว'
        ]);
    }

    /**
     * ลบข้อมูลบุคลากร (Soft Delete)
     */
    public function destroy(Academy $academy, StaffProfile $staff): JsonResponse
    {
        $this->authorizeStaff($academy, $staff);

        $staff->delete();

        $this->auditLogService->log(
            'staff.delete',
            $staff,
            $academy->id,
            'academy',
            ['employee_id' => $staff->employee_id]
        );

        return response()->json([
            'success' => true,
            'message' => 'ลบข้อมูลบุคลากรเรียบร้อยแล้ว'
        ]);
    }

    /**
     * รายชื่อตำแหน่ง
     */
    public function positions(Request $request, Academy $academy): JsonResponse
    {
        $query = Position::byAcademy($academy->id);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        $positions = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $positions
        ]);
    }

    /**
     * สร้างตำแหน่งใหม่
     */
    public function storePosition(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:positions,code',
            'department_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
            'is_teaching_position' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['academy_id'] = $academy->id;

        $position = Position::create($validated);

        $this->auditLogService->log(
            'position.create',
            $position,
            $academy->id,
            'academy',
            ['name' => $position->name]
        );

        return response()->json([
            'success' => true,
            'data' => $position,
            'message' => 'สร้างตำแหน่งเรียบร้อยแล้ว'
        ], 201);
    }

    /**
     * อัปเดตตำแหน่ง
     */
    public function updatePosition(Request $request, Academy $academy, Position $position): JsonResponse
    {
        if ($position->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Position not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50|unique:positions,code,' . $position->id,
            'department_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
            'is_teaching_position' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $position->update($validated);

        return response()->json([
            'success' => true,
            'data' => $position->fresh(),
            'message' => 'อัปเดตตำแหน่งเรียบร้อยแล้ว'
        ]);
    }

    /**
     * ลบตำแหน่ง
     */
    public function destroyPosition(Academy $academy, Position $position): JsonResponse
    {
        if ($position->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Position not found'], 404);
        }

        // Check if position has staff
        if ($position->staffProfiles()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบตำแหน่งที่มีบุคลากรอยู่ได้'
            ], 400);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบตำแหน่งเรียบร้อยแล้ว'
        ]);
    }

    /**
     * Staff Directory - รายชื่อบุคลากรแบบย่อ
     */
    public function directory(Request $request, Academy $academy): JsonResponse
    {
        $staff = StaffProfile::where('academy_id', $academy->id)
            ->active()
            ->with(['user:id,name,avatar', 'position:id,name', 'department:id,name'])
            ->select(['id', 'user_id', 'employee_id', 'position_id', 'department_id', 'phone_extension', 'work_location'])
            ->orderBy('employee_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $staff
        ]);
    }

    /**
     * Staff Summary - สรุปจำนวนบุคลากร
     */
    public function summary(Academy $academy): JsonResponse
    {
        $totalStaff = StaffProfile::where('academy_id', $academy->id)->count();
        
        $byStatus = StaffProfile::where('academy_id', $academy->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byType = StaffProfile::where('academy_id', $academy->id)
            ->selectRaw('employment_type, COUNT(*) as count')
            ->groupBy('employment_type')
            ->pluck('count', 'employment_type');

        $byDepartment = StaffProfile::where('academy_id', $academy->id)
            ->with('department:id,name')
            ->selectRaw('department_id, COUNT(*) as count')
            ->groupBy('department_id')
            ->get()
            ->map(fn($item) => [
                'department' => $item->department?->name ?? 'ไม่ระบุ',
                'count' => $item->count
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalStaff,
                'by_status' => $byStatus,
                'by_type' => $byType,
                'by_department' => $byDepartment
            ]
        ]);
    }

    // Helper methods
    protected function authorizeStaff(Academy $academy, StaffProfile $staff): void
    {
        if ($staff->academy_id !== $academy->id) {
            abort(404, 'Staff not found');
        }
    }

    protected function generateEmployeeId(Academy $academy): string
    {
        $prefix = 'EMP';
        $year = date('Y');
        
        $lastStaff = StaffProfile::where('academy_id', $academy->id)
            ->where('employee_id', 'like', "{$prefix}{$year}%")
            ->orderByRaw('CAST(SUBSTRING(employee_id, 8) AS UNSIGNED) DESC')
            ->first();

        if ($lastStaff) {
            $lastNumber = intval(substr($lastStaff->employee_id, 7));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s%s%04d', $prefix, $year, $newNumber);
    }
}
