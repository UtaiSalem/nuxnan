<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Behavior\StoreBehaviorRecordRequest;
use App\Models\Academy;
use App\Models\Learn\Academy\BehaviorRecord;
use App\Models\Learn\Academy\BehaviorScore;
use App\Models\Student;
use App\Services\BehaviorService;
use App\Traits\ManagesEventPermissions;
use Illuminate\Http\Request;

class BehaviorRecordController extends Controller
{
    use ManagesEventPermissions;

    public function __construct(private BehaviorService $behaviorService) {}

    public function index(Request $request, Academy $academy)
    {
        $query = BehaviorRecord::where('academy_id', $academy->id)
            ->with(['student', 'category', 'recorder', 'classroom']);

        // Filters
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('incident_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('incident_date', '<=', $request->date_to);
        }

        $records = $query->orderByDesc('incident_date')->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $records]);
    }

    public function store(StoreBehaviorRecordRequest $request, Academy $academy)
    {
        // ตรวจสิทธิ์ — ครูขึ้นไป
        if (! $this->isAcademyAdmin($request->user(), $academy)) {
            // TODO: ตรวจ behavior.record permission ด้วย ถ้าไม่ใช่ admin
        }

        $student = Student::findOrFail($request->student_id);

        $record = $this->behaviorService->recordBehavior(
            $academy,
            $student,
            array_merge($request->validated(), ['recorded_by' => $request->user()->id])
        );

        $record->load(['student', 'category', 'recorder']);

        return response()->json([
            'success' => true,
            'data' => $record,
            'message' => $record->status === 'pending_approval' ? 'บันทึกแล้ว รอการอนุมัติ' : 'บันทึกสำเร็จ',
        ], 201);
    }

    public function show(Academy $academy, BehaviorRecord $record)
    {
        $record->load(['student', 'category', 'recorder', 'approver', 'classroom', 'session']);

        return response()->json(['success' => true, 'data' => $record]);
    }

    public function update(StoreBehaviorRecordRequest $request, Academy $academy, BehaviorRecord $record)
    {
        // แก้ไขได้เฉพาะก่อน approve
        if (in_array($record->status, ['approved', 'rejected'])) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถแก้ไขได้'], 422);
        }

        $record->update($request->validated());

        return response()->json(['success' => true, 'data' => $record, 'message' => 'อัปเดตสำเร็จ']);
    }

    public function destroy(Academy $academy, BehaviorRecord $record)
    {
        if (in_array($record->status, ['approved'])) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบได้'], 422);
        }

        $record->delete();

        return response()->json(['success' => true, 'message' => 'ลบสำเร็จ']);
    }

    public function approve(Request $request, Academy $academy, BehaviorRecord $record)
    {
        // ตรวจสิทธิ์ — ต้องเป็น admin/director เท่านั้น
        if (! $this->isAcademyAdmin($request->user(), $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $record = $this->behaviorService->approveRecord($record, $request->user());
        $record->load(['student', 'category', 'recorder', 'approver']);

        return response()->json(['success' => true, 'data' => $record, 'message' => 'อนุมัติสำเร็จ']);
    }

    public function reject(Request $request, Academy $academy, BehaviorRecord $record)
    {
        if (! $this->isAcademyAdmin($request->user(), $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate(['reason' => 'required|string|max:1000']);

        $record = $this->behaviorService->rejectRecord($record, $request->user(), $request->reason);

        return response()->json(['success' => true, 'data' => $record, 'message' => 'ปฏิเสธสำเร็จ']);
    }

    public function pendingApprovals(Academy $academy)
    {
        $records = BehaviorRecord::where('academy_id', $academy->id)
            ->where('status', 'pending_approval')
            ->with(['student', 'category', 'recorder'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $records]);
    }

    public function studentHistory(Academy $academy, Student $student)
    {
        $records = BehaviorRecord::where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->with(['category', 'recorder'])
            ->orderByDesc('incident_date')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $records]);
    }

    public function studentScore(Academy $academy, Student $student)
    {
        $scores = BehaviorScore::where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->orderByDesc('academic_year_id')
            ->get();

        return response()->json(['success' => true, 'data' => $scores]);
    }
}
