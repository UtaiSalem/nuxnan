<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    /**
     * Toggle follow status for a user.
     */
    public function toggleFollow(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id|different:' . $user->id,
        ]);

        try {
            $targetUser = User::find($validated['user_id']);

            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $result = $user->toggleFollow($targetUser);

            return response()->json([
                'success' => true,
                'message' => $result['following'] ? 'User followed successfully' : 'User unfollowed successfully',
                'data' => [
                    'following' => $result['following'],
                    'followers_count' => $result['followers_count'],
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
     * Get followers of a user.
     */
    public function followers(Request $request, int $userId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $followers = $targetUser->followers()
            ->with('follower:id,username,name,profile_photo_path')
            ->paginate($perPage, ['*'], 'page', $page);

        $followersData = $followers->map(function ($follow) use ($user) {
            $follower = $follow->follower;
            return [
                'id' => $follower->id,
                'username' => $follower->username,
                'name' => $follower->name,
                'avatar' => $follower->profile_photo_url,
                'is_following' => $user->isFollowing($follower),
                'followed_at' => $follow->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'followers' => $followersData,
                'current_page' => $followers->currentPage(),
                'total_pages' => $followers->lastPage(),
                'per_page' => $followers->perPage(),
                'total' => $followers->total(),
            ],
        ]);
    }

    /**
     * Get users that a user is following.
     */
    public function following(Request $request, int $userId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $following = $targetUser->following()
            ->with('followed:id,username,name,profile_photo_path')
            ->paginate($perPage, ['*'], 'page', $page);

        $followingData = $following->map(function ($follow) use ($user) {
            $followed = $follow->followed;
            return [
                'id' => $followed->id,
                'username' => $followed->username,
                'name' => $followed->name,
                'avatar' => $followed->profile_photo_url,
                'is_following' => $user->isFollowing($followed),
                'followed_at' => $follow->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'following' => $followingData,
                'current_page' => $following->currentPage(),
                'total_pages' => $following->lastPage(),
                'per_page' => $following->perPage(),
                'total' => $following->total(),
            ],
        ]);
    }

    /**
     * Get follow statistics for a user.
     */
    public function stats(Request $request, int $userId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'followers_count' => $targetUser->followers()->count(),
                'following_count' => $targetUser->following()->count(),
                'is_following' => $user->isFollowing($targetUser),
                'is_followed_by' => $targetUser->isFollowing($user),
            ],
        ]);
    }

    /**
     * Check if current user is following another user.
     */
    public function isFollowing(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $targetUser = User::find($validated['user_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'is_following' => $user->isFollowing($targetUser),
                'followers_count' => $targetUser->followers()->count(),
            ],
        ]);
    }
}