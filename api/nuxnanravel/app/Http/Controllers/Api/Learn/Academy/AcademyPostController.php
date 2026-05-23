<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;

use App\Models\Academy;
use App\Models\Activity;
use App\Models\AcademyPost;
use Illuminate\Http\Request;
use App\Enums\ActivityType;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreAcademyPostRequest;
use App\Http\Requests\UpdateAcademyPostRequest;

class AcademyPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(Academy $academy, Request $request)
    {
        // Check points - require 180 PP to create academy post
        $pointsRequired = 180;
        if (auth()->user()->pp < $pointsRequired) {
            return response()->json([
                'success' => false,
                'message' => "คุณมีแต้มสะสมไม่พอสำหรับการสร้างโพสต์ (ต้องการ {$pointsRequired} แต้ม)",
            ], 403);
        }

        $validatedData = $request->validate([
            'content'   => 'nullable|string|max:1000',
            'images'    => 'array|max:4',
            'images.*'  => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if (empty($validatedData['content']) && !$request->hasFile('images')) {
            return response()->json(['message' => 'Post cannot be empty.'], 422);
        }

        $content = $validatedData['content'] ?? '';
        $hashtags = $this->extractHashtags($content);

        $post = new AcademyPost();
        $post->user_id = auth()->user()->id;
        $post->academy_id = $academy->id;
        $post->content = $content;
        $post->hashtags = json_encode($hashtags);
        $post->save();
        
        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('images/academies/posts', $fileName, 'public');

                $post->images()->create([
                    'filename' => $fileName,
                    'path' => $path,
                    'url' => Storage::url($path),
                ]);
            }
        }

        $activity = new Activity();
        $activity->user_id = $post->user_id;
        $activity->activity_type = ActivityType::CREATE_POST->value;
        $activity->activityable()->associate($post);
        $activity->save();

        // Deduct points after successful save
        auth()->user()->decrement('pp', $pointsRequired);

        // Load the activity with all necessary relationships for FeedPost component
        $activity->load(['user', 'activityable.user', 'activityable.academy', 'activityable.images']);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
            'post' => $post->load('images', 'user', 'academy'),
            'activity' => new \App\Http\Resources\Play\ActivityResource($activity),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Academy $academy, AcademyPost $post)
    {
         return response()->json([
            'success' => true,
            'post' => $post->load('images', 'user', 'comments'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AcademyPost $academyPost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Academy $academy, AcademyPost $post)
    {
        if ($post->user_id !== auth()->id()) {
             // Check academy admin?
             if ($academy->user_id !== auth()->id()) {
                 return response()->json(['message' => 'Unauthorized'], 403);
             }
        }

        $validatedData = $request->validate([
            'content'   => 'nullable|string|max:1000',
        ]);

        $content = $validatedData['content'] ?? '';
        $hashtags = $this->extractHashtags($content);

        $post->content = $content;
        $post->hashtags = json_encode($hashtags);
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'post' => $post,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Academy $academy, AcademyPost $post)
    {
        if ($post->user_id !== auth()->id()) {
             // Check academy admin?
             // For now simple check
             if ($academy->user_id !== auth()->id()) {
                 return response()->json(['message' => 'Unauthorized'], 403);
             }
        }

        // Delete images
        foreach ($post->images as $image) {
            Storage::disk('public')->delete('images/academies/posts/' . $image->filename);
            $image->delete();
        }

        // Delete Activity
        $post->activity()->delete();

        // Delete Post
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }

    private function extractHashtags($content)
    {
        // Regular expression to match hashtags (e.g., #laravel, #webdev)
        $pattern = '/#\w+/';

        preg_match_all($pattern, $content, $matches);

        // Extract hashtags from the matches
        $hashtags = [];
        foreach ($matches[0] as $match) {
            // Remove the '#' symbol
            $tag = str_replace('#', '', $match);
            $hashtags[] = $tag;
        }

        return $hashtags;
    }
}
