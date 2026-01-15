<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Http\Resources\Play\VideoResource;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /**
     * List videos for a specific user.
     */
    public function userVideos(Request $request, $userId)
    {
        $user = User::where('id', $userId)
            ->orWhere('reference_code', $userId)
            ->firstOrFail();

        $query = Video::where('user_id', $user->id);

        // Apply visibility filter based on viewer
        if (auth()->check()) {
            $viewer = auth()->user();
            if ($viewer->id !== $user->id) {
                // Check if friends
                $isFriend = $viewer->friends()->where('users.id', $user->id)->exists();
                if ($isFriend) {
                    $query->whereIn('privacy_settings', [2, 3]);
                } else {
                    $query->where('privacy_settings', 3);
                }
            }
            // Owner sees all their own videos
        } else {
            // Guests only see public videos
            $query->where('privacy_settings', 3);
        }

        $videos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'videos' => VideoResource::collection($videos),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'total' => $videos->total(),
            ],
        ]);
    }

    /**
     * List auth user's videos (profile/videos).
     */
    public function myVideos(Request $request)
    {
        $videos = Video::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'videos' => VideoResource::collection($videos),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'total' => $videos->total(),
            ],
        ]);
    }

    /**
     * Store a newly created video.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'file' => 'required|file|mimes:mp4,webm,mov,avi|max:512000', // 500MB max
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'privacy' => 'nullable|in:public,friends,private',
        ]);

        $user = auth()->user();

        // Upload video file
        $videoPath = $request->file('file')->store('videos/' . $user->id, 'public');

        // Upload thumbnail if provided
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('videos/' . $user->id . '/thumbnails', 'public');
        }

        // Map privacy string to integer
        $privacyMap = [
            'private' => 1,
            'friends' => 2,
            'public' => 3,
        ];
        $privacySettings = $privacyMap[$request->get('privacy', 'public')] ?? 3;

        // Get video duration (basic approach - can be enhanced with FFmpeg)
        $duration = 0;

        $video = Video::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => '/storage/' . $videoPath,
            'thumbnail_url' => $thumbnailPath ? '/storage/' . $thumbnailPath : null,
            'duration' => $duration,
            'privacy_settings' => $privacySettings,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Video uploaded successfully',
            'video' => new VideoResource($video),
        ], 201);
    }

    /**
     * Update video details.
     */
    public function update(Request $request, Video $video)
    {
        // Ensure user owns this video
        if ($video->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'privacy' => 'nullable|in:public,friends,private',
        ]);

        $data = [];
        if ($request->has('title')) {
            $data['title'] = $request->title;
        }
        if ($request->has('description')) {
            $data['description'] = $request->description;
        }
        if ($request->has('privacy')) {
            $privacyMap = [
                'private' => 1,
                'friends' => 2,
                'public' => 3,
            ];
            $data['privacy_settings'] = $privacyMap[$request->privacy] ?? 3;
        }

        $video->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Video updated successfully',
            'video' => new VideoResource($video->fresh()),
        ]);
    }

    /**
     * Delete a video.
     */
    public function destroy(Video $video)
    {
        // Ensure user owns this video
        if ($video->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Delete files from storage
        if ($video->video_url) {
            $path = str_replace('/storage/', '', $video->video_url);
            Storage::disk('public')->delete($path);
        }
        if ($video->thumbnail_url) {
            $path = str_replace('/storage/', '', $video->thumbnail_url);
            Storage::disk('public')->delete($path);
        }

        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully',
        ]);
    }

    /**
     * Record a view for a video.
     */
    public function recordView(Video $video)
    {
        $video->recordView();

        return response()->json([
            'success' => true,
            'views_count' => $video->views_count,
        ]);
    }

    /**
     * Get a single video by ID.
     */
    public function show(Video $video)
    {
        // Check visibility
        if (auth()->check()) {
            $viewer = auth()->user();
            if ($viewer->id !== $video->user_id) {
                $isFriend = $viewer->friends()->where('users.id', $video->user_id)->exists();
                if ($video->privacy_settings === 1) {
                    return response()->json(['success' => false, 'message' => 'Not found'], 404);
                }
                if ($video->privacy_settings === 2 && !$isFriend) {
                    return response()->json(['success' => false, 'message' => 'Not found'], 404);
                }
            }
        } else {
            if ($video->privacy_settings !== 3) {
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }
        }

        return response()->json([
            'success' => true,
            'video' => new VideoResource($video->load('user')),
        ]);
    }
}
