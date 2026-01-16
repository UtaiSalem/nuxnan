<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{
    protected RewardService $rewardService;

    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    /**
     * Get all rewards.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $type = $request->input('type');

        $rewards = $type 
            ? $this->rewardService->getRewardsByType($type)
            : $this->rewardService->getAllRewards();

        return response()->json([
            'success' => true,
            'data' => [
                'rewards' => $rewards,
            ],
        ]);
    }

    /**
     * Get reward details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $reward = $this->rewardService->getRewardDetails($id);

        if (!$reward) {
            return response()->json([
                'success' => false,
                'message' => 'Reward not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reward' => $reward,
            ],
        ]);
    }

    /**
     * Get user rewards.
     */
    public function myRewards(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $userRewards = $this->rewardService->getUserRewards($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user_rewards' => $userRewards,
            ],
        ]);
    }

    /**
     * Redeem reward.
     */
    public function redeem(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'reward_id' => 'required|integer|exists:rewards,id',
            'quantity' => 'nullable|integer|min:1|max:10',
        ]);

        $quantity = $validated['quantity'] ?? 1;
        $result = $this->rewardService->redeemReward($user, $validated['reward_id'], $quantity);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Cancel user reward.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $result = $this->rewardService->cancelUserReward($user, $id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel reward',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reward cancelled successfully',
        ]);
    }

    /**
     * Get reward statistics (Admin only).
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $stats = $this->rewardService->getRewardStats();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
            ],
        ]);
    }
}
