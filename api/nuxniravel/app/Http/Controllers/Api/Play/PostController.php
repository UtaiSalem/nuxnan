<?php

namespace App\Http\Controllers\Api\Play;

use App\Models\Post;
use App\Models\Share;
use App\Models\Activity;
use App\Models\PostImage;
use App\Models\PostBackground;
use App\Models\Feeling;
use App\Models\ActivityTypeModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Enums\ActivityType;
use App\Services\PostService;
use App\Services\PostMediaService;
use App\Services\LocationService;
use App\Services\MentionService;
use App\Services\TaggingService;
use App\Services\LinkPreviewService;
use App\Http\Resources\Play\PostResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Play\ActivityResource;

class PostController extends \App\Http\Controllers\Controller
{
    protected PostService $postService;
    protected PostMediaService $mediaService;
    protected LocationService $locationService;

    public function __construct(
        PostService $postService,
        PostMediaService $mediaService,
        LocationService $locationService
    ) {
        $this->postService = $postService;
        $this->mediaService = $mediaService;
        $this->locationService = $locationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 15);
        
        $query = Post::query()
            ->with([
                'user',
                'postImages',
                'postLocation',
                'postMentions.user',
                'postTaggedUsers.user',
                'postLinkPreview',
                'activity',
            ])
            ->where('is_published', true)
            ->latest();
        
        // Filter by user if specified
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter by post type
        if ($request->has('post_type')) {
            $query->where('post_type', $request->post_type);
        }
        
        // Filter by hashtag
        if ($request->has('hashtag')) {
            $query->whereJsonContains('hashtags', $request->hashtag);
        }
        
