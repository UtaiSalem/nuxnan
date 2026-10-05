<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\FraudRemediationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin fraud-remediation: reverse fraudulent peer transfers and freeze/unfreeze
 * a member's wallets. These move money and lock accounts, so access is limited
 * to SUPER_ADMIN / ADMIN (same bar as approving a withdrawal).
 */
class AdminFraudController extends Controller
{
    public function __construct(protected FraudRemediationService $service) {}

    private function denies(?User $user): bool
    {
        return ! $user || ! ($user->isSuperAdmin() || $user->hasRole('ADMIN'));
    }

    public function reversePointsTransaction(Request $request, int $id): JsonResponse
    {
        $admin = Auth::user();
        if ($this->denies($admin)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $tx = PointsTransaction::find($id);
        if (! $tx) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $data = $request->validate(['reason' => 'required|string|max:500']);

        try {
            $result = $this->service->reversePointsTransfer($tx, $admin, $data['reason']);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'ย้อนรายการโอนแต้มสำเร็จ', 'data' => $result]);
    }

    public function reverseWalletTransaction(Request $request, int $id): JsonResponse
    {
        $admin = Auth::user();
        if ($this->denies($admin)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $tx = WalletTransaction::find($id);
        if (! $tx) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $data = $request->validate(['reason' => 'required|string|max:500']);

        try {
            $result = $this->service->reverseWalletTransfer($tx, $admin, $data['reason']);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'ย้อนรายการโอนเงินสำเร็จ', 'data' => $result]);
    }

    public function freeze(Request $request, int $userId): JsonResponse
    {
        $admin = Auth::user();
        if ($this->denies($admin)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $target = User::find($userId);
        if (! $target) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $data = $request->validate([
            'scope' => 'required|in:points,wallet,both',
            'reason' => 'required|string|max:500',
        ]);

        $user = $this->service->freeze($target, $data['scope'], $admin, $data['reason']);

        return response()->json(['success' => true, 'message' => 'ระงับกระเป๋าสำเร็จ', 'data' => $this->freezeState($user)]);
    }

    public function unfreeze(Request $request, int $userId): JsonResponse
    {
        $admin = Auth::user();
        if ($this->denies($admin)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $target = User::find($userId);
        if (! $target) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $data = $request->validate(['scope' => 'required|in:points,wallet,both']);

        $user = $this->service->unfreeze($target, $data['scope'], $admin);

        return response()->json(['success' => true, 'message' => 'ปลดระงับกระเป๋าสำเร็จ', 'data' => $this->freezeState($user)]);
    }

    private function freezeState(User $user): array
    {
        return [
            'user_id' => $user->id,
            'points_frozen' => $user->isPointsFrozen(),
            'wallet_frozen' => $user->isWalletFrozen(),
            'points_frozen_at' => $user->points_frozen_at,
            'wallet_frozen_at' => $user->wallet_frozen_at,
            'freeze_reason' => $user->freeze_reason,
        ];
    }
}
