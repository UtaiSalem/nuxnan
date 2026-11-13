<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Budget;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * BudgetController - จัดการงบประมาณ
 *
 * ใช้คอลัมน์ตามสคีมาจริงของ `budgets` เท่านั้น (`expense_category_id`, `is_active`, `period`)
 * — ดูคำอธิบายใน {@see Budget}
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
            ->with(['academicYear:id,name', 'category:id,name']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }
        if ($request->filled('period')) {
            $query->where('period', $request->period);
        }

        $budgets = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $budgets,
        ]);
    }

    /**
     * ดูรายละเอียดงบประมาณ (พร้อมรายจ่ายล่าสุด 20 รายการที่นับเข้างบนี้)
     */
    public function show(Academy $academy, Budget $budget): JsonResponse
    {
        $this->ensureBelongsToAcademy($academy, $budget);

        $budget->load(['academicYear', 'category']);
        $budget->setRelation('expenses', $budget->expensesQuery()
            ->with(['category:id,name', 'creator:id,name'])
            ->orderBy('expense_date', 'desc')
            ->limit(20)
            ->get());

        $this->auditService->logView($budget);

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
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where('academy_id', $academy->id)],
            'expense_category_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('academy_id', $academy->id)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'allocated_amount' => 'required|numeric|min:0',
            'period' => ['nullable', Rule::in(array_keys(Budget::PERIODS))],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'sometimes|boolean',
        ]);

        $budget = Budget::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $validated['academic_year_id'],
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'allocated_amount' => $validated['allocated_amount'],
            'spent_amount' => 0,
            'remaining_amount' => $validated['allocated_amount'],
            'period' => $validated['period'] ?? Budget::PERIOD_YEARLY,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->auditService->logCreate($budget);

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
        $this->ensureBelongsToAcademy($academy, $budget);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'allocated_amount' => 'sometimes|numeric|min:0',
            'period' => ['sometimes', Rule::in(array_keys(Budget::PERIODS))],
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'expense_category_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('academy_id', $academy->id)],
        ]);

        $startDate = $validated['start_date'] ?? $budget->start_date->toDateString();
        $endDate = $validated['end_date'] ?? $budget->end_date->toDateString();
        if (strtotime($endDate) <= strtotime($startDate)) {
            throw ValidationException::withMessages([
                isset($validated['end_date']) ? 'end_date' : 'start_date' => 'วันสิ้นสุดต้องอยู่หลังวันเริ่มต้น',
            ]);
        }

        $oldData = $budget->toArray();

        // If allocated_amount changed, recalculate remaining
        if (isset($validated['allocated_amount']) && $validated['allocated_amount'] != $budget->allocated_amount) {
            $validated['remaining_amount'] = $validated['allocated_amount'] - $budget->spent_amount;
        }

        $budget->update($validated);

        $this->auditService->logUpdate($budget, $oldData);

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
        $this->ensureBelongsToAcademy($academy, $budget);

        if ($budget->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'งบประมาณนี้เปิดใช้งานอยู่แล้ว',
            ], 400);
        }

        $oldData = $budget->toArray();
        $budget->activate();

        $this->auditService->logUpdate($budget, $oldData);

        return response()->json([
            'success' => true,
            'message' => 'เปิดใช้งานงบประมาณสำเร็จ',
            'data' => $budget->fresh(),
        ]);
    }

    /**
     * ปิดงบประมาณ
     */
    public function close(Academy $academy, Budget $budget): JsonResponse
    {
        $this->ensureBelongsToAcademy($academy, $budget);

        if (! $budget->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'งบประมาณนี้ปิดอยู่แล้ว',
            ], 400);
        }

        $oldData = $budget->toArray();
        $budget->close();

        $this->auditService->logUpdate($budget, $oldData);

        return response()->json([
            'success' => true,
            'message' => 'ปิดงบประมาณสำเร็จ',
            'data' => $budget->fresh(),
        ]);
    }

    /**
     * ลบงบประมาณ
     *
     * รายจ่ายไม่ได้ผูกกับงบด้วย FK (นับตามหมวด+ช่วงวันที่) ⇒ ลบงบได้โดยรายจ่ายไม่กระทบ
     */
    public function destroy(Academy $academy, Budget $budget): JsonResponse
    {
        $this->ensureBelongsToAcademy($academy, $budget);

        $this->auditService->logDelete($budget);
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

        $budgets = $query->get();

        $summary = [
            'total_budgets' => $budgets->count(),
            'total_allocated' => $budgets->sum('allocated_amount'),
            'total_spent' => $budgets->sum('spent_amount'),
            'total_remaining' => $budgets->sum('remaining_amount'),
            'usage_percentage' => $budgets->sum('allocated_amount') > 0
                ? round(($budgets->sum('spent_amount') / $budgets->sum('allocated_amount')) * 100, 2)
                : 0,
            // active/inactive แบ่งตาม is_active · exceeded = remaining < 0 (นับซ้อนกับ active/inactive ได้)
            'by_status' => [
                'active' => $budgets->where('is_active', true)->count(),
                'inactive' => $budgets->where('is_active', false)->count(),
                'exceeded' => $budgets->filter(fn (Budget $b) => $b->isExceeded())->count(),
            ],
            'budgets_with_usage' => $budgets->map(fn (Budget $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'allocated' => $b->allocated_amount,
                'spent' => $b->spent_amount,
                'remaining' => $b->remaining_amount,
                'usage_percentage' => $b->usage_percentage,
                'is_active' => $b->is_active,
                'is_exceeded' => $b->isExceeded(),
            ])->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * รายการรายจ่ายในงบประมาณ (หมวดเดียวกัน + อยู่ในช่วงวันที่ของงบ)
     */
    public function expenses(Request $request, Academy $academy, Budget $budget): JsonResponse
    {
        $this->ensureBelongsToAcademy($academy, $budget);

        $query = $budget->expensesQuery()->with(['category:id,name', 'creator:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(min((int) $request->input('per_page', 20), 100));

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
        $this->ensureBelongsToAcademy($academy, $budget);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $budget->allocated_amount += $validated['amount'];
        $budget->remaining_amount += $validated['amount'];
        $budget->save();

        $this->auditService->log(
            action: 'add_funds',
            entity: $budget,
            module: 'finance',
            metadata: ['amount' => $validated['amount'], 'notes' => $validated['notes'] ?? '']
        );

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มเงินงบประมาณสำเร็จ',
            'data' => $budget->fresh(),
        ]);
    }

    /**
     * โอนงบประมาณ (ภายในโรงเรียนเดียวกันเท่านั้น)
     *
     * ชื่อพารามิเตอร์ต้องเป็น `$budget` ให้ตรงกับ `{budget}` ใน route
     * — เดิมชื่อ `$fromBudget` ทำให้ Laravel ฉีด Budget เปล่าเข้ามา การโอนจึงพังทุกครั้ง
     */
    public function transfer(Request $request, Academy $academy, Budget $budget): JsonResponse
    {
        $this->ensureBelongsToAcademy($academy, $budget);

        $validated = $request->validate([
            'to_budget_id' => [
                'required',
                'integer',
                Rule::notIn([$budget->id]),
                Rule::exists('budgets', 'id')->where('academy_id', $academy->id),
            ],
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ], [
            'to_budget_id.not_in' => 'ต้องโอนไปยังงบประมาณอื่น',
        ]);

        $amount = (float) $validated['amount'];

        $result = DB::transaction(function () use ($budget, $validated, $amount) {
            // ล็อกแถวตามลำดับ id กัน deadlock เมื่อโอนสวนทางกันพร้อมกัน
            $locked = Budget::whereIn('id', [$budget->id, (int) $validated['to_budget_id']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $from = $locked[$budget->id];
            $to = $locked[(int) $validated['to_budget_id']];

            if ((float) $from->remaining_amount < $amount) {
                return null;
            }

            $from->allocated_amount -= $amount;
            $from->remaining_amount -= $amount;
            $from->save();

            $to->allocated_amount += $amount;
            $to->remaining_amount += $amount;
            $to->save();

            return [$from, $to];
        });

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'งบประมาณต้นทางไม่เพียงพอ',
            ], 400);
        }

        [$from, $to] = $result;

        $this->auditService->log(
            action: 'transfer_budget',
            entity: $from,
            module: 'finance',
            metadata: [
                'amount' => $amount,
                'to_budget_id' => $to->id,
                'to_budget_name' => $to->name,
                'notes' => $validated['notes'] ?? '',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'โอนงบประมาณสำเร็จ',
            'data' => [
                'from' => $from->fresh(),
                'to' => $to->fresh(),
            ],
        ]);
    }

    /**
     * `{budget}` ใน route ไม่ได้ผูกกับ `{academy}` — ต้องกันการเข้าถึงงบของโรงเรียนอื่นเอง (IDOR)
     */
    private function ensureBelongsToAcademy(Academy $academy, Budget $budget): void
    {
        abort_if((int) $budget->academy_id !== (int) $academy->id, 404);
    }
}
