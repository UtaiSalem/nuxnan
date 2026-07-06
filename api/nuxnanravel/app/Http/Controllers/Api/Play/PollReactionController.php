<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Http\Resources\Play\PollCommentResource;
use App\Models\DislikedPoll;
use App\Models\LikedPoll;
use App\Models\Poll;
use App\Models\PollComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollReactionController extends Controller
{
    public function toggleLikePoll(Poll $poll)
    {
        $user = auth()->user();
        $userId = $user->id;
        $hasLiked = LikedPoll::where('poll_id', $poll->id)->where('user_id', $userId)->exists();
        $hasDisliked = DislikedPoll::where('poll_id', $poll->id)->where('user_id', $userId)->exists();

        $requiredPoints = $hasLiked ? 12 : 24;

        if ($user->pp < $requiredPoints) {
            return response()->json([
                'success' => false,
                'message' => $hasLiked
                    ? 'คุณไม่มีพ้อยท์เพียงพอในการยกเลิกไลค์ (ต้องการ 12 แต้ม)'
                    : 'คุณไม่มีพ้อยท์เพียงพอในการกดถูกใจ (ต้องการ 24 แต้ม)',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($poll, $user, $userId, $hasLiked, $hasDisliked) {
                $superAdmin = User::find(1);

                if ($hasDisliked) {
                    DislikedPoll::where('poll_id', $poll->id)->where('user_id', $userId)->delete();
                    $poll->decrement('dislikes');
                }

                if ($hasLiked) {
                    LikedPoll::where('poll_id', $poll->id)->where('user_id', $userId)->delete();
                    $poll->decrement('likes');
                    $user->decrement('pp', 12);
                    if ($superAdmin) {
                        $superAdmin->increment('pp', 12);
                    }
                } else {
                    LikedPoll::create([
                        'poll_id' => $poll->id,
                        'user_id' => $userId,
                    ]);
                    $poll->increment('likes');
                    $user->decrement('pp', 24);
                    if ($poll->user) {
                        $poll->user->increment('pp', 12);
                    }
                    if ($superAdmin) {
                        $superAdmin->increment('pp', 12);
                    }
                }

                return response()->json(['success' => true]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleDislikePoll(Poll $poll)
    {
        $user = auth()->user();
        $userId = $user->id;
        $hasLiked = LikedPoll::where('poll_id', $poll->id)->where('user_id', $userId)->exists();
        $hasDisliked = DislikedPoll::where('poll_id', $poll->id)->where('user_id', $userId)->exists();

        if ($user->pp < 12) {
            return response()->json([
                'success' => false,
                'message' => $hasDisliked
                    ? 'คุณไม่มีพ้อยท์เพียงพอในการยกเลิกดิสไลค์ (ต้องการ 12 แต้ม)'
                    : 'คุณไม่มีพ้อยท์เพียงพอในการกดไม่ถูกใจ (ต้องการ 12 แต้ม)',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($poll, $user, $userId, $hasLiked, $hasDisliked) {
                $superAdmin = User::find(1);

                if ($hasLiked) {
                    LikedPoll::where('poll_id', $poll->id)->where('user_id', $userId)->delete();
                    $poll->decrement('likes');
                }

                if ($hasDisliked) {
                    DislikedPoll::where('poll_id', $poll->id)->where('user_id', $userId)->delete();
                    $poll->decrement('dislikes');
                    $user->decrement('pp', 12);
                    if ($superAdmin) {
                        $superAdmin->increment('pp', 12);
                    }
                } else {
                    DislikedPoll::create([
                        'poll_id' => $poll->id,
                        'user_id' => $userId,
                    ]);
                    $poll->increment('dislikes');
                    $user->decrement('pp', 12);
                    if ($poll->user) {
                        $poll->user->decrement('pp', 12);
                    }
                    if ($superAdmin) {
                        $superAdmin->increment('pp', 24);
                    }
                }

                return response()->json(['success' => true]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeComment(Request $request, Poll $poll)
    {
        $request->validate(['content' => 'required|string']);
        $user = $request->user();

        // Comment cost: 12 points
        if ($user->pp < 12) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีพ้อยท์เพียงพอในการแสดงความคิดเห็น (ต้องการ 12 แต้ม)',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($poll, $user, $request) {
                $comment = PollComment::create([
                    'poll_id' => $poll->id,
                    'user_id' => $user->id,
                    'content' => $request->content,
                ]);

                $user->decrement('pp', 12);
                $superAdmin = User::find(1);
                if ($superAdmin) {
                    $superAdmin->increment('pp', 12);
                }

                return response()->json([
                    'success' => true,
                    'comment' => new PollCommentResource($comment->load('user')),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a poll comment
     */
    public function updateComment(Request $request, PollComment $comment)
    {
        $user = auth()->user();

        // Only comment author can update
        if ($user->id !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขความคิดเห็นนี้',
            ], 403);
        }

        $request->validate(['content' => 'required|string']);

        try {
            $comment->update([
                'content' => $request->content,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'แก้ไขความคิดเห็นสำเร็จ',
                'comment' => new PollCommentResource($comment->fresh()->load('user')),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a poll comment
     */
    public function destroyComment(PollComment $comment)
    {
        $user = auth()->user();
        $poll = $comment->poll;

        // Only comment author or poll author can delete
        if ($user->id !== $comment->user_id && $user->id !== $poll->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ลบความคิดเห็นนี้',
            ], 403);
        }

        try {
            $comment->delete();

            // Decrement poll comments count if exists
            if ($poll && isset($poll->comments_count)) {
                $poll->decrement('comments_count');
            }

            return response()->json([
                'success' => true,
                'message' => 'ลบความคิดเห็นสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