        $posts = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'posts' => PostResource::collection($posts),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        // Return available options for post creation
        return response()->json([
            'success' => true,
            'data' => [
                'privacy_options' => [
                    ['value' => 1, 'label' => 'Only Me', 'label_th' => 'เฉพาะฉัน'],
                    ['value' => 2, 'label' => 'Friends', 'label_th' => 'เพื่อน'],
                    ['value' => 3, 'label' => 'Public', 'label_th' => 'สาธารณะ'],
                ],
                'feelings' => Feeling::active()->ordered()->get(),
                'activity_types' => ActivityTypeModel::active()->ordered()->get(),
                'backgrounds' => PostBackground::active()->ordered()->get(),
                'font_sizes' => ['small', 'medium', 'large', 'xlarge'],
                'max_images' => 20,
                'max_image_size_mb' => 4,
                'max_content_length' => 5000,
                'points_required' => 180,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        Log::info('PostController@store called', [
            'user_id' => auth()->id(),
            'has_content' => $request->has('content'),
            'has_images' => $request->hasFile('images'),
            'has_location' => $request->has('location') || $request->has('location_name'),
            'has_feeling' => $request->has('feeling'),
            'has_background' => $request->has('background_color') || $request->has('background_id'),
        ]);

        try {
            // Check points
            if (auth()->user()->pp < 180) {
                return response()->json([
                    'success' => false,
                    'message' => 'You need at least 180 points to create a post. / คุณมีแต้มสะสมไม่พอสำหรับการโพสต์ กรุณาสะสมแต้มสะสมอย่างน้อย 180 แต้ม',
                ], 403);
            }

            $validatedData = $request->validated();
            
            // Handle background template if background_id is provided
            if (!empty($validatedData['background_id'])) {
                $background = PostBackground::find($validatedData['background_id']);
                if ($background) {
                    $validatedData['background_color'] = $background->background_color;
                    $validatedData['background_gradient'] = $background->background_gradient;
                    $validatedData['background_image'] = $background->background_image;
                    $validatedData['text_color'] = $background->text_color;
                }
            }
            
            // Clean content whitespace
            if (!empty($validatedData['content'])) {
                $validatedData['content'] = preg_replace('/\s+/', ' ', trim($validatedData['content']));
            }
            
            // Handle file uploads
            if ($request->hasFile('images')) {
                $validatedData['images'] = $request->file('images');
            }

            // Create post using service
            $post = $this->postService->createPost($validatedData, auth()->id());

            // Get the activity for response
            $activity = $post->activity;
            $activity->load(['user', 'activityable.user', 'activityable.postImages']);

            // Deduct points
            auth()->user()->decrement('pp', 180);

            return response()->json([
                'success' => true,
                'message' => 'โพสต์สำเร็จ / Post created successfully',
                'post' => new PostResource($post),
                'activity' => new ActivityResource($activity),
            ], 201);
        } catch (\Throwable $th) {
            Log::error('PostController@store error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างโพสต์ / Error creating post',
                'error' => config('app.debug') ? $th->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post): JsonResponse
    {
        // Check if user can view this post
        $user = auth()->user();
        
        // Private post - only owner can view
        if ($post->privacy_settings == 1 && $post->user_id !== $user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this post.',
            ], 403);
        }
        
        // Increment views
        $post->increment('views');

        // Load relationships
        $post->load([
            'user',
            'postImages',
            'postLocation',
            'postMentions.user',
            'postTaggedUsers.user',
            'postLinkPreview',
            'postComments' => function ($query) {
                $query->latest()->limit(3);
            },
        ]);

        return response()->json([
            'success' => true,
            'post' => new PostResource($post),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post): JsonResponse
    {
        if ($post->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this post.',
            ], 403);
        }

        $post->load([
            'postImages',
            'postLocation',
            'postMentions.user',
            'postTaggedUsers.user',
        ]);

        return response()->json([
            'success' => true,
            'post' => new PostResource($post),
            'options' => [
                'privacy_options' => [
                    ['value' => 1, 'label' => 'Only Me', 'label_th' => 'เฉพาะฉัน'],
                    ['value' => 2, 'label' => 'Friends', 'label_th' => 'เพื่อน'],
                    ['value' => 3, 'label' => 'Public', 'label_th' => 'สาธารณะ'],
                ],
                'feelings' => Feeling::active()->ordered()->get(),
                'activity_types' => ActivityTypeModel::active()->ordered()->get(),
                'backgrounds' => PostBackground::active()->ordered()->get(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            
            // Clean content whitespace
            if (!empty($validatedData['content'])) {
                $validatedData['content'] = preg_replace('/\s+/', ' ', trim($validatedData['content']));
            }
            
            // Handle removing feeling
            if (!empty($validatedData['remove_feeling'])) {
                $validatedData['feeling'] = null;
                $validatedData['feeling_icon'] = null;
                $validatedData['activity_type'] = null;
                $validatedData['activity_text'] = null;
            }
            
            // Handle removing background
            if (!empty($validatedData['remove_background'])) {
                $validatedData['background_color'] = null;
                $validatedData['background_gradient'] = null;
                $validatedData['background_image'] = null;
                $validatedData['text_color'] = null;
            }
            
            // Delete specified images
            if (!empty($validatedData['delete_images'])) {
                foreach ($validatedData['delete_images'] as $imageId) {
                    $image = PostImage::where('id', $imageId)
                        ->where('post_id', $post->id)
                        ->first();
                    if ($image) {
                        $this->mediaService->deleteImage($image);
                    }
                }
            }
            
            // Reorder images if specified
            if (!empty($validatedData['image_order'])) {
                $this->mediaService->reorderImages($post, $validatedData['image_order']);
            }
            
            // Handle new file uploads
            if ($request->hasFile('images')) {
                $validatedData['images'] = $request->file('images');
            }

            // Update post using service
            $updatedPost = $this->postService->updatePost($post, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'แก้ไขโพสต์สำเร็จ / Post updated successfully',
                'post' => new PostResource($updatedPost),
            ]);
        } catch (\Throwable $th) {
            Log::error('PostController@update error', [
                'post_id' => $post->id,
                'error' => $th->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแก้ไขโพสต์',
                'error' => config('app.debug') ? $th->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): JsonResponse
    {
        // Check if user owns this post
        if ($post->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ลบโพสต์นี้ / You do not have permission to delete this post',
            ], 403);
        }

        try {
            $this->postService->deletePost($post);

            Log::info('Post deleted successfully', [
                'post_id' => $post->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ลบโพสต์สำเร็จ / Post deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete post', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบโพสต์ / Error deleting post',
            ], 500);
        }
    }

    /**
     * Get the activity for a post.
     */
    public function getPostActivity(Post $post): JsonResponse
    {
        $post->increment('views');

        $activity = $post->activity;
        $activity->load(['user', 'activityable.user', 'activityable.postImages']);

        return response()->json([
            'success' => true,
            'activity' => new ActivityResource($activity),
        ]);
    }

    /**
     * Toggle pin status for a post.
     */
    public function togglePin(Post $post): JsonResponse
    {
        if ($post->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to pin this post.',
            ], 403);
        }

        $post = $this->postService->togglePin($post);

        return response()->json([
            'success' => true,
            'is_pinned' => $post->is_pinned,
            'message' => $post->is_pinned 
                ? 'ปักหมุดโพสต์แล้ว / Post pinned' 
                : 'ยกเลิกปักหมุดแล้ว / Post unpinned',
        ]);
    }

    /**
     * Toggle comments on a post.
     */
    public function toggleComments(Post $post): JsonResponse
    {
        if ($post->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change this setting.',
            ], 403);
        }

        $post = $this->postService->toggleComments($post);

        return response()->json([
            'success' => true,
            'comments_disabled' => $post->comments_disabled,
            'message' => $post->comments_disabled 
                ? 'ปิดคอมเมนต์แล้ว / Comments disabled' 
                : 'เปิดคอมเมนต์แล้ว / Comments enabled',
        ]);
    }

    /**
     * Schedule a post for later publication.
     */
    public function schedule(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to schedule this post.',
            ], 403);
        }

        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $scheduledAt = new \DateTime($request->scheduled_at);
        $post = $this->postService->schedulePost($post, $scheduledAt);

        return response()->json([
            'success' => true,
            'scheduled_at' => $post->scheduled_at,
            'message' => 'ตั้งเวลาโพสต์สำเร็จ / Post scheduled successfully',
        ]);
    }

    /**
     * Get user's scheduled posts.
     */
    public function scheduledPosts(): JsonResponse
    {
        $posts = Post::where('user_id', auth()->id())
            ->where('is_scheduled', true)
            ->where('scheduled_at', '>', now())
            ->with(['postImages', 'postLocation'])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'success' => true,
            'posts' => PostResource::collection($posts),
        ]);
    }

    /**
     * Get user's pinned posts.
     */
    public function pinnedPosts(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', auth()->id());
        
        $posts = Post::where('user_id', $userId)
            ->where('is_pinned', true)
            ->where('is_published', true)
            ->with(['user', 'postImages', 'postLocation'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'posts' => PostResource::collection($posts),
        ]);
    }

    /**
     * Search posts by location.
     */
    public function searchByLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1|max:100',
        ]);

        $posts = $this->locationService->getPostsNearLocation(
            $request->latitude,
            $request->longitude,
            $request->get('radius_km', 10)
        );

        return response()->json([
            'success' => true,
            'posts' => $posts,
        ]);
    }

    /**
     * Get available feelings list.
     */
    public function getFeelings(): JsonResponse
    {
        $feelings = Feeling::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $feelings,
            'feelings' => $feelings, // backward compatibility
        ]);
    }

    /**
     * Get available activity types list.
     */
    public function getActivityTypes(): JsonResponse
    {
        $activityTypes = ActivityTypeModel::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $activityTypes,
            'activity_types' => $activityTypes, // backward compatibility
        ]);
    }

    /**
     * Get available background templates.
     */
    public function getBackgrounds(): JsonResponse
    {
        $backgrounds = PostBackground::active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $backgrounds,
            'backgrounds' => $backgrounds->groupBy('category'), // backward compatibility
        ]);
    }

    /**
     * Get posts where user is tagged.
     */
    public function getTaggedPosts(): JsonResponse
    {
        $posts = Post::whereHas('postTaggedUsers', function ($query) {
            $query->where('user_id', auth()->id())
                  ->where('is_approved', true);
        })
        ->with(['user', 'postImages'])
        ->latest()
        ->paginate(15);

        return response()->json([
            'success' => true,
            'posts' => PostResource::collection($posts),
        ]);
    }

    /**
     * Approve being tagged in a post.
     */
    public function approveTag(Post $post): JsonResponse
    {
        $tag = $post->postTaggedUsers()
            ->where('user_id', auth()->id())
            ->first();

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found.',
            ], 404);
        }

        $tag->approve();

        return response()->json([
            'success' => true,
            'message' => 'Tag approved.',
        ]);
    }

    /**
     * Remove tag from a post (user removes themselves).
     */
    public function removeTag(Post $post): JsonResponse
    {
        $deleted = $post->postTaggedUsers()
            ->where('user_id', auth()->id())
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tag removed.',
        ]);
    }

    /**
     * Extract hashtags from post content.
     */
    private function extractHashtags(string $content): array
    {
        $pattern = '/#\w+/';
        preg_match_all($pattern, $content, $matches);

        $hashtags = [];
        foreach ($matches[0] as $match) {
            $tag = str_replace('#', '', $match);
            $hashtags[] = $tag;
        }

        return $hashtags;
    }
}
