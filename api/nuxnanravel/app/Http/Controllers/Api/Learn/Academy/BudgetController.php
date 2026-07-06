<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Budget;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * BudgetController - จัดการงบประมาณ
 */
class BudgetController extends Controller
{
    protected AuditLogService $auditService;

    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * รายการงบประมาณ
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = Budget::where('academy_id', $academy->id)
            ->with(['academicYear:id,name', 'category:id,name', 'creator:id,name']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $budgets = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $budgets,
        ]);
    }

    /**
     * ดูรายละเอียดงบประมาณ
     */
    public function show(Academy $academy, Budget $budget): JsonResponse
    {
        $budget->load([
            'academicYear',
            'category',
            'creator:id,name',
            'expenses' => function ($q) {
                $q->orderBy('expense_date', 'desc')->limit(20);
            },
        ]);

        $this->auditService->logView($budget, request());

        return response()->json([
            'success' => true,
            'data' => $budget,
        ]);
    }

    /**
     * สร้างงบประมาณ
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'category_id' => 'nullable|exists:expense_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'allocated_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $budget = Budget::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $validated['academic_year_id'],
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'allocated_amount' => $validated['allocated_amount'],
            'spent_amount' => 0,
            'remaining_amount' => $validated['allocated_amount'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => Budget::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        $this->auditService->logCreate($budget, request());

        return response()->json([
            'success' => true,
            'message' => 'สร้างงบประมาณสำเร็จ',
            'data' => $budget->fresh(['academicYear', 'category']),
        ], 201);
    }

    /**
     * อัพเดทงบประมาณ
     */
    public function update(Request $request, Academy $academy, Budget $budget): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'allocated_amount' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'category_id' => 'nullable|exists:expense_categories,id',
        ]);

        $oldData = $budget->toArray();

        // If allocated_amount changed, recalculate remaining
        if (isset($validated['allocated_amount']) && $validated['allocated_amount'] != $budget->allocated_amount) {
            $validated['remaining_amount'] = $validated['allocated_amount'] - $budget->spent_amount;

            // Check if exceeded
            if ($validated['remaining_amount'] < 0) {
                $validated['status'] = Budget::STATUS_EXCEEDED;
            } elseif ($budget->status === Budget::STATUS_EXCEEDED) {
                $validated['status'] = Budget::STATUS_ACTIVE;
            }
        }

        $budget->update($validated);

        $this->auditService->logUpdate($budget, $oldData, request());

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทงบประมาณสำเร็จ',
            'data' => $budget->fresh(['academicYear', 'category']),
        ]);
    }

    /**
     * เปิดใช้งานงบประมาณ
     */
    public function activate(Academy $academy, Budget $budget): JsonResponse
    {
        if ($budget->status !== Budget::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'สามารถเปิดใช้งานได้เฉพาะงบประมาณที่เป็นร่างเท่านั้น',
            ], 400);
        }

        $oldData = $budget->toArray();
        $budget->activate();

        $this->auditService->logUpdate($budget, $oldData, request());

        return response()->json([
            'success' => true,
            'message' => 'เปิดใช้งานงบประมาณสำเร็จ',
        ]);
    }

    /**
     * ปิดงบประมาณ
     */
    public function close(Academy $academy, Budget $budget): JsonResponse
    {
        $oldData = $budget->toArray();
        $budget->close();

        $this->auditService->logUpdate($budget, $oldData, request());

        return response()->json([
            'success' => true,
            'message' => 'ปิดงบประมาณสำเร็จ',
        ]);
    }

    /**
     * ลบงบประมาณ
     */
    public function destroy(Academy $academy, Budget $budget): JsonResponse
    {
        $expenseCount = $budget->expenses()->count();
        if ($expenseCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถลบได้ เนื่องจากมีรายจ่าย {$expenseCount} รายการที่ผูกกับงบประมาณนี้",
            ], 400);
        }

        $this->auditService->logDelete($budget, request());
        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบงบประมาณสำเร็จ',
        ]);
    }

    /**
     * สรุปงบประมาณ
     */
    public function summary(Request $request, Academy $academy): JsonResponse
    {
        $query = Budget::where('academy_id', $academy->id);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $budgets = (clone $query)->get();

        $summary = [
            'total_budgets' => $budgets->count(),
            'total_allocated' => $budgets->sum('allocated_amount'),
            'total_spent' => $budgets->sum('spent_amount'),
            'total_remaining' => $budgets->sum('remaining_amount'),
            'usage_percentage' => $budgets->sum('allocated_amount') > 0
                ? round(($budgets->sum('spent_amount') / $budgets->sum('allocated_amount')) * 100, 2)
                : 0,
            'by_status' => [
                'draft' => (clone $query)->where('status', Budget::STATUS_DRAFT)->count(),
                'active' => (clone $query)->where('status', Budget::STATUS_ACTIVE)->count(),
                'exceeded' => (clone $query)->where('status', Budget::STATUS_EXCEEDED)->count(),
                'closed' => (clone $query)->where('status', Budget::STATUS_CLOSED)->count(),
            ],
            'budgets_with_usage' => $budgets->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'allocated' => $b->allocated_amount,
                'spent' => $b->spent_amount,
                'remaining' => $b->remaining_amount,
                'usage_percentage' => $b->usage_percentage,
                'status' => $b->status,
            ]),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * รายการรายจ่ายในงบประมาณ
     */
    public function expenses(Request $request, Academy $academy, Budget $budget): JsonResponse
    {
        $query = $budget->expenses()->with(['category:id,name', 'creator:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $expenses->items(),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
                'last_page' => $expenses->lastPage(),
            ],
        ]);
    }

    /**
     * เพิ่มเงินงบประมาณ
     */
    public function addFunds(Request $request, Academy $academy, Budget $budget): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $oldData = $budget->toArray();

        $budget->allocated_amount += $validated['amount'];
        $budget->remaining_amount += $validated['amount'];

        // If was exceeded and now has funds, set back to active
        if ($budget->status === Budget::STATUS_EXCEEDED && $budget->remaining_amount >= 0) {
            $budget->status = Budget::STATUS_ACTIVE;
        }

        $budget->save();

        $this->auditService->log(
            action: 'add_funds',
            entity: $budget,
            metadata: ['amount' => $validated['amount'], 'notes' => $validated['notes'] ?? ''],
            request: request()
        );

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มเงินงบประมาณสำเร็จ',
            'data' => $budget->fresh(),
        ]);
    }

    /**
     * โอนงบประมาณ
     */
    public function transfer(Request $request, Academy $academy, Budget $fromBudget): JsonResponse
    {
        $validated = $request->validate([
            'to_budget_id' => 'required|exists:budgets,id|different:'.$fromBudget->id,
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $toBudget = Budget::findOrFail($validated['to_budget_id']);

        // Check if source has enough funds
        if ($fromBudget->remaining_amount < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'งบประมาณต้นทางไม่เพียงพอ',
            ], 400);
        }

        // Transfer
        $fromBudget->allocated_amount -= $validated['amount'];
        $fromBudget->remaining_amount -= $validated['amount'];
        $fromBudget->save();

        $toBudget->allocated_amount += $validated['amount'];
        $toBudget->remaining_amount += $validated['amount'];
        $toBudget->save();

        $this->auditService->log(
            action: 'transfer_budget',
            entity: $fromBudget,
            metadata: [
                'amount' => $validated['amount'],
                'to_budget_id' => $toBudget->id,
                'to_budget_name' => $toBudget->name,
                'notes' => $validated['notes'] ?? '',
            ],
            request: request()
        );

        return response()->json([
            'success' => true,
            'message' => 'โอนงบประมาณสำเร็จ',
            'data' => [
                'from' => $fromBudget->fresh(),
                'to' => $toBudget->fresh(),
            ],
        ]);
    }
}
