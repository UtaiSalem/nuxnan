<?php

namespace App\Services;

use App\Models\AcademyPointTransaction;
use App\Models\AcademyPointWithdrawalRequest;
use App\Models\CoursePointTransaction;
use App\Models\CoursePointWithdrawalRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Traces where the points behind an Academy/Course point withdrawal came from,
 * so a moderator can judge a payout before approving it.
 *
 * An academy/course point account normally accrues value from many donors and
 * students, so incoming credits are not fraud by themselves. What is worth a
 * second look is: the requester funding their own account and then withdrawing
 * it (self-funding), or a small number of accounts supplying most of what is
 * being withdrawn (concentration) — the point-side analogue of the wallet
 * "transfer stolen value in -> withdraw" pattern.
 *
 * Read-only: it never mutates any ledger. Both point ledgers share the same
 * shape (user_id, type, amount, balance_before, balance_after, metadata), so a
 * single trace handles both; direction is taken from the balance delta rather
 * than trusting per-type sign conventions.
 */
class PointWithdrawalFundSourceService
{
    /** Default look-back window (days) before the withdrawal request. */
    public const DEFAULT_WINDOW_DAYS = 90;

    /** Hard cap on the window. */
    public const MAX_WINDOW_DAYS = 730;

    /** Most recent credit rows returned in the timeline. */
    public const INFLOW_LIMIT = 60;

    /** Share of the withdrawal from the single largest funder that trips "high". */
    public const HIGH_RISK_COVERAGE = 0.5;

    /** Human labels for the credit types that fund a point account. */
    private const TYPE_LABELS = [
        'donation_point_credit' => 'รับบริจาคแต้ม',
        'donation_cash_credit' => 'รับบริจาค (เงินแปลงเป็นแต้ม)',
        'ad_revenue' => 'รายได้โฆษณา',
        'lesson_income' => 'รายได้จากบทเรียน',
        'claim_share' => 'ส่วนแบ่งการเคลม',
        'allocation_in' => 'รับจัดสรรแต้ม',
        'student_claim' => 'นักเรียนเคลมแต้ม',
        'refund' => 'คืนแต้ม',
        'withdrawal_release' => 'คืนแต้มจากการถอน',
    ];

    public function forAcademy(AcademyPointWithdrawalRequest $withdrawal, ?int $windowDays = null): array
    {
        return $this->trace(
            AcademyPointTransaction::class,
            'academy',
            (int) $withdrawal->academy_point_account_id,
            $withdrawal,
            $windowDays
        );
    }

    public function forCourse(CoursePointWithdrawalRequest $withdrawal, ?int $windowDays = null): array
    {
        return $this->trace(
            CoursePointTransaction::class,
            'course',
            (int) $withdrawal->course_point_account_id,
            $withdrawal,
            $windowDays
        );
    }

