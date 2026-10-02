<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountFraudReport;
use App\Models\AccountSuspensionAudit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin review queue for member-submitted fraud reports. Admins can triage a
 * report and freeze the reported account's economy straight from a report.
 */
class AdminFraudReportController extends Controller
{
    /**
     * Paginated list of reports, filterable by status and reported account.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AccountFraudReport::query()
            ->with([
                'reporter:id,name,username,email,profile_photo_path',
                'reportedUser:id,name,username,email,profile_photo_path,points_suspended,wallet_suspended',
                'handledBy:id,name,username',
            ]);

        if ($request->filled('status') && in_array($request->status, AccountFraudReport::STATUSES, true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category') && in_array($request->category, AccountFraudReport::CATEGORIES, true)) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('reportedUser', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $reports]);
    }

    /**
     * Status counts for the queue badges.
     */
    public function stats(): JsonResponse
    {
        $counts = AccountFraudReport::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'pending' => (int) ($counts[AccountFraudReport::STATUS_PENDING] ?? 0),
                'reviewing' => (int) ($counts[AccountFraudReport::STATUS_REVIEWING] ?? 0),
                'action_taken' => (int) ($counts[AccountFraudReport::STATUS_ACTION_TAKEN] ?? 0),
                'dismissed' => (int) ($counts[AccountFraudReport::STATUS_DISMISSED] ?? 0),
                'total' => (int) $counts->sum(),
            ],
        ]);
    }

    /**
     * A single report with its reporter, reported account, handler, and the
     * linked transaction (if any).
     */
    public function show(int $id): JsonResponse
    {
        $report = AccountFraudReport::with([
            'reporter:id,name,username,email,profile_photo_path',
            'reportedUser:id,name,username,email,profile_photo_path,points_suspended,wallet_suspended,economy_suspended_reason,economy_suspended_at',
            'handledBy:id,name,username',
        ])->find($id);

        if (! $report) {
            return response()->json(['success' => false, 'message' => 'ไม่พบคำร้องเรียน'], 404);
        }

        $report->setAttribute('related_transaction', $report->relatedTransaction());

        return response()->json(['success' => true, 'data' => $report]);
    }

    /**
     * Triage a report: move it to reviewing / dismissed / action_taken and
     * record the handling admin.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $report = AccountFraudReport::find($id);

        if (! $report) {
            return response()->json(['success' => false, 'message' => 'ไม่พบคำร้องเรียน'], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', [
                AccountFraudReport::STATUS_REVIEWING,
                AccountFraudReport::STATUS_DISMISSED,
                AccountFraudReport::STATUS_ACTION_TAKEN,
            ])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->status = $validated['status'];
        if (array_key_exists('admin_note', $validated)) {
            $report->admin_note = $validated['admin_note'];
        }
        $report->handled_by = $request->user()?->id;
        $report->resolved_at = AccountFraudReport::isResolvedStatus($validated['status']) ? now() : null;
        $report->save();

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสถานะคำร้องเรียนแล้ว',
            'data' => $report->fresh(['handledBy:id,name,username']),
        ]);
    }

    /**
     * Freeze the reported account's economy straight from a report, and close
     * the report as action_taken. Mirrors the admin.users.suspend-economy flow
     * and writes the same suspension audit record.
     */
    public function suspendFromReport(Request $request, int $id): JsonResponse
    {
        $report = AccountFraudReport::find($id);

        if (! $report) {
            return response()->json(['success' => false, 'message' => 'ไม่พบคำร้องเรียน'], 404);
        }

        $data = $request->validate([
            'points' => ['nullable', 'boolean'],
            'wallet' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $suspendPoints = $request->boolean('points', true);
        $suspendWallet = $request->boolean('wallet', true);

        if (! $suspendPoints && ! $suspendWallet) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องเลือกระงับอย่างน้อยหนึ่งระบบ (แต้ม หรือ Wallet)',
            ], 422);
        }

        $reportedUser = User::find($report->reported_user_id);

        if (! $reportedUser) {
            return response()->json(['success' => false, 'message' => 'ไม่พบบัญชีที่ถูกร้องเรียน'], 404);
        }

        $reason = $data['reason'] ?? ('ระงับจากคำร้องเรียนทุจริต #'.$report->id);
        $admin = $request->user();

        DB::transaction(function () use ($reportedUser, $report, $suspendPoints, $suspendWallet, $reason, $admin, $request) {
            $reportedUser->update([
                'points_suspended' => $suspendPoints,
                'wallet_suspended' => $suspendWallet,
                'economy_suspended_reason' => $reason,
                'economy_suspended_at' => now(),
                'economy_suspended_by' => $admin?->id,
            ]);

            AccountSuspensionAudit::create([
                'user_id' => $reportedUser->id,
                'user_email' => $reportedUser->email,
                'action' => 'suspend',
                'points_suspended' => $suspendPoints,
                'wallet_suspended' => $suspendWallet,
                'reason' => $reason,
                'performed_by' => $admin?->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            $report->update([
                'status' => AccountFraudReport::STATUS_ACTION_TAKEN,
                'handled_by' => $admin?->id,
                'admin_note' => $report->admin_note ?: $reason,
                'resolved_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'ระงับบัญชีและปิดคำร้องเรียนเรียบร้อยแล้ว',
            'data' => [
                'report' => $report->fresh(['handledBy:id,name,username']),
                'reported_user' => $reportedUser->fresh(),
            ],
        ]);
    }
}
