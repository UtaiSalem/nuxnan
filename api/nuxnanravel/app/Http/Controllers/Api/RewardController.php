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
     * Get available rewards.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $rewards = $this->rewardService->getAvailableRewards();

            return response()->json([
                'success' => true,
                'data' => [
                    'rewards' => $rewards,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get reward by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $reward = $this->rewardService->getRewardById($id);

            if (!$reward) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reward not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $reward,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Redeem a reward.
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
        ]);

        try {
            $result = $this->rewardService->redeemReward($user->id, $validated['reward_id']);

            return response()->json([
                'success' => true,
                'message' => 'Reward redeemed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Claim a reward.
     */
    public function claim(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $this->rewardService->claimReward($user->id, $id);

            return response()->json([
                'success' => true,
                'message' => 'Reward claimed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
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

        try {
            $rewards = $this->rewardService->getUserRewards($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'rewards' => $rewards,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get reward statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $stats = $this->rewardService->getRewardStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a new reward (Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:points,wallet,badge,feature,discount',
            'value' => 'nullable|string|max:255',
            'points_cost' => 'required|integer|min:0',
            'image_url' => 'nullable|url|max:500',
            'stock' => 'required|integer|min:0',
            'max_redemptions_per_user' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
        ]);

        try {
            $result = $this->rewardService->createReward($validated);

            return response()->json([
                'success' => true,
                'message' => 'Reward created successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a reward (Admin only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'nullable|in:points,wallet,badge,feature,discount',
            'value' => 'nullable|string|max:255',
            'points_cost' => 'nullable|integer|min:0',
            'image_url' => 'nullable|url|max:500',
            'stock' => 'nullable|integer|min:0',
            'max_redemptions_per_user' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
        ]);

        try {
            $this->rewardService->updateReward($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Reward updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a reward (Admin only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->rewardService->deleteReward($id);

            return response()->json([
                'success' => true,
                'message' => 'Reward deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
