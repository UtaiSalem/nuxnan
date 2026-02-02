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
     * Helper method to delete assignment and its related data
     */
    private function deleteAssignment($assignment): void
    {
        // Delete assignment images
        if ($assignment->images && $assignment->images->count() > 0) {
            foreach ($assignment->images as $image) {
                Storage::disk('public')->delete('images/lessons/assignments/' . $image->image_url);
                $image->delete();
            }
        }

        // Delete assignment answers and their images
        if ($assignment->answers && $assignment->answers->count() > 0) {
            foreach ($assignment->answers as $answer) {
                if ($answer->images && $answer->images->count() > 0) {
                    foreach ($answer->images as $answerImage) {
                        Storage::disk('public')->delete($answerImage->image_url);
                        $answerImage->delete();
                    }
                }
                $answer->delete();
            }
        }

        $assignment->delete();
    }

    /**
     * Helper method to delete question and its related data
     */
    private function deleteQuestion($question): void
    {
        // Delete question images
        if ($question->images && $question->images->count() > 0) {
            foreach ($question->images as $image) {
                Storage::disk('public')->delete($image->image_url ?? $image->filename ?? '');
                $image->delete();
            }
        }

        // Delete question options and their images
        if ($question->options && $question->options->count() > 0) {
            foreach ($question->options as $option) {
                if (method_exists($option, 'images') && $option->images && $option->images->count() > 0) {
                    foreach ($option->images as $optImage) {
                        Storage::disk('public')->delete($optImage->image_url ?? $optImage->filename ?? '');
                        $optImage->delete();
                    }
                }
                $option->delete();
            }
        }

        // Delete user answers
        if ($question->userAnswers && $question->userAnswers->count() > 0) {
            $question->userAnswers()->delete();
        }

        $question->delete();
    }

    /**
     * Helper method to delete topic and its related data
     */
    private function deleteTopic($topic): void
    {
        // Delete topic images
        if ($topic->images && $topic->images->count() > 0) {
            foreach ($topic->images as $topic_image) {
                Storage::disk('public')->delete('images/courses/lessons/topics/' . $topic_image->filename);
                $topic_image->delete();
            }
        }

        // Delete topic assignments
        if ($topic->assignments && $topic->assignments->count() > 0) {
            foreach ($topic->assignments as $assignment) {
                $this->deleteAssignment($assignment);
            }
        }

        // Delete topic questions
        if ($topic->questions && $topic->questions->count() > 0) {
            foreach ($topic->questions as $question) {
                $this->deleteQuestion($question);
            }
        }

        $topic->delete();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson)
    {
        if (!$lesson->course->isAdmin(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ลบบทเรียนนี้'
            ], 403);
        }

        try {
            $course = $lesson->course;
            
            \DB::beginTransaction();

            // 1. Delete lesson comments and their related data
            if ($lesson->comments && $lesson->comments->count() > 0) {
                foreach ($lesson->comments as $comment) {
                    // Delete comment images (fixed: removed () from lessonCommentImages)
                    if ($comment->lessonCommentImages && $comment->lessonCommentImages->count() > 0) {
                        foreach ($comment->lessonCommentImages as $comment_image) {
                            Storage::disk('public')->delete('images/courses/lessons/comments/' . $comment_image->filename);
                            $comment_image->delete();
                        }
                    }
                    // Delete comment reactions
                    $comment->likeComment()->detach();
                    $comment->dislikeComment()->detach();
                    $comment->delete();
                }
            }

            // 2. Delete lesson topics and their related data (including assignments/questions)
            if ($lesson->topics && $lesson->topics->count() > 0) {
                foreach ($lesson->topics as $topic) {
                    $this->deleteTopic($topic);
                }
            }

            // 3. Delete lesson assignments (direct assignments on lesson)
            if ($lesson->assignments && $lesson->assignments->count() > 0) {
                foreach ($lesson->assignments as $assignment) {
                    $this->deleteAssignment($assignment);
                }
            }

            // 4. Delete lesson questions (direct questions on lesson)
            if ($lesson->questions && $lesson->questions->count() > 0) {
                foreach ($lesson->questions as $question) {
                    $this->deleteQuestion($question);
                }
            }

            // 5. Delete lesson images
            if ($lesson->images && $lesson->images->count() > 0) {
                foreach ($lesson->images as $lesson_image) {
                    Storage::disk('public')->delete('images/courses/lessons/' . $lesson_image->filename);
                    $lesson_image->delete();
                }
            }

            // 6. Delete lesson progress records
            if ($lesson->progress && $lesson->progress->count() > 0) {
                $lesson->progress()->delete();
            }

            // 7. Delete lesson bookmarks
            if ($lesson->bookmarks && $lesson->bookmarks->count() > 0) {
                $lesson->bookmarks()->delete();
            }

            // 8. Delete lesson reactions
            $lesson->likeLesson()->detach();
            $lesson->dislikeLesson()->detach();

            // 9. Delete the lesson
            $lesson->delete();

            // 10. Decrement course lessons count
            $course->decrement('lessons');

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ลบบทเรียนสำเร็จ'
            ], 200);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error deleting lesson: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบบทเรียน',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

}
