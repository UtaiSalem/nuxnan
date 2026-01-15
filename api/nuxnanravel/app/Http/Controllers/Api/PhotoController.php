<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PhotoController extends Controller
{
    /**
     * Get photos for current user or specific user.
     */
    public function index(Request $request, ?string $identifier = null): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        if ($identifier) {
            // Get photos for specific user
            $user = $this->getUserByIdentifier($identifier);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $query = Photo::query()
                ->forUser($user->id)
                ->where('is_public', true)
                ->with(['user:id,name,username,avatar']);
        } else {
            // Get photos for authenticated user
            $query = Photo::query()
                ->forUser(auth()->id())
                ->with(['user:id,name,username,avatar']);
        }

        $photos = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $photos->items(),
            'meta' => [
                'current_page' => $photos->currentPage(),
                'last_page' => $photos->lastPage(),
                'per_page' => $photos->perPage(),
                'total' => $photos->total(),
            ],
        ]);
    }

    /**
     * Get photos by album.
     */
    public function getByAlbum(Request $request, int $albumId): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $album = Album::with('user')->find($albumId);

        if (!$album) {
            return response()->json([
                'success' => false,
                'message' => 'Album not found',
            ], 404);
        }

        // Check if user can view this album
        if ($album->user_id !== auth()->id() && !$album->is_public) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this album',
            ], 403);
        }

        $photos = Photo::query()
            ->inAlbum($albumId)
            ->with(['user:id,name,username,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $photos->items(),
            'meta' => [
                'current_page' => $photos->currentPage(),
                'last_page' => $photos->lastPage(),
                'per_page' => $photos->perPage(),
                'total' => $photos->total(),
            ],
        ]);
    }

    /**
     * Upload photos.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
            'album_id' => 'nullable|exists:albums,id',
            'caption' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $uploadedPhotos = [];
            $files = $request->file('photos');

            foreach ($files as $file) {
                // Store original photo
                $path = $file->store('photos', 'public');
                $url = Storage::url($path);

                // Generate thumbnail
                $thumbnailPath = $this->generateThumbnail($file);
                $thumbnailUrl = $thumbnailPath ? Storage::url($thumbnailPath) : null;

                $photo = Photo::create([
                    'user_id' => auth()->id(),
                    'album_id' => $request->input('album_id'),
                    'url' => $url,
                    'thumbnail_url' => $thumbnailUrl,
                    'caption' => $request->input('caption'),
                    'is_public' => true,
                ]);

                $uploadedPhotos[] = $photo->load('user:id,name,username,avatar');
            }

            return response()->json([
                'success' => true,
                'message' => 'Photos uploaded successfully',
                'photos' => $uploadedPhotos,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show specific photo.
     */
    public function show(int $id): JsonResponse
    {
        $photo = Photo::with(['user:id,name,username,avatar', 'album'])
            ->find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found',
            ], 404);
        }

        // Check if user can view this photo
        if ($photo->user_id !== auth()->id() && !$photo->is_public) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this photo',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $photo,
        ]);
    }

    /**
     * Update photo.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $photo = Photo::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found',
            ], 404);
        }

        // Check if user owns this photo
        if ($photo->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this photo',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:500',
            'album_id' => 'nullable|exists:albums,id',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $photo->update($request->only(['caption', 'album_id', 'is_public']));

        return response()->json([
            'success' => true,
            'message' => 'Photo updated successfully',
            'data' => $photo->load('user:id,name,username,avatar'),
        ]);
    }

    /**
     * Delete photo.
     */
    public function destroy(int $id): JsonResponse
    {
        $photo = Photo::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found',
            ], 404);
        }

        // Check if user owns this photo
        if ($photo->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this photo',
            ], 403);
        }

        // Delete files from storage
        if ($photo->url) {
            $path = str_replace(Storage::url(''), '', $photo->url);
            Storage::disk('public')->delete($path);
        }

        if ($photo->thumbnail_url) {
            $path = str_replace(Storage::url(''), '', $photo->thumbnail_url);
            Storage::disk('public')->delete($path);
        }

        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully',
        ]);
    }

    /**
     * Like a photo.
     */
    public function like(int $id): JsonResponse
    {
        $photo = Photo::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found',
            ], 404);
        }

        // Check if already liked
        if ($photo->isLikedBy(auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You already liked this photo',
            ], 400);
        }

        $photo->likes()->create([
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo liked successfully',
        ]);
    }

    /**
     * Unlike a photo.
     */
    public function unlike(int $id): JsonResponse
    {
        $photo = Photo::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found',
            ], 404);
        }

        $like = $photo->likes()->where('user_id', auth()->id())->first();

        if (!$like) {
            return response()->json([
                'success' => false,
                'message' => 'You have not liked this photo',
            ], 400);
        }

        $like->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo unliked successfully',
        ]);
    }

    /**
     * Generate thumbnail for image.
     */
    private function generateThumbnail($file): ?string
    {
        try {
            // This is a simplified version
            // In production, you would use Intervention Image or similar library
            // to generate actual thumbnails
            
            $path = $file->store('photos/thumbnails', 'public');
            return $path;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get user by identifier (ID, reference_code, or username).
     */
    private function getUserByIdentifier(string $identifier): ?\App\Models\User
    {
        return \App\Models\User::where('id', $identifier)
            ->orWhere('reference_code', $identifier)
            ->orWhere('username', $identifier)
            ->first();
    }
}
