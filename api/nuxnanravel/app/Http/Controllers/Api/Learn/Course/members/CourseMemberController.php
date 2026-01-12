<?php

namespace App\Http\Controllers\Api\Learn\Course\members;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseMember;
use Illuminate\Http\Request;
use App\Models\CourseQuizResult;
use App\Models\CourseGroupMember;
use App\Models\UserAnswerQuestion;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\Learn\Course\quizzes\CourseQuizResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\members\CourseMemberResource;
use App\Http\Resources\Learn\Course\assignments\AssignmentAnswerResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResourceV2;
use App\Http\Resources\Learn\Course\members\CourseMemberResourceV2;

class CourseMemberController extends Controller
{


    public function index(Course $course, Request $request)
    {
        $query = $course->courseMembers()->with('user', 'group');

        // Apply filters for V2
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }

        $members = $query->orderBy('order_number')->paginate($request->get('per_page', 20));

        // Check if this is API V2 request
        if ($request->header('X-API-Version') === 'v2' || $request->get('api_version') === 'v2') {
            return response()->json([
                'success' => true,
                'members' => CourseMemberResource::collection($members),
                'pagination' => [
                    'current_page' => $members->currentPage(),
                    'last_page' => $members->lastPage(),
                    'per_page' => $members->perPage(),
                    'total' => $members->total(),
                ],
            ]);
        }

