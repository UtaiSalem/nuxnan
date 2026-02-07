<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\TuitionFee;
use App\Models\TuitionFeeItem;
use App\Models\FeeStructure;
use App\Models\FeeItem;
use App\Models\FeeDiscount;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * TuitionFeeController - จัดการค่าเล่าเรียน
 */
class TuitionFeeController extends Controller
{
    protected AuditLogService $auditService;

    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * รายการใบแจ้งหนี้ค่าเล่าเรียน
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = TuitionFee::where('academy_id', $academy->id)
            ->with(['user:id,name,email,profile_photo_path', 'academicYear:id,name', 'semester:id,name']);

        // Filters
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $tuitionFees = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $tuitionFees->items(),
            'meta' => [
                'current_page' => $tuitionFees->currentPage(),
                'per_page' => $tuitionFees->perPage(),
                'total' => $tuitionFees->total(),
                'last_page' => $tuitionFees->lastPage(),
            ],
        ]);
    }

    /**
     * ดูใบแจ้งหนี้
     */
    public function show(Academy $academy, TuitionFee $tuitionFee): JsonResponse
    {
        $tuitionFee->load([
            'user:id,name,email,profile_photo_path',
            'academicYear:id,name',
            'semester:id,name',
            'classroom:id,name',
            'items.feeItem',
            'discounts.scholarship',
            'payments',
        ]);

        $this->auditService->logView($tuitionFee, request());

        return response()->json([
            'success' => true,
            'data' => $tuitionFee,
        ]);
    }

    /**
     * สร้างใบแจ้งหนี้ใหม่
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'fee_structure_id' => 'nullable|exists:fee_structures,id',
            'due_date' => 'required|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.fee_item_id' => 'nullable|exists:fee_items,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.fee_type' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Create tuition fee
            $tuitionFee = TuitionFee::create([
                'academy_id' => $academy->id,
                'user_id' => $validated['user_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'semester_id' => $validated['semester_id'],
                'classroom_id' => $validated['classroom_id'] ?? null,
                'fee_structure_id' => $validated['fee_structure_id'] ?? null,
                'invoice_number' => TuitionFee::generateInvoiceNumber($academy->id),
                'due_date' => $validated['due_date'],
                'status' => TuitionFee::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create items
            $totalAmount = 0;
            $totalDiscount = 0;

            foreach ($validated['items'] as $item) {
                $discount = $item['discount'] ?? 0;
                $netAmount = $item['amount'] - $discount;

                TuitionFeeItem::create([
                    'tuition_fee_id' => $tuitionFee->id,
                    'fee_item_id' => $item['fee_item_id'] ?? null,
                    'name' => $item['name'],
                    'fee_type' => $item['fee_type'],
                    'amount' => $item['amount'],
                    'discount' => $discount,
                    'net_amount' => $netAmount,
                ]);

                $totalAmount += $item['amount'];
                $totalDiscount += $discount;
            }

            // Update totals
            $tuitionFee->update([
                'total_amount' => $totalAmount,
                'discount_amount' => $totalDiscount,
                'net_amount' => $totalAmount - $totalDiscount,
                'remaining_amount' => $totalAmount - $totalDiscount,
            ]);

            DB::commit();

            $this->auditService->logCreate($tuitionFee, request());

            return response()->json([
                'success' => true,
                'message' => 'สร้างใบแจ้งหนี้สำเร็จ',
                'data' => $tuitionFee->fresh(['items', 'user']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างใบแจ้งหนี้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * สร้างใบแจ้งหนี้เป็นกลุ่ม (ทั้งห้อง)
     */
    public function bulkGenerate(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'due_date' => 'required|date|after:today',
        ]);

        try {
            DB::beginTransaction();

            // Get fee structure with items
            $feeStructure = FeeStructure::with('items')->findOrFail($validated['fee_structure_id']);

            // Get students in classroom (via academy members with classroom)
            $students = $academy->members()
                ->whereHas('courseMember', function ($q) use ($validated) {
                    // This may need adjustment based on your actual classroom-student relationship
                })
                ->get();

            // Alternative: Get from a classroom relationship if it exists
            // For now, we'll use a simpler approach
            $studentIds = DB::table('course_members')
                ->where('academy_id', $academy->id)
                ->where('classroom_id', $validated['classroom_id'])
                ->pluck('user_id')
                ->unique();

            $created = 0;
            $skipped = 0;

            foreach ($studentIds as $studentId) {
                // Check if invoice already exists
                $exists = TuitionFee::where('academy_id', $academy->id)
                    ->where('user_id', $studentId)
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->where('semester_id', $validated['semester_id'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Create invoice
                $tuitionFee = TuitionFee::create([
                    'academy_id' => $academy->id,
                    'user_id' => $studentId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester_id' => $validated['semester_id'],
                    'classroom_id' => $validated['classroom_id'],
                    'fee_structure_id' => $feeStructure->id,
                    'invoice_number' => TuitionFee::generateInvoiceNumber($academy->id),
                    'due_date' => $validated['due_date'],
                    'status' => TuitionFee::STATUS_PENDING,
                    'created_by' => auth()->id(),
                ]);

                // Create items from fee structure
                $totalAmount = 0;
                foreach ($feeStructure->items as $feeItem) {
                    TuitionFeeItem::create([
                        'tuition_fee_id' => $tuitionFee->id,
                        'fee_item_id' => $feeItem->id,
                        'name' => $feeItem->name,
                        'fee_type' => $feeItem->fee_type,
                        'amount' => $feeItem->amount,
                        'discount' => 0,
                        'net_amount' => $feeItem->amount,
                    ]);
                    $totalAmount += $feeItem->amount;
                }

                $tuitionFee->update([
                    'total_amount' => $totalAmount,
                    'discount_amount' => 0,
                    'net_amount' => $totalAmount,
                    'remaining_amount' => $totalAmount,
                ]);

                $created++;
            }

            DB::commit();

            $this->auditService->log(
                action: 'bulk_generate',
                entity: $feeStructure,
                metadata: ['created' => $created, 'skipped' => $skipped],
                request: request()
            );

            return response()->json([
                'success' => true,
                'message' => "สร้างใบแจ้งหนี้สำเร็จ {$created} รายการ, ข้าม {$skipped} รายการ",
                'data' => [
                    'created' => $created,
                    'skipped' => $skipped,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างใบแจ้งหนี้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดทใบแจ้งหนี้
     */
    public function update(Request $request, Academy $academy, TuitionFee $tuitionFee): JsonResponse
    {
        // Only allow update if not paid
        if ($tuitionFee->status === TuitionFee::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแก้ไขใบแจ้งหนี้ที่ชำระแล้ว',
            ], 400);
        }

        $validated = $request->validate([
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,cancelled',
        ]);

        $oldData = $tuitionFee->toArray();
        $tuitionFee->update($validated);

        $this->auditService->logUpdate($tuitionFee, $oldData, request());

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทใบแจ้งหนี้สำเร็จ',
            'data' => $tuitionFee->fresh(),
        ]);
    }

    /**
     * เพิ่มส่วนลด
     */
    public function addDiscount(Request $request, Academy $academy, TuitionFee $tuitionFee): JsonResponse
    {
        $validated = $request->validate([
            'discount_type' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'scholarship_id' => 'nullable|exists:scholarships,id',
        ]);

        if ($tuitionFee->status === TuitionFee::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเพิ่มส่วนลดให้ใบแจ้งหนี้ที่ชำระแล้ว',
            ], 400);
        }

        $discount = FeeDiscount::create([
            'tuition_fee_id' => $tuitionFee->id,
            'scholarship_id' => $validated['scholarship_id'] ?? null,
            'discount_type' => $validated['discount_type'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'applied_by' => auth()->id(),
            'applied_at' => now(),
        ]);

        // Recalculate is handled by FeeDiscount boot method

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มส่วนลดสำเร็จ',
            'data' => $tuitionFee->fresh(['items', 'discounts']),
        ]);
    }

    /**
     * ดึงสรุปค่าเล่าเรียน
     */
    public function summary(Request $request, Academy $academy): JsonResponse
    {
        $query = TuitionFee::where('academy_id', $academy->id);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        $summary = [
            'total_invoices' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('net_amount'),
            'total_paid' => (clone $query)->sum('paid_amount'),
            'total_remaining' => (clone $query)->sum('remaining_amount'),
            'by_status' => [
                'pending' => (clone $query)->where('status', TuitionFee::STATUS_PENDING)->count(),
                'partial' => (clone $query)->where('status', TuitionFee::STATUS_PARTIAL)->count(),
                'paid' => (clone $query)->where('status', TuitionFee::STATUS_PAID)->count(),
                'overdue' => (clone $query)->where('status', TuitionFee::STATUS_OVERDUE)->count(),
                'cancelled' => (clone $query)->where('status', TuitionFee::STATUS_CANCELLED)->count(),
            ],
            'overdue_amount' => (clone $query)->where('status', TuitionFee::STATUS_OVERDUE)->sum('remaining_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * ดูใบแจ้งหนี้ของนักเรียน
     */
    public function studentInvoices(Request $request, Academy $academy, int $userId): JsonResponse
    {
        $invoices = TuitionFee::where('academy_id', $academy->id)
            ->where('user_id', $userId)
            ->with(['academicYear:id,name', 'semester:id,name', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * ยกเลิกใบแจ้งหนี้
     */
    public function cancel(Academy $academy, TuitionFee $tuitionFee): JsonResponse
    {
        if ($tuitionFee->paid_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยกเลิกใบแจ้งหนี้ที่มีการชำระเงินแล้ว',
            ], 400);
        }

        $oldData = $tuitionFee->toArray();
        $tuitionFee->update(['status' => TuitionFee::STATUS_CANCELLED]);

        $this->auditService->logUpdate($tuitionFee, $oldData, request());

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกใบแจ้งหนี้สำเร็จ',
        ]);
    }
}
