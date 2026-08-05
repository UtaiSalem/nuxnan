<?php

namespace App\Http\Controllers\Api\Learn\Course\points;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\CoursePointCampaign;
use App\Services\Campaign\CampaignDeliveryService;
use App\Services\CoursePointAccountService;
use Illuminate\Http\Request;

class CoursePointCampaignController extends Controller
{
    public function __construct(
        protected CoursePointAccountService $service,
        protected CampaignDeliveryService $delivery
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
    public function claim(Request $request, Course $course, CoursePointCampaign $campaign)
    {
        // ตรวจว่า campaign เป็นของ course นี้
        abort_if($campaign->course_id !== $course->id, 404);

        $data = $request->validate([
            'viewed_donor_id' => 'nullable|integer|exists:users,id',
            'viewed_donation_id' => 'nullable|integer|exists:course_donates,id',
            'viewed_ad_id' => 'nullable|integer|exists:adverts,id',
        ]);
        $result = $this->service->claimManualCampaign($campaign->id, auth()->user(), $data['viewed_donor_id'] ?? null, $data['viewed_donation_id'] ?? null, $data['viewed_ad_id'] ?? null);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function view(Course $course, CoursePointCampaign $campaign)
    {
        abort_if($campaign->course_id !== $course->id, 404);
        abort_unless($campaign->isClaimable(), 422);
        abort_unless($course->courseMembers()->where('user_id', auth()->id())->exists(), 403);
        abort_if($campaign->claims()->where('user_id', auth()->id())->exists(), 422);

        $donation = CourseDonate::with('donor.profile')
            ->where('course_id', $course->id)
            ->where('donation_type', CourseDonate::TYPE_POINT)
            ->whereIn('status', [CourseDonate::STATUS_APPROVED, CourseDonate::STATUS_COMPLETED])
            ->where('remaining_points', '>', 0)
            ->orderBy('created_at')
            ->first();
        $donor = $donation?->donor;

        if (! $donation) {
            $ad = $this->delivery->query('course', $course->academy_id, $course->id)->orderBy('created_at')->with('advertiser')->first();
            if ($ad) {
                return response()->json([
                    'mode' => 'ad', 'donor' => null, 'donation' => null,
                    'ad' => ['id' => $ad->id, 'media_image' => $ad->media_image, 'media_link' => $ad->media_link, 'title' => $ad->title, 'description' => $ad->description, 'duration' => $ad->duration, 'advertiser' => $ad->advertiser ? ['name' => $ad->advertiser->name, 'avatar' => $ad->advertiser->profile_photo_url] : null],
                    'expected_points' => $campaign->points_per_claim, 'campaign_title' => $campaign->title, 'view_duration_seconds' => $ad->duration,
                ]);
            }
        }

        return response()->json([
            'mode' => $donation ? 'donor' : 'empty',
            'donor' => $donor ? ['id' => $donor->id, 'name' => $donor->name, 'username' => $donor->username, 'avatar' => $donor->avatar, 'personal_code' => $donor->personal_code, 'profile' => $donor->profile] : null,
            'donation' => $donation ? ['id' => $donation->id, 'points_amount' => $donation->points_amount, 'remaining_points' => $donation->remaining_points, 'purpose' => $donation->purpose, 'created_at' => $donation->created_at, 'anonymous' => $donation->anonymous, 'donor_display_name' => $donation->donor_display_name] : null,
            'expected_points' => $campaign->points_per_claim,
            'campaign_title' => $campaign->title,
            'view_duration_seconds' => 10,
            'course_avatar' => $donation ? null : ($course->image ?? $course->avatar ?? $course->cover ?? null),
            'course_title' => $donation ? null : ($course->name ?? $course->title),
        ]);
    }

    public function available(Course $course)
    {
        $user = auth()->user();
        $enrolled = $course->user_id === $user->id || $course->courseMembers()->where('user_id', $user->id)->exists();
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
