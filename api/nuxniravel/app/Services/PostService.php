<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Activity;
use App\Models\PostImage;
use App\Enums\ActivityType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PostService
{
    protected MentionService $mentionService;
    protected LinkPreviewService $linkPreviewService;
    protected LocationService $locationService;
    protected TaggingService $taggingService;
    protected PostMediaService $mediaService;

    public function __construct(
        MentionService $mentionService,
        LinkPreviewService $linkPreviewService,
        LocationService $locationService,
        TaggingService $taggingService,
        PostMediaService $mediaService
    ) {
        $this->mentionService = $mentionService;
        $this->linkPreviewService = $linkPreviewService;
        $this->locationService = $locationService;
        $this->taggingService = $taggingService;
        $this->mediaService = $mediaService;
    }

    /**
     * Create a new post with all features.
     */
    public function createPost(array $data, int $userId): Post
    {
        return DB::transaction(function () use ($data, $userId) {
            // Extract hashtags from content
            $content = $data['content'] ?? '';
            $hashtags = $this->extractHashtags($content);

            // Determine post type
            $postType = $this->determinePostType($data);

            // Create the post
            $post = Post::create([
                'user_id' => $userId,
                'content' => $content,
                'privacy_settings' => $data['privacy_settings'] ?? 3,
                'status' => 1,
                'hashtags' => $hashtags,
                'post_type' => $postType,
                
                // Location (simple)
                'location' => $data['location_name'] ?? null,
                
                // Feeling/Activity
                'feeling' => $data['feeling'] ?? null,
                'feeling_icon' => $data['feeling_icon'] ?? null,
                'activity_type' => $data['activity_type'] ?? null,
                'activity_text' => $data['activity_text'] ?? null,
                
                // Background/Theme (for text posts)
                'background_color' => $data['background_color'] ?? null,
                'background_gradient' => $data['background_gradient'] ?? null,
                'background_image' => $data['background_image'] ?? null,
                'text_color' => $data['text_color'] ?? null,
                'font_size' => $data['font_size'] ?? 'medium',
                
                // Scheduling
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'is_scheduled' => !empty($data['scheduled_at']),
                'is_published' => empty($data['scheduled_at']),
                
                // Options
                'comments_disabled' => $data['comments_disabled'] ?? false,
                'is_pinned' => $data['is_pinned'] ?? false,
                
                // Poll reference
                'poll_id' => $data['poll_id'] ?? null,
            ]);

            // Upload images if provided
            if (!empty($data['images'])) {
                $this->mediaService->uploadImages(
                    $post, 
                    $data['images'],
                    $data['image_captions'] ?? null
                );
            }

            // Save structured location if provided
            if (!empty($data['location'])) {
                $this->locationService->saveLocation($post, $data['location']);
            }

            // Extract and save mentions
            if ($content) {
                $this->mentionService->extractAndSaveMentions($post, $content, $userId);
            }

            // Tag users if provided
            if (!empty($data['tagged_users'])) {
                $this->taggingService->tagUsers($post, $data['tagged_users'], $userId);
            }

            // Generate link preview if content contains URL
            if ($content && !$post->hasMedia()) {
                $this->linkPreviewService->extractAndSaveLinkPreview($post, $content);
            }

            // Create activity
            $this->createActivity($post, ActivityType::CREATE_POST);

            // Load relationships
            $post->load([
                'user',
                'postImages',
                'postLocation',
                'postMentions.user',
                'postTaggedUsers.user',
                'postLinkPreview',
            ]);

            return $post;
        });
    }

    /**
     * Update an existing post.
     */
    public function updatePost(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data) {
            $content = $data['content'] ?? $post->content;
            $hashtags = $this->extractHashtags($content);
            
            // Track if content changed for edit marker
            $contentChanged = $content !== $post->content;

            $post->update([
                'content' => $content,
                'hashtags' => $hashtags,
                'privacy_settings' => $data['privacy_settings'] ?? $post->privacy_settings,
                'location' => $data['location_name'] ?? $post->location,
                
                // Feeling/Activity
                'feeling' => $data['feeling'] ?? $post->feeling,
                'feeling_icon' => $data['feeling_icon'] ?? $post->feeling_icon,
                'activity_type' => $data['activity_type'] ?? $post->activity_type,
                'activity_text' => $data['activity_text'] ?? $post->activity_text,
                
                // Background/Theme
                'background_color' => $data['background_color'] ?? $post->background_color,
                'background_gradient' => $data['background_gradient'] ?? $post->background_gradient,
                'background_image' => $data['background_image'] ?? $post->background_image,
                'text_color' => $data['text_color'] ?? $post->text_color,
                'font_size' => $data['font_size'] ?? $post->font_size,
                
                // Options
                'comments_disabled' => $data['comments_disabled'] ?? $post->comments_disabled,
                
                // Edit tracking
                'is_edited' => $contentChanged ? true : $post->is_edited,
                'edited_at' => $contentChanged ? now() : $post->edited_at,
            ]);

            // Upload new images if provided
            if (!empty($data['images'])) {
                $this->mediaService->uploadImages(
                    $post,
                    $data['images'],
                    $data['image_captions'] ?? null
                );
            }

            // Update structured location if provided
            if (isset($data['location'])) {
                if ($data['location']) {
                    $this->locationService->saveLocation($post, $data['location']);
                } else {
                    $this->locationService->removeLocation($post);
                }
            }

            // Update mentions
            if ($content) {
                $this->mentionService->updateMentions($post, $content, $post->user_id);
            }

            // Update tagged users if provided
            if (isset($data['tagged_users'])) {
                $this->taggingService->updateTags($post, $data['tagged_users'], $post->user_id);
            }

            // Update link preview if no media
            if ($content && !$post->hasMedia()) {
                $this->linkPreviewService->extractAndSaveLinkPreview($post, $content);
            }

            // Reload relationships
            $post->load([
                'user',
                'postImages',
                'postLocation',
                'postMentions.user',
                'postTaggedUsers.user',
                'postLinkPreview',
            ]);

            return $post;
        });
    }

    /**
     * Delete a post and all related data.
     */
    public function deletePost(Post $post): bool
    {
        return DB::transaction(function () use ($post) {
            // Delete images
            $this->mediaService->deleteAllImages($post);

            // Delete mentions
            $this->mentionService->removeMentions($post);

            // Delete tags
            $this->taggingService->removeAllTags($post);

            // Delete link preview
            $this->linkPreviewService->removeLinkPreview($post);

            // Delete location
            $this->locationService->removeLocation($post);

            // Delete shares and their activities
            foreach ($post->postShares as $share) {
                Activity::where('activityable_type', 'App\\Models\\Share')
                    ->where('activityable_id', $share->id)
                    ->delete();
                $share->delete();
            }

            // Delete comments
            $post->postComments()->delete();

            // Delete activity
            $post->activity()->delete();

            // Delete the post
            return $post->delete();
        });
    }

    /**
     * Pin or unpin a post.
     */
    public function togglePin(Post $post): Post
    {
        $post->update(['is_pinned' => !$post->is_pinned]);
        return $post;
    }

    /**
     * Toggle comments on a post.
     */
    public function toggleComments(Post $post): Post
    {
        $post->update(['comments_disabled' => !$post->comments_disabled]);
        return $post;
    }

    /**
     * Schedule a post.
     */
    public function schedulePost(Post $post, \DateTime $scheduledAt): Post
    {
        $post->update([
            'scheduled_at' => $scheduledAt,
            'is_scheduled' => true,
            'is_published' => false,
        ]);
        
        return $post;
    }

    /**
     * Publish a scheduled post.
     */
    public function publishScheduledPost(Post $post): Post
    {
        $post->update([
            'is_scheduled' => false,
            'is_published' => true,
        ]);
        
        return $post;
    }

    /**
     * Extract hashtags from content.
     */
    protected function extractHashtags(string $content): array
    {
        $pattern = '/#(\w+)/u';
        preg_match_all($pattern, $content, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Determine post type based on content.
     */
    protected function determinePostType(array $data): string
    {
        if (!empty($data['poll_id'])) {
            return 'poll';
        }
        
        if (!empty($data['images'])) {
            return 'photo';
        }
        
        if (!empty($data['background_color']) || !empty($data['background_gradient'])) {
            return 'status';
        }
        
        $content = $data['content'] ?? '';
        if ($this->linkPreviewService->extractFirstUrl($content)) {
            return 'link';
        }
        
        return 'text';
    }

    /**
     * Create activity for post action.
     */
    protected function createActivity(Post $post, ActivityType $type): Activity
    {
        $activity = new Activity();
        $activity->user_id = $post->user_id;
        $activity->activity_type = $type->value;
        $activity->activityable()->associate($post);
        $activity->save();
        
        return $activity;
    }
}
