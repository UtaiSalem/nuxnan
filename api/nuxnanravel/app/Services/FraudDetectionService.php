<?php

namespace App\Services;

use App\Models\AcademyPointAccount;
use App\Models\CampaignDeliveryEvent;
use App\Models\CourseDonate;
use App\Models\RiskEvent;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;

class FraudDetectionService
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function scanDonationVelocity(int $windowMinutes = 60, int $threshold = 5): int
    {
        $since = Carbon::now()->subMinutes($windowMinutes);
        $created = 0;
        $rows = $this->db->table('course_donates')->select('donor_id')->selectRaw('COUNT(*) as count')->whereNotNull('donor_id')->where('created_at', '>=', $since)->groupBy('donor_id')->having('count', '>=', $threshold)->get();
        foreach ($rows as $row) {
            $key = 'donation_velocity:'.$row->donor_id.':'.Carbon::now()->format('Y-m-d-H');
            $event = RiskEvent::firstOrCreate(['deduplication_key' => $key], ['rule_name' => 'donation_velocity', 'subject_type' => User::class, 'subject_id' => $row->donor_id, 'severity' => RiskEvent::SEVERITY_MEDIUM, 'score' => 50, 'evidence' => ['count' => (int) $row->count, 'threshold' => $threshold, 'window_minutes' => $windowMinutes], 'status' => RiskEvent::STATUS_OPEN]);
            $created += $event->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    public function scanSelfDonationCluster(int $windowMinutes = 7, int $threshold = 3): int
    {
        $since = Carbon::now()->subMinutes($windowMinutes);
        $rows = $this->db->table('course_donates as d')->join('courses as c', 'c.id', '=', 'd.course_id')->select('d.donor_id', 'd.course_id')->selectRaw('COUNT(*) as count')->whereColumn('d.donor_id', 'c.user_id')->where('d.created_at', '>=', $since)->groupBy('d.donor_id', 'd.course_id')->having('count', '>=', $threshold)->get();
        $created = 0;
        foreach ($rows as $row) {
            $key = 'self_donation:'.$row->donor_id.':'.$row->course_id;
            $subjectId = CourseDonate::where('donor_id', $row->donor_id)->where('course_id', $row->course_id)->latest('id')->value('id');
            $event = RiskEvent::firstOrCreate(['deduplication_key' => $key], ['rule_name' => 'self_donation_cluster', 'subject_type' => CourseDonate::class, 'subject_id' => $subjectId, 'severity' => RiskEvent::SEVERITY_HIGH, 'score' => 75, 'evidence' => ['count' => (int) $row->count, 'threshold' => $threshold, 'window_minutes' => $windowMinutes, 'course_id' => $row->course_id], 'status' => RiskEvent::STATUS_OPEN]);
            $created += $event->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    public function scanAdFraud(int $windowHours = 24): int
    {
        $created = 0;
        $events = CampaignDeliveryEvent::whereNotNull('fraud_reason')->where('created_at', '>=', Carbon::now()->subHours($windowHours))->get();
        foreach ($events as $delivery) {
            $reason = $delivery->fraud_reason;
            $severity = ['low_visibility' => 'low', 'below_required_duration' => 'medium', 'replayed' => 'high'][$reason] ?? 'medium';
            $event = RiskEvent::firstOrCreate(['deduplication_key' => 'ad_fraud:'.$delivery->id], ['rule_name' => 'ad_fraud', 'subject_type' => CampaignDeliveryEvent::class, 'subject_id' => $delivery->id, 'severity' => $severity, 'score' => ['low' => 25, 'medium' => 50, 'high' => 75][$severity], 'evidence' => ['fraud_reason' => $reason, 'metadata' => $delivery->metadata], 'status' => RiskEvent::STATUS_OPEN]);
            $created += $event->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    /**
     * Flag any academy point account whose stored balance is negative — this
     * must never happen and would indicate a double-credit or broken debit.
     */
    public function scanAcademyNegativeBalance(): int
    {
        $created = 0;
        foreach (AcademyPointAccount::where('balance', '<', 0)->get() as $account) {
            $key = 'academy_negative_balance:'.$account->academy_id;
            $event = RiskEvent::firstOrCreate(['deduplication_key' => $key], ['rule_name' => 'academy_negative_balance', 'subject_type' => AcademyPointAccount::class, 'subject_id' => $account->id, 'severity' => RiskEvent::SEVERITY_CRITICAL, 'score' => 100, 'evidence' => ['academy_id' => $account->academy_id, 'balance' => $account->balance], 'status' => RiskEvent::STATUS_OPEN]);
            $created += $event->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    /**
     * Flag completed ad deliveries whose recorded split total does not match the
     * expected gross (duration * per-second rate). A mismatch means the reward
     * was tampered with or the policy changed mid-flight without versioning.
     */
    public function scanAdRevenuePolicy(int $windowHours = 24): int
    {
        $perSecond = (int) config('campaign.gross_reward_per_view_per_second', 20);
        $since = Carbon::now()->subHours($windowHours);
        $created = 0;
        $events = CampaignDeliveryEvent::where('status', CampaignDeliveryEvent::STATUS_COMPLETED)
            ->where('event_type', 'rewarded_view')
            ->whereNotNull('metadata->reward_splits')
            ->where('created_at', '>=', $since)
            ->get();
        foreach ($events as $delivery) {
            $splits = $delivery->metadata['reward_splits'] ?? [];
            $gross = (int) ($delivery->required_duration * $perSecond);
            $distributed = (int) ($splits['student'] ?? 0) + (int) ($splits['course'] ?? 0) + (int) ($splits['academy'] ?? 0) + (int) ($splits['platform'] ?? 0);
            if ($distributed !== $gross) {
                $key = 'ad_revenue_policy:'.$delivery->id;
                $event = RiskEvent::firstOrCreate(['deduplication_key' => $key], ['rule_name' => 'ad_revenue_policy', 'subject_type' => CampaignDeliveryEvent::class, 'subject_id' => $delivery->id, 'severity' => RiskEvent::SEVERITY_HIGH, 'score' => 85, 'evidence' => ['gross' => $gross, 'distributed' => $distributed, 'splits' => $splits, 'policy_id' => $splits['policy_id'] ?? null, 'policy_version' => $splits['policy_version'] ?? null], 'status' => RiskEvent::STATUS_OPEN]);
                $created += $event->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $created;
    }
}
