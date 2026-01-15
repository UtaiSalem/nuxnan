<?php

namespace App\Http\Controllers\Api\Play;

use App\Models\User;
use App\Models\Friend;
use App\Http\Requests\StoreFriendRequest;
use App\Http\Requests\UpdateFriendRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\Play\FriendshipResource;
use Illuminate\Support\Facades\Auth;

class FriendController extends \App\Http\Controllers\Controller
{
    /**
     * Get friend suggestions (people the user may know).
     * Returns users who are not already friends with the authenticated user.
     */
    public function suggestions()
    {
        $authFriends = auth()->user()->getFriends()->pluck('id')->toArray();

        $suggestions = User::where('id', '!=', Auth::id())
            ->whereNotIn('id', $authFriends)
            ->inRandomOrder()
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'users' => UserResource::collection($suggestions),
        ]);
    }

    /**
     * Get pending friend requests for the authenticated user.
     */
    public function pendingRequests()
    {
        $pendingFriends = auth()->user()->getFriendRequests();

        return response()->json([
            'success' => true,
            'requests' => FriendshipResource::collection($pendingFriends),
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
            'message' => 'Friend request sent successfully.'
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
            'message' => 'Friend request accepted successfully.'
        ], 200);
    }

    /**
     * deny friend request
     */
    public function denyFriendRequest(User $friend){

        // $user = auth()->user();
        auth()->user()->denyFriendRequest($friend);

        return response()->json([
            'success' => true,
            'message' => 'Friend request denied successfully.'
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
            'message' => 'Friend removed successfully.'
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
            'message' => 'Friend removed successfully.'
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
            ]
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

        if (!$user) {
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
            ]
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
            'message' => 'Unfriended successfully.'
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
                'full_name' => $profile ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: $friend->name : $friend->name,
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
        if (!$user->updated_at) return false;
        return $user->updated_at->diffInMinutes(now()) < 5;
    }

    /**
     * Get mutual friends count.
     */
    private function getMutualFriendsCount(User $friend): int
    {
        $authUser = auth()->user();
        if (!$authUser) return 0;
        
        $authFriends = $authUser->getFriends()->pluck('id')->toArray();
        $friendFriends = $friend->getFriends()->pluck('id')->toArray();
        
        return count(array_intersect($authFriends, $friendFriends));
    }
}
