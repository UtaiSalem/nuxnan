<?php

namespace App\Services;

use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Traces where the money behind a wallet withdrawal came from so an admin can
 * judge whether the request is fraudulent before paying it out.
 *
 * The known abuse pattern is: attacker signs into a victim's account, transfers
 * the victim's POINTS to their own account (points ledger: transfer_in),
 * converts those points into wallet money (points_to_wallet conversion), then
 * withdraws the wallet balance. This service surfaces that trail — incoming
 * points transfers, incoming wallet transfers and points->wallet conversions —
 * with each counterparty resolved, plus a simple risk assessment.
 *
 * Read-only: it never mutates any ledger.
 */
class WithdrawalFundSourceService
{
    /** Points-to-THB exchange rate (mirrors PointsService::convertPointsToWallet). */
    public const POINTS_PER_THB = 1200;

    /** Default look-back window (days) before the withdrawal request. */
    public const DEFAULT_WINDOW_DAYS = 30;

    /** Hard cap on the window so a hand-crafted request cannot scan forever. */
    public const MAX_WINDOW_DAYS = 365;

    /** Most recent inflow rows returned in the timeline. */
    public const INFLOW_LIMIT = 60;

    /** Share of the withdrawal traceable to incoming transfers that trips "high" risk. */
    public const HIGH_RISK_COVERAGE = 0.5;

    /** Distinct funding senders that trips the "multiple senders" flag. */
    public const MULTIPLE_SENDERS_THRESHOLD = 3;

    /**
     * Points credited from a shared pool someone else filled (Phase 5 / D3 of
     * .agents/plans/disable-p2p-value-transfers.md). These features stay on, but
     * A can still route value to B through them — A donates/fills a pool or buys
     * B's course, B claims/sells, converts and withdraws — so they are watched
     * here at the payout gate. Value => label shown to the admin.
     */
    private const POOL_CLAIM_LABELS = [
        'donation_claim' => 'กดรับกองบริจาค',
        'donation_claim_reward_claimer' => 'กดรับกองโรงเรียน/วิชา',
        'course_manual_claim' => 'แคมเปญรายวิชา',
        'lesson_completion_reward' => 'รางวัลบทเรียน',
        'quiz_completion_reward' => 'รางวัลแบบทดสอบ',
    ];

    private const CAMPAIGN_CLAIM_SOURCES = ['course_manual_claim', 'lesson_completion_reward', 'quiz_completion_reward'];

    public function trace(WalletTransaction $withdrawal, ?int $windowDays = null): array
    {
        $windowDays = max(1, min($windowDays ?? self::DEFAULT_WINDOW_DAYS, self::MAX_WINDOW_DAYS));

        $userId = $withdrawal->user_id;
        $until = $withdrawal->created_at ? Carbon::parse($withdrawal->created_at) : Carbon::now();
        $since = $until->copy()->subDays($windowDays);

        // --- Incoming POINTS transfers (transfer_in: source_id = sender user id) ---
        $pointsIn = PointsTransaction::where('user_id', $userId)
            ->where('transaction_type', 'transfer_in')
            ->whereBetween('created_at', [$since, $until])
            ->orderByDesc('created_at')
            ->get();

        // --- Points -> wallet conversions (how points became withdrawable money) ---
        $conversions = PointsTransaction::where('user_id', $userId)
            ->where('transaction_type', 'conversion')
            ->where('source_type', 'points_to_wallet')
            ->whereBetween('created_at', [$since, $until])
            ->orderByDesc('created_at')
            ->get();

        // --- Incoming WALLET transfers (metadata->from_user_id set) ---
        $walletIn = WalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 'transfer')
            ->whereNotNull('metadata->from_user_id')
            ->whereBetween('created_at', [$since, $until])
            ->orderByDesc('created_at')
            ->get();

        // --- Indirect channels: points claimed from a pool someone else filled ---
        $poolClaims = PointsTransaction::where('user_id', $userId)
            ->where('transaction_type', 'earn')
            ->whereIn('source_type', array_keys(self::POOL_CLAIM_LABELS))
            ->whereBetween('created_at', [$since, $until])
            ->orderByDesc('created_at')
            ->get();
        // ledger row id => the user who put that value in the pool (donor / campaign creator)
        $poolFunders = $this->poolFunders($poolClaims);

