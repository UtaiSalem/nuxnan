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
}
