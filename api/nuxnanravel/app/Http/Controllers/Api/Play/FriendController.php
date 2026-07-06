<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;

class FriendController extends Controller
{
    /**
     * Get friend suggestions (people the user may know).
     * Returns users who are not already friends with the authenticated user.
     */
    public function suggestions()
    {
        $authUser = auth()->user();
        $userType = User::class;

        $excludedUserIds = Friendship::query()
            ->where(function ($query) use ($authUser, $userType) {
                $query->where(function ($query) use ($authUser, $userType) {
                    $query->where('sender_type', $userType)
                        ->where('sender_id', $authUser->id);
                })->orWhere(function ($query) use ($authUser, $userType) {
                    $query->where('recipient_type', $userType)
                        ->where('recipient_id', $authUser->id);
                });
            })
            ->get(['sender_id', 'recipient_id'])
            ->flatMap(fn (Friendship $friendship) => [
                (int) $friendship->sender_id,
                (int) $friendship->recipient_id,
            ])
            ->push((int) $authUser->id)
            ->unique()
            ->values();

        $suggestions = User::query()
            ->select(['id', 'name', 'email', 'reference_code', 'profile_photo_path'])
            ->whereNotIn('id', $excludedUserIds)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'users' => $suggestions->map(fn (User $user) => $this->formatWidgetUser($user)),
        ]);
    }

    /**
     * Get pending friend requests for the authenticated user.
     */
    public function pendingRequests()
    {
        $authUser = auth()->user();
        $userType = User::class;

        $pendingFriends = Friendship::query()
            ->with([
                'sender:id,name,email,reference_code,profile_photo_path',
                'recipient:id,name,email,reference_code,profile_photo_path',
            ])
            ->where('recipient_type', $userType)
            ->where('recipient_id', $authUser->id)
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'requests' => $pendingFriends->map(fn (Friendship $friendship) => [
                'id' => $friendship->id,
                'sender' => $friendship->sender ? $this->formatWidgetUser($friendship->sender) : null,
                'recipient' => $friendship->recipient ? $this->formatWidgetUser($friendship->recipient) : null,
                'sender_type' => $friendship->sender_type,
                'sender_id' => $friendship->sender_id,
                'recipient_type' => $friendship->recipient_type,
                'recipient_id' => $friendship->recipient_id,
                'status' => $friendship->status,
                'created_at' => $friendship->created_at,
                'updated_at' => $friendship->updated_at,
                'diff_humans_created_at' => $friendship->created_at?->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function addFriendRequest(User $recipient)
    {
        $user = auth()->user();

        $user->befriend($recipient);

        return response()->json([
            'success' => true,
            'user' => $recipient,
            'message' => 'Friend request sent successfully.',
        ], 200);
    }

    /**
     * accept friend request
     */
    public function acceptFriendRequest(User $friend)
    {
        $user = auth()->user();

        $user->acceptFriendRequest($friend);

        return response()->json([
            'success' => true,
            'message' => 'Friend request accepted successfully.',
        ], 200);
    }

    /**
     * deny friend request
     */
    public function denyFriendRequest(User $friend)
    {

        // $user = auth()->user();
        auth()->user()->denyFriendRequest($friend);

        return response()->json([
            'success' => true,
            'message' => 'Friend request denied successfully.',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $friend)
    {
        $user = auth()->user();

        $user->unfriend($friend);

        return response()->json([
            'success' => true,
            'message' => 'Friend removed successfully.',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteFriendRequest(User $friend)
    {
        $user = auth()->user();

        $user->unfriend($friend);

        return response()->json([
            'success' => true,
            'message' => 'Friend removed successfully.',
        ], 200);
    }

    /**
     * Get the authenticated user's friends list.
     */
    public function index()
    {
        $user = auth()->user();
        $friends = $user->getFriends();

        return response()->json([
            'success' => true,
            'data' => $this->formatFriends($friends),
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => $friends->count(),
            ],
        ]);
    }

    /**
     * Get friends of a specific user by identifier.
     */
    public function userFriends(string $identifier)
    {
        // Find user by ID, reference_code, or username
        $user = User::where('id', $identifier)
            ->orWhere('reference_code', $identifier)
            ->orWhere('name', $identifier)
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $friends = $user->getFriends();

        return response()->json([
            'success' => true,
            'data' => $this->formatFriends($friends),
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => $friends->count(),
            ],
        ]);
    }

    /**
     * Unfriend a user.
     */
    public function unfriend(User $friend)
    {
        $user = auth()->user();

        $user->unfriend($friend);

        return response()->json([
            'success' => true,
            'message' => 'Unfriended successfully.',
        ], 200);
    }

    /**
     * Search friends by name.
     */
    public function search()
    {
        $query = request('q', '');
        $user = auth()->user();
        $friends = $user->getFriends();

        if ($query) {
            $friends = $friends->filter(function ($friend) use ($query) {
                return stripos($friend->name, $query) !== false;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatFriends($friends),
        ]);
    }

    /**
     * Format friends data for response.
     */
    private function formatFriends($friends)
    {
        return $friends->map(function ($friend) {
            $profile = $friend->profile;

            return [
                'id' => $friend->id,
                'user_id' => $friend->id,
                'username' => $friend->name,
                'name' => $friend->name,
                'full_name' => $profile ? trim(($profile->first_name ?? '').' '.($profile->last_name ?? '')) ?: $friend->name : $friend->name,
                'avatar' => $friend->profile_photo_url ?? '/images/default-avatar.png',
                'reference_code' => $friend->reference_code,
                'level' => $profile ? $this->calculateLevel($friend->pp ?? 0) : 1,
                'is_online' => $this->isUserOnline($friend),
                'last_seen' => $friend->updated_at?->diffForHumans(),
                'mutual_friends_count' => $this->getMutualFriendsCount($friend),
            ];
        })->values();
    }

    /**
     * Format the compact user shape needed by sidebar widgets.
     */
    private function formatWidgetUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->name,
            'email' => $user->email,
            'avatar' => $user->profile_photo_url,
            'profile_photo_url' => $user->profile_photo_url,
            'reference_code' => $user->reference_code,
        ];
    }

    /**
     * Calculate user level from points.
     */
    private function calculateLevel(int $points): int
    {
        // Simple level calculation: 1 level per 1000 points
        return max(1, intval($points / 1000) + 1);
    }

    /**
     * Check if user is online (active in last 5 minutes).
     */
    private function isUserOnline(User $user): bool
    {
        if (! $user->updated_at) {
            return false;
        }

        return $user->updated_at->diffInMinutes(now()) < 5;
    }

    /**
     * Get mutual friends count.
     */
    private function getMutualFriendsCount(User $friend): int
    {
        $authUser = auth()->user();
        if (! $authUser) {
            return 0;
        }

        $authFriends = $authUser->getFriends()->pluck('id')->toArray();
        $friendFriends = $friend->getFriends()->pluck('id')->toArray();

        return count(array_intersect($authFriends, $friendFriends));
    }
}
