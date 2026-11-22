<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicUserResource;
use App\Models\Friendship;
use App\Models\User;
use App\Services\Profile\ProfileAccess;
use App\Support\ProfileUserResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
            ->select(['id', 'name', 'username', 'reference_code', 'profile_photo_path'])
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
                'sender:id,name,username,reference_code,profile_photo_path',
                'recipient:id,name,username,reference_code,profile_photo_path',
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
     * ส่งคำขอเป็นเพื่อน — เคารพ allow_friend_requests ของผู้รับ และแจ้งตามจริงเมื่อส่งไม่ได้
     */
    public function addFriendRequest(User $recipient)
    {
        $user = auth()->user();

        if ((int) $user->id === (int) $recipient->id) {
            return response()->json([
                'success' => false,
                'code' => 'CANNOT_FRIEND_SELF',
                'message' => 'ส่งคำขอเป็นเพื่อนถึงตัวเองไม่ได้',
            ], 422);
        }

        if ($recipient->profile && $recipient->profile->allow_friend_requests === false) {
            return response()->json([
                'success' => false,
                'code' => 'FRIEND_REQUESTS_DISABLED',
                'message' => 'ผู้ใช้นี้ปิดรับคำขอเป็นเพื่อน',
            ], 403);
        }

        if (! $user->befriend($recipient)) {
            return response()->json([
                'success' => false,
                'code' => 'FRIEND_REQUEST_NOT_ALLOWED',
                'message' => 'ส่งคำขอเป็นเพื่อนไม่ได้ (เป็นเพื่อนกันแล้ว หรือมีคำขอค้างอยู่)',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'user' => new PublicUserResource($recipient),
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
        $friends = $user->getFriends(20);

        return response()->json([
            'success' => true,
            'data' => $this->formatFriends($friends->getCollection()),
            'meta' => $this->paginationMeta($friends),
        ]);
    }

    /**
     * Get friends of a specific user by identifier (id, reference_code, personal_code หรือ username).
     * ทำตาม privacy ของเจ้าของ: public = ทุกคน · friends = เฉพาะเพื่อน · private = เจ้าของเท่านั้น
     */
    public function userFriends(string $identifier)
    {
        $user = ProfileUserResolver::resolve($identifier);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if (! ProfileAccess::for(auth()->user(), $user)->canSeeFriendsList()) {
            return response()->json([
                'success' => false,
                'code' => 'FRIENDS_LIST_PRIVATE',
                'message' => 'รายชื่อเพื่อนของผู้ใช้นี้เป็นส่วนตัว',
            ], 403);
        }

        $friends = $user->getFriends(20);

        return response()->json([
            'success' => true,
            'data' => $this->formatFriends($friends->getCollection()),
            'meta' => $this->paginationMeta($friends),
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
     * ไม่ส่งชื่อจริง-นามสกุล (แต่ละคนตั้ง privacy เอง) และไม่มีสถานะออนไลน์ (ระบบยังไม่มี presence จริง)
     *
     * @param  Collection<int, User>  $friends
     */
    private function formatFriends($friends)
    {
        $mutualCounts = $this->mutualFriendCounts($friends);

        return $friends->map(function ($friend) use ($mutualCounts) {
            return [
                'id' => $friend->id,
                'user_id' => $friend->id,
                'username' => $friend->username ?? $friend->name,
                'name' => $friend->name,
                'full_name' => $friend->name,
                'avatar' => $friend->profile_photo_url ?? '/images/default-avatar.png',
                'reference_code' => $friend->reference_code,
                'level' => (int) ($friend->xp_level ?? 1),
                'mutual_friends_count' => $mutualCounts[(int) $friend->id] ?? 0,
            ];
        })->values();
    }

    /**
     * นับเพื่อนร่วมระหว่างผู้ชมกับเพื่อนแต่ละคนในหน้านี้ ด้วย query จำนวนคงที่ (ไม่ query ต่อแถว)
     *
     * @param  Collection<int, User>  $friends
     * @return array<int, int> friend id => จำนวนเพื่อนร่วม
     */
    private function mutualFriendCounts($friends): array
    {
        $viewer = auth()->user();
        $pageIds = $friends->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (! $viewer || $pageIds === []) {
            return [];
        }

        $viewerFriendIds = array_flip(array_map('intval', $viewer->getFriendsQueryBuilder()->pluck('id')->all()));
        $type = $viewer->getMorphClass();

        $rows = Friendship::query()
            ->where('status', 'accepted')
            ->where('sender_type', $type)
            ->where('recipient_type', $type)
            ->where(function ($q) use ($pageIds) {
                $q->whereIn('sender_id', $pageIds)->orWhereIn('recipient_id', $pageIds);
            })
            ->get(['sender_id', 'recipient_id']);

        // เก็บเป็นเซตของคน ไม่ใช่นับแถว — บางคู่ถูกบันทึกว่ายอมรับซ้ำ 2 แถว จะได้ไม่นับเพื่อนร่วมคนเดิมสองครั้ง
        $mutual = [];

        foreach ($rows as $row) {
            $sender = (int) $row->sender_id;
            $recipient = (int) $row->recipient_id;

            if (in_array($sender, $pageIds, true) && isset($viewerFriendIds[$recipient])) {
                $mutual[$sender][$recipient] = true;
            }

            if (in_array($recipient, $pageIds, true) && isset($viewerFriendIds[$sender])) {
                $mutual[$recipient][$sender] = true;
            }
        }

        return array_map('count', $mutual);
    }

    /**
     * @param  LengthAwarePaginator  $paginator
     * @return array<string, int>
     */
    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Format the compact user shape needed by sidebar widgets.
     */
    private function formatWidgetUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username ?? $user->name,
            'avatar' => $user->profile_photo_url,
            'profile_photo_url' => $user->profile_photo_url,
            'reference_code' => $user->reference_code,
        ];
    }
}
