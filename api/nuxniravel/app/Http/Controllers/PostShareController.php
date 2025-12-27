<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Share;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\Play\ActivityResource;
use App\Http\Resources\Play\ShareResource;

class PostShareController extends Controller
{
    /**
     * Share a post
     */
    public function share(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Find post
            $post = Post::find($id);
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบโพสต์'
                ], 404);
            }
            
            // Prevent sharing own post
            if ($post->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่สามารถแชร์โพสต์ของตัวเองได้'
                ], 403);
            }
            
            // Check if already shared (check Share table, not Activity)
            $existingShare = Share::where('user_id', $user->id)
                ->where('shareable_type', 'App\\Models\\Post')
                ->where('shareable_id', $post->id)
                ->first();
            
            if ($existingShare) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณได้แชร์โพสต์นี้แล้ว'
                ], 400);
            }
            
            // Check user has enough points
            $pointsRequired = 36;
            $userPoints = $user->pp ?? 0;
            if ($userPoints < $pointsRequired) {
                return response()->json([
                    'success' => false,
                    'message' => "แต้มของคุณไม่เพียงพอ\n\nต้องการ: {$pointsRequired} แต้ม\nมีอยู่: {$userPoints} แต้ม\nขาดอีก: " . ($pointsRequired - $userPoints) . " แต้ม"
                ], 400);
            }
            
            // Validate request
            $validated = $request->validate([
                'share_comment' => 'nullable|string|max:1000',
                'privacy' => 'nullable|string|in:public,friends,private',
                'tagged_friends' => 'nullable|array',
                'location' => 'nullable|string|max:255'
            ]);
            
            DB::beginTransaction();
            
            try {
                // Deduct points from user
                $user->pp -= $pointsRequired;
                $user->save();
                
                // Add points to post author (18 points)
                $postAuthor = User::find($post->user_id);
                if ($postAuthor) {
                    $postAuthor->pp += 18;
                    $postAuthor->save();
                }
                
                // Add points to system (Super Admin - User ID 1)
                $superAdmin = User::find(1);
                if ($superAdmin) {
                    $superAdmin->pp += 18;
                    $superAdmin->save();
                }
                
                // Increment post shares and views
                $post->increment('shares');
                $post->increment('views');
                
                // Create Share record first
                $share = Share::create([
                    'user_id' => $user->id,
                    'shareable_type' => 'App\\Models\\Post',
                    'shareable_id' => $post->id,
                    'share_comment' => $validated['share_comment'] ?? null,
                    'privacy' => $validated['privacy'] ?? 'public'
                ]);
                
                // Create activity record pointing to Share (not Post)
                $activityDetails = [
                    'share_comment' => $validated['share_comment'] ?? null,
                    'privacy' => $validated['privacy'] ?? 'public',
                    'original_post_type' => 'Post',
                    'original_post_id' => $post->id
                ];
                
                $activity = Activity::create([
                    'user_id' => $user->id,
                    'activityable_type' => 'App\\Models\\Share',
                    'activityable_id' => $share->id,
                    'activity_type' => 'share_post',
                    'activity_details' => json_encode($activityDetails)
                ]);
                
                DB::commit();
                
                // Load activity with relationships for response
                $activity->load(['user', 'activityable']);
                
                Log::info('Post shared successfully', [
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'activity_id' => $activity->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'แชร์โพสต์อำเร็จ',
                    'activity' => new ActivityResource($activity),
                    'shares' => $post->shares,
                    'user_points' => $user->pp
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to share post', [
                'error' => $e->getMessage(),
                'post_id' => $id,
                'user_id' => $request->user()->id ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแชร์โพสต์'
            ], 500);
        }
    }
    
    /**
     * Unshare a post (remove share and activity)
     */
    public function unshare(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Find the share record
            $share = Share::where('user_id', $user->id)
                ->where('shareable_type', 'App\\Models\\Post')
                ->where('shareable_id', $id)
                ->first();
            
            if (!$share) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบการแชร์ของคุณ'
                ], 404);
            }
            
            // Find post
            $post = Post::find($id);
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบโพสต์'
                ], 404);
            }
            
            DB::beginTransaction();
            
            try {
                // Delete activity that points to this share
                Activity::where('activityable_type', 'App\\Models\\Share')
                    ->where('activityable_id', $share->id)
                    ->delete();
                
                // Delete share record
                $share->delete();
                
                // Decrement post shares count
                if ($post->shares > 0) {
                    $post->decrement('shares');
                }
                
                DB::commit();
                
                Log::info('Post unshared successfully', [
                    'user_id' => $user->id,
                    'post_id' => $post->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'ยกเลิกการแชร์สำเร็จ',
                    'shares' => $post->shares
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to unshare post', [
                'error' => $e->getMessage(),
                'post_id' => $id,
                'user_id' => $request->user()->id ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการยกเลิกการแชร์'
            ], 500);
        }
    }
    
    /**
     * Get list of users who shared this post
     */
    public function shares(Request $request, $id)
    {
        try {
            $post = Post::find($id);
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบโพสต์'
                ], 404);
            }
            
            // Get all shares for this post from Share table
            $shares = Share::where('shareable_type', 'App\\Models\\Post')
                ->where('shareable_id', $id)
                ->with('user:id,name,profile_photo_path')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            return response()->json([
                'success' => true,
                'shares' => $shares->items(),
                'total' => $shares->total(),
                'pagination' => [
                    'current_page' => $shares->currentPage(),
                    'last_page' => $shares->lastPage(),
                    'per_page' => $shares->perPage(),
                    'has_more' => $shares->hasMorePages()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get shares list', [
                'error' => $e->getMessage(),
                'post_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดรายการผู้แชร์'
            ], 500);
        }
    }
}
