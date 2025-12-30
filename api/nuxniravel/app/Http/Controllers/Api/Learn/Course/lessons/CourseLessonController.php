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
        return $course->user_id === auth()->id();
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
                'isCourseAdmin' => $course->user_id === auth()->id(),
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
                'youtube_url' => 'nullable|url',
                'order' => 'required|integer|min:0',
                'min_read' => 'required|integer|min:0',
                'point_tuition_fee' => 'required|numeric|min:0',
                'status' => 'required|in:0,1', // 0=draft, 1=published
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            ]);

            // Create lesson
            $lesson = $course->courseLessons()->create([
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'content' => $validated['content'],
                'youtube_url' => $validated['youtube_url'],
                'order' => $validated['order'],
                'min_read' => $validated['min_read'],
                'point_tuition_fee' => $validated['point_tuition_fee'],
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
                'youtube_url' => 'nullable|url',
                'min_read' => 'required|integer|min:0',
                'order' => 'required|integer|min:0',
                'point_tuition_fee' => 'required|numeric|min:0',
                'status' => 'required|in:0,1',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            // Update lesson
            $lesson->update([
                'title' => $validated['title'],
                'description' => $validated['description'] === "null" ? null : $validated['description'],
                'content' => $validated['content'] === "null" ? null : $validated['content'],
                'youtube_url' => $validated['youtube_url'] === "null" ? null : $validated['youtube_url'],
                'min_read' => $validated['min_read'],
                'order' => $validated['order'],
                'point_tuition_fee' => $validated['point_tuition_fee'],
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
     * Remove the specified lesson
     */
    public function destroy(Course $course, Lesson $lesson)
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

            // Delete lesson comments and their related data
            if ($lesson->comments->count() > 0) {
                foreach ($lesson->comments as $comment) {
                    // Delete comment images
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

            // Delete lesson topics and their images
            if ($lesson->topics && $lesson->topics->count() > 0) {
                foreach ($lesson->topics as $topic) {
                    if ($topic->images && $topic->images->count() > 0) {
                        foreach ($topic->images as $topic_image) {
                            Storage::disk('public')->delete('images/courses/lessons/topics/' . $topic_image->filename);
                            $topic_image->delete();
                        }
                    }
                    $topic->delete();
                }
            }

            // Delete lesson images
            if ($lesson->images && $lesson->images->count() > 0) {
                foreach ($lesson->images as $lesson_image) {
                    Storage::disk('public')->delete('images/courses/lessons/' . $lesson_image->filename);
                    $lesson_image->delete();
                }
            }

            // Delete lesson reactions
            if ($lesson->likes) {
                $lesson->likeLesson()->detach();
            }
            if ($lesson->dislikes) {
                $lesson->dislikeLesson()->detach();
            }

            // Delete the lesson
            $lesson->delete();

            // Decrement course lessons count
            $course->decrement('lessons');

            return response()->json([
                'success' => true,
                'message' => 'ลบบทเรียนสำเร็จ'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error deleting lesson: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบบทเรียน'
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
