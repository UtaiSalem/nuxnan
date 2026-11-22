<?php

namespace App\Http\Controllers\Api\Play;

use App\Exceptions\AccountEconomyRestrictedException;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Services\FeedPointsService;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;

class PostReactionController extends Controller
{
    public function toggleLikePost(Post $post)
    {
        try {
            return DB::transaction(function () use ($post) {
                $payer = User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                $userId = $payer->id;
                $hasLiked = $post->likedPost()->where('user_id', $userId)->exists();
                $action = $hasLiked ? 'unlike' : 'like';
                $requiredPoints = (int) config("economy.feed_costs.{$action}");
                $platform = User::find(1);
                $author = $post->user;
                $credits = [
                    ['user' => $platform, 'amount' => $requiredPoints, 'sourceType' => 'feed_platform_fee'],
                ];

                if (! $hasLiked) {
                    $authorShare = (int) config('economy.feed_costs.like_author_share');
                    $credits = [
                        ['user' => $author, 'amount' => $authorShare, 'sourceType' => 'feed_like_reward'],
                        ['user' => $platform, 'amount' => $requiredPoints - $authorShare, 'sourceType' => 'feed_platform_fee'],
                    ];
                }

                $charge = app(FeedPointsService::class)->charge(
                    $payer,
                    $requiredPoints,
                    "feed_{$action}",
                    $post->id,
                    "{$action} post #{$post->id}",
                    $credits,
                    function () use ($post, $userId, $hasLiked) {
                        // Switching reactions detaches the opposite reaction without a refund.
                        if ($post->dislikedPost()->where('user_id', $userId)->exists()) {
                            $post->dislikedPost()->detach($userId);
                            $post->decrement('dislikes');
                        }

                        $post->likedPost()->toggle($userId);
                        if ($hasLiked) {
                            $post->decrement('likes');
                        } else {
                            $post->increment('likes');
                        }

                        return [];
                    },
                );

                if (! $charge['ok']) {
                    return response()->json([
                        'success' => false,
                        'code' => 'INSUFFICIENT_PP',
                        'message' => "แต้มสะสมไม่เพียงพอ ต้องใช้ {$requiredPoints} แต้มในการ {$action}",
                        'required_pp' => $requiredPoints,
                        'current_pp' => $charge['current_pp'],
                    ], 422);
                }

                return response()->json([
                    'success' => true,
                    'user_pp' => $charge['user_pp'],
                ]);
            });
        } catch (AccountEconomyRestrictedException $e) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_RESTRICTED',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    public function toggleDislikePost(Post $post)
    {
        try {
            return DB::transaction(function () use ($post) {
                $payer = User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                $userId = $payer->id;
                $hasDisliked = $post->dislikedPost()->where('user_id', $userId)->exists();
                $action = $hasDisliked ? 'undislike' : 'dislike';
                $requiredPoints = (int) config("economy.feed_costs.{$action}");
                $platform = User::find(1);
                $author = $post->user;
                $credits = [
                    ['user' => $platform, 'amount' => $requiredPoints, 'sourceType' => 'feed_platform_fee'],
                ];

                $charge = app(FeedPointsService::class)->charge(
                    $payer,
                    $requiredPoints,
                    "feed_{$action}",
                    $post->id,
                    "{$action} post #{$post->id}",
                    $credits,
                    function () use ($post, $userId, $hasDisliked, $author, $platform) {
                        // Switching reactions detaches the opposite reaction without a refund.
                        if ($post->likedPost()->where('user_id', $userId)->exists()) {
                            $post->likedPost()->detach($userId);
                            $post->decrement('likes');
                        }

                        $post->dislikedPost()->toggle($userId);
                        if ($hasDisliked) {
                            $post->decrement('dislikes');
                        } else {
                            $post->increment('dislikes');
                            if ($author) {
                                $fine = app(FeedPointsService::class)->fineIfAffordable(
                                    $author,
                                    (int) config('economy.feed_costs.dislike_author_fine'),
                                    'feed_dislike_fine',
                                    $post->id,
                                    'ค่าปรับเจ้าของโพสต์จากการกดไม่ถูกใจ',
                                );
                                if ($fine > 0 && $platform) {
                                    app(PointsService::class)->earn(
                                        User::whereKey($platform->id)->lockForUpdate()->firstOrFail(),
                                        $fine,
                                        'feed_platform_fee',
                                        $post->id,
                                        'ค่าปรับจากการกดไม่ถูกใจเข้าระบบ',
                                    );
                                }
                            }
                        }

                        return [];
                    },
                );

                if (! $charge['ok']) {
                    return response()->json([
                        'success' => false,
                        'code' => 'INSUFFICIENT_PP',
                        'message' => "แต้มสะสมไม่เพียงพอ ต้องใช้ {$requiredPoints} แต้มในการ {$action}",
                        'required_pp' => $requiredPoints,
                        'current_pp' => $charge['current_pp'],
                    ], 422);
                }

                return response()->json([
                    'success' => true,
                    'user_pp' => $charge['user_pp'],
                ]);
            });
        } catch (AccountEconomyRestrictedException $e) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_RESTRICTED',
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
