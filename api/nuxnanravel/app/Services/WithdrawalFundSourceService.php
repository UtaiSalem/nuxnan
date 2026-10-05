<?php

namespace App\Services;

use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

        // Resolve every counterparty in one query.
        $counterpartyIds = collect()
            ->merge($pointsIn->pluck('source_id'))
            ->merge($walletIn->map(fn (WalletTransaction $t) => $t->metadata['from_user_id'] ?? null))
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
            ],
            'inflows' => $timeline,
            'risk' => $this->assessRisk($incomingValueThb, $withdrawalAmount, $coverage, $pointsIn->count(), $walletIn->count(), count($senders)),
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
    private function assessRisk(float $incomingValueThb, float $withdrawalAmount, float $coverage, int $pointsInCount, int $walletInCount, int $senderCount): array
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

        $hasIncoming = $incomingValueThb > 0 && ($pointsInCount > 0 || $walletInCount > 0);
        $coveragePct = (int) round($coverage * 100);

        if ($hasIncoming && $coverage >= self::HIGH_RISK_COVERAGE) {
            $flags[] = 'funded_by_incoming_transfers';
            $reasons[] = 'ยอดที่รับโอนเข้ามา (~'.number_format($incomingValueThb, 2)." บาท) คิดเป็น {$coveragePct}% ของยอดถอน — เงินส่วนใหญ่ที่ถอนมาจากการรับโอน";
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
