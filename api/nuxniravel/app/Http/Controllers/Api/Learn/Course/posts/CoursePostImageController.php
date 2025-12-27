<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\CoursePost;
use Illuminate\Http\Request;
use App\Models\CoursePostImage;

class CoursePostImageController extends Controller
{
    public $plearndAdmin = null;

    public function __construct()
    {
        $this->plearndAdmin = User::find(1);
    }
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CoursePostImage $coursePostImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CoursePostImage $coursePostImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CoursePostImage $coursePostImage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CoursePostImage $coursePostImage)
    {
        //
    }

    //toggle like image
    public function toggleLike(Course $course, CoursePost $course_post, CoursePostImage $image)
    {
        
        try {

            if(auth()->user()->pp > 24){
                $image->postImageLikes()->toggle(auth()->id());
                if($image->postImageLikes()->where('user_id', auth()->id())->exists())
                {
                    $image->increment('likes');
                    auth()->user()->decrement('pp', 24);
                    $course_post->user()->increment('pp',12);
                    $this->plearndAdmin->increment('pp', 12);
                }
                else
                {
                    $image->decrement('likes');
                    auth()->user()->decrement('pp', 12);
                    $this->plearndAdmin->increment('pp', 12);
                };

                return response()->json([
                    'success' => true,
                ]);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have enough points to like this post. / คุณไม่มีพ้อยท์เพียงพอในการกดถูกใจโพสต์นี้ กรุณาสะสมแต้มเพิ่มเติม',
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 404);
        }
    }

    //toggle dislike image
    public function toggleDislike(Course $course, CoursePost $course_post, CoursePostImage $image)
    {
        try {
            if(auth()->user()->pp > 24){
                $image->postImageDislikes()->toggle(auth()->id());
                if($image->postImageDislikes()->where('user_id', auth()->id())->exists()){
                    $image->increment('dislikes');
                    auth()->user()->decrement('pp', 12);
                    $course_post->user()->decrement('pp', 12);
                    $this->plearndAdmin->increment('pp', 24);
                }
                else{
                    $image->decrement('dislikes');
                    auth()->user()->decrement('pp', 12);
                    $this->plearndAdmin->increment('pp', 12);
                };

                return response()->json([
                    'success' => true,
                ]);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have enough points to dislike this post. / คุณไม่มีพ้อยท์เพียงพอในการกดไม่ถูกใจโพสต์นี้ กรุณาสะสมแต้มเพิ่มเติม',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 404);
        }
    }
}
