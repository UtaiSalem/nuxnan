<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\PublicCourseDetailResource;
use App\Http\Resources\Public\PublicCourseResource;
use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\CoursePointCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicCourseController extends Controller
{
    private function donationQuery()
    {
        return Course::query()->where(fn ($q) => $q->where('donation_enabled', true)->when(config('platform.course_donation.enabled', true), fn ($q) => $q->orWhereNull('donation_enabled')));
    }

    private function active($q)
    {
        return $q->where('status', 'active')->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    private function resolve(string $key): Course
    {
        return $this->donationQuery()->with(['academy', 'user'])->where(fn ($q) => $q->where('slug', $key)->when(ctype_digit($key), fn ($q) => $q->orWhereKey((int) $key)))->firstOrFail();
    }

    public function index(Request $request)
    {
        $statuses = [CourseDonate::STATUS_COMPLETED, CourseDonate::STATUS_APPROVED];
        $query = $this->donationQuery()->with(['academy', 'user'])
            ->withSum(['courseDonates as total_donated_points' => fn ($q) => $q->whereIn('status', $statuses)], 'points_amount')
            ->withCount(['courseDonates as total_donors' => fn ($q) => $q->whereIn('status', $statuses)->whereNotNull('donor_id')])
            ->withCount(['pointCampaigns as active_campaign_count' => fn ($q) => $this->active($q)]);
        if ($request->filled('q')) {
            $term = '%'.$request->q.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('description', 'like', $term));
        }
        if ($request->filled('academy_id')) {
            $query->where('academy_id', $request->academy_id);
        }
        if ($request->filled('subject_area') && Schema::hasColumn('courses', 'subject_area')) {
            $query->where('subject_area', $request->subject_area);
        }
        if ($request->boolean('active_campaign')) {
            $query->whereHas('pointCampaigns', fn ($q) => $this->active($q));
        }
        $request->sort === 'most_supported' ? $query->orderByDesc('total_donated_points') : ($request->sort === 'most_active' ? $query->orderByDesc('active_campaign_count') : $query->latest());

        return PublicCourseResource::collection($query->paginate(12));
    }

    public function show(string $course, Request $request)
    {
        $course = $this->resolve($course);
        $this->signals($course);

        return new PublicCourseDetailResource($course);
    }

    public function supportSummary(string $course)
    {
        return response()->json(['data' => $this->summary($this->resolve($course))]);
    }

    private function signals(Course $course): void
    {
        $s = $this->summary($course);
        foreach (['total_donated_points', 'total_donors'] as $key) {
            $course->setAttribute($key, $s[$key]);
        } $course->setAttribute('active_campaign_count', $s['active_campaigns_count']);
        $course->setAttribute('support_summary', $s);
    }

    private function summary(Course $course): array
    {
        $d = CourseDonate::where('course_id', $course->id)->whereIn('status', ['completed', 'approved']);
        $campaigns = $this->active(CoursePointCampaign::where('course_id', $course->id))->get();

        return ['total_donated_points' => (int) $d->sum('points_amount'), 'total_donated_cash' => (float) $d->sum('cash_amount'), 'total_donors' => (int) (clone $d)->whereNotNull('donor_id')->distinct('donor_id')->count('donor_id'), 'active_campaigns_count' => $campaigns->count(), 'campaign_progress' => $campaigns->map(fn ($c) => ['campaign_id' => $c->id, 'title' => $c->title, 'total_budget' => $c->max_claims === null ? null : $c->max_claims * $c->points_per_claim, 'spent_budget' => $c->total_points_claimed, 'remaining_budget' => $c->max_claims === null ? null : max(0, $c->max_claims * $c->points_per_claim - $c->total_points_claimed)])->values(), 'recent_donors' => $d->latest()->limit(5)->get()->map(fn ($x) => ['display_name' => $x->anonymous ? 'Anonymous donor' : ($x->donor_display_name ?: 'Supporter')])->values()];
    }
}
