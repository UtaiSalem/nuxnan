<?php

namespace App\Http\Controllers\Api\Learn\Course\points;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePointCampaign;
use App\Services\CoursePointAccountService;
use Illuminate\Http\Request;

class CoursePointCampaignController extends Controller
{
    public function __construct(
        protected CoursePointAccountService $service
    ) {}

    // GET /courses/{course}/points/campaigns
    public function index(Course $course)
    {
        $campaigns = CoursePointCampaign::where('course_id', $course->id)
            ->latest()->get();

        return response()->json(['data' => $campaigns]);
    }

    // POST /courses/{course}/points/campaigns
    public function store(Request $request, Course $course)
    {
        $this->authorizeCourseAdmin($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_per_claim' => 'required|integer|min:1',
            'max_claims' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $result = $this->service->createCampaign($course->id, $data, $request->user()->id);

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    // POST /courses/{course}/points/campaigns/{campaign}/claim
    public function claim(Course $course, CoursePointCampaign $campaign)
    {
        // ตรวจว่า campaign เป็นของ course นี้
        abort_if($campaign->course_id !== $course->id, 404);

        $result = $this->service->claimManualCampaign($campaign->id, auth()->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function available(Course $course)
    {
        $user = auth()->user();
        $enrolled = $course->courseMembers()->where('user_id', $user->id)->exists();
        $data = CoursePointCampaign::where('course_id', $course->id)->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_MANUAL)->get()->filter(fn ($campaign) => $campaign->isClaimable())->map(function ($campaign) use ($user, $enrolled) {
            $claimed = $campaign->claims()->where('user_id', $user->id)->exists();

            return ['id' => $campaign->id, 'title' => $campaign->title, 'description' => $campaign->description, 'points_per_claim' => $campaign->points_per_claim, 'max_claims' => $campaign->max_claims, 'remaining' => $campaign->max_claims ? max(0, $campaign->max_claims - $campaign->total_claimed) : null, 'total_claimed' => $campaign->total_claimed, 'status' => $campaign->status, 'starts_at' => $campaign->starts_at, 'ends_at' => $campaign->ends_at, 'claimed_by_auth' => $claimed, 'can_claim' => $enrolled && ! $claimed && (! $campaign->max_claims || $campaign->total_claimed < $campaign->max_claims)];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function pause(Course $course, CoursePointCampaign $campaign)
    {
        $this->authorizeCourseAdmin($course);
        $campaign->update(['status' => CoursePointCampaign::STATUS_PAUSED]);

        return response()->json(['success' => true]);
    }

    public function end(Course $course, CoursePointCampaign $campaign)
    {
        $this->authorizeCourseAdmin($course);
        $campaign->update(['status' => CoursePointCampaign::STATUS_ENDED]);

        return response()->json(['success' => true]);
    }

    private function authorizeCourseAdmin(Course $course): void
    {
        $user = auth()->user();
        abort_unless(
            $course->user_id === $user->id || $user->hasRole('admin'),
            403
        );
    }
}
