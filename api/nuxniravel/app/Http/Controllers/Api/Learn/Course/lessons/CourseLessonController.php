<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;


use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\info\CourseResource;
use App\Http\Resources\Learn\lessons\LessonResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\groups\CourseGroupResource;


class CourseLessonController extends \App\Http\Controllers\Controller
{
    public function index(Course $course)
    {
        return response()->json([
            'course'        => new CourseResource($course),
            'lessons'       => LessonResource::collection($course->courseLessons()->orderBy('order')->paginate()),
            'isCourseAdmin' => $course->user_id === auth()->id(),
            'courseMemberOfAuth'=> $course->courseMembers()->where('user_id', auth()->id())->first(),

            // 'groups'       => CourseGroupResource::collection($course->courseGroups()->orderBy('order')->get()),
            'groups'       => $course->courseGroups()->get(['id', 'name']),
        ]);
    }

    public function show(Course $course, Lesson $lesson)
    {
        $isCourseAdmin = $lesson->user_id === auth()->id();
        try {
            if (!$isCourseAdmin && (auth()->user()->pp < $lesson->point_tuition_fee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have enough points to access this lesson // คุณมีพอยต์ไม่เพียงพอเพื่อเข้าถึงบทเรียนนี้'
                ], 401);
            }

            $lesson->increment('view_count');

            if (!$isCourseAdmin) { auth()->user()->decrement('pp', $lesson->point_tuition_fee); }

            $course->increment('points', $lesson->point_tuition_fee);

            return response()->json([
                'course'                => new CourseResource($course),
                'lesson'                => new LessonResource($lesson),
                'isCourseAdmin'         => $lesson->user_id === auth()->id(),
                'courseMemberOfAuth'    => $course->courseMembers()->where('user_id', auth()->id())->first(),
                'authUserPP'            => auth()->user()->pp,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found'
            ], 404);
        }
    }

        /**
     * Store a newly created resource in storage.
     */
    public function store(Course $course, Request $request)
    {
        $validated  = $request->validate([
            'title'             => 'required',
            'description'       => 'nullable',
            'content'           => 'nullable',
            'youtube_url'       => 'nullable',
            'order'             => 'required',
            'min_read'          => 'required',
            'point_tuition_fee' => 'required||numeric||min:0',
            'status'            => 'required',
        ]);

        $validated['user_id'] = auth()->id();

        // $lesson = $course->CourseLessons()->create($validated);
        $lesson = $course->CourseLessons()->create([
            'user_id'           =>  auth()->id(),
            'title'             =>  $validated['title'],
            'description'       =>  $validated['description'],
            'content'           =>  $validated['content'],
            'youtube_url'       =>  $validated['youtube_url'],
            'order'             =>  $validated['order'],
            'min_read'          =>  $validated['min_read'],
            'point_tuition_fee' => $validated['point_tuition_fee'],
            'status'            =>  $validated['status'],
        ]);

        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/lessons', $image, $fileName);

                $lesson->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        if($lesson){ $course->increment('lessons');}

        return response()->json([
            'success' => true,
            'newLesson' => new LessonResource($lesson)
        ], 200);
    }

    //edit lesson page
    public function edit(Course $course, Lesson $lesson)
    {
        try {
            if ($course->user_id !== auth()->id()) {
                return to_route('course.lessons.index', $course->id, $lesson->id);
            }
            return response()->json([
                'isCourseAdmin'         => $lesson->user_id === auth()->id(),
                'courseMemberOfAuth'    => $course->courseMembers()->where('user_id', auth()->id())->first(),
                'course' => new CourseResource($course),
                'lesson' => new LessonResource($lesson),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Course $course, Lesson $lesson, Request $request)
    {
        try {
            if ($course->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validated = $request->validate([
                'title'             => 'required',
                'description'       => 'nullable',
                'content'           => 'required',
                'youtube_url'       => 'nullable',
                'min_read'          => 'required',
                'order'             => 'required',
                'point_tuition_fee' => 'required||numeric||min:0',
                'status'            => 'required',
                'images.*'          => 'image|mimes:jpeg,png,jpg,gif,svg|max:4048|nullable',
            ]);

    
            if($request->hasFile('images')) {
                $images = $request->file('images');
                $fileNames = [];
                foreach ($images as $image) {
                    $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                    $image_url = Storage::disk('public')->putFileAs('images/courses/lessons', $image, $fileName);
                    // $fileNames[] = $fileName;
    
                    $lessonImages[] = $lesson->images()->create([
                        'image_url' => $image_url
                    ]);
                }
            }

            $lesson->update([
                'title'             => $request->title,
                'description'       => $validated['description'] === "null" ? null : $validated['description'],
                'content'           => $validated['content'] === "null" ? null : $validated['content'],
                'youtube_url'       => $validated['youtube_url'] === "null" ? null : $validated['youtube_url'],
                'min_read'          => $validated['min_read'],
                'order'             => $validated['order'],
                'point_tuition_fee' => $validated['point_tuition_fee'],
                'status'            => $validated['status']
            ]);

            if($request->hasFile('images')) {
                $images = $request->file('images');
                foreach ($images as $image) {
                    $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('images/courses/lessons', $image, $fileName);
                    $lesson->images()->create([
                        'filename' => $fileName,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'lesson' => new LessonResource($lesson),
                'message' => 'Lesson updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course, Lesson $lesson)
    {
        try {
            // delete lesson comments if exist
            if ($lesson->comments->count() > 0) {
                foreach ($lesson->comments as $comment) {
                    if ($comment->lessonCommentImages->count() > 0) {
                            foreach ($comment->lessonCommentImages() as $comment_image) {
                            Storage::disk('public')->delete('images/courses/lessons/comments/'. $comment_image->filename);
                            $comment_image->delete();
                        }
                    }
                    $comment->likeComment()->detach();
                    $comment->dislikeComment()->detach();
                }
                $lesson->comments()->delete();
            }

            // delete lesson topics if exist
            if ($lesson->topics->count() > 0) {
                foreach ($lesson->topics as $topic) {
                    if ($topic->images->count() > 0) {
                        foreach ($topic->images as $topic_image) {
                            Storage::disk('public')->delete('images/courses/lessons/topics/'. $topic_image->filename);
                            $topic_image->delete();
                        }
                    }
                }
                $lesson->topics()->delete();
            }

            if ($lesson->images->count() > 0) {
                foreach ($lesson->images as $lesson_image) {
                    Storage::disk('public')->delete('images/courses/lessons/'. $lesson_image->filename);
                    $lesson_image->delete();
                }
            }

            // delete lesson likes if exist
            if ($lesson->likes) {
                $lesson->likeLesson()->detach();
            }
            // delete lesson dislikes if exist
            if ($lesson->dislikes) {
                $lesson->dislikeLesson()->detach();
            }

            $lesson->delete();

            return response()->json([
                'success' => true
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
