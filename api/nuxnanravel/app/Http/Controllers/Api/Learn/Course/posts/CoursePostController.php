<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\Activity;
use App\Models\CoursePost;
use App\Models\CoursePostImage;
use Illuminate\Http\Request;
use App\Enums\ActivityType;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Play\ActivityResource;
use App\Http\Resources\Learn\Course\posts\CoursePostResource;
use App\Models\Poll;
use App\Models\QuestionOption;
use App\Http\Requests\StoreCoursePostRequest;
use App\Http\Requests\UpdateCoursePostRequest;

class CoursePostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course, Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $type = $request->input('type'); // discussions, questions, materials, announcements
        
        $query = CoursePost::where('course_id', $course->id)
            ->with([
                'user:id,name,email,profile_photo_path',
                'post_images',
                'post_comments' => function($q) {
                    $q->with(['user:id,name,email,profile_photo_path'])
                      ->orderBy('created_at', 'desc')
                      ->limit(3);
                },
                'poll.options',
                'poll.user',
                'poll.comments.user',
            ])
            ->withCount(['post_comments', 'post_likes', 'post_dislikes'])
            ->orderBy('created_at', 'desc');
        
        // Add type filter if specified
        if ($type && $type !== 'all') {
            $query->where('post_type', $type);
        }

        // Filter by group_id
        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        } else {
            // Optional: Exclude group posts from main course feed if desired
            // $query->whereNull('group_id');
        }
        
        $posts = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'success' => true,
            'data' => CoursePostResource::collection($posts),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'has_more' => $posts->hasMorePages(),
            ]
        ], 200);
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
                'group_id'          => 'nullable|exists:course_groups,id',
            ]);

            $content = $validatedData['content'] ?? '';
            $hashtags = $this->extractHashtags($content);

            $post               = new CoursePost();
            $post->user_id      = auth()->user()->id;
            $post->course_id    = $course->id;
            $post->group_id     = $validatedData['group_id'] ?? null;
            $post->academy_id   = $course->academy_id ?? null;
            $post->content      = $validatedData['content'] ?? '';
            $post->privacy_settings = 3;
            $post->status       = 0;
            $post->hashtags     = $hashtags;

            // Handle Poll Creation
            if ($request->has('poll_question') && $request->has('poll_options')) {
                $pollTitle = $request->poll_question;
                $pollOptions = $request->poll_options;
                $pollDuration = $request->poll_duration ?? 24; // Hours
                $pollPointsPool = (int)$request->input('poll_points_pool', 0);
                
                if (!empty($pollTitle) && is_array($pollOptions) && count($pollOptions) >= 2) {
                    // Check if user has enough points for poll with reward pool
                    $totalPointsNeeded = 180 + $pollPointsPool;
                    if (auth()->user()->pp < $totalPointsNeeded) {
                        return response()->json([
                            'success' => false,
                            'message' => "คุณมีแต้มสะสมไม่พอสำหรับการสร้างโพล (ต้องการ {$totalPointsNeeded} แต้ม)",
                        ], 403);
                    }

                    // Calculate points per vote
                    $maxVotes = (int)$request->input('poll_max_votes', 100);
                    $pointsPerVote = $pollPointsPool > 0 ? floor($pollPointsPool / $maxVotes) : 0;

                    $poll = Poll::create([
                        'user_id' => auth()->id(),
                        'title' => $pollTitle,
                        'start_date' => now(),
                        'end_date' => now()->addHours((int)$pollDuration),
                        'is_active' => true,
                        'is_public' => true,
                        'points_pool' => $pollPointsPool,
                        'points_per_vote' => $pointsPerVote,
                        'points_distributed' => 0,
                        'max_votes' => $maxVotes,
                    ]);

                    foreach ($pollOptions as $index => $optionText) {
                        if (trim($optionText)) {
                            $poll->options()->create([
                                'text' => trim($optionText),
                                'position' => $index,
                            ]);
                        }
                    }
                    
                    $post->poll_id = $poll->id;
                    $post->post_type = 'poll';

                    // Deduct poll points pool from user (base cost is deducted separately)
                    if ($pollPointsPool > 0) {
                        auth()->user()->decrement('pp', $pollPointsPool);
                    }
                }
            }

            $post->save();
            
            if($request->hasFile('images')) {
                $images = $request->file('images');
                foreach ($images as $image) {
                    $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('images/courses/posts', $image, $fileName);

                    $post->post_images()->create([
                        'filename' => $fileName,
                    ]);
                }
            }

            $activity = new Activity();
            $activity->user_id = $post->user_id;
            $activity->activity_type = ActivityType::CREATE_POST->value;
            $activity->activityable()->associate($post);
            $activity->save();

            auth()->user()->decrement('pp',180);

            // Reload the post with all necessary relationships including poll
            $post = $post->fresh(['user', 'post_images', 'post_comments.user', 'course', 'academy', 'poll.options', 'poll.user', 'poll.comments.user']);
            
            // Reload activity with all relationships for poll
            $activity->load(['user', 'activityable.user', 'activityable.poll.options', 'activityable.poll.user', 'activityable.poll.comments.user']);

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

        $activityResource = new ActivityResource($course_post->activity);
        
        return response()->json([
            'activity' => new ActivityResource($course_post->activity),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course, CoursePost $course_post)
    {
        $course_post->increment('views');
        
        $activityResource = new ActivityResource($course_post->activity);
        
        return response()->json([
            'activity' => new ActivityResource($course_post->activity),
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

        $course_post->content = $validatedData['content'];
        $course_post->privacy_settings = $validatedData['privacy_settings'];
        $course_post->hashtags = json_encode($hashtags);
        $course_post->save();

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

        $course_post->refresh();

        return response()->json([
            'success'   => true,
            'post'      => new CoursePostResource($course_post),
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
            $course_post->post_likes()->delete();
            $course_post->post_dislikes()->delete();
            // $course_post->likedPosts()->detach();
            // $course_post->dislikedPosts()->detach();

            // Delete post comments
            foreach ($course_post->post_comments as $comment) {
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
            $course_post->activity()->delete();
            // Finally, delete the post itself
            $course_post->delete();
    
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
