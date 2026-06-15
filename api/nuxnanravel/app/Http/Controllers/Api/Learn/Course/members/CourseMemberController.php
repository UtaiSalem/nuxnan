<?php

namespace App\Http\Controllers\Api\Learn\Course\members;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\assignments\AssignmentAnswerResource;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use App\Http\Resources\Learn\Course\members\CourseMemberResource;
use App\Http\Resources\Learn\Course\members\CourseMemberResourceV2;
use App\Http\Resources\Learn\Course\quizzes\CourseQuizResource;
use App\Models\Course;
use App\Models\CourseGroupMember;
use App\Models\CourseMember;
use App\Models\CoursePurchase;
use App\Models\CourseQuizResult;
use App\Models\UserAnswerQuestion;
use App\Services\AttendanceEligibilityService;
use App\Services\LearnerIdentityService;
use App\Services\CourseMemberRemovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseMemberController extends Controller
{
    protected AttendanceEligibilityService $eligibilityService;

    protected LearnerIdentityService $identityService;

    protected CourseMemberRemovalService $removalService;

    public function __construct(
        AttendanceEligibilityService $eligibilityService, 
        LearnerIdentityService $identityService,
        CourseMemberRemovalService $removalService
    ) {
        $this->eligibilityService = $eligibilityService;
        $this->identityService = $identityService;
        $this->removalService = $removalService;
    }

    private function orderedCourseLessons(Course $course)
    {
        return $course->courseLessons()
            ->orderByRaw('`order` IS NULL')
            ->orderBy('order')
            ->orderBy('created_at');
    }

    public function index(Course $course, Request $request)
    {
        $query = $course->courseMembers()->with('user', 'group', 'course');

        // Apply filters for V2
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('course_member_status') && $request->course_member_status !== null) {
            $query->where('course_member_status', $request->course_member_status);
        }

        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $searchTerm = '%'.$request->search.'%';
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm);
            });
        }

        $members = $query->orderBy('order_number')->paginate($request->get('per_page', 20));

        // Batch-compute eligibility to avoid N+1 — attach data to each model before
        // passing to the resource so CourseMemberResource reads from $member->eligibility_data
        foreach ($members as $member) {
            $member->eligibility_data = $this->eligibilityService->canTakeExam($member);
            $member->identity_data = $this->identityService->resolve($member->user, $member->course);
        }

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
            'course' => new CourseResource($course),
            'members' => CourseMemberResource::collection($members),
            'groups' => CourseGroupResource::collection($course->courseGroups),
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
            'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
            'filters' => $request->only(['status', 'role', 'group_id', 'search']),
        ]);
    }

    public function show(Course $course, CourseMember $member)
    {
        $member->load(['user', 'group', 'course']);
        $userId = $member->user_id;

        // Get all lessons with completion status
        $lessons = $this->orderedCourseLessons($course)->get()->map(function ($lesson) use ($userId) {
            $progress = \App\Models\LessonProgress::where('lesson_id', $lesson->id)
                ->where('user_id', $userId)
                ->first();

            $completed = $progress && $progress->status === 'completed';

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'completed' => $completed,
                'progress_percentage' => $completed ? 100 : 0,
                'status_label' => $completed ? 'เสร็จสิ้น' : 'ยังไม่เริ่ม',
            ];
        });

        // Get all assignments (course + lesson) with submission status and scores
        $courseAssignments = $course->courseAssignments;
        $lessonAssignments = $this->orderedCourseLessons($course)->with('assignments')->get()->flatMap->assignments;
        $allAssignments = $courseAssignments->merge($lessonAssignments);

        // Filter assignments based on status and group
        $assignments = $allAssignments->filter(function ($assignment) use ($member) {
            // Must be published
            if ($assignment->status !== 1) {
                return false;
            }

            // Check group restriction
            if (! empty($assignment->target_groups)) {
                if (! $member->group_id) {
                    return false;
                }
                if (! in_array($member->group_id, $assignment->target_groups)) {
                    return false;
                }
            }

            return true;
        })->map(function ($assignment) use ($userId) {
            $answer = \App\Models\AssignmentAnswer::where('assignment_id', $assignment->id)
                ->where('user_id', $userId)
                ->first();

            $actualStatus = 'not_submitted';
            $statusLabel = 'ยังไม่ส่ง';
            $progressPercentage = 0;

            if ($answer) {
                if ($answer->status === 'graded' || $answer->points !== null) {
                    $actualStatus = 'graded';
                    $statusLabel = 'ตรวจแล้ว';
                    $progressPercentage = 100;
                } elseif ($answer->status === 'in_review') {
                    $actualStatus = 'in_review';
                    $statusLabel = 'รอกาตรวจ';
                    $progressPercentage = 50;
                } else {
                    $actualStatus = 'submitted';
                    $statusLabel = 'ส่งแล้ว';
                    $progressPercentage = 50;
                }
            }

            return [
                'id' => $assignment->id,
                'title' => $assignment->title ?? $assignment->name ?? 'งานที่ '.$assignment->id,
                'max_score' => $assignment->points ?? $assignment->max_score ?? 100,
                'score' => $answer ? $answer->points : null,
                'submitted' => $answer !== null,
                'status' => $actualStatus,
                'status_label' => $statusLabel,
                'progress_percentage' => $progressPercentage,
                'submitted_at' => $answer ? $answer->created_at : null,
                'graded' => $actualStatus === 'graded',
            ];
        })->values();

        // Get all quizzes with completion status and scores
        $courseQuizzes = $course->courseQuizzes()->with('questions')->get();

        $quizzes = $courseQuizzes->filter(function ($quiz) {
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

            $maxScore = $quiz->total_score;
            if (! $maxScore || $maxScore == 0) {
                $maxScore = $quiz->questions->sum('points');
            }
            if (! $maxScore || $maxScore == 0) {
                $maxScore = $quiz->questions->count() ?: 10;
            }

            $percentage = $result && $maxScore > 0
                ? ($result->score / $maxScore) * 100
                : 0;

            $passed = $result !== null
                ? ($quiz->passing_score ? round($percentage, 2) >= $quiz->passing_score : true)
                : null;

            $statusLabel = 'ยังไม่ทำ';
            if ($result) {
                $statusLabel = $passed ? 'ผ่าน' : 'ไม่ผ่าน';
            }

            return [
                'id' => $quiz->id,
                'title' => $quiz->title ?? $quiz->name ?? 'แบบทดสอบที่ '.$quiz->id,
                'max_score' => $maxScore,
                'score' => $result ? $result->score : null,
                'completed' => $result !== null,
                'attempt_count' => $attemptCount,
                'completed_at' => $result ? $result->created_at : null,
                'passed' => $passed,
                'progress_percentage' => $result ? ($passed ? 100 : 50) : 0,
                'status_label' => $statusLabel,
            ];
        })->values();

        // Calculate overall progress
        $totalItems = count($lessons) + count($assignments) + count($quizzes);
        $completedItems = collect($lessons)->where('completed', true)->count() +
                          collect($assignments)->where('status', 'graded')->count() +
                          collect($quizzes)->where('passed', true)->count();

        $overallPercentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;

        return response()->json([
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
            'course' => new CourseResource($course),
            'member' => new CourseMemberResource($member),
            'groups' => CourseGroupResource::collection($course->courseGroups),
            'lessons' => $lessons,
            'assignments' => $assignments,
            'quizzes' => $quizzes,
            'overall_progress' => [
                'progress_percentage' => $overallPercentage,
                'status_label' => $overallPercentage >= 100 ? 'เสร็จสมบูรณ์' : ($overallPercentage > 0 ? 'กำลังดำเนินการ' : 'ยังไม่ได้เริ่ม'),
            ],
        ]);
    }

    public function storemember(Course $course, Request $request)
    {
        $user = auth()->user();

        // Idempotency: if user already a member, return early before doing anything else
        // (especially before wallet charge — prevents double-billing on retries)
        $existingMember = CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            if ($request->group_id) {
                $existingMember->group_id = $request->group_id;
                $existingMember->save();

                $courseGroupMember = CourseGroupMember::where('course_id', $course->id)
                    ->where('user_id', $user->id)
                    ->first();
                if ($courseGroupMember) {
                    $courseGroupMember->group_id = $request->group_id;
                    $courseGroupMember->save();
                }
            }

            return response()->json([
                'success' => true,
                'newCourseMember' => $existingMember->fresh(),
                'memberStatus' => $existingMember->status,
                'paid' => false,
                'amount_paid' => 0,
                'transaction_id' => null,
                'already_member' => true,
            ], 200);
        }

        // Lifecycle guard: delegate to CoursePolicy::enroll, which calls
        // CourseLifecycleService. Must run BEFORE wallet charge.
        $gate = \Illuminate\Support\Facades\Gate::inspect('enroll', $course);
        if ($gate->denied()) {
            $msg = $gate->message() ?: 'รายวิชานี้ปิดรับสมัครแล้ว';

            return response()->json([
                'success' => false,
                'code' => $gate->code() ?: 'COURSE_ENROLLMENT_CLOSED',
                'message' => $msg,
                'msg' => $msg,
            ], 422);
        }

        // Calculate course price
        $price = $course->tuition_fees ?? $course->price ?? 0;

        // Apply discount if exists
        if ($price > 0 && $course->discount > 0) {
            $price = $price - ($price * $course->discount / 100);
        }

        $courseAutoAcceptMembers = ($course->courseSettings->auto_accept_members ?? 1) === 0 ? 0 : 1;
        $requiresApproval = $courseAutoAcceptMembers === 0;

        // For paid courses, only charge immediately if auto-approved (no approval required)
        if ($price > 0 && ! $requiresApproval) {
            // Check wallet balance
            if ($user->wallet < $price) {
                return response()->json([
                    'success' => false,
                    'msg' => 'ยอดเงินในกระเป๋าไม่เพียงพอ กรุณาเติมเงินก่อนสมัครเรียน',
                    'required' => $price,
                    'current_balance' => $user->wallet,
                    'need_topup' => true,
                ], 400);
            }

            // Process payment via WalletService
            try {
                $walletService = app(\App\Services\WalletService::class);
                $transaction = $walletService->purchaseCourse($user, $course);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'msg' => 'การชำระเงินล้มเหลว: '.$e->getMessage(),
                ], 400);
            }
        }

        $new_course_member = new CourseMember;
        $new_course_member->user_id = $user->id;
        $new_course_member->course_id = $course->id;
        $new_course_member->group_id = $request->group_id ?? null;
        $new_course_member->status = $courseAutoAcceptMembers;
        $new_course_member->course_member_status = $courseAutoAcceptMembers;
        $new_course_member->enrollment_date = now();

        // Auto-populate identity from Academy/Classroom
        $this->identityService->autoPopulate($new_course_member);

        $new_course_member->save();

        // Fire gamification event
        \App\Services\UsageEventService::fire($user, \App\Enums\UsageEventType::COURSE_JOIN->value, 'course', $course->id);

        if ($new_course_member->group_id) {
            $newCourseGroupMember = new CourseGroupMember;
            $newCourseGroupMember->user_id = $user->id;
            $newCourseGroupMember->course_id = $new_course_member->course_id;
            $newCourseGroupMember->group_id = $new_course_member->group_id;
            $newCourseGroupMember->status = $courseAutoAcceptMembers;
            $newCourseGroupMember->save();
        }

        return response()->json([
            'success' => true,
            'newCourseMember' => CourseMember::find($new_course_member->id),
            'memberStatus' => $new_course_member->status,
            'paid' => $price > 0,
            'amount_paid' => $price > 0 ? $price : 0,
            'transaction_id' => isset($transaction) ? $transaction->id : null,
        ], 200);
    }

    public function destroy(Course $course, CourseMember $member)
    {
        $user = auth()->user();
        $isSelf = $user && $user->id === $member->user_id;

        if (! $isSelf && ! $course->hasPermission($user, 'remove_members')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            // Delete ALL membership rows for this user in this course to handle duplicates and ensure clean exit
            CourseGroupMember::where('course_id', $course->id)
                ->where('user_id', $member->user_id)
                ->delete();

            CourseMember::where('course_id', $course->id)
                ->where('user_id', $member->user_id)
                ->delete();

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
            'last_accessed_tab' => $request->tab,
        ]);
    }

    public function setActiveGroupTab(Course $course, CourseMember $member, Request $request)
    {
        $member->update([
            'last_accessed_group_tab' => $request->group_tab,
        ]);

        return response()->json([
            'success' => true,
        ], 200);
    }

    // function update
    public function update(Course $course, CourseMember $member, Request $request)
    {
        if (! $course->hasPermission(auth()->user(), 'edit_member_details')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'member_name' => 'nullable|string|max:255',
            'order_number' => 'nullable|numeric',
            'member_code' => 'nullable|string|max:50',
            'group_id' => 'nullable|exists:course_groups,id',
            'course_member_status' => 'nullable|in:0,1',
        ]);

        if ($request->has('group_id') && $request->group_id != $member->group_id) {
            $this->moveMemberToGroup($member, $request->group_id);
        }

        $data = [];

        if ($request->has('member_name')) {
            $data['member_name'] = $request->member_name;
        }
        if ($request->has('order_number')) {
            $data['order_number'] = $request->filled('order_number') ? (int) $request->order_number : null;
        }
        if ($request->has('member_code')) {
            $data['member_code'] = $request->member_code;
        }

        if ($request->has('course_member_status')) {
            $data['course_member_status'] = (int) $request->course_member_status;
            $data['status'] = (int) $request->course_member_status; // keep status in sync
        }

        $member->update($data);

        return response()->json([
            'success' => true,
            'notes_comments' => $member->notes_comments,
        ], 200);
    }

    // function delete course's member
    public function deleteCourseMember(Course $course, CourseMember $member)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

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
                    $user_assignment_answers = AssignmentAnswerResource::collection($assignment->answers()->where('user_id', $member->user_id)->get());
                    foreach ($user_assignment_answers as $user_assignment_answer) {
                        if ($user_assignment_answer->images->count() > 0) {
                            foreach ($user_assignment_answer->images as $image) {
                                Storage::disk('public')->delete('images/courses/assignments/answers/'.$image->filename);
                            }
                            $user_assignment_answer->images()->delete();
                        }
                        $user_assignment_answer->delete();
                    }
                }
            }

            $user_answer_questions = UserAnswerQuestion::where('course_id', $course->id)->where('user_id', $member->user_id)->delete();
            $course_quiz_results = CourseQuizResult::where('course_id', $course->id)->where('user_id', $member->user_id)->delete();

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

    // function update member bonus points
    public function updateBonusPoints(Course $course, CourseMember $member, Request $request)
    {
        if (! $course->hasPermission(auth()->user(), 'add_bonus_points')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member->update(['bonus_points' => $request->bonus_points]);

        // Compute actual max total from all score sources
        $lessons = $course->courseLessons()->with(['assignments', 'questions'])->get();
        $computedMaxTotal = $course->courseAssignments->sum('points')
            + $lessons->flatMap->assignments->sum('points')
            + $course->courseQuizzes->sum('total_score')
            + $lessons->flatMap->questions->sum('points');

        $percentage = ($computedMaxTotal > 0) ? (($member->achieved_score + $request->bonus_points) / $computedMaxTotal) * 100 : 0;
        $percentage = min(100, max(0, $percentage));
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
        if (! $course->hasPermission(auth()->user(), 'edit_grades')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member->update([
            'grade_progress' => $request->grade_progress,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function updateNotesComments(Course $course, CourseMember $member, Request $request)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member->update([
            'notes_comments' => $request->notes_comments,
        ]);

        return response()->json([
            'success' => true,
            'notes_comments' => $member->notes_comments,
        ]);
    }

    public function getMembersRequesters(Course $course)
    {
        $members = $course->courseMembers()->with('user', 'group')->where('course_member_status', 0)->latest()->paginate();

        return response()->json([
            'course' => new CourseResource($course),
            'groups' => CourseGroupResource::collection($course->courseGroups),
            'members' => CourseMemberResource::collection($members),
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
            'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function approveRequest(Course $course, CourseMember $member)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if ($member->course_id !== $course->id || $member->course_member_status !== 0) {
            return response()->json(['success' => false, 'message' => 'ไม่พบคำขอหรือสถานะไม่ถูกต้อง'], 422);
        }

        $price = $course->tuition_fees ?? $course->price ?? 0;
        $isPaidCourse = $price > 0;

        $member->update([
            'course_member_status' => 1,
            'status' => $isPaidCourse ? 0 : 1,
        ]);

        if ($member->group_id) {
            CourseGroupMember::where('course_id', $course->id)
                ->where('user_id', $member->user_id)
                ->where('group_id', $member->group_id)
                ->update(['status' => 1, 'request_status' => 'approved']);
        }

        $content = $isPaidCourse
            ? 'คำขอเข้าเรียนได้รับการอนุมัติ กรุณาชำระเงินเพื่อเริ่มเรียน'
            : 'คำขอเข้าเรียนได้รับการอนุมัติแล้ว';

        \App\Models\Notification::create([
            'user_id' => $member->user_id,
            'sender_id' => auth()->id(),
            'type' => $isPaidCourse ? 'course_approved_payment_required' : 'course_approved',
            'content' => $content,
            'action_url' => "/learn/courses/{$course->id}",
            'related_id' => $course->id,
            'read_status' => false,
        ]);

        return new CourseMemberResource($member->load('user', 'group'));
    }

    public function rejectRequest(Course $course, CourseMember $member)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if ($member->course_id !== $course->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบคำขอ'], 404);
        }

        CourseGroupMember::where('course_id', $course->id)
            ->where('user_id', $member->user_id)
            ->delete();

        $userId = $member->user_id;
        $member->delete();

        \App\Models\Notification::create([
            'user_id' => $userId,
            'sender_id' => auth()->id(),
            'type' => 'course_rejected',
            'content' => 'ขออภัย คำขอเข้าเรียนไม่ผ่านการพิจารณา',
            'related_id' => $course->id,
            'read_status' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'ปฏิเสธคำขอเรียบร้อยแล้ว']);
    }

    public function completePayment(Course $course, CourseMember $member)
    {
        if ($member->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if ($member->course_id !== $course->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404);
        }
        if ($member->course_member_status !== 1 || $member->status !== 0) {
            return response()->json(['success' => false, 'message' => 'ไม่อยู่ในสถานะรอชำระเงิน'], 422);
        }

        $price = $course->tuition_fees ?? $course->price ?? 0;
        if ($price <= 0) {
            // self-heal: คอร์สฟรีที่ค้างอยู่ -> activate เลย
            $member->update(['status' => 1]);

            return response()->json(['success' => true, 'message' => 'เปิดใช้งานแล้ว']);
        }

        $user = auth()->user();
        if ($user->wallet < $price) {
            return response()->json([
                'success' => false,
                'msg' => 'ยอดเงินในกระเป๋าไม่เพียงพอ กรุณาเติมเงินก่อน',
                'required' => $price,
                'current_balance' => $user->wallet,
                'need_topup' => true,
            ], 400);
        }

        try {
            $walletService = app(\App\Services\WalletService::class);
            $transaction = $walletService->purchaseCourse($user, $course);

            // Record the purchase for refund traceability
            CoursePurchase::create([
                'purchase_type' => 'enrollment',
                'source_course_id' => $course->id,
                'buyer_id' => $user->id,
                'seller_id' => $course->user_id, // Course owner
                'course_member_id' => $member->id,
                'amount_wallet' => $price,
                'wallet_transaction_id' => $transaction->id,
                'payment_mode' => 'wallet',
                'status' => 'completed',
                'paid_at' => now(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => 'การชำระเงินล้มเหลว: '.$e->getMessage()], 400);
        }

        $member->update(['status' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'ชำระเงินสำเร็จ เริ่มเรียนได้เลย',
            'transaction_id' => $transaction->id,
        ]);
    }

    public function memberSettings(Course $course, CourseMember $course_member)
    {
        // Eager load relationships to avoid N+1 queries
        $course_member->load(['user', 'group', 'course']);
        $course->load(['courseLessons', 'courseGroups', 'courseAssignments', 'courseQuizzes']);

        return response()->json([
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
            'course' => new CourseResource($course),
            'lessons' => LessonResource::collection($course->courseLessons),
            'groups' => CourseGroupResource::collection($course->courseGroups),
            'member' => new CourseMemberResource($course_member),
            'assignments' => AssignmentResource::collection($course->courseAssignments),
            'quizzes' => CourseQuizResource::collection($course->courseQuizzes),
            'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function removalPreview(Course $course, CourseMember $member)
    {
        try {
            $preview = $this->removalService->preview($course, $member, auth()->user());
            return response()->json([
                'success' => true,
                'preview' => $preview
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function removeMember(Course $course, CourseMember $member, Request $request)
    {
        $request->validate([
            'mode' => 'required|in:self_leave,admin_remove,cancel_request',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->removalService->execute(
                $course, 
                $member, 
                auth()->user(), 
                $request->mode, 
                $request->reason
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function memberProgress(Course $course, CourseMember $course_member)
    {
        $course_member->load('course', 'user');
        $userId = $course_member->user_id;

        // 1. Lessons Progress (Bulk)
        $lessonIds = $course->courseLessons()->pluck('id');
        $allLessonProgress = \App\Models\LessonProgress::whereIn('lesson_id', $lessonIds)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('lesson_id');

        $lessons = $this->orderedCourseLessons($course)->get()->map(function ($lesson) use ($allLessonProgress) {
            $progress = $allLessonProgress->get($lesson->id);
            $completed = $progress && $progress->status === 'completed';

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'completed' => $completed,
                'progress_percentage' => $completed ? 100 : 0,
                'status_label' => $completed ? 'เสร็จสิ้น' : 'ยังไม่เริ่ม',
            ];
        });

        // 2. Assignments (Bulk)
        $courseAssignments = $course->courseAssignments;
        $lessonAssignments = $course->courseLessons()->with('assignments')->get()->flatMap(function($lesson) {
            return $lesson->assignments->map(function($a) use ($lesson) {
                $a->lesson_id = $lesson->id;
                return $a;
            });
        });
        $allAssignments = $courseAssignments->merge($lessonAssignments);
        $allAssignmentIds = $allAssignments->pluck('id');

        $allAssignmentAnswers = \App\Models\AssignmentAnswer::whereIn('assignment_id', $allAssignmentIds)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('assignment_id');

        $assignments = $allAssignments->filter(function ($assignment) use ($course_member) {
            if ($assignment->status !== 1) return false;
            if (! empty($assignment->target_groups)) {
                if (! $course_member->group_id || ! in_array($course_member->group_id, $assignment->target_groups)) return false;
            }
            return true;
        })->map(function ($assignment) use ($allAssignmentAnswers) {
            $answer = $allAssignmentAnswers->get($assignment->id);
            $actualStatus = 'not_submitted';
            $statusLabel = 'ยังไม่ส่ง';
            $progressPercentage = 0;

            if ($answer) {
                if ($answer->status === 'graded' || $answer->points !== null) {
                    $actualStatus = 'graded';
                    $statusLabel = 'ตรวจแล้ว';
                    $progressPercentage = 100;
                } elseif ($answer->status === 'in_review') {
                    $actualStatus = 'in_review';
                    $statusLabel = 'กำลังตรวจ';
                    $progressPercentage = 50;
                } else {
                    $actualStatus = 'submitted';
                    $statusLabel = 'ส่งแล้ว';
                    $progressPercentage = 50;
                }
            }

            return [
                'id' => $assignment->id,
                'lesson_id' => $assignment->lesson_id ?? null,
                'title' => $assignment->title ?? $assignment->name ?? 'งานที่ '.$assignment->id,
                'max_score' => $assignment->points ?? $assignment->max_score ?? 100,
                'score' => $answer ? $answer->points : null,
                'submitted' => $answer !== null,
                'status' => $actualStatus,
                'status_label' => $statusLabel,
                'progress_percentage' => $progressPercentage,
                'submitted_at' => $answer ? $answer->created_at : null,
                'graded' => $actualStatus === 'graded',
            ];
        })->values();

        // 3. Quizzes (Bulk: Course Quizzes + Lesson Quizzes)
        $courseQuizzes = $course->courseQuizzes()->with('questions')->get();
        $allQuizResults = \App\Models\CourseQuizResult::whereIn('quiz_id', $courseQuizzes->pluck('id'))
            ->where('user_id', $userId)
            ->where('course_id', $course->id)
            ->get()
            ->groupBy('quiz_id');

        $quizzes = $courseQuizzes->filter(function ($quiz) {
            return $quiz->is_active;
        })->map(function ($quiz) use ($allQuizResults) {
            $userResults = $allQuizResults->get($quiz->id) ?? collect([]);
            $result = $userResults->sortByDesc('score')->first();
            $attemptCount = $userResults->count();
            $maxScore = $quiz->total_score ?: $quiz->questions->sum('points') ?: ($quiz->questions->count() ?: 10);
            $percentage = $result && $maxScore > 0 ? ($result->score / $maxScore) * 100 : 0;
            $passed = $result !== null ? ($quiz->passing_score ? round($percentage, 2) >= $quiz->passing_score : true) : null;

            return [
                'id' => $quiz->id,
                'title' => $quiz->title ?? 'แบบทดสอบที่ '.$quiz->id,
                'max_score' => $maxScore,
                'score' => $result ? $result->score : null,
                'completed' => $result !== null,
                'attempt_count' => $attemptCount,
                'completed_at' => $result ? $result->created_at : null,
                'passed' => $passed,
                'progress_percentage' => $result ? ($passed ? 100 : 50) : 0,
                'status_label' => $result ? ($passed ? 'ผ่าน' : 'ไม่ผ่าน') : 'ยังไม่ทำ',
            ];
        })->values();

        // Lesson quizzes (questions inside lessons)
        $lessonsWithQuestions = $course->courseLessons()->whereHas('questions')->get();
        $lessonQuizList = $lessonsWithQuestions->map(function($lesson) use ($userId) {
            $questions = $lesson->questions;
            $questionIds = $questions->pluck('id');
            $answers = \App\Models\LessonAnswerQuestion::whereIn('question_id', $questionIds)
                ->where('user_id', $userId)
                ->get();
            
            $completed = $answers->count() > 0;
            $earned = $answers->where('is_correct', true)->sum('points');
            $max = $questions->sum(function($q) { return $q->points ?? 1; });

            return [
                'id' => $lesson->id,
                'lesson_id' => $lesson->id,
                'is_lesson_quiz' => true,
                'title' => 'แบบทดสอบ: ' . $lesson->title,
                'max_score' => $max,
                'score' => $completed ? $earned : null,
                'completed' => $completed,
                'attempt_count' => $completed ? 1 : 0,
                'status_label' => $completed ? 'ทำแล้ว' : 'ยังไม่เริ่ม',
                'progress_percentage' => $completed ? 100 : 0,
            ];
        });

        // 4. Summary & Grade (Canonical)
        $scoreService = app(\App\Services\CourseScoreService::class);
        $scoreData = $scoreService->calculateGradeData($course_member);

        return response()->json([
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
            'course' => new CourseResource($course),
            'lessons' => $lessons,
            'groups' => CourseGroupResource::collection($course->courseGroups),
            'member' => new CourseMemberResource($course_member),
            'assignments' => $assignments,
            'quizzes' => $quizzes->concat($lessonQuizList),
            'courseMemberOfAuth' => $course_member,
            'overall_progress' => [
                'progress_percentage' => $scoreData['score_percentage'],
                'status_label' => $scoreData['score_percentage'] >= 100 ? 'เสร็จสมบูรณ์' : ($scoreData['score_percentage'] > 0 ? 'กำลังดำเนินการ' : 'ยังไม่ได้เริ่ม'),
            ],
            'scores' => $scoreData,
        ]);
    }

    public function updateOrderNumber(Course $course, CourseMember $member, Request $request)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member->update([
            'order_number' => $request->order_number,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function updateMemberCode(Course $course, CourseMember $member, Request $request)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Coerce member_code to string before validation
        if ($request->has('member_code') && $request->member_code !== null) {
            $request->merge(['member_code' => (string) $request->member_code]);
        }

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
            'attendanceRecords',
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
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

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
                        $this->internalRemoveMember($member, $request->reason);
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

        if ($totalItems === 0) {
            return 0;
        }

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
        if ($quizResults->isEmpty()) {
            return 0;
        }

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

    private function moveMemberToGroup(CourseMember $member, ?int $groupId)
    {
        if (! $groupId) {
            $this->removeMemberFromGroup($member);

            return;
        }

        $member->update(['group_id' => $groupId]);

        // Update group member relationship
        // course_group_members.status is ENUM('0','1'), must pass string values
        CourseGroupMember::updateOrCreate(
            [
                'user_id' => $member->user_id,
                'course_id' => $member->course_id,
            ],
            [
                'group_id' => $groupId,
                'status' => $member->status > 0 ? '1' : '0',
            ]
        );
    }

    private function removeMemberFromGroup(CourseMember $member)
    {
        $member->update(['group_id' => null]);

        CourseGroupMember::where('user_id', $member->user_id)
            ->where('course_id', $member->course_id)
            ->delete();
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

    private function internalRemoveMember(CourseMember $member, ?string $reason)
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
                        Storage::disk('public')->delete('images/courses/assignments/answers/'.$image->filename);
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
        $query = $course->courseMembers()->with('user', 'group', 'course');

        // Apply filters for V2
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('course_member_status') && $request->course_member_status !== null) {
            $query->where('course_member_status', $request->course_member_status);
        }

        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $searchTerm = '%'.$request->search.'%';
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

            // Coerce member_code to string before validation
            if ($request->has('member_code') && $request->member_code !== null) {
                $request->merge(['member_code' => (string) $request->member_code]);
            }

            $validated = $request->validate([
                'member_name' => 'nullable|string|max:255',
                'order_number' => 'nullable|numeric|min:0',
                'member_code' => 'nullable|string|max:50',
                'role' => 'nullable|integer|min:1|max:3',
                'status' => 'nullable|boolean',
                'group_id' => 'nullable|exists:course_groups,id',
                'bonus_points' => 'nullable|integer|min:0',
                'grade_progress' => 'nullable|string|max:10',
                'notes_comments' => 'nullable|string|max:1000',
            ]);

            // Handle group_id change
            if (array_key_exists('group_id', $validated)) {
                $newGroupId = $validated['group_id'];
                unset($validated['group_id']);

                if ($newGroupId != $member->group_id) {
                    if ($newGroupId) {
                        $this->moveMemberToGroup($member, $newGroupId);
                    } else {
                        $this->removeMemberFromGroup($member);
                    }
                }
            }

            $member->update($validated);

            // If bonus points are being updated, recalculate grade progress
            if ($request->has('bonus_points')) {
                $course = $member->course;
                $newGradeProgress = CourseMember::calculateGradeFromPercentage(
                    (($member->achieved_score + $member->bonus_points) / ($course->total_score ?: 1)) * 100
                );
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

    /**
     * Update own profile - allows students to update their own member information
     */
    public function updateOwnProfile(Course $course, CourseMember $member, Request $request)
    {
        // Check if the authenticated user is the owner of this member record
        if ($member->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Coerce member_code to string before validation
        if ($request->has('member_code') && $request->member_code !== null) {
            $request->merge(['member_code' => (string) $request->member_code]);
        }

        $request->validate([
            'member_name' => 'nullable|string|max:255',
            'order_number' => 'nullable|numeric',
            'member_code' => 'nullable|string|max:50',
            'group_id' => 'nullable|exists:course_groups,id',
        ]);

        // Handle group_id change
        if ($request->has('group_id') && $request->group_id != $member->group_id) {
            if ($request->group_id) {
                $this->moveMemberToGroup($member, $request->group_id);
            } else {
                $this->removeMemberFromGroup($member);
            }
        }

        $member->update([
            'member_name' => $request->member_name,
            'order_number' => $request->filled('order_number') ? (int) $request->order_number : null,
            'member_code' => $request->member_code,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $member->fresh(),
        ], 200);
    }
}