        return response()->json([
            'course'                => new CourseResource($course),
            'members'               => CourseMemberResource::collection($members),
            'groups'                => CourseGroupResource::collection($course->courseGroups),
            'isCourseAdmin'         => $course->user_id === auth()->id(),
            'courseMemberOfAuth'    => $course->courseMembers()->where('user_id', auth()->id())->first(),
            'filters' => $request->only(['status', 'role', 'group_id', 'search']),
        ]);
    }

    public function show(Course $course, CourseMember $member)
    {
        $member->load(['user', 'group']);
        $userId = $member->user_id;
        
        // Get all lessons with completion status
        $lessons = $course->courseLessons()->get()->map(function ($lesson) use ($userId) {
            $progress = \App\Models\LessonProgress::where('lesson_id', $lesson->id)
                ->where('user_id', $userId)
                ->first();
            
            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'completed' => $progress && $progress->status === 'completed',
            ];
        });
        
        // Get all assignments (course + lesson) with submission status and scores
        $courseAssignments = $course->courseAssignments;
        $lessonAssignments = $course->courseLessons()->with('assignments')->get()->flatMap->assignments;
        $allAssignments = $courseAssignments->merge($lessonAssignments);
        
        // Filter assignments based on status and group
        $assignments = $allAssignments->filter(function($assignment) use ($member) {
            // Must be published
            if ($assignment->status !== 1) return false;
            
            // Check group restriction
            if (!empty($assignment->target_groups)) {
                // If member has no group, but assignment targets specific groups -> Exclude?
                // Assuming restriction means "Only these groups". So No Group = Excluded.
                if (!$member->group_id) return false;

                if (!in_array($member->group_id, $assignment->target_groups)) {
                    return false;
                }
            }
            return true;
        })->map(function ($assignment) use ($userId) {
            $answer = \App\Models\AssignmentAnswer::where('assignment_id', $assignment->id)
                ->where('user_id', $userId)
                ->first();
            
            return [
                'id' => $assignment->id,
                'title' => $assignment->title ?? $assignment->name ?? 'งานที่ ' . $assignment->id,
                'max_score' => $assignment->points ?? $assignment->max_score ?? 100,
                'score' => $answer ? $answer->points : null,
                'submitted' => $answer !== null,
                'status' => $answer ? $answer->status : 'not_submitted', // submitted, in_review, graded
                'submitted_at' => $answer ? $answer->created_at : null,
                'graded' => $answer && $answer->status === 'graded',
            ];
        })->values();
        
        // Get all quizzes with completion status and scores (eager load questions for max_score calculation)
        $courseQuizzes = $course->courseQuizzes()->with('questions')->get();
        
        $quizzes = $courseQuizzes->filter(function($quiz) {
            return $quiz->is_active;
        })->map(function ($quiz) use ($userId, $course) {
            $result = \App\Models\CourseQuizResult::where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->where('course_id', $course->id)
                ->orderBy('score', 'desc') // Get best score
                ->first();
            
            // Count attempts
            $attemptCount = \App\Models\CourseQuizResult::where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->where('course_id', $course->id)
                ->count();
            
            // Calculate max_score - prioritize total_score from quiz (synced when adding questions)
            // Then try eager loaded questions sum, then question count
            $maxScore = $quiz->total_score;
            if (!$maxScore || $maxScore == 0) {
                $maxScore = $quiz->questions->sum('points');
            }
            if (!$maxScore || $maxScore == 0) {
                $maxScore = $quiz->questions->count() ?: 10;
            }
            
            return [
                'id' => $quiz->id,
                'title' => $quiz->title ?? $quiz->name ?? 'แบบทดสอบที่ ' . $quiz->id,
                'max_score' => $maxScore,
                'score' => $result ? $result->score : null,
                'completed' => $result !== null,
                'attempt_count' => $attemptCount,
                'completed_at' => $result ? $result->created_at : null,
                'passed' => $result !== null 
                    ? ($quiz->passing_score ? $result->score >= $quiz->passing_score : true) 
                    : null,
            ];
        })->values();

        return response()->json([
            'isCourseAdmin' => $course->user_id === auth()->id(),
            'course' => new CourseResource($course),
            'member' => new CourseMemberResource($member),
            'groups' => CourseGroupResource::collection($course->courseGroups),
            'lessons' => $lessons,
            'assignments' => $assignments,
            'quizzes' => $quizzes,
        ]);
    }

    public function storemember(Course $course, Request $request)
    {
        if (auth()->user()->pp < $course->tuition_fees) {
            return response()->json([
                'success' => false,
                'msg'     => 'แต้มสะสมไม่เพียงพอ กรุณาเติมแต้มสะสมก่อนสมัครสมาชิก'
            ], 201);
        }

        $course_member = CourseMember::where('course_id', $course->id)->where('user_id', auth()->id())->first();

        if (!$course_member) {

            $courseAutoAcceptMembers = $course->courseSettings->auto_accept_members === 0 ? 0 : 1;

            $new_course_member = new CourseMember();
            $new_course_member->user_id     = auth()->id();
            $new_course_member->course_id   = $course->id;
            $new_course_member->group_id    = $request->group_id ?? null;
            $new_course_member->status      = $courseAutoAcceptMembers;
            $new_course_member->course_member_status      = $courseAutoAcceptMembers;
            $new_course_member->save();

            $newCourseGroupMember = new CourseGroupMember();
            $newCourseGroupMember->user_id      = auth()->id();
            $newCourseGroupMember->course_id    = $new_course_member->course_id;
            $newCourseGroupMember->group_id     = $new_course_member->group_id;
            $newCourseGroupMember->status       = $courseAutoAcceptMembers;
            $newCourseGroupMember->save();
            
        }else{

            $course_member->group_id = $request->group_id;
            $course_member->save();
            $new_course_member = $course_member;

            $courseGroupMember = CourseGroupMember::where('course_id', $course->id)->where('user_id', auth()->id())->first();
            $courseGroupMember->group_id = $request->group_id;
            $courseGroupMember->save();
        }

        return response()->json([
            'success' => true,
            'newCourseMember' => CourseMember::find($new_course_member->id),
            // 'newCourseMember' => new CourseMemberResource(CourseMember::find($new_course_member->id)),
            // 'setting' =>  $curent_member_status,
        ], 200);
    }

    public function destroy(Course $course, CourseMember $member)
    {     
        try {

            $member_group = CourseGroupMember::where('group_id', $member->group_id)->where('user_id', $member->user_id)->first();
            if ($member_group) {
                $member_group->delete();
            }

            $member->delete();
            
            return response()->json([
                'success' => true,
            ], 200);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function setActiveTab(Course $course, CourseMember $member, Request $request)
    {
        $member->update([
            'last_accessed_tab' => $request->tab
        ]);
    }
    public function setActiveGroupTab(Course $course, CourseMember $member, Request $request)
    {
        $member->update([
            'last_accessed_group_tab' => $request->group_tab
        ]);

        return response()->json([
            'success' => true,
        ], 200);
    }

    //function update
    public function update(Course $course, CourseMember $member, Request $request)
    {
        $request->validate([
            'member_name'   => 'nullable|string|max:255',
            'order_number'  => 'nullable|integer',
            'member_code'   => 'nullable|string|max:50',
            'group_id'      => 'nullable|exists:course_groups,id',
        ]);

        if ($request->has('group_id') && $request->group_id != $member->group_id) {
            $this->moveMemberToGroup($member, $request->group_id);
        }

        $member->update([
            'member_name'   => $request->member_name,
            'order_number'  => $request->order_number,
            'member_code'   => $request->member_code,
        ]);

        return response()->json([
            'success' => true,
            'notes_comments' => $member->notes_comments
        ], 200);
    }

    // function delete course's member
    public function deleteCourseMember(Course $course, CourseMember $member)
    {
        
        try {
            $member_group = CourseGroupMember::where('course_id', $course->id)
                                                ->where('group_id', $member->group_id ?? null)
                                                ->where('user_id', $member->user_id)
                                                ->first();
            
            // if ($member_group) $member_group->delete();
            
            // if ($member->status == 1) {
            //     if($course->enrolled_students > 0) $course->decrement('enrolled_students');
            // }

            // $user_answer_questions = UserAnswerQuestion::where('course_id', $course->id)->where('user_id', $member->user_id)->get();
            // if ($user_answer_questions->count() > 0) {
            //     foreach ($user_answer_questions as $user_answer_question) {
            //         if($user_answer_question) $user_answer_question->delete();
            //     }
            // }

            // $course_quiz_results = CourseQuizResult::where('course_id', $course->id)->where('user_id', $member->user_id)->get();
            // if ($course_quiz_results->count() > 0) {
            //     foreach ($course_quiz_results as $course_quiz_result) {
            //         if($course_quiz_result) $course_quiz_result->delete();
            //     }
            // }

            $assignments = $course->assignments()->get();
            if ($assignments->count() > 0) {
                foreach ($assignments as $assignment) {
                    $user_assignment_answers =  AssignmentAnswerResource::collection($assignment->answers()->where('user_id', $member->user_id)->get());
                    foreach ($user_assignment_answers as $user_assignment_answer) {
                        if($user_assignment_answer->images->count() > 0) {
                            foreach ($user_assignment_answer->images as $image) {
                                Storage::disk('public')->delete('images/courses/assignments/answers/'. $image->filename);
                            }
                            $user_assignment_answer->images()->delete();
                        }
                        $user_assignment_answer->delete();
                    }            
                }
            }

            $user_answer_questions = UserAnswerQuestion::where('course_id', $course->id)->where('user_id', $member->user_id)->delete();
            $course_quiz_results = CourseQuizResult::where('course_id', $course->id)->where('user_id', $member->user_id)->delete();

            if ($member_group) $member_group->delete();

            $member->delete();
            
            return response()->json([
                'success' => true,
            ], 200);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    //function update member bonus points
    public function updateBonusPoints(Course $course, CourseMember $member, Request $request)
    {
        $member->update(['bonus_points' => $request->bonus_points]);

        $percentage = (($member->achieved_score + $request->bonus_points) / $course->total_score) * 100;
        $grade = CourseMember::calculateGradeFromPercentage($percentage);

        $member->update([
            'grade_progress' => $grade,
        ]);

        $member->refresh();
        
        return response()->json([
            'success' => true,
            'bonus_points' => $member->bonus_points,
            'grade_progress' => $member->grade_progress,
            'grade_name' => $member->getGradeName(),
        ], 200);
    }

    public function updateGradeProgress(Course $course, CourseMember $member, Request $request)
    {
        $member->update([
            'grade_progress' => $request->grade_progress,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function updateNotesComments(Course $course, CourseMember $member, Request $request)
    {
        $member->update([
            'notes_comments' => $request->notes_comments,
        ]);

        return response()->json([
            'success' => true,
            'notes_comments' => $member->notes_comments
        ]);
    }

    public function getMembersRequesters(Course $course)
    {
        $members = $course->courseMembers()->where('course_member_status', 0)->latest()->paginate();

        return response()->json([
            'course' => new CourseResource($course),
            'groups' => CourseGroupResource::collection($course->courseGroups),
            'members' => CourseMemberResource::collection($members),
            'isCourseAdmin' => $course->user_id === auth()->id(),
            'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function memberSettings(Course $course, CourseMember $course_member)
    {
        return response()->json([
            'isCourseAdmin' => $course->user_id === auth()->id(),
            'course'        => new CourseResource($course),
            'lessons'       => LessonResource::collection($course->courseLessons),
            'groups'        => CourseGroupResource::collection($course->courseGroups),
            'member'        => new CourseMemberResource($course_member),
            'assignments'       => AssignmentResource::collection($course->courseAssignments), 
            'quizzes'           => CourseQuizResource::collection($course->courseQuizzes),
            'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
            // 'courseMemberOfAuth' => $course_member,
        ]);
    }

    public function memberProgress(Course $course, CourseMember $course_member)
    {
        $userId = $course_member->user_id;
        
        // Get quiz data for THE SELECTED member (not auth user)
        $courseQuizzes = $course->courseQuizzes()->with('questions')->get();
        
        $quizzes = $courseQuizzes->filter(function($quiz) {
            return $quiz->is_active;
        })->map(function ($quiz) use ($userId, $course) {
            $result = \App\Models\CourseQuizResult::where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->where('course_id', $course->id)
                ->orderBy('score', 'desc')
                ->first();
            
            $attemptCount = \App\Models\CourseQuizResult::where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->where('course_id', $course->id)
                ->count();
            
            // Calculate max_score - prioritize total_score from quiz
            $maxScore = $quiz->total_score;
            if (!$maxScore || $maxScore == 0) {
                $maxScore = $quiz->questions->sum('points');
            }
            if (!$maxScore || $maxScore == 0) {
                $maxScore = $quiz->questions->count() ?: 10;
            }
            
            return [
                'id' => $quiz->id,
                'title' => $quiz->title ?? 'แบบทดสอบที่ ' . $quiz->id,
                'max_score' => $maxScore,
                'score' => $result ? $result->score : null,
                'completed' => $result !== null,
                'attempt_count' => $attemptCount,
                'completed_at' => $result ? $result->created_at : null,
                'passed' => $result !== null 
                    ? ($quiz->passing_score ? $result->score >= $quiz->passing_score : true) 
                    : null,
            ];
        })->values();
        
        return response()->json([
            'isCourseAdmin' => $course->user_id === auth()->id(),
            'course'        => new CourseResource($course),
            'lessons'       => LessonResource::collection($course->courseLessons),
            'groups'        => CourseGroupResource::collection($course->courseGroups),
            'member'        => new CourseMemberResource($course_member),
            'assignments'   => AssignmentResource::collection($course->courseAssignments), 
            'quizzes'       => $quizzes,
            'courseMemberOfAuth' => $course_member,
        ]);
    }

    public function updateOrderNumber(Course $course, CourseMember $member, Request $request)
    {
        $member->update([
            'order_number' => $request->order_number,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function updateMemberCode(Course $course, CourseMember $member, Request $request)
    {
        $request->validate([
            'member_code' => 'required|string|max:50',
        ]);

        $member->update([
            'member_code' => $request->member_code,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * V2: Display member details with enhanced information
     */
    public function showV2(Course $course, CourseMember $member, Request $request)
    {
        $member->load([
            'user',
            'group',
            'quizResults',
            'assignmentAnswers',
            'attendanceRecords'
        ]);

        // Calculate member statistics
        $stats = [
            'completion_rate' => $this->calculateCompletionRate($course, $member),
            'average_score' => $this->calculateAverageScore($course, $member),
            'attendance_rate' => $this->calculateAttendanceRate($course, $member),
            'last_activity' => $this->getLastActivity($member),
            'time_spent' => $this->calculateTimeSpent($course, $member),
        ];

        return response()->json([
            'success' => true,
            'member' => new CourseMemberResource($member),
            'stats' => $stats,
        ]);
    }

    /**
     * V2: Update member status with enhanced validation
     */
    public function updateStatusV2(Course $course, CourseMember $member, Request $request)
    {
        $request->validate([
            'status' => 'required|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        $member->update([
            'status' => $request->status,
            'course_member_status' => $request->status,
            'status_changed_by' => auth()->id(),
            'status_change_reason' => $request->reason,
            'status_changed_at' => now(),
        ]);

        // Observer will handle enrolled_students increment/decrement

        return response()->json([
            'success' => true,
            'member' => new CourseMemberResource($member),
        ]);
    }

    /**
     * V2: Update member progress with detailed tracking
     */
    public function updateProgressV2(Course $course, CourseMember $member, Request $request)
    {
        $request->validate([
            'lessons_completed' => 'nullable|array',
            'assignments_completed' => 'nullable|array',
            'quizzes_completed' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Update progress data
        $progressData = [
            'lessons_completed' => $request->lessons_completed ?? [],
            'assignments_completed' => $request->assignments_completed ?? [],
            'quizzes_completed' => $request->quizzes_completed ?? [],
            'notes_comments' => $request->notes,
            'last_progress_update' => now(),
        ];

        $member->update($progressData);

        // Recalculate completion percentage
        $completionPercentage = $this->calculateCompletionRate($course, $member);
        $member->update(['completion_percentage' => $completionPercentage]);

        return response()->json([
            'success' => true,
            'member' => new CourseMemberResource($member),
            'completion_percentage' => $completionPercentage,
        ]);
    }

    /**
     * V2: Add note to member with timestamp tracking
     */
    public function addNoteV2(Course $course, CourseMember $member, Request $request)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
            'type' => 'required|in:general,warning,achievement,behavior',
        ]);

        $existingNotes = $member->notes_comments ? json_decode($member->notes_comments, true) : [];
        
        $newNote = [
            'id' => uniqid(),
            'content' => $request->note,
            'type' => $request->type,
            'added_by' => auth()->id(),
            'added_at' => now()->toISOString(),
        ];

        $existingNotes[] = $newNote;
        
        $member->update(['notes_comments' => json_encode($existingNotes)]);

        return response()->json([
            'success' => true,
            'note' => $newNote,
        ]);
    }

    /**
     * V2: Bulk update multiple members
     */
    public function bulkUpdateV2(Course $course, Request $request)
    {
        $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:course_members,id',
            'action' => 'required|in:activate,deactivate,move_group,add_points,remove',
            'group_id' => 'nullable|exists:course_groups,id',
            'points' => 'nullable|integer|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $members = CourseMember::whereIn('id', $request->member_ids)
            ->where('course_id', $course->id)
            ->get();

        $results = [];

        foreach ($members as $member) {
            try {
                switch ($request->action) {
                    case 'activate':
                        $this->activateMember($member, $request->reason);
                        break;
                    case 'deactivate':
                        $this->deactivateMember($member, $request->reason);
                        break;
                    case 'move_group':
                        $this->moveMemberToGroup($member, $request->group_id);
                        break;
                    case 'add_points':
                        $this->addPointsToMember($member, $request->points, $request->reason);
                        break;
                    case 'remove':
                        $this->removeMember($member, $request->reason);
                        break;
                }

                $results[] = [
                    'member_id' => $member->id,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'member_id' => $member->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * V2: Get member analytics data
     */
    public function analyticsV2(Course $course, CourseMember $member)
    {
        $analytics = [
            'login_frequency' => $this->getLoginFrequency($member),
            'activity_heatmap' => $this->getActivityHeatmap($member),
            'performance_trends' => $this->getPerformanceTrends($course, $member),
            'engagement_metrics' => $this->getEngagementMetrics($course, $member),
            'completion_timeline' => $this->getCompletionTimeline($course, $member),
        ];

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    // Private helper methods for V2

    private function calculateCompletionRate(Course $course, CourseMember $member): float
    {
        $totalItems = $course->courseLessons()->count() +
                     $course->courseAssignments()->count() +
                     $course->courseQuizzes()->count();
        
        if ($totalItems === 0) return 0;

        $completedItems = 0;
        
        // Count completed lessons
        if ($member->lessons_completed) {
            $completedItems += count(json_decode($member->lessons_completed, true) ?? []);
        }
        
        // Count completed assignments
        if ($member->assignments_completed) {
            $completedItems += count(json_decode($member->assignments_completed, true) ?? []);
        }
        
        // Count completed quizzes
        if ($member->quizzes_completed) {
            $completedItems += count(json_decode($member->quizzes_completed, true) ?? []);
        }

        return round(($completedItems / $totalItems) * 100, 2);
    }

    private function calculateAverageScore(Course $course, CourseMember $member): float
    {
        $quizResults = $member->quizResults;
        if ($quizResults->isEmpty()) return 0;

        $totalScore = $quizResults->sum('score');
        $totalMaxScore = $quizResults->sum('max_score');

        return $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 100, 2) : 0;
    }

    private function calculateAttendanceRate(Course $course, CourseMember $member): float
    {
        // Implement attendance calculation logic
        return 0; // Placeholder
    }

    private function getLastActivity(CourseMember $member): ?string
    {
        // Implement last activity tracking
        return $member->updated_at->toISOString();
    }

    private function calculateTimeSpent(Course $course, CourseMember $member): int
    {
        // Implement time spent calculation
        return 0; // Placeholder in minutes
    }

    private function activateMember(CourseMember $member, ?string $reason)
    {
        $member->update([
            'status' => 1,
            'course_member_status' => 1,
            'status_changed_by' => auth()->id(),
            'status_change_reason' => $reason,
            'status_changed_at' => now(),
        ]);
        // Observer will handle enrolled_students increment
    }

    private function deactivateMember(CourseMember $member, ?string $reason)
    {
        $member->update([
            'status' => 0,
            'course_member_status' => 0,
            'status_changed_by' => auth()->id(),
            'status_change_reason' => $reason,
            'status_changed_at' => now(),
        ]);
        // Observer will handle enrolled_students decrement
    }

    private function moveMemberToGroup(CourseMember $member, int $groupId)
    {
        $member->update(['group_id' => $groupId]);
        
        // Update group member relationship
        CourseGroupMember::updateOrCreate(
            [
                'user_id' => $member->user_id,
                'course_id' => $member->course_id,
            ],
            [
                'group_id' => $groupId,
                'status' => $member->status,
            ]
        );
    }

    private function addPointsToMember(CourseMember $member, int $points, ?string $reason)
    {
        $member->increment('bonus_points', $points);
        
        // Add to points history
        $history = $member->points_history ? json_decode($member->points_history, true) : [];
        $history[] = [
            'points' => $points,
            'reason' => $reason,
            'added_by' => auth()->id(),
            'added_at' => now()->toISOString(),
        ];
        $member->update(['points_history' => json_encode($history)]);
    }

    private function removeMember(CourseMember $member, ?string $reason)
    {
        // Clean up member data
        $this->cleanupMemberData($member);
        
        // Observer will handle enrolled_students decrement
        $member->delete();
    }

    private function cleanupMemberData(CourseMember $member)
    {
        // Clean up assignment answers
        $assignments = $member->course->assignments()->get();
        foreach ($assignments as $assignment) {
            $userAssignmentAnswers = AssignmentAnswer::where('assignment_id', $assignment->id)
                ->where('user_id', $member->user_id)
                ->get();
            
            foreach ($userAssignmentAnswers as $answer) {
                if ($answer->images->count() > 0) {
                    foreach ($answer->images as $image) {
                        Storage::disk('public')->delete('images/courses/assignments/answers/' . $image->filename);
                    }
                    $answer->images()->delete();
                }
                $answer->delete();
            }
        }

        // Clean up quiz results and user answers
        CourseQuizResult::where('course_id', $member->course_id)
            ->where('user_id', $member->user_id)
            ->delete();
            
        UserAnswerQuestion::where('course_id', $member->course_id)
            ->where('user_id', $member->user_id)
            ->delete();

        // Clean up group membership
        CourseGroupMember::where('course_id', $member->course_id)
            ->where('user_id', $member->user_id)
            ->delete();
    }

    private function getLoginFrequency(CourseMember $member): array
    {
        // Implement login frequency analytics
        return []; // Placeholder
    }

    private function getActivityHeatmap(CourseMember $member): array
    {
        // Implement activity heatmap data
        return []; // Placeholder
    }

    private function getPerformanceTrends(Course $course, CourseMember $member): array
    {
        // Implement performance trends analysis
        return []; // Placeholder
    }

    private function getEngagementMetrics(Course $course, CourseMember $member): array
    {
        // Implement engagement metrics calculation
        return []; // Placeholder
    }

    private function getCompletionTimeline(Course $course, CourseMember $member): array
    {
        // Implement completion timeline tracking
        return []; // Placeholder
    }

    /**
     * V2: API endpoint for listing course members with enhanced filtering
     */
    public function indexV2(Course $course, Request $request)
    {
        $query = $course->courseMembers()->with('user', 'group');

        // Apply filters for V2
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'order_number');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $members = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => CourseMemberResourceV2::collection($members),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ]);
    }

    /**
     * V2: API endpoint for updating member information
     */
    public function updateV2(Request $request, $memberId)
    {
        try {
            $member = CourseMember::findOrFail($memberId);
            
            $validated = $request->validate([
                'member_name' => 'nullable|string|max:255',
                'order_number' => 'nullable|integer|min:0',
                'member_code' => 'nullable|string|max:50',
                'role' => 'nullable|integer|min:1|max:3',
                'status' => 'nullable|boolean',
                'group_id' => 'nullable|exists:course_groups,id',
                'bonus_points' => 'nullable|integer|min:0',
                'grade_progress' => 'nullable|string|max:10',
                'notes_comments' => 'nullable|string|max:1000',
            ]);

            $member->update($validated);

            // If bonus points are being updated, recalculate grade progress
            if ($request->has('bonus_points')) {
                $course = $member->course;
                $newGradeProgress = $this->calculateGrade((($member->achieved_score + $member->bonus_points) / $course->total_score) * 100);
                $member->update(['grade_progress' => $newGradeProgress]);
            }

            return response()->json([
                'success' => true,
                'data' => new CourseMemberResourceV2($member->fresh()),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
