<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Share;
use App\Models\Activity;
use App\Models\CoursePost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\Play\ShareResource;

class ShareController extends Controller
{
    /**
     * Create a new share
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'shareable_type' => 'required|string|in:Post,CoursePost,AcademyPost',
                'shareable_id' => 'required|integer',
                'share_comment' => 'nullable|string|max:5000',
                'privacy' => 'nullable|string|in:public,friends,private'
            ]);
            
            // Determine the full class name
            $shareableTypeMap = [
                'Post' => 'App\\Models\\Post',
                'CoursePost' => 'App\\Models\\CoursePost',
                'AcademyPost' => 'App\\Models\\AcademyPost',
            ];
            
            $shareableType = $shareableTypeMap[$validated['shareable_type']];
            $shareableId = $validated['shareable_id'];
            
            // Find the shareable item
            $shareable = $shareableType::find($shareableId);
            if (!$shareable) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบโพสต์ที่ต้องการแชร์'
                ], 404);
            }
            
            // Prevent sharing own post
            if ($shareable->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่สามารถแชร์โพสต์ของตัวเองได้'
                ], 403);
            }
            
            // Check if already shared
            $existingShare = Share::where('user_id', $user->id)
                ->where('shareable_type', $shareableType)
                ->where('shareable_id', $shareableId)
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
                    'message' => "แต้มของคุณไม่เพียงพอในการแชร์ (ต้องการ {$pointsRequired} แต้ม, มีอยู่ {$userPoints} แต้ม)"
                ], 400);
            }
            
            DB::beginTransaction();
            
            try {
                // Create share
                $share = Share::create([
                    'user_id' => $user->id,
                    'shareable_type' => $shareableType,
                    'shareable_id' => $shareableId,
                    'share_comment' => $validated['share_comment'] ?? null,
                    'privacy' => $validated['privacy'] ?? 'public'
                ]);
                
                // Create activity
                $activity = Activity::create([
                    'user_id' => $user->id,
                    'activityable_type' => 'App\\Models\\Share',
                    'activityable_id' => $share->id,
                    'activity_type' => 'share_post',
                    'activity_details' => json_encode([
                        'share_comment' => $validated['share_comment'] ?? null,
                        'privacy' => $validated['privacy'] ?? 'public',
                        'original_post_type' => $validated['shareable_type'],
                        'original_post_id' => $shareableId
                    ])
                ]);
                
                // Deduct points from sharer
                $user->decrement('pp', $pointsRequired);
                
                // Give points to post author (18 points)
                $shareable->user->increment('pp', 18);
                
                // Increment shares count on original post
                $shareable->increment('shares');
                
                DB::commit();
                
                // Load relationships
                $share->load(['user', 'shareable', 'activity']);
                
                Log::info('Share created successfully', [
                    'user_id' => $user->id,
                    'share_id' => $share->id,
                    'shareable_type' => $validated['shareable_type'],
                    'shareable_id' => $shareableId
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'แชร์โพสต์สำเร็จ',
                    'share' => new ShareResource($share),
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
            Log::error('Share creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแชร์โพสต์',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a share
     * ลบการแชร์โพสต์โดยไม่กระทบโพสต์ต้นฉบับ
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            Log::info('Share deletion attempt', [
                'share_id' => $id,
                'user_id' => $user->id
            ]);
            
            $share = Share::with(['shareable', 'activity', 'shareComments'])->find($id);
            
            if (!$share) {
                Log::warning('Share not found', ['share_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบการแชร์ (ID: ' . $id . ')'
                ], 404);
            }
            
            Log::info('Share found', [
                'share_id' => $share->id,
                'share_user_id' => $share->user_id,
                'requesting_user_id' => $user->id
            ]);
            
            // Check ownership
            if ($share->user_id !== $user->id) {
                Log::warning('Unauthorized share deletion attempt', [
                    'share_id' => $id,
                    'share_owner' => $share->user_id,
                    'requesting_user' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบการแชร์นี้'
                ], 403);
            }
            
            DB::beginTransaction();
            
            try {
                // Get original post (เก็บไว้ก่อนลบ share)
                $shareable = $share->shareable;
                $shareableExists = $shareable ? true : false;
                
                // 1. ลบ Activity ของการแชร์ (activityable_type = Share)
                if ($share->activity) {
                    $share->activity->delete();
                    Log::info('Share activity deleted', ['activity_id' => $share->activity->id]);
                }
                
                // 2. ลบ Share Comments ทั้งหมด
                if ($share->shareComments()->count() > 0) {
                    $commentCount = $share->shareComments()->count();
                    $share->shareComments()->delete();
                    Log::info('Share comments deleted', ['count' => $commentCount]);
                }
                
                // 3. ลบ Likes และ Dislikes
                $share->likedShare()->detach();
                $share->dislikedShare()->detach();
                
                // 4. ลบ Share record
                $share->delete();
                
                // 5. ลด shares count ของโพสต์ต้นฉบับ (ไม่ลบโพสต์)
                if ($shareableExists && $shareable) {
                    $shareable->decrement('shares');
                    Log::info('Decremented shares count on original post', [
                        'post_type' => get_class($shareable),
                        'post_id' => $shareable->id,
                        'remaining_shares' => $shareable->shares
                    ]);
                }
                
                DB::commit();
                
                Log::info('Share deleted successfully', [
                    'share_id' => $id,
                    'user_id' => $user->id,
                    'original_post_preserved' => $shareableExists
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'ลบการแชร์สำเร็จ (โพสต์ต้นฉบับยังคงอยู่)'
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Share deletion failed', [
                'share_id' => $id,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบการแชร์',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shares for a post
     */
    public function shares(Request $request, $type, $id)
    {
        try {
            $typeMap = [
                'post' => 'App\\Models\\Post',
                'course-post' => 'App\\Models\\CoursePost',
                'academy-post' => 'App\\Models\\AcademyPost',
            ];
            
            if (!isset($typeMap[$type])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ประเภทโพสต์ไม่ถูกต้อง'
                ], 400);
            }
            
            $shares = Share::where('shareable_type', $typeMap[$type])
                ->where('shareable_id', $id)
                ->with(['user', 'shareable'])
                ->latest()
                ->paginate(20);
            
            return response()->json([
                'success' => true,
                'shares' => ShareResource::collection($shares)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ], 500);
        }
    }
}