    private function trace(string $txClass, string $scope, int $accountId, $withdrawal, ?int $windowDays): array
    {
        $windowDays = max(1, min($windowDays ?? self::DEFAULT_WINDOW_DAYS, self::MAX_WINDOW_DAYS));

        $accountColumn = $scope === 'academy' ? 'academy_point_account_id' : 'course_point_account_id';
        $requesterId = (int) $withdrawal->requested_by;
        $withdrawalAmount = (int) $withdrawal->amount;
        $until = $withdrawal->created_at ? Carbon::parse($withdrawal->created_at) : Carbon::now();
        $since = $until->copy()->subDays($windowDays);

        // Every credit into this account in the window (balance went up). Amount
        // is unsigned, so direction must come from the balance delta.
        $credits = $txClass::query()
            ->where($accountColumn, $accountId)
            ->whereColumn('balance_after', '>', 'balance_before')
            ->whereBetween('created_at', [$since, $until])
            ->orderByDesc('created_at')
            ->get();

        $people = $this->resolvePeople($credits->pluck('user_id'));

        $inflows = $credits->take(self::INFLOW_LIMIT)->map(function ($t) use ($people) {
            return [
                'type' => $t->type,
                'type_label' => self::TYPE_LABELS[$t->type] ?? $t->type,
                'amount' => (int) $t->amount,
                'unit' => 'points',
                'direction' => 'in',
                'description' => $t->metadata['note'] ?? $t->metadata['purpose'] ?? null,
                'created_at' => optional($t->created_at)->toIso8601String(),
                'counterparty' => $this->person($t->user_id ? $people->get($t->user_id) : null),
            ];
        })->values()->all();

        $funders = $this->funders($credits, $people, $requesterId);

        $totalCredited = (int) $credits->sum('amount');
        $userFunded = $credits->filter(fn ($t) => ! empty($t->user_id));
        $userFundedPoints = (int) $userFunded->sum('amount');

        $topFunderPoints = $funders[0]['points'] ?? 0;
        $topCoverage = $withdrawalAmount > 0 ? round($topFunderPoints / $withdrawalAmount, 4) : 0.0;
        $selfFunded = collect($funders)->firstWhere('is_requester', true);

        return [
            'scope' => $scope,
            'window_days' => $windowDays,
            'window_from' => $since->toIso8601String(),
            'window_to' => $until->toIso8601String(),
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawalAmount,
                'unit' => 'points',
                'created_at' => optional($withdrawal->created_at)->toIso8601String(),
                'requester' => $this->person($withdrawal->requester ?? User::find($requesterId)),
            ],
            'summary' => [
                'total_credited_points' => $totalCredited,
                'credit_count' => $credits->count(),
                'user_funded_points' => $userFundedPoints,
                'user_funded_count' => $userFunded->count(),
                'top_funder_coverage_percent' => (int) round($topCoverage * 100),
                'funders' => $funders,
            ],
            'inflows' => $inflows,
            'risk' => $this->assessRisk($withdrawalAmount, $funders, $topCoverage, (bool) $selfFunded, $selfFunded),
        ];
    }

    /**
     * Roll credits up per funding user (only credits carrying a user_id — a real
     * counterparty, not platform/ad revenue), largest first.
     */
    private function funders(Collection $credits, Collection $people, int $requesterId): array
    {
        $map = [];

        foreach ($credits as $t) {
            $id = $t->user_id;
            if (! $id) {
                continue;
            }
            if (! isset($map[$id])) {
                $map[$id] = [
                    'user' => $this->person($people->get($id)) ?? ['id' => $id, 'name' => 'ผู้ใช้ #'.$id, 'username' => null, 'avatar' => null],
                    'points' => 0,
                    'count' => 0,
                    'is_requester' => (int) $id === $requesterId,
                ];
            }
            $map[$id]['points'] += (int) $t->amount;
            $map[$id]['count']++;
        }

        return collect($map)->sortByDesc('points')->values()->all();
    }

    private function resolvePeople(Collection $userIds): Collection
    {
        $ids = $userIds->filter()->unique()->values();

        return $ids->isEmpty() ? collect() : User::whereIn('id', $ids)->get()->keyBy('id');
    }

    private function person(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar' => $user->profile_photo_url ?? $user->avatar ?? null,
        ];
    }

    /**
     * Explainable risk: self-funding by the requester, or a single account
     * supplying most of the withdrawal, are the point-side fraud tells.
     */
    private function assessRisk(int $withdrawalAmount, array $funders, float $topCoverage, bool $selfFunded, ?array $selfFunder): array
    {
        $flags = [];
        $reasons = [];
        $coveragePct = (int) round($topCoverage * 100);

        if ($selfFunded && $selfFunder) {
            $flags[] = 'self_funded';
            $selfPct = $withdrawalAmount > 0 ? (int) round(($selfFunder['points'] / $withdrawalAmount) * 100) : 0;
            $reasons[] = 'ผู้ขอถอนเป็นผู้เติมแต้มเข้าบัญชีนี้เอง (~'.number_format($selfFunder['points'])." แต้ม ≈ {$selfPct}% ของยอดถอน) — ควรตรวจสอบว่าเป็นการปั่นแต้มเข้าบัญชีตนเองแล้วถอนหรือไม่";
        }

        if (count($funders) === 1 && ! empty($funders)) {
            $flags[] = 'single_funder';
            $reasons[] = 'แต้มที่รับเข้าในช่วงนี้มาจากผู้ใช้เพียงคนเดียว';
        }

        if ($topCoverage >= self::HIGH_RISK_COVERAGE && ! empty($funders)) {
            $flags[] = 'concentrated_funding';
            $top = $funders[0];
            $reasons[] = "ผู้เติมแต้มรายใหญ่สุด ({$top['user']['name']}) คิดเป็น {$coveragePct}% ของยอดถอน — เงินที่ถอนกระจุกตัวจากแหล่งเดียว";
        }

        if ($selfFunded || $topCoverage >= self::HIGH_RISK_COVERAGE) {
            $level = 'high';
        } elseif (! empty($funders)) {
            $level = 'medium';
        } else {
            $level = 'low';
            $reasons[] = 'ไม่พบการรับแต้มจากผู้ใช้รายบุคคลในช่วงที่ตรวจสอบ (มาจากระบบ/โฆษณา หรือไม่มีการรับเข้า)';
        }

        return [
            'level' => $level,
            'top_funder_coverage_percent' => $coveragePct,
            'flags' => $flags,
            'reasons' => $reasons,
        ];
    }
}
