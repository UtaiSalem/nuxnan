<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Play\ActivityResource;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupAdmin;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\Notification;
use App\Services\AcademyScopeAccessService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcademyPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(Academy $academy, Request $request)
    {
        // Check points - require 180 PP to create academy post
        $pointsRequired = 180;
        if (auth()->user()->pp < $pointsRequired) {
            return response()->json([
                'success' => false,
                'message' => "คุณมีแต้มสะสมไม่พอสำหรับการสร้างโพสต์ (ต้องการ {$pointsRequired} แต้ม)",
            ], 403);
        }

        $validatedData = $request->validate([
            'content' => 'nullable|string|max:1000',
            'images' => 'array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'posted_as_group_id' => 'nullable|integer|exists:academy_groups,id',
            'post_type' => 'nullable|string|in:regular,announcement,event,director,attendance,achievement',
            'target_audience' => 'nullable|array',
            'target_audience.*' => 'string|in:student,teacher,parent,all',
            'reward_points' => 'nullable|integer|min:0|max:999',
            'embed_data' => 'nullable|array',
            'is_pinned' => 'nullable|boolean',
            'scope_type' => 'nullable|in:academy,department,classroom',
            'scope_id' => 'nullable|integer',
        ]);

        if (empty($validatedData['content']) && ! $request->hasFile('images')) {
            return response()->json(['message' => 'Post cannot be empty.'], 422);
        }

        // Custom validation check สำหรับ Posted as Group
        if ($request->filled('posted_as_group_id')) {
            $groupId = $validatedData['posted_as_group_id'];
            $userId = auth()->id();

            // 1. ตรวจสอบว่าเป็นสมาชิก หรือ Admin ของกลุ่มนั้นหรือไม่
            $isMember = AcademyGroupMember::where('academy_group_id', $groupId)
                ->where('user_id', $userId)
                ->exists();
            $isAdmin = AcademyGroupAdmin::where('academy_group_id', $groupId)
                ->where('user_id', $userId)
                ->exists();

            if (! $isMember && ! $isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่ใช่สมาชิกของส่วนงานนี้',
                ], 403);
            }

            // 2. ตรวจสอบสิทธิ์ can_post ของกลุ่ม (STRICT permission check)
            $canPost = AcademyGroupPermission::where('academy_group_id', $groupId)
                ->where('permission_key', 'can_post')
                ->where('enabled', true)
                ->exists();

            if (! $canPost) {
                return response()->json([
                    'success' => false,
                    'message' => 'ส่วนงานนี้ยังไม่ได้เปิดสิทธิ์โพสต์',
                ], 403);
            }
        }

        // ตรวจว่า scope เป็นของโรงเรียนนี้จริง และผู้โพสต์เป็นสมาชิกของฝ่าย/ห้องนั้น
        $scope = app(AcademyScopeAccessService::class)->authorizePost(
            $academy,
            auth()->user(),
            $validatedData['scope_type'] ?? null,
            isset($validatedData['scope_id']) ? (int) $validatedData['scope_id'] : null
        );

        $content = $validatedData['content'] ?? '';
        $hashtags = $this->extractHashtags($content);

        $isAcademyAdmin = $academy->isAdmin(auth()->user());
        $postType = $validatedData['post_type'] ?? 'regular';
        $targetAudience = $validatedData['target_audience'] ?? null;
        $rewardPoints = $validatedData['reward_points'] ?? 0;
        $embedData = $validatedData['embed_data'] ?? null;
        $isPinned = $validatedData['is_pinned'] ?? false;

        if (! $isAcademyAdmin) {
            $postType = 'regular';
            $targetAudience = null;
            $rewardPoints = 0;
            $embedData = null;
            $isPinned = false;
        }

        $post = new AcademyPost;
        $post->user_id = auth()->user()->id;
        $post->academy_id = $academy->id;
        $post->scope_type = $scope['scope_type'];
        $post->scope_id = $scope['scope_id'];
        $post->content = $content;
        $post->hashtags = json_encode($hashtags);
        if ($request->filled('posted_as_group_id')) {
            $post->posted_as_group_id = $validatedData['posted_as_group_id'];
        }
        $post->post_type = $postType;
        $post->target_audience = $targetAudience;
        $post->reward_points = $rewardPoints;
        $post->embed_data = $embedData;
        $post->is_pinned = $isPinned;
        $post->save();

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                $path = $image->storeAs('images/academies/posts', $fileName, 'public');

                $post->images()->create([
                    'filename' => $fileName,
                    'path' => $path,
                    'url' => Storage::url($path),
                ]);
            }
        }

        $activity = new Activity;
        $activity->user_id = $post->user_id;
        $activity->activity_type = ActivityType::CREATE_POST->value;
        $activity->activityable()->associate($post);
        $activity->save();

        // Dispatch notifications for group post to all group members/admins
        if ($post->posted_as_group_id) {
            $groupId = $post->posted_as_group_id;
            $group = AcademyGroup::find($groupId);
            if ($group) {
                $memberIds = AcademyGroupMember::where('academy_group_id', $groupId)
                    ->pluck('user_id');
                $adminIds = AcademyGroupAdmin::where('academy_group_id', $groupId)
                    ->pluck('user_id');

                $recipientIds = $memberIds->merge($adminIds)->unique()->filter(function ($id) use ($post) {
                    return $id != $post->user_id;
                });

                $notifications = [];
                foreach ($recipientIds as $recipientId) {
                    $notifications[] = [
                        'user_id' => $recipientId,
                        'sender_id' => auth()->id(),
                        'type' => Notification::TYPE_GROUP_POST_CREATED,
                        'content' => "มีข่าวสารโพสต์ใหม่ในนามกลุ่ม \"{$group->name}\"",
                        'action_url' => "/academies/{$academy->name}/groups/{$groupId}",
                        'related_id' => $post->id,
                    ];
                }
                app(NotificationService::class)->sendBulk($notifications);
            }
        }

        // Deduct points after successful save
        auth()->user()->decrement('pp', $pointsRequired);

        // Load the activity with all necessary relationships for FeedPost component
        $activity->load(['user', 'activityable.user', 'activityable.academy', 'activityable.images']);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
            'post' => $post->load('images', 'user', 'academy'),
            'activity' => new ActivityResource($activity),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Academy $academy, AcademyPost $post)
    {
        return response()->json([
            'success' => true,
            'post' => $post->load('images', 'user', 'comments'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AcademyPost $academyPost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Academy $academy, AcademyPost $post)
    {
        if ($post->user_id !== auth()->id()) {
            // Check academy admin?
            if ($academy->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $validatedData = $request->validate([
            'content' => 'nullable|string|max:1000',
        ]);

        $content = $validatedData['content'] ?? '';
        $hashtags = $this->extractHashtags($content);

        $post->content = $content;
        $post->hashtags = json_encode($hashtags);
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'post' => $post,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Academy $academy, AcademyPost $post)
    {
        if ($post->user_id !== auth()->id()) {
            // Check academy admin?
            // For now simple check
            if ($academy->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Delete images
        foreach ($post->images as $image) {
            Storage::disk('public')->delete('images/academies/posts/'.$image->filename);
            $image->delete();
        }

        // Delete Activity
        $post->activity()->delete();

        // Delete Post
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }

    private function extractHashtags($content)
    {
        // Regular expression to match hashtags (e.g., #laravel, #webdev)
        $pattern = '/#\w+/';

        preg_match_all($pattern, $content, $matches);

        // Extract hashtags from the matches
        $hashtags = [];
        foreach ($matches[0] as $match) {
            // Remove the '#' symbol
            $tag = str_replace('#', '', $match);
            $hashtags[] = $tag;
        }

        return $hashtags;
    }
}
