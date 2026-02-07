<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Payment;
use App\Models\TuitionFee;
use App\Models\PaymentMethod;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * PaymentController - จัดการการชำระเงิน
 */
class PaymentController extends Controller
{
    protected AuditLogService $auditService;

    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * รายการการชำระเงิน
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = Payment::where('academy_id', $academy->id)
            ->with([
                'user:id,name,email,profile_photo_path',
                'tuitionFee:id,invoice_number,net_amount',
                'paymentMethod:id,name',
            ]);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('receipt_number', 'like', "%{$request->search}%")
                    ->orWhereHas('user', function ($uq) use ($request) {
                        $uq->where('name', 'like', "%{$request->search}%");
                    });
            });
        }

        // Sorting
        $query->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_order', 'desc'));

        $payments = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * ดูรายละเอียดการชำระเงิน
     */
    public function show(Academy $academy, Payment $payment): JsonResponse
    {
        $payment->load([
            'user:id,name,email,profile_photo_path',
            'tuitionFee.items',
            'paymentMethod',
            'confirmedBy:id,name',
        ]);

        $this->auditService->logView($payment, request());

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * บันทึกการชำระเงิน
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'tuition_fee_id' => 'required|exists:tuition_fees,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'transfer_reference' => 'nullable|string|max:100',
            'slip_image' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Verify tuition fee belongs to this academy
        $tuitionFee = TuitionFee::where('academy_id', $academy->id)
            ->findOrFail($validated['tuition_fee_id']);

        // Check if amount exceeds remaining
        if ($validated['amount'] > $tuitionFee->remaining_amount) {
            return response()->json([
                'success' => false,
                'message' => 'จำนวนเงินเกินยอดค้างชำระ (เหลือ ' . number_format($tuitionFee->remaining_amount, 2) . ' บาท)',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $paymentMethod = PaymentMethod::find($validated['payment_method_id']);

            $payment = Payment::create([
                'academy_id' => $academy->id,
                'tuition_fee_id' => $tuitionFee->id,
                'payment_method_id' => $validated['payment_method_id'],
                'user_id' => $tuitionFee->user_id,
                'receipt_number' => Payment::generateReceiptNumber($academy->id),
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method_name' => $paymentMethod->name,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account' => $validated['bank_account'] ?? null,
                'transfer_reference' => $validated['transfer_reference'] ?? null,
                'slip_image' => $validated['slip_image'] ?? null,
                'status' => Payment::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            $this->auditService->logCreate($payment, request());

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการชำระเงินสำเร็จ รอการตรวจสอบ',
                'data' => $payment->fresh(['tuitionFee', 'paymentMethod']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกการชำระเงิน',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ยืนยันการชำระเงิน
     */
    public function confirm(Academy $academy, Payment $payment): JsonResponse
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยืนยันรายการที่ดำเนินการแล้ว',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $oldData = $payment->toArray();

            // Confirm payment
            $payment->confirm(auth()->id());

            // Update tuition fee
            $tuitionFee = $payment->tuitionFee;
            $tuitionFee->paid_amount += $payment->amount;
            $tuitionFee->remaining_amount = $tuitionFee->net_amount - $tuitionFee->paid_amount;
            $tuitionFee->updatePaymentStatus();

            DB::commit();

            $this->auditService->logUpdate($payment, $oldData, request());

            return response()->json([
                'success' => true,
                'message' => 'ยืนยันการชำระเงินสำเร็จ',
                'data' => $payment->fresh(['tuitionFee']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการยืนยัน',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ปฏิเสธการชำระเงิน
     */
    public function reject(Request $request, Academy $academy, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($payment->status !== Payment::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถปฏิเสธรายการที่ดำเนินการแล้ว',
            ], 400);
        }

        $oldData = $payment->toArray();
        $payment->reject(auth()->id(), $validated['reason']);

        $this->auditService->logUpdate($payment, $oldData, request());

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธการชำระเงินสำเร็จ',
        ]);
    }

    /**
     * สรุปการชำระเงิน
     */
    public function summary(Request $request, Academy $academy): JsonResponse
    {
        $query = Payment::where('academy_id', $academy->id);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $confirmed = (clone $query)->where('status', Payment::STATUS_CONFIRMED);

        $summary = [
            'total_transactions' => (clone $query)->count(),
            'total_amount' => (clone $confirmed)->sum('amount'),
            'by_status' => [
                'pending' => (clone $query)->where('status', Payment::STATUS_PENDING)->count(),
                'confirmed' => (clone $query)->where('status', Payment::STATUS_CONFIRMED)->count(),
                'rejected' => (clone $query)->where('status', Payment::STATUS_REJECTED)->count(),
            ],
            'pending_amount' => (clone $query)->where('status', Payment::STATUS_PENDING)->sum('amount'),
            'by_payment_method' => (clone $confirmed)
                ->selectRaw('payment_method_name, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('payment_method_name')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * รายการวิธีการชำระเงิน
     */
    public function paymentMethods(Academy $academy): JsonResponse
    {
        $methods = PaymentMethod::where('academy_id', $academy->id)
            ->orWhereNull('academy_id') // Global payment methods
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * รายงานรายวัน
     */
    public function dailyReport(Request $request, Academy $academy): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        $payments = Payment::where('academy_id', $academy->id)
            ->where('payment_date', $date)
            ->where('status', Payment::STATUS_CONFIRMED)
            ->with(['user:id,name', 'tuitionFee:id,invoice_number'])
            ->orderBy('confirmed_at')
            ->get();

        $summary = [
            'date' => $date,
            'total_transactions' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'by_method' => $payments->groupBy('payment_method_name')
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ]),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'payments' => $payments,
            ],
        ]);
    }
}
