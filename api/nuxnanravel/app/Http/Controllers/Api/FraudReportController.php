<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFraudReportRequest;
use App\Models\AccountFraudReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Member-facing endpoint for reporting a fraudulent account. Reports land in
 * the admin review queue (Admin\AdminFraudReportController).
 */
class FraudReportController extends Controller
{
    /**
     * Submit a fraud report against another member's account.
     */
    public function store(StoreFraudReportRequest $request): JsonResponse
    {
        $reporter = Auth::user();
        $validated = $request->validated();

        // Guard against spamming: one open report per reporter/reported pair.
        $existing = AccountFraudReport::where('reporter_id', $reporter->id)
            ->where('reported_user_id', $validated['reported_user_id'])
            ->whereIn('status', [AccountFraudReport::STATUS_PENDING, AccountFraudReport::STATUS_REVIEWING])
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'คุณได้ร้องเรียนบัญชีนี้ไว้แล้ว และกำลังอยู่ระหว่างการตรวจสอบ',
            ], 422);
        }

        $report = AccountFraudReport::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $validated['reported_user_id'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'related_transaction_type' => $validated['related_transaction_type'] ?? null,
            'related_transaction_id' => $validated['related_transaction_id'] ?? null,
            'evidence_note' => $validated['evidence_note'] ?? null,
            'status' => AccountFraudReport::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ส่งคำร้องเรียนเรียบร้อยแล้ว ทีมงานจะตรวจสอบโดยเร็วที่สุด',
            'data' => $report,
        ], 201);
    }

    /**
     * List the reports the authenticated member has submitted.
     */
    public function mine(Request $request): JsonResponse
    {
        $reports = AccountFraudReport::with('reportedUser:id,name,username,profile_photo_path')
            ->where('reporter_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }
}
