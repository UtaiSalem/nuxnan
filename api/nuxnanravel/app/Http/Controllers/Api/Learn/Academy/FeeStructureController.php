<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\FeeItem;
use App\Models\FeeStructure;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * FeeStructureController - จัดการโครงสร้างค่าธรรมเนียม
 */
class FeeStructureController extends Controller
{
    protected AuditLogService $auditService;

    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * รายการโครงสร้างค่าธรรมเนียม
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = FeeStructure::where('academy_id', $academy->id)
            ->with(['academicYear:id,name', 'items']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $structures = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $structures,
        ]);
    }

    /**
     * ดูโครงสร้างค่าธรรมเนียม
     */
    public function show(Academy $academy, FeeStructure $feeStructure): JsonResponse
    {
        $feeStructure->load(['academicYear', 'items']);

        return response()->json([
            'success' => true,
            'data' => $feeStructure,
        ]);
    }

    /**
     * สร้างโครงสร้างค่าธรรมเนียม
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'grade_levels' => 'nullable|array',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.fee_type' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'items.*.is_required' => 'boolean',
            'items.*.is_recurring' => 'boolean',
            'items.*.recurring_period' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Create fee structure
            $feeStructure = FeeStructure::create([
                'academy_id' => $academy->id,
                'academic_year_id' => $validated['academic_year_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'grade_levels' => $validated['grade_levels'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Create items
            $totalAmount = 0;
            foreach ($validated['items'] as $index => $item) {
                FeeItem::create([
                    'fee_structure_id' => $feeStructure->id,
                    'name' => $item['name'],
                    'fee_type' => $item['fee_type'],
                    'amount' => $item['amount'],
                    'description' => $item['description'] ?? null,
                    'is_required' => $item['is_required'] ?? true,
                    'is_recurring' => $item['is_recurring'] ?? false,
                    'recurring_period' => $item['recurring_period'] ?? null,
                    'display_order' => $index + 1,
                ]);
                $totalAmount += $item['amount'];
            }

            $feeStructure->update(['total_amount' => $totalAmount]);

            DB::commit();

            $this->auditService->logCreate($feeStructure, request());

            return response()->json([
                'success' => true,
                'message' => 'สร้างโครงสร้างค่าธรรมเนียมสำเร็จ',
                'data' => $feeStructure->fresh(['items']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างโครงสร้างค่าธรรมเนียม',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดทโครงสร้างค่าธรรมเนียม
     */
    public function update(Request $request, Academy $academy, FeeStructure $feeStructure): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'grade_levels' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $oldData = $feeStructure->toArray();
        $feeStructure->update($validated);

        $this->auditService->logUpdate($feeStructure, $oldData, request());

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทโครงสร้างค่าธรรมเนียมสำเร็จ',
            'data' => $feeStructure->fresh(['items']),
        ]);
    }

    /**
     * เพิ่มรายการค่าธรรมเนียม
     */
    public function addItem(Request $request, Academy $academy, FeeStructure $feeStructure): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fee_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'is_recurring' => 'boolean',
            'recurring_period' => 'nullable|string',
        ]);

        $maxOrder = $feeStructure->items()->max('display_order') ?? 0;

        $item = FeeItem::create([
            'fee_structure_id' => $feeStructure->id,
            'name' => $validated['name'],
            'fee_type' => $validated['fee_type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'is_required' => $validated['is_required'] ?? true,
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurring_period' => $validated['recurring_period'] ?? null,
            'display_order' => $maxOrder + 1,
        ]);

        $feeStructure->updateTotal();

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มรายการค่าธรรมเนียมสำเร็จ',
            'data' => $item,
        ], 201);
    }

    /**
     * อัพเดทรายการค่าธรรมเนียม
     */
    public function updateItem(Request $request, Academy $academy, FeeStructure $feeStructure, FeeItem $feeItem): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'fee_type' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'is_recurring' => 'boolean',
            'recurring_period' => 'nullable|string',
            'display_order' => 'sometimes|integer',
        ]);

        $feeItem->update($validated);
        $feeStructure->updateTotal();

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทรายการค่าธรรมเนียมสำเร็จ',
            'data' => $feeItem->fresh(),
        ]);
    }

    /**
     * ลบรายการค่าธรรมเนียม
     */
    public function removeItem(Academy $academy, FeeStructure $feeStructure, FeeItem $feeItem): JsonResponse
    {
        $feeItem->delete();
        $feeStructure->updateTotal();

        return response()->json([
            'success' => true,
            'message' => 'ลบรายการค่าธรรมเนียมสำเร็จ',
        ]);
    }

    /**
     * ลบโครงสร้างค่าธรรมเนียม
     */
    public function destroy(Academy $academy, FeeStructure $feeStructure): JsonResponse
    {
        // Check if being used by any tuition fees
        $usageCount = $feeStructure->tuitionFees()->count();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถลบได้ เนื่องจากมีใบแจ้งหนี้ {$usageCount} รายการที่ใช้โครงสร้างนี้",
            ], 400);
        }

        $this->auditService->logDelete($feeStructure, request());

        $feeStructure->items()->delete();
        $feeStructure->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบโครงสร้างค่าธรรมเนียมสำเร็จ',
        ]);
    }

    /**
     * คัดลอกโครงสร้างค่าธรรมเนียม
     */
    public function duplicate(Request $request, Academy $academy, FeeStructure $feeStructure): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        try {
            DB::beginTransaction();

            $newStructure = $feeStructure->replicate();
            $newStructure->name = $validated['name'];
            $newStructure->academic_year_id = $validated['academic_year_id'] ?? $feeStructure->academic_year_id;
            $newStructure->save();

            foreach ($feeStructure->items as $item) {
                $newItem = $item->replicate();
                $newItem->fee_structure_id = $newStructure->id;
                $newItem->save();
            }

            DB::commit();

            $this->auditService->logCreate($newStructure, request());

            return response()->json([
                'success' => true,
                'message' => 'คัดลอกโครงสร้างค่าธรรมเนียมสำเร็จ',
                'data' => $newStructure->fresh(['items']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการคัดลอก',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * รายการประเภทค่าธรรมเนียม
     */
    public function feeTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => FeeItem::TYPES,
        ]);
    }
}
