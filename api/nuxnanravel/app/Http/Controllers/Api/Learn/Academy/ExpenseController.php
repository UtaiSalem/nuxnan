<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ExpenseController - จัดการรายจ่าย
 */
class ExpenseController extends Controller
{
    protected AuditLogService $auditService;

    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * รายการรายจ่าย
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = Expense::where('academy_id', $academy->id)
            ->with(['category:id,name', 'creator:id,name']);

        // Filters
        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('vendor', 'like', "%{$request->search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $expenses = $query->paginate($request->input('per_page', 20));

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
     * ดูรายละเอียดรายจ่าย
     */
    public function show(Academy $academy, Expense $expense): JsonResponse
    {
        $expense->load(['category', 'creator:id,name', 'approver:id,name']);

        $this->auditService->logView($expense);

        return response()->json([
            'success' => true,
            'data' => $expense,
        ]);
    }

    /**
     * บันทึกรายจ่าย
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'vendor' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'receipt_image' => 'nullable|string',
        ]);

        $expense = Expense::create([
            'academy_id' => $academy->id,
            'expense_category_id' => $validated['expense_category_id'],
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'vendor' => $validated['vendor'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'receipt_image' => $validated['receipt_image'] ?? null,
            'status' => Expense::STATUS_PENDING,
            'requested_by' => auth()->id(),
        ]);

        $this->auditService->logCreate($expense);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกรายจ่ายสำเร็จ',
            'data' => $expense->fresh(['category']),
        ], 201);
    }

    /**
     * อัพเดทรายจ่าย
     */
    public function update(Request $request, Academy $academy, Expense $expense): JsonResponse
    {
        if ($expense->status === Expense::STATUS_APPROVED || $expense->status === Expense::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแก้ไขรายจ่ายที่อนุมัติหรือจ่ายแล้ว',
            ], 400);
        }

        $validated = $request->validate([
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|numeric|min:0.01',
            'expense_date' => 'sometimes|date',
            'vendor' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'receipt_image' => 'nullable|string',
        ]);

        $oldData = $expense->toArray();
        $expense->update($validated);

        $this->auditService->logUpdate($expense, $oldData);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทรายจ่ายสำเร็จ',
            'data' => $expense->fresh(['category']),
        ]);
    }

    /**
     * อนุมัติรายจ่าย
     */
    public function approve(Academy $academy, Expense $expense): JsonResponse
    {
        if ($expense->status !== Expense::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอนุมัติรายการที่ไม่ได้รออนุมัติ',
            ], 400);
        }

        $oldData = $expense->toArray();
        $expense->approve(auth()->id());

        $this->auditService->logApproval($expense, true);

        return response()->json([
            'success' => true,
            'message' => 'อนุมัติรายจ่ายสำเร็จ',
            'data' => $expense->fresh(['category']),
        ]);
    }

    /**
     * ปฏิเสธรายจ่าย
     */
    public function reject(Request $request, Academy $academy, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($expense->status !== Expense::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถปฏิเสธรายการที่ไม่ได้รออนุมัติ',
            ], 400);
        }

        $oldData = $expense->toArray();
        $expense->reject(auth()->id(), $validated['reason']);

        $this->auditService->logApproval($expense, false, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธรายจ่ายสำเร็จ',
        ]);
    }

    /**
     * ทำเครื่องหมายว่าจ่ายแล้ว
     */
    public function markAsPaid(Academy $academy, Expense $expense): JsonResponse
    {
        if ($expense->status !== Expense::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องอนุมัติก่อนจึงจะทำเครื่องหมายจ่ายได้',
            ], 400);
        }

        $oldData = $expense->toArray();
        $expense->markAsPaid();

        $this->auditService->logUpdate($expense, $oldData);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสถานะเป็นจ่ายแล้ว',
        ]);
    }

    /**
     * ลบรายจ่าย
     */
    public function destroy(Academy $academy, Expense $expense): JsonResponse
    {
        if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PAID])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบรายจ่ายที่อนุมัติหรือจ่ายแล้ว',
            ], 400);
        }

        $this->auditService->logDelete($expense);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบรายจ่ายสำเร็จ',
        ]);
    }

    /**
     * สรุปรายจ่าย
     */
    public function summary(Request $request, Academy $academy): JsonResponse
    {
        $query = Expense::where('academy_id', $academy->id);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        $approved = (clone $query)->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_PAID]);

        $summary = [
            'total_expenses' => (clone $query)->count(),
            'total_amount' => (clone $approved)->sum('amount'),
            'pending_approval' => (clone $query)->where('status', Expense::STATUS_PENDING)->count(),
            'pending_amount' => (clone $query)->where('status', Expense::STATUS_PENDING)->sum('amount'),
            'by_category' => (clone $approved)
                ->selectRaw('expense_category_id, SUM(amount) as total')
                ->with('category:id,name')
                ->groupBy('expense_category_id')
                ->get()
                ->map(fn ($item) => [
                    'category' => $item->category?->name ?? 'ไม่ระบุ',
                    'amount' => $item->total,
                ]),
            'by_status' => [
                'pending' => (clone $query)->where('status', Expense::STATUS_PENDING)->count(),
                'approved' => (clone $query)->where('status', Expense::STATUS_APPROVED)->count(),
                'paid' => (clone $query)->where('status', Expense::STATUS_PAID)->count(),
                'rejected' => (clone $query)->where('status', Expense::STATUS_REJECTED)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    // =====================================================
    // Expense Categories
    // =====================================================

    /**
     * รายการหมวดหมู่
     */
    public function categories(Academy $academy): JsonResponse
    {
        $categories = ExpenseCategory::where('academy_id', $academy->id)
            ->with('children')
            ->rootCategories()
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * สร้างหมวดหมู่
     */
    public function storeCategory(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:expense_categories,id',
        ]);

        $category = ExpenseCategory::create([
            'academy_id' => $academy->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างหมวดหมู่สำเร็จ',
            'data' => $category,
        ], 201);
    }

    /**
     * อัพเดทหมวดหมู่
     */
    public function updateCategory(Request $request, Academy $academy, ExpenseCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทหมวดหมู่สำเร็จ',
            'data' => $category->fresh(),
        ]);
    }

    /**
     * ลบหมวดหมู่
     */
    public function destroyCategory(Academy $academy, ExpenseCategory $category): JsonResponse
    {
        $expenseCount = $category->expenses()->count();
        if ($expenseCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถลบได้ เนื่องจากมีรายจ่าย {$expenseCount} รายการที่ใช้หมวดหมู่นี้",
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบหมวดหมู่สำเร็จ',
        ]);
    }
}
