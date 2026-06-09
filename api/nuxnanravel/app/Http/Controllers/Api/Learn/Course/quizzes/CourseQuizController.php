<?php

namespace App\Http\Controllers\Api\Learn\Course\quizzes;

use App\Exceptions\ImageCopyException;
use App\Exceptions\ImageNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\quizzes\CourseQuizResource;
// use App\Http\Resources\LessonResource;
// use Illuminate\Support\Facades\Validator;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\CourseQuizResult;
use App\Models\CourseRemediationEnrollment;
use App\Models\CourseRemediationSession;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\UserAnswerQuestion;
use App\Services\AttendanceEligibilityService;
use App\Services\ContentVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CourseQuizController extends Controller
{
    private const QUIZ_IMAGE_PATH = 'images/courses/quizzes/questions/';

    private const REMEDIATION_SESSIONS_ENABLED = false;

    protected AttendanceEligibilityService $eligibilityService;
    protected ContentVisibilityService $visibility;

    public function __construct(AttendanceEligibilityService $eligibilityService, ContentVisibilityService $visibility)
    {
        $this->eligibilityService = $eligibilityService;
        $this->visibility = $visibility;
    }

    public function index(Course $course)
    {
        $user = auth()->user();
        $isCourseAdmin = $course->isAdmin($user);

        $quizzes = $course->courseQuizzes()
            ->with(['questions', 'userResults' => fn ($q) => $q->where('user_id', auth()->id())])
            ->withCount('questions')
            ->when(! $isCourseAdmin, fn ($q) => $q->where('is_active', 1))
            ->get();

        return response()->json([
            'course' => new CourseResource($course),
            'quizzes' => CourseQuizResource::collection($quizzes),
            // 'groups'             => CourseGroupResource::collection($course->courseGroups),
            'isCourseAdmin' => $isCourseAdmin,
            'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function create()
    {
        return response()->json(['success' => true]);
    }

    public function store(Course $course, Request $request)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'time_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            // 'end_date'          => 'nullable|date|after:start_date',
            'end_date' => 'nullable|date',
            'shuffle_questions' => 'boolean',
            'passing_score' => 'nullable|integer|min:0',

        ]);

        $validated['user_id'] = auth()->id();
        $validated['is_active'] = $validated['is_active'] ? 1 : 0;
        $validated['shuffle_questions'] = $validated['shuffle_questions'] ? 1 : 0;
        $validated['start_date'] = $validated['start_date'] ? Carbon::parse($validated['start_date'])->setTimezone('Asia/Bangkok') : null;
        $validated['end_date'] = $validated['end_date'] ? Carbon::parse($validated['end_date'])->setTimezone('Asia/Bangkok') : null;
        try {

            $quiz = $course->courseQuizzes()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Quiz created successfully',
                'quiz' => new CourseQuizResource($quiz),
            ], 201); // Created

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create quiz',
                'error' => $e->getMessage(),
            ], 500); // Internal Server Error
        }
    }

    public function show(Course $course, CourseQuiz $quiz)
    {
        // Ownership Check
        abort_if($quiz->course_id !== $course->id, 404);

        $user = auth()->user();
        $isCourseAdmin = $course->isAdmin($user);

        // Visibility check for student
        if (!$isCourseAdmin) {
            $this->visibility->assertVisibleOrFail($quiz, $user, 404);
        }

        $quiz->load([
            'questions' => function ($q) {
                $q->with(['options.images', 'images']);
            },
            'userResults' => function ($q) use ($isCourseAdmin) {
                if (! $isCourseAdmin) {
                    $q->where('user_id', auth()->id());
                }
                $q->with('user');
            },
        ]);

        // Check Exam Eligibility for Student
        $eligibilityInfo = null;
        $canTakeExam = true;

        if (! $isCourseAdmin) {
            $member = $course->courseMembers()->where('user_id', auth()->id())->first();
            if ($member) {
                // If course has point deduction for exams, or requires attendance, check it
                $eligibilityInfo = $this->eligibilityService->canTakeExam($member);

                // Check for retake grant from remediation
                $quizResult = CourseQuizResult::where('user_id', auth()->id())
                    ->where('quiz_id', $quiz->id)
                    ->first();

                $hasRetakeGrant = $quizResult && $quizResult->hasActiveRetakeGrant();

                $canTakeExam = ($eligibilityInfo['can_take_exam'] ?? true) ||
                               ($eligibilityInfo['eligibility_status'] ?? '') === 'unlocked' ||
                               $hasRetakeGrant;
            }
        }

        $groups = [];
        if ($isCourseAdmin) {
            $groups = $course->courseGroups->map(function ($group) use ($course) {
                $memberUserIds = $course->courseMembers()
                    ->where('group_id', $group->id)
                    ->pluck('user_id')
                    ->toArray();

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'member_user_ids' => $memberUserIds,
                    'member_count' => count($memberUserIds),
                ];
            });
        }

        // If student is not eligible and hasn't unlocked it, do not return questions in the resource
        $quizResource = new CourseQuizResource($quiz);
        $questionsHiddenReason = null;

        if (! $canTakeExam && ! $isCourseAdmin) {
            // Strip questions to prevent cheating before paying/unlocking
            // We can do this by using a different resource or stripping it out
            $quizArray = $quizResource->toArray(request());
            unset($quizArray['questions']);
            $quizResource = $quizArray;
            $questionsHiddenReason = ! empty($eligibilityInfo['reasons'])
                ? implode(', ', $eligibilityInfo['reasons'])
                : 'คุณยังไม่มีสิทธิ์ทำข้อสอบนี้';
        }

        // ── Remediation status สำหรับ student ──
        $remediationStatus = null;
        $retakeStatus = null;

        if (! $isCourseAdmin) {
            // ดึง session ที่ผูกกับ quiz นี้
            if (self::REMEDIATION_SESSIONS_ENABLED) {
                $session = CourseRemediationSession::where('course_id', $course->id)
                    ->where('quiz_id', $quiz->id)
                    ->whereIn('status', ['open', 'in_progress', 'completed'])
                    ->latest()
                    ->first();

                if ($session) {
                    $enrollment = CourseRemediationEnrollment::where('remediation_session_id', $session->id)
                        ->where('student_id', auth()->id())
                        ->first();

                    $remediationStatus = [
                        'session_id' => $session->id,
                        'session_title' => $session->title,
                        'session_status' => $session->status,
                        'enrollment' => $enrollment ? [
                            'id' => $enrollment->id,
                            'status' => $enrollment->status,
                            'remediation_score' => $enrollment->remediation_score,
                            'remediation_grade' => $enrollment->remediation_grade,
                        ] : null,
                    ];
                }
            }

            // Check for retake grant from any remediation
            $quizResult = CourseQuizResult::where('user_id', auth()->id())
                ->where('quiz_id', $quiz->id)
                ->first();

            if ($quizResult) {
                $retakeStatus = [
                    'unlocked' => $quizResult->retake_unlocked_at !== null,
                    'used' => $quizResult->retake_used_at !== null,
                    'can_retake' => $quizResult->hasActiveRetakeGrant(),
                    'unlocked_at' => $quizResult->retake_unlocked_at?->toDateTimeString(),
                ];
            }
        }

        return response()->json([
            'quiz' => $quizResource,
            'groups' => $groups,
            'eligibility' => $eligibilityInfo,
            'canTakeExam' => $canTakeExam,
            'questions_hidden_reason' => $questionsHiddenReason,
            'remediation_status' => $remediationStatus,
            'retake_status' => $retakeStatus,
        ]);
    }

    public function edit(Course $course, CourseQuiz $quiz)
    {
        return response()->json([
            'quiz' => new CourseQuizResource($quiz),
        ]);
    }

    public function update(Course $course, CourseQuiz $quiz, Request $request)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // $quiz->update($request->all());
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'time_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            // 'end_date'          => 'nullable|date|after:start_date',
            'end_date' => 'nullable|date',
            'shuffle_questions' => 'boolean',
            'passing_score' => 'nullable|integer|min:0',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['is_active'] = $validated['is_active'] ? 1 : 0;
        $validated['shuffle_questions'] = $validated['shuffle_questions'] ? 1 : 0;
        $validated['start_date'] = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
        $validated['end_date'] = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

        $quiz->update($validated);

        // return redirect()->route('quizzes.show', $quiz);
        return response()->json([
            'success' => true,
            'message' => 'Quiz updated successfully',
            'quiz' => new CourseQuizResource($quiz),
        ], 200); // OK
    }

    public function destroy(Course $course, CourseQuiz $quiz)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $questions = $quiz->questions;
        foreach ($questions as $question) {
            if ($question->images) {
                foreach ($question->images as $q_image) {
                    Storage::disk('public')->delete('images/courses/quizzes/questions/'.$q_image->filename);
                }
                $question->images()->delete();
            }

            if ($question->options) {
                foreach ($question->options as $q_option) {
                    if ($q_option->images) {
                        foreach ($q_option->images as $q_opt_image) {
                            Storage::disk('public')->delete('images/courses/quizzes/questions/'.$q_opt_image->filename);
                        }
                        $q_option->images()->delete();
                    }
                }
                $question->options()->delete();
            }

            // $userAnswerQuestion
            $userAnswerQuestion = $question->userAnswers;
            foreach ($userAnswerQuestion as $answer) {
                $answer->delete();
            }
            $course->decrement('total_score', $question->points);
            $quiz->decrement('total_score', $question->points);
            $quiz->decrement('total_questions');

            $question->delete();
        }

        $userResults = $quiz->userResults;

        foreach ($userResults as $result) {
            $courseMember = CourseMember::where('user_id', $result->user_id)->where('course_id', $result->course_id)->first();

            $result->delete();

            if ($courseMember) {
                app(\App\Services\CourseScoreService::class)->recompute($courseMember);
            }
        }

        $course->decrement('total_score', $quiz->total_score);

        $quiz->delete();

        return response()->json([
            'success' => true,
        ], 204);
    }

    public function calculateSumQuizsEfficiency(Course $course, CourseQuiz $quiz)
    {
        $crsQuizsResult = CourseQuizResult::where('user_id', auth()->id())->where('course_id', $quiz->course_id)->get();
        $sumEfficiency = $crsQuizsResult->sum('efficiency');

        $courseMember = CourseMember::where('user_id', auth()->id())->where('course_id', $quiz->course_id)->first();
        $courseMember->update([
            'efficiency' => $sumEfficiency,
        ]);

        return response()->json([
            'success' => true,
        ], 200);
    }

    public function getQuizzes(Course $course, Request $request)
    {
        $quizs = CourseQuiz::where('title', 'like', '%'.$request->input('title').'%')->limit(5)->get();

        return response()->json([
            'success' => true,
            'quizs' => $quizs,
        ], 200);
    }

    /**
     * Search quizzes across all courses that user has admin access to
     */
    public function searchQuizzes(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'quizzes' => [],
            ]);
        }

        $user = auth()->user();

        // Get course IDs where user is admin
        $adminCourseIds = Course::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('courseMembers', function ($q2) use ($user) {
                    $q2->where('user_id', $user->id)
                        ->whereIn('role', ['admin', 'teacher', 'instructor']);
                });
        })->pluck('id');

        // Search quizzes from courses user can admin
        $quizzes = CourseQuiz::with(['course:id,name,code'])
            ->whereIn('course_id', $adminCourseIds)
            ->where('title', 'like', '%'.$query.'%')
            ->select(['id', 'title', 'course_id', 'total_score', 'time_limit', 'passing_score', 'is_active'])
            ->withCount('questions')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'quizzes' => $quizzes,
        ]);
    }

    public function duplicateQuiz(CourseQuiz $quiz, Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();

            $customTitle = $request->input('title');
            $newQuiz = $this->createDuplicateQuiz($quiz, $request->course_id, $customTitle);
            $this->duplicateQuestions($quiz, $newQuiz);
            $this->updateCourseTotalScore($newQuiz, $quiz->total_score);

            DB::commit();

            return response()->json([
                'success' => true,
                'quiz' => new CourseQuizResource($newQuiz),
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Quiz duplication failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate quiz: '.$e->getMessage(),
            ], 500);
        }
    }

    private function createDuplicateQuiz(CourseQuiz $quiz, int $courseId, ?string $customTitle = null): CourseQuiz
    {
        $newQuiz = $quiz->replicate();
        $newQuiz->course_id = $courseId;
        if ($customTitle) {
            $newQuiz->title = $customTitle;
        }
        $newQuiz->save();

        return $newQuiz;
    }

    private function duplicateQuestions(CourseQuiz $originalQuiz, CourseQuiz $newQuiz): void
    {
        $originalQuiz->questions->each(function ($question) use ($newQuiz) {
            $newQuestion = $this->duplicateQuestion($question, $newQuiz->id);
            $this->duplicateQuestionImages($question, $newQuestion);
            $this->duplicateOptions($question, $newQuestion);
        });
    }

    private function duplicateQuestion($question, int $quizId): Question
    {
        $newQuestion = $question->replicate();
        $newQuestion->questionable_id = $quizId;
        $newQuestion->correct_answers = null;
        $newQuestion->correct_option_id = null;
        $newQuestion->save();

        return $newQuestion;
    }

    private function duplicateQuestionImages($question, $newQuestion): void
    {
        $question->images->each(function ($image) use ($newQuestion) {
            $this->duplicateImage($image, $newQuestion->id);
        });
    }

    private function duplicateImage($image, int $newImageableId): void
    {
        if (! Storage::disk('public')->exists(self::QUIZ_IMAGE_PATH.$image->filename)) {
            throw new ImageNotFoundException('Original image file not found');
        }

        $newImage = $image->replicate();
        $newImage->imageable_id = $newImageableId;
        $newImageFilename = uniqid().'.'.File::extension($image->url);
        $newImageUrl = self::QUIZ_IMAGE_PATH.$newImageFilename;

        if (! Storage::disk('public')->copy(
            self::QUIZ_IMAGE_PATH.$image->filename,
            $newImageUrl
        )) {
            throw new ImageCopyException('Failed to copy image file');
        }

        $newImage->filename = $newImageFilename;
        $newImage->save();
    }

    private function duplicateOptions($question, $newQuestion): void
    {
        $question->options->each(function ($option) use ($newQuestion) {
            $newOption = $this->duplicateOption($option, $newQuestion);
            $this->duplicateOptionImages($option, $newOption);

            if ($option->is_correct) {
                $this->updateCorrectAnswer($newQuestion, $newOption->id);
            }
        });
    }

    private function duplicateOption($option, $newQuestion): QuestionOption
    {
        $newOption = $option->replicate();
        $newOption->optionable_id = $newQuestion->id;
        $newOption->save();

        return $newOption;
    }

    private function duplicateOptionImages($option, $newOption): void
    {
        $option->images->each(function ($image) use ($newOption) {
            $this->duplicateImage($image, $newOption->id);
        });
    }

    public function recalculateResults(Course $course, CourseQuiz $quiz)
    {
        // Check authorization (Teacher/Admin only)
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // 1. Ensure Quiz Total Score is in sync with Questions
        $currentTotalScore = $quiz->questions()->sum('points');
        if ($quiz->total_score != $currentTotalScore) {
            $quiz->update(['total_score' => $currentTotalScore]);
        }

        $results = $quiz->userResults;
        $updatedCount = 0;

        foreach ($results as $result) {
            // Get all answers for this user & quiz
            $userAnswers = UserAnswerQuestion::where('quiz_id', $quiz->id)
                ->where('user_id', $result->user_id)
                ->get();

            // 2. Validate and Update Answer Points
            foreach ($userAnswers as $answer) {
                $question = Question::find($answer->question_id);
                if ($question) {
                    // Re-check correctness
                    $isCorrect = ($answer->answer_id == $question->correct_option_id);
                    // Or Check if the chosen option is correct (some logic uses option.is_correct)
                    // UserAnswerQuestionController uses $selectedOption->is_correct
                    $selectedOption = QuestionOption::find($answer->answer_id);
                    $isCorrect = $selectedOption && $selectedOption->is_correct;

                    $points = $isCorrect ? $question->points : 0;

                    if ($answer->points != $points) {
                        $answer->update(['points' => $points]);
                    }
                } else {
                    // Question deleted. Controller usually deletes answers, but if not:
                    if ($answer->points > 0) {
                        $answer->update(['points' => 0]);
                    }
                }
            }

            // Re-fetch to get updated points
            $userAnswers = UserAnswerQuestion::where('quiz_id', $quiz->id)
                ->where('user_id', $result->user_id)
                ->get();

            $newScore = $userAnswers->sum('points');

            $percentage = $currentTotalScore > 0 ? ($newScore / $currentTotalScore) * 100 : 0;

            $status = round($percentage, 2) >= $quiz->passing_score
                ? 3 // PASSED
                : 4; // FAILED

            $result->update([
                'score' => $newScore,
                'percentage' => $percentage,
                'status' => $status,
            ]);

            $updatedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "คำนวณคะแนนใหม่เรียบร้อยแล้ว ($updatedCount รายการ)",
            'quiz_total_score' => $currentTotalScore,
        ]);
    }

    private function updateCorrectAnswer($question, int $optionId): void
    {
        $question->update([
            'correct_answers' => $optionId,
            'correct_option_id' => $optionId,
        ]);
    }

    private function updateCourseTotalScore(CourseQuiz $quiz, int $score): void
    {
        $quiz->course->increment('total_score', $score);
    }
}