        // --- Indirect channels: income from selling a course (buyer chose the price paid) ---
        $courseSales = WalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 'course_income')
            ->whereBetween('created_at', [$since, $until])
            ->orderByDesc('created_at')
            ->get();

        // Resolve every counterparty in one query.
        $counterpartyIds = collect()
            ->merge($pointsIn->pluck('source_id'))
            ->merge($walletIn->map(fn (WalletTransaction $t) => $t->metadata['from_user_id'] ?? null))
            ->merge(array_values($poolFunders))
            ->merge($courseSales->map(fn (WalletTransaction $t) => $t->metadata['buyer_id'] ?? null))
            ->filter()
            ->unique()
            ->values();

        $people = $counterpartyIds->isEmpty()
            ? collect()
            : User::whereIn('id', $counterpartyIds)->get()->keyBy('id');

        $inflows = collect();

        foreach ($pointsIn as $t) {
            $inflows->push([
                'ledger' => 'points',
                'type' => 'transfer_in',
                'type_label' => 'รับโอนแต้ม',
                'amount' => (float) $t->amount,
                'unit' => 'points',
                'value_thb' => round(((float) $t->amount) / self::POINTS_PER_THB, 2),
                'description' => $t->description,
                'created_at' => optional($t->created_at)->toIso8601String(),
                'counterparty' => $this->person($people->get($t->source_id)),
            ]);
        }

        foreach ($walletIn as $t) {
            $fromId = $t->metadata['from_user_id'] ?? null;
            $inflows->push([
                'ledger' => 'wallet',
                'type' => 'transfer',
                'type_label' => 'รับโอนเงิน',
                'amount' => (float) $t->amount,
                'unit' => 'THB',
                'value_thb' => round((float) $t->amount, 2),
                'description' => $t->description,
                'created_at' => optional($t->created_at)->toIso8601String(),
                'counterparty' => $this->person($fromId ? $people->get($fromId) : null),
            ]);
        }

        foreach ($poolClaims as $t) {
            $funderId = $poolFunders[$t->id] ?? null;
            $inflows->push([
                'ledger' => 'points',
                'type' => 'pool_claim',
                'type_label' => self::POOL_CLAIM_LABELS[$t->source_type] ?? $t->source_type,
                'amount' => (float) $t->amount,
                'unit' => 'points',
                'value_thb' => round(((float) $t->amount) / self::POINTS_PER_THB, 2),
                'description' => $t->description,
                'created_at' => optional($t->created_at)->toIso8601String(),
                'counterparty' => $this->person($funderId ? $people->get($funderId) : null),
            ]);
        }

        foreach ($courseSales as $t) {
            $buyerId = $t->metadata['buyer_id'] ?? null;
            $inflows->push([
                'ledger' => 'wallet',
                'type' => 'course_sale',
                'type_label' => 'ขายรายวิชา',
                'amount' => (float) $t->amount,
                'unit' => 'THB',
                'value_thb' => round((float) $t->amount, 2),
                'description' => $t->description,
                'created_at' => optional($t->created_at)->toIso8601String(),
                'counterparty' => $this->person($buyerId ? $people->get($buyerId) : null),
            ]);
        }

        // Conversions are shown for the trail but are NOT new external value
        // (they move the user's own points into their own wallet), so they are
        // excluded from the incoming-value risk total to avoid double counting.
        foreach ($conversions as $t) {
            $walletAmount = $t->metadata['wallet_amount'] ?? (((float) $t->amount) / self::POINTS_PER_THB);
            $inflows->push([
                'ledger' => 'points',
                'type' => 'conversion',
                'type_label' => 'แปลงแต้มเป็นเงิน',
                'amount' => (float) $t->amount,
                'unit' => 'points',
                'value_thb' => round((float) $walletAmount, 2),
                'description' => $t->description,
                'created_at' => optional($t->created_at)->toIso8601String(),
                'counterparty' => null,
            ]);
        }

        $timeline = $inflows
            ->sortByDesc('created_at')
            ->take(self::INFLOW_LIMIT)
            ->values()
            ->all();

        // --- Aggregates ---
        $pointsInPoints = (float) $pointsIn->sum('amount');
        $pointsInThb = round($pointsInPoints / self::POINTS_PER_THB, 2);
        $walletInThb = round((float) $walletIn->sum('amount'), 2);
        $convertedPoints = (float) $conversions->sum('amount');
        $convertedThb = round($conversions->sum(function (PointsTransaction $t) {
            return (float) ($t->metadata['wallet_amount'] ?? (((float) $t->amount) / self::POINTS_PER_THB));
        }), 2);

        $senders = $this->senders($pointsIn, $walletIn, $people);

        $poolClaimPoints = (float) $poolClaims->sum('amount');
        $poolClaimThb = round($poolClaimPoints / self::POINTS_PER_THB, 2);
        $courseSalesThb = round((float) $courseSales->sum('amount'), 2);
        $topIndirect = $this->topIndirectFunder($poolClaims, $poolFunders, $courseSales, $people, $userId);

        $withdrawalAmount = (float) $withdrawal->amount;
        $incomingValueThb = round($pointsInThb + $walletInThb, 2);
        $coverage = $withdrawalAmount > 0 ? round($incomingValueThb / $withdrawalAmount, 4) : 0.0;

        return [
            'window_days' => $windowDays,
            'exchange_rate' => self::POINTS_PER_THB,
            'window_from' => $since->toIso8601String(),
            'window_to' => $until->toIso8601String(),
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawalAmount,
                'net_amount' => (float) ($withdrawal->net_amount ?? $withdrawal->metadata['net_amount'] ?? $withdrawalAmount),
                'created_at' => optional($withdrawal->created_at)->toIso8601String(),
            ],
            'summary' => [
                'inbound_points_transfers' => [
                    'count' => $pointsIn->count(),
                    'points' => $pointsInPoints,
                    'value_thb' => $pointsInThb,
                ],
                'inbound_wallet_transfers' => [
                    'count' => $walletIn->count(),
                    'value_thb' => $walletInThb,
                ],
                'points_converted' => [
                    'count' => $conversions->count(),
                    'points' => $convertedPoints,
                    'value_thb' => $convertedThb,
                ],
                'incoming_value_thb' => $incomingValueThb,
                'senders' => $senders,
                'pool_claims' => [
                    'count' => $poolClaims->count(),
                    'points' => $poolClaimPoints,
                    'value_thb' => $poolClaimThb,
                ],
                'course_sales' => [
                    'count' => $courseSales->count(),
                    'value_thb' => $courseSalesThb,
                ],
                'top_indirect_funder' => $topIndirect,
            ],
            'inflows' => $timeline,
            'risk' => $this->assessRisk($incomingValueThb, $withdrawalAmount, $coverage, $pointsIn->count(), $walletIn->count(), count($senders), $poolClaims->count(), $poolClaimThb, $courseSales->count(), $courseSalesThb, $topIndirect),
        ];
    }

    /**
     * Build the distinct-senders roll-up across both ledgers, keyed by user.
     */
    private function senders(Collection $pointsIn, Collection $walletIn, Collection $people): array
    {
        $map = [];

        foreach ($pointsIn as $t) {
            $id = $t->source_id;
            if (! $id) {
                continue;
            }
            $map[$id] ??= $this->newSenderRow($people->get($id), $id);
            $map[$id]['points'] += (float) $t->amount;
            $map[$id]['value_thb'] += ((float) $t->amount) / self::POINTS_PER_THB;
            $map[$id]['count']++;
        }

        foreach ($walletIn as $t) {
            $id = $t->metadata['from_user_id'] ?? null;
            if (! $id) {
                continue;
            }
            $map[$id] ??= $this->newSenderRow($people->get($id), $id);
            $map[$id]['thb'] += (float) $t->amount;
            $map[$id]['value_thb'] += (float) $t->amount;
            $map[$id]['count']++;
        }

        return collect($map)
            ->map(function (array $row) {
                $row['points'] = round($row['points'], 2);
                $row['thb'] = round($row['thb'], 2);
                $row['value_thb'] = round($row['value_thb'], 2);

                return $row;
            })
            ->sortByDesc('value_thb')
            ->values()
            ->all();
    }

    /**
     * For each pool-claim ledger row, who put that value into the pool:
     * the donor (public / academy / course donation pools) or the course admin
     * who created the campaign (campaign and lesson/quiz rewards).
     *
     * @return array<int, int> points_transactions.id => user id
     */
    private function poolFunders(Collection $poolClaims): array
    {
        if ($poolClaims->isEmpty()) {
            return [];
        }

        $funders = [];

        $public = $poolClaims->where('source_type', 'donation_claim');
        if ($public->isNotEmpty()) {
            $donors = DB::table('donates')->whereIn('id', $public->pluck('source_id')->filter()->unique())->pluck('donor_id', 'id');
            foreach ($public as $t) {
                $funders[$t->id] = $donors[$t->source_id] ?? null;
            }
        }

        // academy and course donation claims share one source_type; the claim rows
        // keep the claimer's ledger id, which tells them apart.
        $pooled = $poolClaims->where('source_type', 'donation_claim_reward_claimer')->pluck('id');
        if ($pooled->isNotEmpty()) {
            foreach (['academy_donate_claims' => ['academy_donates', 'academy_donate_id'], 'course_donate_claims' => ['course_donates', 'course_donate_id']] as $claims => [$donations, $fk]) {
                $rows = DB::table("{$claims} as c")
                    ->join("{$donations} as d", 'd.id', '=', "c.{$fk}")
                    ->whereIn('c.claimer_transaction_id', $pooled)
                    ->pluck('d.donor_id', 'c.claimer_transaction_id');
                foreach ($rows as $txId => $donorId) {
                    $funders[$txId] = $donorId;
                }
            }
        }

        $campaignRows = $poolClaims->whereIn('source_type', self::CAMPAIGN_CLAIM_SOURCES);
        if ($campaignRows->isNotEmpty()) {
            $creators = DB::table('course_point_campaigns')
                ->whereIn('id', $campaignRows->map(fn (PointsTransaction $t) => $t->metadata['campaign_id'] ?? null)->filter()->unique())
                ->pluck('created_by', 'id');
            foreach ($campaignRows as $t) {
                $funders[$t->id] = $creators[$t->metadata['campaign_id'] ?? 0] ?? null;
            }
        }

        return array_map(fn ($id) => $id === null ? null : (int) $id, $funders);
    }

    /**
     * The single other person who routed the most value to this user through an
     * indirect channel (a pool they filled, or a course they bought). Value that
     * came from the user themselves (own donation / own campaign) is ignored.
     */
    private function topIndirectFunder(Collection $poolClaims, array $poolFunders, Collection $courseSales, Collection $people, int $userId): ?array
    {
        $totals = [];

        foreach ($poolClaims as $t) {
            $id = $poolFunders[$t->id] ?? null;
            if ($id && $id !== $userId) {
                $totals[$id] = ($totals[$id] ?? 0) + ((float) $t->amount) / self::POINTS_PER_THB;
            }
        }

        foreach ($courseSales as $t) {
            $id = (int) ($t->metadata['buyer_id'] ?? 0);
            if ($id && $id !== $userId) {
                $totals[$id] = ($totals[$id] ?? 0) + (float) $t->amount;
            }
        }

        if (! $totals) {
            return null;
        }

        arsort($totals);
        $id = (int) array_key_first($totals);

        return [
            'user' => $this->person($people->get($id)) ?? ['id' => $id, 'name' => 'ผู้ใช้ #'.$id, 'username' => null, 'avatar' => null],
            'value_thb' => round($totals[$id], 2),
        ];
    }

    private function newSenderRow(?User $user, int $id): array
    {
        return [
            'user' => $this->person($user) ?? ['id' => $id, 'name' => 'ผู้ใช้ #'.$id, 'username' => null, 'avatar' => null],
            'points' => 0.0,
            'thb' => 0.0,
            'value_thb' => 0.0,
            'count' => 0,
        ];
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
     * Simple, explainable risk scoring: how much of the withdrawal can be traced
     * back to money that arrived from other users shortly before the request.
     */
    private function assessRisk(float $incomingValueThb, float $withdrawalAmount, float $coverage, int $pointsInCount, int $walletInCount, int $senderCount, int $poolClaimCount = 0, float $poolClaimThb = 0, int $courseSaleCount = 0, float $courseSalesThb = 0, ?array $topIndirect = null): array
    {
        $flags = [];
        $reasons = [];

        if ($pointsInCount > 0) {
            $flags[] = 'incoming_points_transfers';
            $reasons[] = "ผู้ขอถอนได้รับโอนแต้มจากผู้ใช้อื่น {$pointsInCount} รายการในช่วงก่อนถอน — ตรงกับรูปแบบทุจริต (รับโอนแต้ม → แปลงเป็นเงิน → ถอน)";
        }

        if ($walletInCount > 0) {
            $flags[] = 'incoming_wallet_transfers';
            $reasons[] = "ผู้ขอถอนได้รับโอนเงินจากผู้ใช้อื่น {$walletInCount} รายการในช่วงก่อนถอน";
        }

        if ($senderCount >= self::MULTIPLE_SENDERS_THRESHOLD) {
            $flags[] = 'multiple_senders';
            $reasons[] = "เงินเข้ามาจากผู้ใช้ต่างกันหลายคน ({$senderCount} คน) — ควรตรวจสอบว่าเป็นบัญชีที่ถูกสวมสิทธิ์หรือไม่";
        }

        if ($poolClaimCount > 0) {
            $flags[] = 'pool_claim_rewards';
            $reasons[] = "ได้แต้มจากการกดรับกองบริจาค/แคมเปญรายวิชา {$poolClaimCount} รายการ (~".number_format($poolClaimThb, 2).' บาท) ในช่วงก่อนถอน';
        }

        if ($courseSaleCount > 0) {
            $flags[] = 'course_sale_income';
            $reasons[] = "มีรายได้จากการขายรายวิชา {$courseSaleCount} รายการ (~".number_format($courseSalesThb, 2).' บาท) ในช่วงก่อนถอน';
        }

        // One person behind most of the withdrawal via a pool they filled or a
        // course they bought = the indirect form of "A transfers to B".
        $indirectConcentrated = $topIndirect && $withdrawalAmount > 0
            && $topIndirect['value_thb'] / $withdrawalAmount >= self::HIGH_RISK_COVERAGE;
        if ($indirectConcentrated) {
            $pct = (int) round($topIndirect['value_thb'] / $withdrawalAmount * 100);
            $flags[] = 'single_indirect_funder';
            $reasons[] = "ยอดที่ถอน ~{$pct}% (~".number_format($topIndirect['value_thb'], 2)." บาท) มาจาก {$topIndirect['user']['name']} คนเดียว ผ่านกองที่เขาเติม/รายวิชาที่เขาซื้อ — ตรวจว่าเป็นการโอนระหว่างกันทางอ้อมหรือไม่";
        }

        $hasIncoming = $incomingValueThb > 0 && ($pointsInCount > 0 || $walletInCount > 0);
        $coveragePct = (int) round($coverage * 100);

        if ($hasIncoming && $coverage >= self::HIGH_RISK_COVERAGE) {
            $flags[] = 'funded_by_incoming_transfers';
            $reasons[] = 'ยอดที่รับโอนเข้ามา (~'.number_format($incomingValueThb, 2)." บาท) คิดเป็น {$coveragePct}% ของยอดถอน — เงินส่วนใหญ่ที่ถอนมาจากการรับโอน";
            $level = 'high';
        } elseif ($indirectConcentrated) {
            $level = 'high';
        } elseif ($hasIncoming) {
            $level = 'medium';
        } else {
            $level = 'low';
            $reasons[] = 'ไม่พบการรับโอนแต้ม/เงินจากผู้ใช้อื่นในช่วงเวลาที่ตรวจสอบ';
        }

        return [
            'level' => $level,
            'incoming_value_thb' => $incomingValueThb,
            'withdrawal_amount' => $withdrawalAmount,
            'coverage_ratio' => $coverage,
            'coverage_percent' => $coveragePct,
            'flags' => $flags,
            'reasons' => $reasons,
        ];
    }
}
