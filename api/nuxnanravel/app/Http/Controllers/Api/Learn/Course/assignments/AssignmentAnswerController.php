<?php

namespace App\Http\Controllers\Api\Learn\Course\assignments;

use App\Enums\UsageEventType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\assignments\AssignmentAnswerResource;
use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\AssignmentAnswerAttachment;
use App\Models\Course;
use App\Models\CourseMember;
use App\Services\ContentVisibilityService;
use App\Services\CourseScoreService;
use App\Services\UsageEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssignmentAnswerController extends Controller
{
    protected ContentVisibilityService $visibility;

    public function __construct(ContentVisibilityService $visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * Resolve the course this assignment belongs to.
     *
     * Always derived from the assignment itself, never from a request parameter:
     * a client-supplied course_id would let a user point isAdmin() at a course they
     * happen to own and read or download another student's work.
     */
    private function resolveCourse(Assignment $assignment): ?Course
    {
        if ($assignment->assignmentable_type === Course::class) {
            return Course::find($assignment->assignmentable_id);
        }

        return $assignment->getLesson()?->course;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Assignment $assignment, Request $request)
    {
        $user = auth()->user();
        $course = $this->resolveCourse($assignment);

        $isCourseAdmin = $course ? $course->isAdmin($user) : false;

        // Visibility guard for students
        if (! $isCourseAdmin) {
            $this->visibility->assertVisibleOrFail($assignment, $user, 404);
        }

        $query = $assignment->answers()->with('user', 'images', 'attachments', 'assignment.assignmentable')->latest();

        // Students may only ever see their own submission, never a classmate's.
        // AssignmentResource already scopes answers this way; this endpoint did not.
        if (! $isCourseAdmin) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('group_id') && $request->group_id != 'all') {
            $groupId = $request->group_id;
            $query->whereHas('user.courseMembers', function ($q) use ($groupId) {
                $q->where('group_id', $groupId);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return AssignmentAnswerResource::collection($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Assignment $assignment, Request $request)
    {
        $user = auth()->user();
        $course = $this->resolveCourse($assignment);

        $isCourseAdmin = $course ? $course->isAdmin($user) : false;

        // Visibility guard for students
        if (! $isCourseAdmin) {
            $this->visibility->assertVisibleOrFail($assignment, $user, 403);
        }

        // Lifecycle guard: block regular assignment submissions after the course ends.
        if ($course) {
            $gate = Gate::inspect('submitAssignment', $course);
            if ($gate->denied()) {
                return response()->json([
                    'success' => false,
                    'code' => $gate->code() ?: 'WORK_TYPE_LOCKED_AFTER_END',
                    'message' => $gate->message() ?: 'รายวิชาสิ้นสุดแล้ว ไม่สามารถส่งงานได้',
                ], 422);
            }
        }

        // Completion requirement guard
        $lesson = $assignment->getLesson();
        if ($lesson && $lesson->require_completion_before_exercises) {
            if (! $lesson->canUserDoExercises($user, $isCourseAdmin)) {
                return response()->json([
                    'success' => false,
                    'code' => 'LESSON_COMPLETION_REQUIRED',
                    'message' => 'กรุณาอ่านบทเรียนให้จบก่อนส่งงาน',
                ], 422);
            }
        }

        try {
            $validated = $request->validate([
                'content' => 'nullable|string',
                'course_id' => 'nullable|integer',
                'images' => 'nullable|array|max:10',
                'images.*' => 'file|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
                'attachments' => 'nullable|array|max:5',
                'attachments.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,csv,zip|max:20480',
                'deleted_images' => 'nullable|array',
                'deleted_images.*' => 'integer',
                'deleted_attachments' => 'nullable|array',
                'deleted_attachments.*' => 'integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors(),
            ], 422);
        }

        $answer = $assignment->answers()->where('user_id', auth()->id())->first();
        if ($answer) {
            $oldPoints = $answer->points ?? 0;
            $courseMember = CourseMember::where('course_id', $request->course_id)->where('user_id', $answer->user_id)->first();
            if ($courseMember) {
                $courseMember->achieved_score -= $oldPoints;
                $courseMember->save();
            }

            $answer->update([
                'content' => $request->content,
                'points' => null,
            ]);

            // Handle deleted images
            if ($request->filled('deleted_images')) {
                $deletedIds = $request->input('deleted_images');
                if (is_array($deletedIds)) {
                    $imagesToDelete = $answer->images()->whereIn('id', $deletedIds)->get();
                    foreach ($imagesToDelete as $img) {
                        try {
                            Storage::disk('public')->delete('images/courses/assignments/answers/'.$img->filename);
                        } catch (\Exception $e) {
                            // Log error but continue
                        }
                        $img->delete();
                    }
                }
            }

            if ($request->filled('deleted_attachments')) {
                $deletedIds = $request->input('deleted_attachments');
                if (is_array($deletedIds)) {
                    $attachmentsToDelete = $answer->attachments()->whereIn('id', $deletedIds)->get();
                    foreach ($attachmentsToDelete as $att) {
                        try {
                            Storage::disk('local')->delete('course-materials/assignment-answers/'.$att->filename);
                        } catch (\Exception $e) {
                            // Log error but continue
                        }
                        $att->delete();
                    }
                }
            }
        } else {
            $answer = $assignment->answers()->create([
                'user_id' => auth()->id(),
                'content' => $request->content,
                'points' => null,
            ]);

            // Fire gamification event
            UsageEventService::fire(auth()->user(), UsageEventType::ASSIGNMENT_SUBMIT->value, 'assignment', $assignment->id);
        }

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                // Use getClientOriginalExtension first, fallback to guessExtension for reliability
                $extension = $image->getClientOriginalExtension();
                if (empty($extension)) {
                    $extension = $image->guessExtension() ?? 'jpg';
                }
                $fileName = uniqid().'.'.$extension;
                $image_url = Storage::disk('public')->putFileAs('images/courses/assignments/answers/', $image, $fileName);
                $answer->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        if ($request->hasFile('attachments')) {
            $courseIdForFile = $course?->id;
            $maxOrder = $answer->attachments()->max('order') ?? 0;

            foreach ($request->file('attachments') as $file) {
                $maxOrder++;
                $extension = $file->getClientOriginalExtension();
                $filename = (string) Str::uuid().'.'.$extension;

                Storage::disk('local')->putFileAs('course-materials/assignment-answers', $file, $filename);

                $answer->attachments()->create([
                    'assignment_id' => $assignment->id,
                    'course_id' => $courseIdForFile,
                    'uploaded_by' => auth()->id(),
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'extension' => $extension,
                    'size' => $file->getSize(),
                    'order' => $maxOrder,
                ]);
            }
        }

        $answer->load('user', 'images', 'attachments', 'assignment.assignmentable');

        return response()->json([
            'success' => true,
            'newAnswer' => new AssignmentAnswerResource($answer),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assignment $assignment, AssignmentAnswer $answer, Request $request)
    {
        $user = auth()->user();
        $course = $this->resolveCourse($assignment);

        // Ownership check: if student, can only delete their own answer
        if (! ($course && $course->isAdmin($user))) {
            if ($answer->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            // Draft guard
            $this->visibility->assertVisibleOrFail($assignment, $user, 403);
        }

        try {
            DB::beginTransaction();

            $courseMember = CourseMember::where('course_id', $request->course_id)
                ->where('user_id', $answer->user_id)
                ->first();

            if ($courseMember) {
                $courseMember->achieved_score -= $answer->points;
                $courseMember->save();
            }

            foreach ($answer->images as $image) {
                Storage::disk('public')->delete('images/courses/assignments/answers/'.$image->filename);
            }
            foreach ($answer->attachments as $attachment) {
                Storage::disk('local')->delete('course-materials/assignment-answers/'.$attachment->filename);
            }

            $answer->images()->delete();
            $answer->attachments()->delete();
            $answer->delete();

            DB::commit();

            return response()->json([
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting assignment answer: '.$e->getMessage());

            return response()->json(['error' => 'Failed to delete assignment answer'], 500);
        }
    }

    public function setAnswerPoints(Assignment $assignment, AssignmentAnswer $answer, Request $request)
    {
        // Resolve Course ID
        $courseId = $request->course_id;
        if (! $courseId) {
            if ($assignment->assignmentable_type === 'App\Models\Lesson') {
                $courseId = $assignment->assignmentable->course_id;
            } elseif ($assignment->assignmentable_type === 'App\Models\Course') {
                $courseId = $assignment->assignmentable->id;
            }
        }

        $courseMember = CourseMember::where('course_id', $courseId)->where('user_id', $answer->user_id)->first();

        $answer->update([
            'points' => $request->points,
            'feedback' => $request->feedback,
            'status' => 'graded', // Set status to graded when points are assigned
        ]);

        // Only update score if member found (e.g. not admin grading themselves or test data)
        if ($courseMember) {
            app(CourseScoreService::class)->recompute($courseMember);
        }

        // Fire gamification event
        if ($answer->points > 0) {
            UsageEventService::fire($answer->user, UsageEventType::ASSIGNMENT_GRADED->value, 'assignment', $assignment->id, ['points' => $answer->points]);
        }

        return response()->json([
            'success' => true,
        ], 200);
    }

    public function downloadAttachment(Assignment $assignment, AssignmentAnswer $answer, $attachment)
    {
        $attachment = $attachment instanceof AssignmentAnswerAttachment
            ? $attachment
            : AssignmentAnswerAttachment::findOrFail($attachment);

        $course = $this->resolveCourse($assignment);

        if ($attachment->assignment_answer_id !== $answer->id || $answer->assignment_id !== $assignment->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไฟล์แนบนี้ไม่ได้อยู่ในคำตอบที่ระบุ',
            ], 404);
        }

        $user = auth()->user();
        if (! (($course && $course->isAdmin($user)) || $answer->user_id === $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ดาวน์โหลดไฟล์นี้',
            ], 403);
        }

        $path = 'course-materials/assignment-answers/'.$attachment->filename;

        if (! Storage::disk('local')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์ หรือคุณไม่มีสิทธิ์ดาวน์โหลดไฟล์นี้',
            ], 403);
        }

        $attachment->increment('download_count');

        return Storage::disk('local')->download($path, $attachment->original_name);
    }
}
