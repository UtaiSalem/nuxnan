<?php

namespace App\Http\Controllers\Api\Play;

use App\Exceptions\AccountEconomyRestrictedException;
use App\Http\Controllers\Controller;
use App\Models\PostImage;
use App\Models\User;
use App\Services\FeedPointsService;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;

class PostImageReactionController extends Controller
{
    public function toggleLikePostImage(PostImage $post_image)
    {
        try {
            return DB::transaction(function () use ($post_image) {
                $payer = User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                $userId = $payer->id;
                $hasLiked = $post_image->likedPostImage()->where('user_id', $userId)->exists();
                $action = $hasLiked ? 'unlike' : 'like';
                $requiredPoints = (int) config("economy.feed_costs.{$action}");
                $platform = User::find(1);
                $author = $post_image->post->user;
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
                    $post_image->id,
                    "{$action} postimage #{$post_image->id}",
                    $credits,
                    function () use ($post_image, $userId, $hasLiked) {
                        // Switching reactions detaches the opposite reaction without a refund.
                        if ($post_image->dislikedPostImage()->where('user_id', $userId)->exists()) {
                            $post_image->dislikedPostImage()->detach($userId);
                            $post_image->decrement('dislikes');
                        }

                        $post_image->likedPostImage()->toggle($userId);
                        if ($hasLiked) {
                            $post_image->decrement('likes');
                        } else {
                            $post_image->increment('likes');
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

    public function toggleDislikePostImage(PostImage $post_image)
    {
        try {
            return DB::transaction(function () use ($post_image) {
                $payer = User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                $userId = $payer->id;
                $hasDisliked = $post_image->dislikedPostImage()->where('user_id', $userId)->exists();
                $action = $hasDisliked ? 'undislike' : 'dislike';
                $requiredPoints = (int) config("economy.feed_costs.{$action}");
                $platform = User::find(1);
                $author = $post_image->post->user;
                $credits = [
                    ['user' => $platform, 'amount' => $requiredPoints, 'sourceType' => 'feed_platform_fee'],
                ];

                $charge = app(FeedPointsService::class)->charge(
                    $payer,
                    $requiredPoints,
                    "feed_{$action}",
                    $post_image->id,
                    "{$action} postimage #{$post_image->id}",
                    $credits,
                    function () use ($post_image, $userId, $hasDisliked, $author, $platform) {
                        // Switching reactions detaches the opposite reaction without a refund.
                        if ($post_image->likedPostImage()->where('user_id', $userId)->exists()) {
                            $post_image->likedPostImage()->detach($userId);
                            $post_image->decrement('likes');
                        }

                        $post_image->dislikedPostImage()->toggle($userId);
                        if ($hasDisliked) {
                            $post_image->decrement('dislikes');
                        } else {
                            $post_image->increment('dislikes');
                            if ($author) {
                                $fine = app(FeedPointsService::class)->fineIfAffordable(
                                    $author,
                                    (int) config('economy.feed_costs.dislike_author_fine'),
                                    'feed_dislike_fine',
                                    $post_image->id,
                                    'ค่าปรับเจ้าของโพสต์จากการกดไม่ถูกใจ',
                                );
                                if ($fine > 0 && $platform) {
                                    app(PointsService::class)->earn(
                                        User::whereKey($platform->id)->lockForUpdate()->firstOrFail(),
                                        $fine,
                                        'feed_platform_fee',
                                        $post_image->id,
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
