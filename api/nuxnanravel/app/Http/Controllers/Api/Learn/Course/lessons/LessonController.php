<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course)
    {
        // $isCourseAdmin = $course->user_id == auth()->id() ? true: false;
        return response()->json([
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
            'course'        => new CourseResource($course),
            'lessons'       => LessonResource::collection($course->lessons),
            'groups'        => CourseGroupResource::collection($course->courseGroups),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Course $course, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title'         => ['required','string'],
            'description'   => ['nullable','string'],
            'content'       => ['nullable','string'],
            'youtube_url'   => ['nullable','string'],
            'status'        => ['required'],
            // validate the image
            'images.*'      => 'image|mimes:jpeg,png,jpg,gif,svg|max:4048|nullable',
        ]);
        
        $validated['user_id'] = auth()->id();

        $lesson = $course->lessons()->create([
            'user_id'       => $validated['user_id'],
            'title'         => $validated['title'],
            'description'   => $validated['description'] === null || $validated['description'] === "null" ? null : $validated['description'],
            'content'       => $validated['content'] === null || $validated['content'] === "null" ? null : $validated['content'],
            'youtube_url'   => $validated['youtube_url'] === null || $validated['youtube_url'] === "null" ? null : $validated['youtube_url'],
            'status'        => $validated['status'],
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
            'newLesson' => new LessonResource(Lesson::find($lesson->id))
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Lesson $lesson)
    {
        $isCourseAdmin = $lesson->course->isAdmin(auth()->user());
        $course = $lesson->course;
        
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
                'isCourseAdmin'         => $course->isAdmin(auth()->user()),
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
     * Show the form for editing the specified resource.
     */
    public function edit(Lesson $lesson)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Course $course, Lesson $lesson, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title'             => ['required','string'],
            'description'       => ['nullable','string'],
            'content'           => ['nullable','string'],
            'youtube_url'       => ['nullable','string'],
            'min_read'          => ['nullable','integer'],
            'point_tuition_fee' => ['nullable','integer'],
            'order'             => ['nullable','integer'],
            'status'            => ['nullable','integer'],
            'images.*'          => 'image|mimes:jpeg,png,jpg,gif,svg|max:4048|nullable',
        ]);

        $lesson->update([
            'title'             => $validated['title'],
            'description'       => $validated['description'] === null || $validated['description'] === "null" ? null : $validated['description'],
            'content'           => $validated['content'] === null || $validated['content'] === "null" ? null : $validated['content'],
            'youtube_url'       => $validated['youtube_url'] === null || $validated['youtube_url'] === "null" ? null : $validated['youtube_url'],
            'min_read'          => $validated['min_read'] ?? $lesson->min_read,
            'point_tuition_fee' => $validated['point_tuition_fee'] ?? $lesson->point_tuition_fee,
            'order'             => $validated['order'] ?? $lesson->order,
            'status'            => $validated['status'] ?? $lesson->status,
        ]);

        $lessonImages = [];
        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/lessons', $image, $fileName);

                $lessonImages[] = $lesson->images()->create([
                    'filename' => $fileName
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'lesson' => new LessonResource($lesson->fresh()),
            'images' => $lessonImages
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson)
    {
        if (!$lesson->course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

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
        //throw $th;
        }
    }

}
