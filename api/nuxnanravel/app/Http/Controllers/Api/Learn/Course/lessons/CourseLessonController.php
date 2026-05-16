<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;


use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;


class CourseLessonController extends \App\Http\Controllers\Controller
{
    /**
     * Helper method to upload lesson images
     */
    private function uploadLessonImages(Request $request, Lesson $lesson): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        $images = $request->file('images');
        
        foreach ($images as $image) {
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/courses/lessons', $image, $fileName);

            $lesson->images()->create([
                'filename' => $fileName,
            ]);
        }
    }

    /**
     * Check if user has permission to manage the course
     */
    private function checkCoursePermission(Course $course): bool
    {
        return $course->isAdmin(auth()->user());
    }

    /**
     * Check if user has enough points to access lesson
     */
    private function checkUserPoints(int $requiredPoints): bool
    {
        return auth()->user()->pp >= $requiredPoints;
    }

    /**
     * Display a listing of lessons
     */
    public function index(Course $course)
    {
        try {
            return response()->json([
                'course' => new CourseResource($course),
                'lessons' => LessonResource::collection($course->courseLessons()->orderBy('order')->paginate()),
                'isCourseAdmin' => $course->isAdmin(auth()->user()),
                'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
                'groups' => $course->courseGroups()->get(['id', 'name']),
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching lessons: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดบทเรียน',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified lesson
     */
    public function show(Course $course, Lesson $lesson)
    {
        try {
            $isCourseAdmin = $this->checkCoursePermission($course);
            $user = auth()->user();
            
            // Verify lesson belongs to this course
            if ($lesson->course_id !== $course->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'บทเรียนนี้ไม่ได้อยู่ในรายวิชาที่ระบุ'
                ], 404);
            }

            // Check if user has enough points (only for non-admin users)
            if (!$isCourseAdmin && $lesson->point_tuition_fee > 0) {
                if (!$this->checkUserPoints($lesson->point_tuition_fee)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'คุณมีพอยต์ไม่เพียงพอ',
                        'required_points' => $lesson->point_tuition_fee,
                        'current_points' => $user->pp,
                        'shortage' => $lesson->point_tuition_fee - $user->pp
                    ], 403);
                }

                // Deduct points from user
                $user->decrement('pp', $lesson->point_tuition_fee);
                $course->increment('points', $lesson->point_tuition_fee);
            }

            // Increment view count
            $lesson->increment('view_count');

            // Update recently viewed course
            if ($user && $user->id) {
                \App\Models\RecentlyViewedCourse::updateOrInsert(
                    ['user_id' => $user->id, 'course_id' => $course->id],
                    ['updated_at' => now()]
                );
            }

            return response()->json([
                'success' => true,
                'course' => new CourseResource($course),
                'lesson' => new LessonResource($lesson),
                'isCourseAdmin' => $isCourseAdmin,
                'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
                'authUserPP' => $user->pp,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error showing lesson: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดบทเรียน'
            ], 500);
        }
    }

        /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created lesson
     */
    public function store(Course $course, Request $request)
    {
        try {
            // Check permission
            if (!$this->checkCoursePermission($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์สร้างบทเรียนในรายวิชานี้'
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'youtube_url' => 'nullable|string',
                'order' => 'nullable|integer|min:0',
                'min_read' => 'nullable|integer|min:0',
                'point_tuition_fee' => 'nullable|numeric|min:0',
                'status' => 'required', // Relaxed as it's an enum/in
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            ]);

            // Create lesson
            $lesson = $course->courseLessons()->create([
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'description' => ($validated['description'] === "null" || $validated['description'] === "") ? null : $validated['description'],
                'content' => ($validated['content'] === "null" || $validated['content'] === "") ? null : $validated['content'],
                'youtube_url' => ($validated['youtube_url'] === "null" || $validated['youtube_url'] === "") ? null : $validated['youtube_url'],
                'order' => $validated['order'] ?? 0,
                'min_read' => $validated['min_read'] ?? 1,
                'point_tuition_fee' => $validated['point_tuition_fee'] ?? 0,
                'status' => $validated['status'],
            ]);

            // Upload images using helper method
            $this->uploadLessonImages($request, $lesson);

            // Increment course lessons count
            $course->increment('lessons');

            return response()->json([
                'success' => true,
                'message' => 'สร้างบทเรียนสำเร็จ',
                'newLesson' => new LessonResource($lesson)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating lesson: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างบทเรียน'
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified lesson
     */
    public function edit(Course $course, Lesson $lesson)
    {
        try {
            // Check permission
            if (!$this->checkCoursePermission($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขบทเรียนนี้'
                ], 403);
            }

            // Verify lesson belongs to this course
            if ($lesson->course_id !== $course->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'บทเรียนนี้ไม่ได้อยู่ในรายวิชาที่ระบุ'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'isCourseAdmin' => true,
                'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
                'course' => new CourseResource($course),
                'lesson' => new LessonResource($lesson),
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error editing lesson: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลบทเรียน'
            ], 500);
        }
    }

    /**
     * Update the specified lesson
     */
    public function update(Course $course, Lesson $lesson, Request $request)
    {
        try {
            // Check permission
            if (!$this->checkCoursePermission($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขบทเรียนนี้'
                ], 403);
            }

            // Verify lesson belongs to this course
            if ($lesson->course_id !== $course->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'บทเรียนนี้ไม่ได้อยู่ในรายวิชาที่ระบุ'
               ], 404);
            }

            // Validate request
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'youtube_url' => 'nullable|string',
                'min_read' => 'nullable|integer|min:0',
                'order' => 'nullable|integer|min:0',
                'point_tuition_fee' => 'nullable|numeric|min:0',
                'status' => 'required',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            ]);

            // Update lesson
            $lesson->update([
                'title' => $validated['title'],
                'description' => ($validated['description'] === "null" || $validated['description'] === "") ? null : $validated['description'],
                'content' => ($validated['content'] === "null" || $validated['content'] === "") ? null : $validated['content'],
                'youtube_url' => ($validated['youtube_url'] === "null" || $validated['youtube_url'] === "") ? null : $validated['youtube_url'],
                'min_read' => $validated['min_read'] ?? $lesson->min_read,
                'order' => $validated['order'] ?? $lesson->order,
                'point_tuition_fee' => $validated['point_tuition_fee'] ?? $lesson->point_tuition_fee,
                'status' => $validated['status']
            ]);

            // Upload new images if provided
            $this->uploadLessonImages($request, $lesson);

            return response()->json([
                'success' => true,
                'message' => 'อัปเดตบทเรียนสำเร็จ',
                'lesson' => new LessonResource($lesson),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating lesson: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตบทเรียน'
            ], 500);
        }
    }


    /**
     * Helper method to delete assignment and its related data
     */
    private function deleteAssignment($assignment, CourseMediaService $mediaService): void
    {
        // Delete assignment images if unused
        if ($assignment->images && $assignment->images->count() > 0) {
            foreach ($assignment->images as $image) {
                $mediaService->deleteUnused(
                    'assignment_image',
                    \App\Models\AssignmentImage::class,
                    'image_url',
                    $image->image_url,
                    $image->id
                );
                $image->delete();
            }
        }

        // Delete assignment answers and their images
        if ($assignment->answers && $assignment->answers->count() > 0) {
            foreach ($assignment->answers as $answer) {
                if ($answer->images && $answer->images->count() > 0) {
                    foreach ($answer->images as $answerImage) {
                        $mediaService->deleteUnused(
                            'assignment_answer_image',
                            \App\Models\AssignmentAnswerImage::class,
                            'filename',
                            $answerImage->filename,
                            $answerImage->id
                        );
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
    private function deleteQuestion($question, CourseMediaService $mediaService): void
    {
        // Delete question images if unused
        if ($question->images && $question->images->count() > 0) {
            foreach ($question->images as $image) {
                $filename = $image->filename ?? $image->image_url;
                $field = $image->filename ? 'filename' : 'image_url';
                $mediaService->deleteUnused(
                    'question_image',
                    \App\Models\QuestionImage::class,
                    $field,
                    $filename,
                    $image->id
                );
                $image->delete();
            }
        }

        // Delete question options and their images
        if ($question->options && $question->options->count() > 0) {
            foreach ($question->options as $option) {
                if (method_exists($option, 'images') && $option->images && $option->images->count() > 0) {
                    foreach ($option->images as $optImage) {
                        $optFilename = $optImage->filename ?? $optImage->image_url;
                        $optField = $optImage->filename ? 'filename' : 'image_url';
                        $mediaService->deleteUnused(
                            'option_image',
                            \App\Models\QuestionImage::class,
                            $optField,
                            $optFilename,
                            $optImage->id
                        );
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
    private function deleteTopic($topic, CourseMediaService $mediaService): void
    {
        // Delete topic images if unused
        if ($topic->images && $topic->images->count() > 0) {
            foreach ($topic->images as $topic_image) {
                $mediaService->deleteUnused(
                    'topic_image',
                    \App\Models\TopicImage::class,
                    'filename',
                    $topic_image->filename,
                    $topic_image->id
                );
                $topic_image->delete();
            }
        }

        // Delete topic assignments
        if ($topic->assignments && $topic->assignments->count() > 0) {
            foreach ($topic->assignments as $assignment) {
                $this->deleteAssignment($assignment, $mediaService);
            }
        }

        // Delete topic questions
        if ($topic->questions && $topic->questions->count() > 0) {
            foreach ($topic->questions as $question) {
                $this->deleteQuestion($question, $mediaService);
            }
        }

        $topic->delete();
    }

    /**
     * Remove the specified lesson
     */
    public function destroy(Course $course, Lesson $lesson, CourseMediaService $mediaService)
    {
        try {
            // Check permission
            if (!$this->checkCoursePermission($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบบทเรียนนี้'
                ], 403);
            }

            // Verify lesson belongs to this course
            if ($lesson->course_id !== $course->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'บทเรียนนี้ไม่ได้อยู่ในรายวิชาที่ระบุ'
                ], 404);
            }

            \DB::beginTransaction();

            // 1. Delete lesson comments and their related data
            if ($lesson->comments && $lesson->comments->count() > 0) {
                foreach ($lesson->comments as $comment) {
                    // Delete comment images
                    if ($comment->lessonCommentImages && $comment->lessonCommentImages->count() > 0) {
                        foreach ($comment->lessonCommentImages as $comment_image) {
                            $mediaService->deleteUnused(
                                'lesson_comment_image',
                                \App\Models\LessonCommentImage::class,
                                'filename',
                                $comment_image->filename,
                                $comment_image->id
                            );
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
                    $this->deleteTopic($topic, $mediaService);
                }
            }

            // 3. Delete lesson assignments (direct assignments on lesson)
            if ($lesson->assignments && $lesson->assignments->count() > 0) {
                foreach ($lesson->assignments as $assignment) {
                    $this->deleteAssignment($assignment, $mediaService);
                }
            }

            // 4. Delete lesson questions (direct questions on lesson)
            if ($lesson->questions && $lesson->questions->count() > 0) {
                foreach ($lesson->questions as $question) {
                    $this->deleteQuestion($question, $mediaService);
                }
            }

            // 5. Delete lesson images if unused
            if ($lesson->images && $lesson->images->count() > 0) {
                foreach ($lesson->images as $lesson_image) {
                    $mediaService->deleteUnused(
                        'lesson_image',
                        \App\Models\LessonImage::class,
                        'filename',
                        $lesson_image->filename,
                        $lesson_image->id
                    );
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

    public function toggleBookmark(Course $course, Lesson $lesson)
    {
        $user = auth()->user();
        $bookmark = $lesson->bookmarks()->where('user_id', $user->id)->first();

        if ($bookmark) {
            $bookmark->delete();
            return response()->json([
                'success' => true,
                'bookmarked' => false,
                'message' => 'ยกเลิกบุ๊กมาร์กบทเรียนแล้ว'
            ]);
        } else {
            $lesson->bookmarks()->create(['user_id' => $user->id]);
            return response()->json([
                'success' => true,
                'bookmarked' => true,
                'message' => 'บุ๊กมาร์กบทเรียนแล้ว'
            ]);
        }
    }

    public function shareLesson(Course $course, Lesson $lesson)
    {
        $lesson->increment('share_count');
        return response()->json([
            'success' => true,
            'message' => 'แชร์บทเรียนสำเร็จ'
        ]);
    }
}
