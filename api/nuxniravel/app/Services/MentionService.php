<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\PostMention;
use App\Notifications\PostMentionNotification;
use Illuminate\Support\Facades\Log;

class MentionService
{
    /**
     * Extract @mentions from content and create mention records.
     */
    public function extractAndSaveMentions(Post $post, string $content, int $mentionedBy): array
    {
        $mentions = $this->extractMentions($content);
        $savedMentions = [];

        foreach ($mentions as $mention) {
            $user = User::where('username', $mention['username'])->first();
            
            if ($user && $user->id !== $mentionedBy) {
                $savedMention = PostMention::updateOrCreate(
                    [
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'mentioned_by' => $mentionedBy,
                        'username' => $mention['username'],
                        'position' => $mention['position'],
                    ]
                );
                
                $savedMentions[] = $savedMention;
            }
        }

        return $savedMentions;
    }

    /**
     * Extract @mentions from content.
     * Returns array of ['username' => string, 'position' => int]
     */
    public function extractMentions(string $content): array
    {
        $pattern = '/@([a-zA-Z0-9_]+)/';
        $mentions = [];
        
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[1] as $match) {
            $mentions[] = [
                'username' => $match[0],
                'position' => $match[1] - 1, // Subtract 1 to account for @ symbol
            ];
        }

        return $mentions;
    }

    /**
     * Send notifications for unnotified mentions.
     */
    public function sendMentionNotifications(Post $post): void
    {
        $unnotifiedMentions = $post->postMentions()->unnotified()->with('user')->get();
        
        foreach ($unnotifiedMentions as $mention) {
            try {
                // Send notification (you'll need to create this notification class)
                // $mention->user->notify(new PostMentionNotification($post, $mention));
                
                $mention->markAsNotified();
            } catch (\Exception $e) {
                Log::error('Failed to send mention notification', [
                    'mention_id' => $mention->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Remove all mentions for a post.
     */
    public function removeMentions(Post $post): bool
    {
        return $post->postMentions()->delete() >= 0;
    }

    /**
     * Update mentions when content is edited.
     */
    public function updateMentions(Post $post, string $newContent, int $mentionedBy): array
    {
        // Remove existing mentions
        $this->removeMentions($post);
        
        // Extract and save new mentions
        return $this->extractAndSaveMentions($post, $newContent, $mentionedBy);
    }

    /**
     * Convert @mentions to clickable links in content.
     */
    public function convertMentionsToLinks(string $content, string $baseUrl = '/profile/'): string
    {
        $pattern = '/@([a-zA-Z0-9_]+)/';
        
        return preg_replace_callback($pattern, function ($matches) use ($baseUrl) {
            $username = $matches[1];
            $user = User::where('username', $username)->first();
            
            if ($user) {
                return '<a href="' . $baseUrl . $username . '" class="mention">@' . $username . '</a>';
            }
            
            return '@' . $username;
        }, $content);
    }
}
