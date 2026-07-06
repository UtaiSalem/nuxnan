<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\SalaryStructure;
use App\Models\StaffProfile;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * รายการเงินเดือน
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = Payroll::whereHas('staffProfile', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->with(['staffProfile.user:id,name,avatar']);

        // Filter by staff
        if ($request->has('staff_profile_id')) {
            $query->byStaff($request->staff_profile_id);
        }

        // Filter by period
        if ($request->has('pay_period')) {
            $query->byPeriod($request->pay_period);
        }

        // Filter by year/month
        if ($request->has('year')) {
            $query->where('pay_period', 'like', $request->year.'-%');
        }

        if ($request->has('month')) {
            $year = $request->get('year', now()->year);
            $query->where('pay_period', sprintf('%04d-%02d', $year, $request->month));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        $payrolls = $query->orderBy('pay_period', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payrolls,
        ]);
    }

    /**
     * แสดงรายละเอียดเงินเดือน
     */
    public function show(Academy $academy, Payroll $payroll): JsonResponse
    {
        $this->authorizePayroll($academy, $payroll);

        $payroll->load([
            'staffProfile.user:id,name,email,avatar',
            'staffProfile.position:id,name',
            'items' => fn ($q) => $q->ordered(),
            'approver:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $payroll,
        ]);
    }

    /**
     * สร้างรายการเงินเดือน
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'staff_profile_id' => 'required|exists:staff_profiles,id',
            'pay_period' => 'required|date_format:Y-m',
            'base_salary' => 'required|numeric|min:0',
            'salary_structure_id' => 'nullable|exists:salary_structures,id',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|in:earning,deduction',
            'items.*.code' => 'required_with:items|string|max:50',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.amount' => 'required_with:items|numeric',
            'items.*.is_taxable' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $staffProfile = StaffProfile::findOrFail($validated['staff_profile_id']);

        if ($staffProfile->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }

        // Check if payroll already exists for this period
        $existing = Payroll::where('staff_profile_id', $staffProfile->id)
            ->where('pay_period', $validated['pay_period'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'เงินเดือนสำหรับงวดนี้ถูกสร้างแล้ว',
            ], 400);
        }

        // Calculate totals
        $totalEarnings = $validated['base_salary'];
        $totalDeductions = 0;

        if (! empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                if ($item['type'] === 'earning') {
                    $totalEarnings += $item['amount'];
                } else {
                    $totalDeductions += $item['amount'];
                }
            }
        }

        // Apply salary structure if provided
        if (! empty($validated['salary_structure_id'])) {
            $structure = SalaryStructure::find($validated['salary_structure_id']);
            if ($structure) {
                $totalEarnings += $structure->getTotalAllowances();
                $totalDeductions += $structure->getTotalDeductions();
            }
        }

        $payroll = Payroll::create([
            'staff_profile_id' => $staffProfile->id,
            'salary_structure_id' => $validated['salary_structure_id'] ?? null,
            'pay_period' => $validated['pay_period'],
            'base_salary' => $validated['base_salary'],
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_salary' => $totalEarnings - $totalDeductions,
            'status' => Payroll::STATUS_DRAFT,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create payroll items
        if (! empty($validated['items'])) {
            foreach ($validated['items'] as $index => $item) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => $item['type'],
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'is_taxable' => $item['is_taxable'] ?? ($item['type'] === 'earning'),
                    'display_order' => $index + 1,
                ]);
            }
        }

        // Apply salary structure items
        if (! empty($validated['salary_structure_id']) && $structure) {
            $order = count($validated['items'] ?? []) + 1;

            if ($structure->allowances) {
                foreach ($structure->allowances as $allowance) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'type' => 'earning',
                        'code' => $allowance['code'] ?? 'ALW',
                        'name' => $allowance['name'],
                        'amount' => $allowance['amount'],
                        'is_taxable' => $allowance['is_taxable'] ?? true,
                        'display_order' => $order++,
                    ]);
                }
            }

            if ($structure->deductions) {
                foreach ($structure->deductions as $deduction) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'type' => 'deduction',
                        'code' => $deduction['code'] ?? 'DED',
                        'name' => $deduction['name'],
                        'amount' => $deduction['amount'],
                        'is_taxable' => false,
                        'display_order' => $order++,
                    ]);
                }
            }
        }

        $this->auditLogService->log(
            'payroll.create',
            $payroll,
            $academy->id,
            'academy',
            [
                'pay_period' => $payroll->pay_period,
                'net_salary' => $payroll->net_salary,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $payroll->load(['staffProfile.user:id,name', 'items']),
            'message' => 'สร้างรายการเงินเดือนเรียบร้อยแล้ว',
        ], 201);
    }

    /**
     * อัปเดตรายการเงินเดือน
     */
    public function update(Request $request, Academy $academy, Payroll $payroll): JsonResponse
    {
        $this->authorizePayroll($academy, $payroll);

        if ($payroll->status !== Payroll::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'สามารถแก้ไขได้เฉพาะรายการที่เป็น Draft เท่านั้น',
            ], 400);
        }

        $validated = $request->validate([
            'base_salary' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $payroll->update($validated);

        // Recalculate if base salary changed
        if (isset($validated['base_salary'])) {
            $payroll->calculateNetSalary();
        }

        return response()->json([
            'success' => true,
            'data' => $payroll->fresh(['items']),
            'message' => 'อัปเดตรายการเงินเดือนเรียบร้อยแล้ว',
        ]);
    }

    /**
     * เพิ่มรายการ (รายได้/หักเงิน)
     */
    public function addItem(Request $request, Academy $academy, Payroll $payroll): JsonResponse
    {
        $this->authorizePayroll($academy, $payroll);

        if ($payroll->status !== Payroll::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'สามารถแก้ไขได้เฉพาะรายการที่เป็น Draft เท่านั้น',
            ], 400);
        }

        $validated = $request->validate([
            'type' => 'required|in:earning,deduction',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'is_taxable' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $maxOrder = $payroll->items()->max('display_order') ?? 0;

        $item = PayrollItem::create([
            'payroll_id' => $payroll->id,
            'type' => $validated['type'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'is_taxable' => $validated['is_taxable'] ?? ($validated['type'] === 'earning'),
            'display_order' => $maxOrder + 1,
        ]);

        // Recalculate
        $payroll->calculateNetSalary();

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'เพิ่มรายการเรียบร้อยแล้ว',
        ], 201);
    }

    /**
     * ลบรายการ
     */
    public function removeItem(Academy $academy, Payroll $payroll, PayrollItem $item): JsonResponse
    {
        $this->authorizePayroll($academy, $payroll);

        if ($item->payroll_id !== $payroll->id) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        if ($payroll->status !== Payroll::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'สามารถแก้ไขได้เฉพาะรายการที่เป็น Draft เท่านั้น',
            ], 400);
        }

        $item->delete();

        // Recalculate
        $payroll->calculateNetSalary();

        return response()->json([
            'success' => true,
            'message' => 'ลบรายการเรียบร้อยแล้ว',
        ]);
    }

    /**
     * อนุมัติเงินเดือน
     */
    public function approve(Request $request, Academy $academy, Payroll $payroll): JsonResponse
    {
        $this->authorizePayroll($academy, $payroll);

        if ($payroll->status !== Payroll::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'สามารถอนุมัติได้เฉพาะรายการที่เป็น Draft เท่านั้น',
            ], 400);
        }

        $payroll->approve(auth()->id());

        $this->auditLogService->log(
            'payroll.approve',
            $payroll,
            $academy->id,
            'academy',
            ['net_salary' => $payroll->net_salary]
        );

        return response()->json([
            'success' => true,
            'data' => $payroll->fresh(['approver:id,name']),
            'message' => 'อนุมัติเงินเดือนเรียบร้อยแล้ว',
        ]);
    }

    /**
     * ยืนยันการจ่าย
     */
    public function markAsPaid(Request $request, Academy $academy, Payroll $payroll): JsonResponse
    {
        $this->authorizePayroll($academy, $payroll);

        if ($payroll->status !== Payroll::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องอนุมัติก่อนจึงจะจ่ายได้',
            ], 400);
        }

        $validated = $request->validate([
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $payroll->markAsPaid(
            $validated['payment_date'] ?? now(),
            $validated['payment_method'] ?? null,
            $validated['payment_reference'] ?? null
        );

        $this->auditLogService->log(
            'payroll.paid',
            $payroll,
            $academy->id,
            'academy',
            [
                'net_salary' => $payroll->net_salary,
                'payment_date' => $payroll->payment_date,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $payroll->fresh(),
            'message' => 'บันทึกการจ่ายเงินเดือนเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Generate Bulk Payroll - สร้างเงินเดือนเป็นกลุ่ม
     */
    public function bulkGenerate(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'pay_period' => 'required|date_format:Y-m',
            'salary_structure_id' => 'nullable|exists:salary_structures,id',
            'department_id' => 'nullable|exists:departments,id',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'exists:staff_profiles,id',
        ]);

        $query = StaffProfile::where('academy_id', $academy->id)
            ->active()
            ->whereNotNull('base_salary');

        if (! empty($validated['department_id'])) {
            $query->where('department_id', $validated['department_id']);
        }

        if (! empty($validated['staff_ids'])) {
            $query->whereIn('id', $validated['staff_ids']);
        }

        $staffList = $query->get();
        $created = 0;
        $skipped = 0;

        $structure = null;
        if (! empty($validated['salary_structure_id'])) {
            $structure = SalaryStructure::find($validated['salary_structure_id']);
        }

        foreach ($staffList as $staff) {
            // Check if already exists
            $existing = Payroll::where('staff_profile_id', $staff->id)
                ->where('pay_period', $validated['pay_period'])
                ->exists();

            if ($existing) {
                $skipped++;

                continue;
            }

            $totalEarnings = $staff->base_salary;
            $totalDeductions = 0;

            if ($structure) {
                $totalEarnings += $structure->getTotalAllowances();
                $totalDeductions += $structure->getTotalDeductions();
            }

            $payroll = Payroll::create([
                'staff_profile_id' => $staff->id,
                'salary_structure_id' => $validated['salary_structure_id'] ?? null,
                'pay_period' => $validated['pay_period'],
                'base_salary' => $staff->base_salary,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $totalEarnings - $totalDeductions,
                'status' => Payroll::STATUS_DRAFT,
            ]);

            // Add structure items
            if ($structure) {
                $order = 1;
                if ($structure->allowances) {
                    foreach ($structure->allowances as $allowance) {
                        PayrollItem::create([
                            'payroll_id' => $payroll->id,
                            'type' => 'earning',
                            'code' => $allowance['code'] ?? 'ALW',
                            'name' => $allowance['name'],
                            'amount' => $allowance['amount'],
                            'is_taxable' => $allowance['is_taxable'] ?? true,
                            'display_order' => $order++,
                        ]);
                    }
                }
                if ($structure->deductions) {
                    foreach ($structure->deductions as $deduction) {
                        PayrollItem::create([
                            'payroll_id' => $payroll->id,
                            'type' => 'deduction',
                            'code' => $deduction['code'] ?? 'DED',
                            'name' => $deduction['name'],
                            'amount' => $deduction['amount'],
                            'is_taxable' => false,
                            'display_order' => $order++,
                        ]);
                    }
                }
            }

            $created++;
        }

        $this->auditLogService->log(
            'payroll.bulk_generate',
            null,
            $academy->id,
            'academy',
            [
                'pay_period' => $validated['pay_period'],
                'created' => $created,
                'skipped' => $skipped,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
            ],
            'message' => "สร้างรายการเงินเดือน {$created} รายการ (ข้าม {$skipped} รายการ)",
        ]);
    }

    /**
     * Salary Structures - โครงสร้างเงินเดือน
     */
    public function salaryStructures(Academy $academy): JsonResponse
    {
        $structures = SalaryStructure::byAcademy($academy->id)
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $structures,
        ]);
    }

    /**
     * สร้างโครงสร้างเงินเดือน
     */
    public function storeSalaryStructure(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pay_frequency' => 'nullable|in:monthly,bi_weekly,weekly',
            'allowances' => 'nullable|array',
            'allowances.*.name' => 'required_with:allowances|string',
            'allowances.*.code' => 'nullable|string',
            'allowances.*.amount' => 'required_with:allowances|numeric|min:0',
            'allowances.*.is_taxable' => 'nullable|boolean',
            'deductions' => 'nullable|array',
            'deductions.*.name' => 'required_with:deductions|string',
            'deductions.*.code' => 'nullable|string',
            'deductions.*.amount' => 'required_with:deductions|numeric|min:0',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['academy_id'] = $academy->id;

        // If setting as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            SalaryStructure::byAcademy($academy->id)->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $structure = SalaryStructure::create($validated);

        return response()->json([
            'success' => true,
            'data' => $structure,
            'message' => 'สร้างโครงสร้างเงินเดือนเรียบร้อยแล้ว',
        ], 201);
    }

    /**
     * สรุปเงินเดือนประจำงวด
     */
    public function summary(Request $request, Academy $academy): JsonResponse
    {
        $payPeriod = $request->get('pay_period', now()->format('Y-m'));

        $payrolls = Payroll::whereHas('staffProfile', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->where('pay_period', $payPeriod)->get();

        $summary = [
            'pay_period' => $payPeriod,
            'total_staff' => $payrolls->count(),
            'total_base_salary' => $payrolls->sum('base_salary'),
            'total_earnings' => $payrolls->sum('total_earnings'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'by_status' => [
                'draft' => $payrolls->where('status', 'draft')->count(),
                'approved' => $payrolls->where('status', 'approved')->count(),
                'paid' => $payrolls->where('status', 'paid')->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Pay Slip - ใบแจ้งเงินเดือน
     */
    public function payslip(Academy $academy, Payroll $payroll): JsonResponse
    {
        $this->authorizePayroll($academy, $payroll);

        $payroll->load([
            'staffProfile.user:id,name,email',
            'staffProfile.position:id,name',
            'staffProfile.department:id,name',
            'items' => fn ($q) => $q->ordered(),
        ]);

        $earnings = $payroll->items->where('type', 'earning');
        $deductions = $payroll->items->where('type', 'deduction');

        return response()->json([
            'success' => true,
            'data' => [
                'payslip_number' => $payroll->payslip_number,
                'pay_period' => $payroll->pay_period,
                'staff' => [
                    'employee_id' => $payroll->staffProfile->employee_id,
                    'name' => $payroll->staffProfile->user?->name,
                    'position' => $payroll->staffProfile->position?->name,
                    'department' => $payroll->staffProfile->department?->name,
                ],
                'base_salary' => $payroll->base_salary,
                'earnings' => $earnings->map(fn ($e) => [
                    'name' => $e->name,
                    'amount' => $e->amount,
                ]),
                'deductions' => $deductions->map(fn ($d) => [
                    'name' => $d->name,
                    'amount' => $d->amount,
                ]),
                'total_earnings' => $payroll->total_earnings,
                'total_deductions' => $payroll->total_deductions,
                'net_salary' => $payroll->net_salary,
                'payment_date' => $payroll->payment_date,
                'status' => $payroll->status,
            ],
        ]);
    }

    // Helper methods
    protected function authorizePayroll(Academy $academy, Payroll $payroll): void
    {
        if ($payroll->staffProfile->academy_id !== $academy->id) {
            abort(404, 'Payroll not found');
        }
    }
}
