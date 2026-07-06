<?php

namespace App\Http\Controllers\Api\Learn\Course\assignments;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Models\Assignment;
use App\Models\AssignmentImage;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Services\ContentVisibilityService;
use App\Services\CourseMediaService;
use App\Services\CourseScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CourseAssignmentController extends Controller
{
    protected ContentVisibilityService $visibility;

    public function __construct(ContentVisibilityService $visibility)
    {
        $this->visibility = $visibility;
    }

    public function index(Course $course)
    {
        $user = auth()->user();
        $isCourseAdmin = $course->isAdmin($user);

        // Get lesson IDs for parent visibility filtering
        $lessonIds = $course->courseLessons()->pluck('id');
        $topicIds = Topic::whereIn('lesson_id', $lessonIds)->pluck('id');

        $assignmentsQuery = Assignment::where(function ($q) use ($course, $lessonIds, $topicIds) {
            $q->where(function ($q) use ($course) {
                $q->where('assignmentable_type', Course::class)
                    ->where('assignmentable_id', $course->id);
            })->orWhere(function ($q) use ($lessonIds) {
                $q->where('assignmentable_type', Lesson::class)
                    ->whereIn('assignmentable_id', $lessonIds);
            })->orWhere(function ($q) use ($topicIds) {
                $q->where('assignmentable_type', Topic::class)
                    ->whereIn('assignmentable_id', $topicIds);
            });
        });

        // Filter for students
        if (! $isCourseAdmin) {
            $assignmentsQuery->where('status', 1); // Published only

            // Filter by parent lesson visibility
            $publishedLessonIds = $course->courseLessons()
                ->where('publication_status', Lesson::STATUS_PUBLISHED)
                ->pluck('id');
            $publishedTopicIds = Topic::whereIn('lesson_id', $publishedLessonIds)->pluck('id');

            $assignmentsQuery->where(function ($q) use ($publishedLessonIds, $publishedTopicIds) {
                $q->where('assignmentable_type', Course::class)
                    ->orWhere(function ($q) use ($publishedLessonIds) {
                        $q->where('assignmentable_type', Lesson::class)
                            ->whereIn('assignmentable_id', $publishedLessonIds);
                    })->orWhere(function ($q) use ($publishedTopicIds) {
                        $q->where('assignmentable_type', Topic::class)
                            ->whereIn('assignmentable_id', $publishedTopicIds);
                    });
            });
        }

        return response()->json([
            'course' => new CourseResource($course),
            'assignments' => AssignmentResource::collection($assignmentsQuery->withCount('answers')->latest()->paginate(15)),
            'groups' => $course->courseGroups()->get(['id', 'name']),
            'isCourseAdmin' => $isCourseAdmin,
            'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function show(Course $course, Assignment $assignment)
    {
        $user = auth()->user();
        $isCourseAdmin = $course->isAdmin($user);

        // Ownership check: must belong to the course or its lessons/topics
        $lesson = $assignment->getLesson();
        if ($assignment->assignmentable_type === Course::class) {
            abort_if($assignment->assignmentable_id !== $course->id, 404);
        } elseif ($lesson) {
            abort_if($lesson->course_id !== $course->id, 404);
        } else {
            // Not attached to course or lesson/topic of this course
            abort(404);
        }

        if (! $isCourseAdmin) {
            $this->visibility->assertVisibleOrFail($assignment, $user, 404);
        }

        return response()->json([
            'assignment' => new AssignmentResource($assignment),
            'course' => new CourseResource($course),
            'groups' => $course->courseGroups()->get(['id', 'name']),
            'isCourseAdmin' => $isCourseAdmin,
        ]);
    }

    public function store(Course $course, Request $request)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'points' => 'required',
            'passing_score' => 'nullable|integer|min:0',
            'due_date' => 'nullable',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'target_groups' => 'nullable|array',
            'target_groups.*' => 'integer', // Ensure each target group ID is an integer
            'increase_points' => 'nullable',
            'decrease_points' => 'nullable',
            'status' => 'required|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $assignment = $course->assignments()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'points' => $validated['points'],
            'passing_score' => $validated['passing_score'] ?? floor($validated['points'] / 2),
            'graded_score' => $validated['points'],
            'due_date' => ! empty($validated['due_date']) ? Carbon::parse($validated['due_date'])->setTimezone('Asia/Bangkok') : null,
            'start_date' => ! empty($validated['start_date']) ? Carbon::parse($validated['start_date'])->setTimezone('Asia/Bangkok') : null,
            'end_date' => ! empty($validated['end_date']) ? Carbon::parse($validated['end_date'])->setTimezone('Asia/Bangkok') : null,
            'target_groups' => $validated['target_groups'] ?? null,
            'increase_points' => $validated['increase_points'] ?? null,
            'decrease_points' => $validated['decrease_points'] ?? null,
            'status' => $validated['status'],
        ]);

        app(CourseScoreService::class)->syncCourseTotalScore($course);
        $course->increment('assignments');

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/assignments', $image, $fileName);
                $fileNames[] = $fileName;

                $assignment->images()->create([
                    'image_url' => $fileName,
                ]);
            }
        }

        return response()->json([
            'assignment' => new AssignmentResource($assignment),
        ], 200);
    }

    public function update(Course $course, Assignment $assignment, Request $request)
    {
        // Ownership check: assignment must belong to this course (via Course/Lesson/Topic)
        $lesson = $assignment->getLesson();
        if ($assignment->assignmentable_type === Course::class) {
            abort_if($assignment->assignmentable_id !== $course->id, 404);
        } elseif ($lesson) {
            abort_if($lesson->course_id !== $course->id, 404);
        } else {
            abort(404, 'Assignment does not belong to this course');
        }

        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'points' => 'required',
            'passing_score' => 'nullable|integer|min:0',
            'due_date' => 'nullable',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'target_groups' => 'nullable|array',
            'target_groups.*' => 'integer', // Ensure each target group ID is an integer
            'increase_points' => 'nullable',
            'decrease_points' => 'nullable',
            'status' => 'required|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $assignment->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'points' => $validated['points'],
            'passing_score' => $validated['passing_score'] ?? 0,
            'graded_score' => $validated['points'],
            'due_date' => ! empty($validated['due_date']) ? Carbon::parse($validated['due_date'])->setTimezone('Asia/Bangkok') : null,
            'start_date' => ! empty($validated['start_date']) ? Carbon::parse($validated['start_date'])->setTimezone('Asia/Bangkok') : null,
            'end_date' => ! empty($validated['end_date']) ? Carbon::parse($validated['end_date'])->setTimezone('Asia/Bangkok') : null,
            // 'target_groups'      => json_encode($validated['target_groups'])->toArray(),
            // 'target_groups'         => json_encode($validated['target_groups']),
            'target_groups' => $validated['target_groups'] ?? null,
            'increase_points' => $validated['increase_points'] ?? null,
            'decrease_points' => $validated['decrease_points'] ?? null,
            'status' => $validated['status'],
        ]);

        app(CourseScoreService::class)->syncCourseTotalScore($course);

        if ($request->hasFile('images')) {
            $images = $request->file('images');

            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/assignments', $image, $fileName);

                $assignment->images()->create([
                    // 'image_url' => $image_url
                    'image_url' => $fileName,
                ]);
            }
        }

        return response()->json([
            'assignment' => new AssignmentResource($assignment),
        ], 200);
    }

    public function destroy(Course $course, Assignment $assignment, CourseMediaService $mediaService)
    {
        try {
            // Ownership check: assignment must belong to this course (via Course/Lesson/Topic)
            $lesson = $assignment->getLesson();
            if ($assignment->assignmentable_type === Course::class) {
                abort_if($assignment->assignmentable_id !== $course->id, 404);
            } elseif ($lesson) {
                abort_if($lesson->course_id !== $course->id, 404);
            } else {
                abort(404, 'Assignment does not belong to this course');
            }

            if (! $course->isAdmin(auth()->user())) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            DB::transaction(function () use ($assignment, $course, $mediaService) {
                // Delete answer images
                foreach ($assignment->answers as $answer) {
                    foreach ($answer->images as $image) {
                        Storage::disk('public')->delete('images/courses/assignments/answers/'.$image->filename);
                    }
                    $answer->images()->delete();
                }

                // Delete assignment images via media service
                foreach ($assignment->images as $image) {
                    $mediaService->deleteIfUnused(
                        'images/courses/assignments/'.$image->image_url,
                        AssignmentImage::class,
                        'image_url',
                        $image->image_url,
                        $image->id
                    );
                }

                // Only sync total_score for Course-level assignments
                // Lesson/Topic assignments have their own score tracking
                if ($assignment->assignmentable_type === Course::class) {
                    app(CourseScoreService::class)->syncCourseTotalScore($course);
                }

                $assignment->answers()->delete();
                $assignment->images()->delete();
                $assignment->delete();
            });

            return response()->json(['success' => true], 200);
        } catch (HttpException $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage() ?: 'ไม่พบข้อมูล'], $e->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Assignment destroy failed: '.$e->getMessage(), [
                'course_id' => $course->id,
                'assignment_id' => $assignment->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'msg' => 'ลบไม่สำเร็จ: '.$e->getMessage()], 500);
        }
    }
}
