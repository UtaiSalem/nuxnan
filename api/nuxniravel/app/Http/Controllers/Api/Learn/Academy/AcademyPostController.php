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
    public function store(Academy $academy, Request $request)
    {

        $validatedData = $request->validate([
            'content'   => 'nullable|string|max:1000',
            'images'    => 'array|max:4',
            'images.*'  => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $content = $validatedData['content'] ?? '';
        $hashtags = $this->extractHashtags($content);

        // $request->all()->dd();

        $post = new AcademyPost();
        $post->user_id = auth()->user()->id;
        $post->academy_id = $academy->id;
        $post->content = $validatedData['content'] ?? '';
        $post->hashtags = json_encode($hashtags);
        $post->save();
        
        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/academies/posts', $image, $fileName);

                $post->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        $activity = new Activity();
        $activity->user_id = $post->user_id;
        $activity->activity_type = ActivityType::CREATE_POST->value;
        $activity->activityable()->associate($post);
        $activity->save();

        auth()->user()->decrement('pp',36);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(AcademyPost $academyPost)
    {
        //
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
    public function update(UpdateAcademyPostRequest $request, AcademyPost $academyPost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademyPost $academyPost)
    {
        //
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
