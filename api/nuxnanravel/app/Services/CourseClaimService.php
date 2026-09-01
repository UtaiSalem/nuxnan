<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\CourseDonateClaim;
use App\Models\CourseMember;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CourseClaimService
{
    public function __construct(protected PointsService $points, protected CoursePointAccountService $accounts) {}

    public function listClaimable(User $user, Course $course, int $page = 1, int $perPage = 12): array
    {
        $this->assertMember($user, $course);

        $today = now()->startOfDay();
        $baseQuery = CourseDonate::where('course_id', $course->id)->where('donation_type', 'point')->where('status', 'completed')->where('remaining_points', '>', 0);
        $accountBalance = (int) (CoursePointAccount::where('course_id', $course->id)->value('balance') ?? 0);
        $poolTotal = min((int) (clone $baseQuery)->sum('remaining_points'), $accountBalance);
        $donations = (clone $baseQuery)->with('donor')->orderBy('created_at')->paginate(min(max($perPage, 1), 50), ['*'], 'page', max($page, 1));
        $maxCost = (int) config('economy.course_claim_max_cost', 270);
        $ratioClaimer = (float) config('economy.course_claim_ratio_claimer', 210 / 270);
        $items = $donations->map(function (CourseDonate $donation) use ($user, $today, $maxCost, $ratioClaimer) {
            $cost = min((int) $donation->remaining_points, $maxCost);

            return [
                'id' => $donation->id,
                'donor_display_name' => $donation->anonymous ? 'ผู้ไม่ประสงค์ออกนาม' : $donation->donor_display_name,
                'donor_avatar' => $donation->donor?->avatar,
                'donor_personal_code' => $donation->anonymous ? null : $donation->donor?->personal_code,
                'purpose' => $donation->purpose,
                'points_amount' => (int) $donation->points_amount,
                'remaining_points' => (int) $donation->remaining_points,
                'created_at' => $donation->created_at,
                'my_claims_today' => CourseDonateClaim::where('course_donate_id', $donation->id)->where('claimer_id', $user->id)->where('claimed_at', '>=', $today)->count(),
                'claim_amount_preview' => $cost,
                'claimer_reward_preview' => (int) round($cost * $ratioClaimer),
            ];
        })->values();
        $allCount = DB::table('user_daily_claim_counters')->where('user_id', $user->id)->where('claimed_at', '>=', $today)->count();

        return [
            'donations' => $items,
            'pagination' => [
                'current_page' => $donations->currentPage(),
                'last_page' => $donations->lastPage(),
                'per_page' => $donations->perPage(),
                'total' => $donations->total(),
                'has_more' => $donations->hasMorePages(),
            ],
            'my_claims_today_all_tiers' => $allCount,
            'cap_reached' => $allCount >= (int) config('economy.course_claim_cap_total_per_day'),
            'pool_total_remaining' => $poolTotal,
        ];
    }

    public function claimSpecific(User $user, Course $course, CourseDonate $donation): CourseDonateClaim
    {
        return DB::transaction(function () use ($user, $course, $donation) {
            $this->assertMember($user, $course);

            $donation = CourseDonate::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            if ((int) $donation->course_id !== (int) $course->id || $donation->donation_type !== 'point' || $donation->status !== 'completed') {
                throw new DomainException('donation_not_claimable');
            }
            $maxCost = (int) config('economy.course_claim_max_cost', 270);
            $remaining = (int) $donation->remaining_points;
            if ($remaining < 1) {
                throw new DomainException('donation_not_claimable');
            }
            $cost = min($remaining, $maxCost);
            $today = now()->startOfDay();
            if (DB::table('user_daily_claim_counters')->where('user_id', $user->id)->where('claimed_at', '>=', $today)->count() >= (int) config('economy.course_claim_cap_total_per_day')) {
                throw new DomainException('daily_cap_reached');
            }
            if (CourseDonateClaim::where('course_donate_id', $donation->id)->where('claimer_id', $user->id)->where('claimed_at', '>=', $today)->count() >= (int) config('economy.course_claim_cap_per_donation_per_day')) {
                throw new DomainException('per_donation_cap_reached');
            }
            $platform = User::where('personal_code', config('economy.platform_personal_code'))->lockForUpdate()->firstOrFail();
            $suggester = $user->suggester_code ? User::where('personal_code', $user->suggester_code)->where('id', '!=', $user->id)->where('id', '!=', $platform->id)->lockForUpdate()->first() : null;
            $rewardClaimer = (int) round($cost * (float) config('economy.course_claim_ratio_claimer', 210 / 270));
            $rewardSuggester = $suggester ? (int) round($cost * (float) config('economy.course_claim_ratio_suggester', 30 / 270)) : 0;
            $rewardCourse = (int) round($cost * (float) config('economy.course_claim_ratio_course', 20 / 270)) + ($suggester ? 0 : (int) round($cost * (float) config('economy.course_claim_ratio_suggester', 30 / 270)));
            $rewardPlatform = $cost - $rewardClaimer - $rewardSuggester - $rewardCourse;
            if ($rewardPlatform < 0) {
                $rewardCourse += $rewardPlatform;
                $rewardPlatform = 0;
            }
            $this->accounts->debit($course->id, $user->id, $cost, CoursePointTransaction::TYPE_STUDENT_CLAIM, null, ['donation_id' => $donation->id], true);
            $donation->decrement('remaining_points', $cost);
            $claimerTx = $this->points->earn($user, $rewardClaimer, 'donation_claim_reward_claimer', $donation->id);
            $suggesterTx = $suggester ? $this->points->earn($suggester, $rewardSuggester, 'donation_claim_reward_suggester', $donation->id) : null;
            $courseTx = $this->accounts->creditClaimShare($course->id, $course->user_id, $rewardCourse, ['donation_id' => $donation->id]);
            $platformTx = $this->points->earn($platform, $rewardPlatform, 'donation_claim_reward_platform', $donation->id);
            if ($rewardClaimer + ($suggester ? $rewardSuggester : 0) + $rewardCourse + $rewardPlatform !== $cost) {
                throw new DomainException('Invalid claim ledger split.');
            }

            return CourseDonateClaim::create([
                'course_donate_id' => $donation->id,
                'course_id' => $course->id,
                'claimer_id' => $user->id,
                'suggester_id' => $suggester?->id,
                'amount_claimer' => $rewardClaimer,
                'amount_suggester' => $suggester ? $rewardSuggester : 0,
                'amount_course' => $rewardCourse,
                'amount_platform' => $rewardPlatform,
                'claimer_transaction_id' => $claimerTx->id,
                'suggester_transaction_id' => $suggesterTx?->id,
                'course_transaction_id' => $courseTx->id,
                'platform_transaction_id' => $platformTx->id,
                'claimed_at' => now(),
            ]);
        });
    }

    public function listClaims(User $user, Course $course, int $page = 1, int $perPage = 15): array
    {
        $this->assertMember($user, $course);

        $claims = CourseDonateClaim::where('course_id', $course->id)
            ->with(['claimer:id,name,profile_photo_path,personal_code', 'donation:id,anonymous,donor_display_name'])
            ->orderByDesc('claimed_at')
            ->paginate(min(max($perPage, 1), 50), ['*'], 'page', max($page, 1));
        $items = $claims->map(fn (CourseDonateClaim $claim) => [
            'id' => $claim->id,
            'claimer_name' => $claim->claimer?->name,
            'claimer_avatar' => $claim->claimer?->avatar,
            'claimer_personal_code' => $claim->claimer?->personal_code,
            'donor_display_name' => $claim->donation?->anonymous ? 'ผู้ไม่ประสงค์ออกนาม' : $claim->donation?->donor_display_name,
            'amount_claimer' => (int) $claim->amount_claimer,
            'amount_course' => (int) $claim->amount_course,
            'claimed_at' => $claim->claimed_at,
            'is_mine' => (int) $claim->claimer_id === (int) $user->id,
        ])->values();
        $courseClaims = CourseDonateClaim::where('course_id', $course->id);

        return [
            'claims' => $items,
            'pagination' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
                'has_more' => $claims->hasMorePages(),
            ],
            'summary' => [
                'total_claims' => (int) (clone $courseClaims)->count(),
                'my_claims_count' => (int) (clone $courseClaims)->where('claimer_id', $user->id)->count(),
                'my_points_total' => (int) ((clone $courseClaims)->where('claimer_id', $user->id)->sum('amount_claimer') ?? 0),
            ],
        ];
    }

    private function assertMember(User $user, Course $course): void
    {
        if ((int) $course->user_id !== (int) $user->id && ! CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->exists()) {
            throw new DomainException('not_a_course_member');
        }
    }
}
