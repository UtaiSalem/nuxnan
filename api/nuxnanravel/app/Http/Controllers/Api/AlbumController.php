<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AlbumController extends Controller
{
    /**
     * Get albums for current user or specific user.
     */
    public function index(Request $request, ?string $identifier = null): JsonResponse
    {
        if ($identifier) {
            // Get albums for specific user
            $user = $this->getUserByIdentifier($identifier);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $albums = Album::query()
                ->where('user_id', $user->id)
                ->public()
                ->with(['user:id,name,username,avatar'])
                ->withCount('photos')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Get albums for authenticated user
            $albums = Album::query()
                ->where('user_id', auth()->id())
                ->with(['user:id,name,username,avatar'])
                ->withCount('photos')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $albums,
        ]);
    }

    /**
     * Show specific album.
     */
    public function show(int $id): JsonResponse
    {
        $album = Album::with(['user:id,name,username,avatar', 'photos'])
            ->withCount('photos')
            ->find($id);

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

        return response()->json([
            'success' => true,
            'data' => $album,
        ]);
    }

    /**
     * Create new album.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $album = Album::create([
            'user_id' => auth()->id(),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_public' => $request->input('is_public', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Album created successfully',
            'data' => $album->load('user:id,name,username,avatar'),
        ], 201);
    }

    /**
     * Update album.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $album = Album::find($id);

        if (!$album) {
            return response()->json([
                'success' => false,
                'message' => 'Album not found',
            ], 404);
        }

        // Check if user owns this album
        if ($album->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this album',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cover_photo' => 'nullable|string|max:500',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $album->update($request->only(['name', 'description', 'cover_photo', 'is_public']));

        return response()->json([
            'success' => true,
            'message' => 'Album updated successfully',
            'data' => $album->load('user:id,name,username,avatar'),
        ]);
    }

    /**
     * Delete album.
     */
    public function destroy(int $id): JsonResponse
    {
        $album = Album::find($id);

        if (!$album) {
            return response()->json([
                'success' => false,
                'message' => 'Album not found',
            ], 404);
        }

        // Check if user owns this album
        if ($album->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this album',
            ], 403);
        }

        // Delete all photos in the album
        foreach ($album->photos as $photo) {
            // Delete files from storage
            if ($photo->url) {
                $path = str_replace(\Illuminate\Support\Facades\Storage::url(''), '', $photo->url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }

            if ($photo->thumbnail_url) {
                $path = str_replace(\Illuminate\Support\Facades\Storage::url(''), '', $photo->thumbnail_url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }

            $photo->delete();
        }

        $album->delete();

        return response()->json([
            'success' => true,
            'message' => 'Album deleted successfully',
        ]);
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
