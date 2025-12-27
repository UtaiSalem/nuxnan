<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\Activity;
use App\Models\CoursePost;
use Illuminate\Http\Request;
use App\Enums\ActivityType;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\Learn\posts\CoursePostResource;
use App\Http\Requests\StoreCoursePostRequest;
use App\Http\Requests\UpdateCoursePostRequest;

class CoursePostController extends Controller
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
    public function store(Course $course, Request $request)
    {
        try {
            $validatedData = $request->validate([
                'content'           => 'nullable|string|max:5000',
                'images.*'          => 'image|mimes:jpeg,png,jpg,gif,svg|max:4048|nullable',
            ]);

            $content = $validatedData['content'] ?? '';
            $hashtags = $this->extractHashtags($content);

            $post               = new CoursePost();
            $course_post->user_id      = auth()->user()->id;
            $post->course_id    = $course->id;
            $post->academy_id   = $course->academy_id ?? null;
            $post->content      = $validatedData['content'] ?? '';
            $post->privacy_settings = 3;
            $post->status       = 0;
            $post->hashtags     = json_encode($hashtags);
            $post->save();
            
            if($request->hasFile('images')) {
                $images = $request->file('images');
                foreach ($images as $image) {
                    $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('images/courses/posts', $image, $fileName);

                    $course_post->post_images()->create([
                        'filename' => $fileName,
                    ]);
                }
            }

            $activity = new Activity();
            $activity->user_id = $course_post->user_id;
            $activity->activity_type = ActivityType::CREATE_POST->value;
            $activity->activityable()->associate($post);
            $activity->save();

            auth()->user()->decrement('pp',180);

            // return to_route('course.feeds', $course->id);
            return response()->json([
                'success'   => true,
                'message'   => 'Course post created successfully',
                'post'      => new CoursePostResource($post),
                'activity'  => new ActivityResource($activity),
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create course post'. $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course, CoursePost $course_post)
    {
        $course_post->increment('views');

        $activityResource = new ActivityResource($post->activity);
        
        return response()->json([
            'activity' => new ActivityResource($post->activity),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course, CoursePost $course_post)
    {
        $course_post->increment('views');
        
        $activityResource = new ActivityResource($post->activity);
        
        return response()->json([
            'activity' => new ActivityResource($post->activity),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Course $course, CoursePost $course_post, Request $request,)
    {

        $validatedData = $request->validate([
            'content'           => 'required|string|max:5000',
            'privacy_settings'  => 'required|integer|in:1,2,3',
            'images.*'          => 'image|mimes:jpeg,png,jpg,gif,svg|max:4048|nullable',
        ]);

        $content = $validatedData['content'];
        $hashtags = $this->extractHashtags($content);

        $post->content = $validatedData['content'];
        $post->privacy_settings = $validatedData['privacy_settings'];
        $post->hashtags = json_encode($hashtags);
        $post->save();

        if($request->hasFile('images')) {
            $post_images = $request->file('images');
            foreach ($post_images as $image) {
                $fileName = $course_post->id . uniqid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/posts', $image, $fileName);
                CoursePostImage::create([
                    'post_id' => $course_post->id,
                    'filename' => $fileName,
                ]);
            }
        }

        $post->refresh();

        return response()->json([
            'success'   => true,
            'post'      => new CoursePostResource($post),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course, CoursePost $course_post)
    {
        try {

            // Delete post images
            foreach ($course_post->post_images as $cp_image) {
                
                $cp_image->image_comments->each(function ($img_comment){
                    $img_comment->liked()->detach();
                    $img_comment->disliked()->detach();
                    
                    $img_comment->delete();
                });

                $cp_image->postImageLikes()->detach();
                $cp_image->postImageDislikes()->detach();
                
                Storage::disk('public')->delete('images/courses/posts/' . $cp_image->filename);
                $cp_image->delete();
            }
            
            // Delete post likes and dislikes
            $post->post_likes()->delete();
            $post->post_dislikes()->delete();
            // $course_post->likedPosts()->detach();
            // $course_post->dislikedPosts()->detach();

            // Delete post comments
            foreach ($post->post_comments as $comment) {
                // Delete comment images if any
                foreach ($comment->postCommentImages as $commentImage) {
                    Storage::disk('public')->delete('images/courses/posts/comments/' . $commentImage->filename);                    
                    $commentImage->delete();
                }

                $comment->comment_likes()->detach();
                $comment->comment_dislikes()->detach();

                $comment->delete();
            }
            
            // Delete the activity associated with the post
            $post->activity()->delete();
            // Finally, delete the post itself
            $post->delete();
    
            return response()->json([
                'success' => true,
                'message' => 'Course post and all related data deleted successfully',
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course post: ' . $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Extract hashtags from post content.
     *
     * @param string $content The post content.
     * @return array An array of extracted hashtags.
     */
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
