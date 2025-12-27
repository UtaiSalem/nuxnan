<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\PostTaggedUser;
use Illuminate\Support\Facades\Log;

class TaggingService
{
    /**
     * Tag users in a post.
     */
    public function tagUsers(Post $post, array $userIds, int $taggedBy): array
    {
        $savedTags = [];

        foreach ($userIds as $userId) {
            // Don't allow tagging yourself
            if ($userId == $taggedBy) {
                continue;
            }

            // Check if user exists
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $tag = PostTaggedUser::updateOrCreate(
                [
                    'post_id' => $post->id,
                    'user_id' => $userId,
                ],
                [
                    'tagged_by' => $taggedBy,
                    'is_approved' => true, // Auto-approve by default
                ]
            );

            $savedTags[] = $tag;
        }

        return $savedTags;
    }

    /**
     * Remove a user tag from a post.
     */
    public function removeTag(Post $post, int $userId): bool
    {
        return $post->postTaggedUsers()
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Remove all tags from a post.
     */
    public function removeAllTags(Post $post): bool
    {
        return $post->postTaggedUsers()->delete() >= 0;
    }

    /**
     * Update tags - remove existing and add new ones.
     */
    public function updateTags(Post $post, array $userIds, int $taggedBy): array
    {
        $this->removeAllTags($post);
        return $this->tagUsers($post, $userIds, $taggedBy);
    }

    /**
     * Get all posts where a user is tagged.
     */
    public function getTaggedPosts(User $user, bool $approvedOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = $user->taggedInPosts();
        
        if ($approvedOnly) {
            $query->wherePivot('is_approved', true);
        }

        return $query->get();
    }

    /**
     * Approve a tag (when user approves being tagged).
     */
    public function approveTag(Post $post, int $userId): bool
    {
        return $post->postTaggedUsers()
            ->where('user_id', $userId)
            ->update(['is_approved' => true]) > 0;
    }

    /**
     * Reject and remove a tag.
     */
    public function rejectTag(Post $post, int $userId): bool
    {
        return $this->removeTag($post, $userId);
    }

    /**
     * Send notifications for unnotified tags.
     */
    public function sendTagNotifications(Post $post): void
    {
        $unnotifiedTags = $post->postTaggedUsers()
            ->unnotified()
            ->approved()
            ->with('user')
            ->get();

        foreach ($unnotifiedTags as $tag) {
            try {
                // Send notification (you'll need to create this notification class)
                // $tag->user->notify(new PostTaggedNotification($post, $tag));
                
                $tag->markAsNotified();
            } catch (\Exception $e) {
                Log::error('Failed to send tag notification', [
                    'tag_id' => $tag->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
