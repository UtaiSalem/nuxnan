<?php

namespace App\Http\Controllers\Api\Learn\Course\quizzes;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseQuiz;
use Illuminate\Http\Request;
use App\Models\CourseQuizResult;
use App\Models\UserAnswerQuestion;
use App\Constants\QuizConstants;
use App\Services\AttendanceEligibilityService;
use Illuminate\Support\Facades\DB;

class CourseQuizResultController extends Controller
{
    protected AttendanceEligibilityService $eligibilityService;

    public function __construct(AttendanceEligibilityService $eligibilityService)
    {
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Course $course, CourseQuiz $quiz, Request $request)
    {
        $isCourseAdmin = $course->isAdmin(auth()->user());

        if (!$isCourseAdmin) {
            $member = $course->courseMembers()->where('user_id', auth()->id())->first();
            if ($member) {
                $eligibilityInfo = $this->eligibilityService->canTakeExam($member);
                if (!$eligibilityInfo['can_take_exam'] && $eligibilityInfo['eligibility_status'] !== 'unlocked') {
                    return response()->json([
                        'status' => false,
                        'message' => !empty($eligibilityInfo['reasons'])
                            ? implode(', ', $eligibilityInfo['reasons'])
                            : 'คุณยังไม่มีสิทธิ์ทำข้อสอบนี้',
                        'eligibility' => $eligibilityInfo,
                    ], 403);
                }
            }
        }

        $quizResult = $course->courseQuizResults()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($quizResult) {
            $quizResult->update([
                'status'        => 0,
                'started_at'    => date('Y-m-d H:i:s'),
                // 'completed_at'  => $request->completed_at, // Reset completion on new start?
                'completed_at'  => null,
            ]);

            return response()->json([
                'status'        => true,
                'quizResult'    => $quizResult
            ], 201);

        }else {
            $quizResult = CourseQuizResult::create([
                'user_id'       => auth()->id(),
                'course_id'     => $course->id,
                'quiz_id'       => $quiz->id,
                'status'        => 0,
                'started_at'    => date('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status'        => true,
                'quizResult'    => $quizResult
            ], 201);
        }
    
    }

    public function update(Course $course, CourseQuiz $quiz, CourseQuizResult $result, Request $request)
    {
        // Ensure the result belongs to the user
        if ($result->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = [];

        if ($request->has('finalize') && $request->finalize == true) {
            $data['completed_at'] = now();
        }

        if ($request->has('duration')) {
            $data['duration'] = $request->duration;
        }

        // Recalculate score, percentage, and status to ensure accuracy on completion
        $quizUserAnswers = UserAnswerQuestion::where('user_id', auth()->id())
            ->where('quiz_id', $quiz->id)
            ->get();

        $data['score'] = $quizUserAnswers->sum('points');
        $data['attempted_questions'] = $quizUserAnswers->count();
        $data['correct_answers'] = $quizUserAnswers->filter(fn($a) => $a->points > 0)->count();
        $data['incorrect_answers'] = $quizUserAnswers->filter(fn($a) => $a->points == 0)->count();
        $data['percentage'] = $quiz->total_score > 0
            ? round(($data['score'] / $quiz->total_score) * 100, 2)
            : 0;
        $data['status'] = $data['percentage'] >= $quiz->passing_score
            ? QuizConstants::STATUS_PASSED
            : QuizConstants::STATUS_FAILED;

        $result->update($data);

        // Fire gamification events if finalized
        if ($request->has('finalize') && $request->finalize == true) {
            \App\Services\UsageEventService::fire(auth()->user(), 'quiz_submit', 'quiz', $quiz->id, [
                'score' => $data['score'],
                'percentage' => $data['percentage'],
                'status' => $data['status']
            ]);

            if ($data['status'] === QuizConstants::STATUS_PASSED) {
                \App\Services\UsageEventService::fire(auth()->user(), 'quiz_pass', 'quiz', $quiz->id);
            }
        }

        return response()->json([
            'success' => true,
            'quizResult' => $result
        ]);
    }


}
