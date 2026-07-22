<?php

namespace App\Http\Controllers\Api\Learn\Course\points;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePointCampaign;
use App\Models\CourseQuiz;
use App\Services\CoursePointAccountService;
use Illuminate\Http\Request;

class QuizRewardCampaignController extends Controller
{
    public function __construct(protected CoursePointAccountService $service) {}

    public function show(Course $course, CourseQuiz $quiz)
    {
        $this->authorizeCourseAdmin($course);

        return response()->json(['data' => CoursePointCampaign::where('quiz_id', $quiz->id)->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_QUIZ)->whereIn('status', ['active', 'paused'])->first()]);
    }

    public function store(Request $request, Course $course, CourseQuiz $quiz)
    {
        $this->authorizeCourseAdmin($course);
        $data = $request->validate(['title' => 'nullable|string|max:255', 'description' => 'nullable|string', 'points_per_claim' => 'required|integer|min:1', 'max_claims' => 'nullable|integer|min:1', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at']);
        $result = $this->service->createQuizRewardCampaign($course->id, $quiz->id, $data, $request->user()->id);

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    public function destroy(Course $course, CourseQuiz $quiz)
    {
        $this->authorizeCourseAdmin($course);
        $campaign = CoursePointCampaign::where('quiz_id', $quiz->id)->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_QUIZ)->whereIn('status', ['active', 'paused'])->firstOrFail();

        return response()->json($this->service->cancelCampaign($campaign->id));
    }

    private function authorizeCourseAdmin(Course $course): void
    {
        $user = auth()->user();
        abort_unless($course->user_id === $user->id || $user->hasRole('admin'), 403);
    }
}
