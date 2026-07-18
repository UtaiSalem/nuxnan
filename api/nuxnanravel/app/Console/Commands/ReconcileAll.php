<?php

namespace App\Console\Commands;

use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\CampaignDeliveryEvent;
use App\Models\CoursePointAccount;
use App\Models\CoursePointCampaign;
use App\Models\CoursePointTransaction;
use App\Models\RiskEvent;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReconcileAll extends Command
{
    protected $signature = 'reconcile:all {--emit-risk : create RiskEvent on mismatch} {--user= : limit to one user id} {--course= : limit to one course id} {--academy= : limit to one academy id}';

    protected $description = 'Reconcile user wallets, course and academy point ledgers, and ad revenue splits';

    public function handle(): int
    {
        $checks = [
            'user_wallet' => $this->userWallets(),
            'course_balance' => $this->courseBalances(),
            'course_reserved' => $this->courseReserved(),
            'academy_balance' => $this->academyBalances(),
            'ad_revenue_gross' => $this->adRevenueGross(),
        ];
        $rows = [];
        $totalMismatches = 0;
        foreach ($checks as $name => $result) {
            $totalMismatches += count($result['mismatches']);
            $rows[] = [$name, $result['scanned'], count($result['mismatches']), implode(', ', array_slice(array_column($result['mismatches'], 'id'), 0, 5)) ?: '-'];
            if ($this->option('emit-risk')) {
                foreach ($result['mismatches'] as $mismatch) {
                    $this->emitRisk($name, $mismatch['subject'], $mismatch['stored'], $mismatch['expected'], $mismatch['diff']);
                }
            }
        }
        $this->table(['check', 'scanned', 'mismatches', 'first IDs'], $rows);

        return $totalMismatches ? self::FAILURE : self::SUCCESS;
    }

    private function userWallets(): array
    {
        $query = User::query()->select('id', 'wallet');
        if ($this->option('user')) {
            $query->whereKey($this->option('user'));
        }
        $mismatches = [];
        $query->each(function (User $user) use (&$mismatches) {
            $expected = (float) WalletTransaction::where('user_id', $user->id)->sum(DB::raw('balance_after - balance_before'));
            $stored = (float) $user->wallet;
            $diff = round($stored - $expected, 2);
            if (abs($diff) > 0.01) {
                $mismatches[] = ['id' => $user->id, 'subject' => $user, 'stored' => $stored, 'expected' => $expected, 'diff' => $diff];
            }
        });

        return ['scanned' => $query->count(), 'mismatches' => $mismatches];
    }

    private function courseBalances(): array
    {
        $query = CoursePointAccount::query();
        if ($this->option('course')) {
            $query->where('course_id', $this->option('course'));
        }
        $mismatches = [];
        $scanned = $query->count();
        $query->each(function (CoursePointAccount $account) use (&$mismatches) {
            $expected = (float) CoursePointTransaction::where('course_point_account_id', $account->id)->sum(DB::raw('balance_after - balance_before'));
            $stored = (float) $account->balance;
            $diff = round($stored - $expected, 2);
            if (abs($diff) > 0.01) {
                $mismatches[] = ['id' => $account->course_id, 'subject' => $account, 'stored' => $stored, 'expected' => $expected, 'diff' => $diff];
            }
        });

        return ['scanned' => $scanned, 'mismatches' => $mismatches];
    }

    private function courseReserved(): array
    {
        $query = CoursePointAccount::query()->where('reserved_balance', '>', 0);
        if ($this->option('course')) {
            $query->where('course_id', $this->option('course'));
        }
        $mismatches = [];
        $scanned = $query->count();
        $query->each(function (CoursePointAccount $account) use (&$mismatches) {
            $expected = (float) CoursePointCampaign::where('course_point_account_id', $account->id)->whereIn('status', ['active', 'paused'])->whereNotNull('max_claims')->selectRaw('COALESCE(SUM((max_claims-total_claimed)*points_per_claim),0) value')->value('value');
            $stored = (float) $account->reserved_balance;
            $diff = round($stored - $expected, 2);
            if (abs($diff) > 0.01) {
                $mismatches[] = ['id' => $account->course_id, 'subject' => $account, 'stored' => $stored, 'expected' => $expected, 'diff' => $diff];
            }
        });

        return ['scanned' => $scanned, 'mismatches' => $mismatches];
    }

    private function academyBalances(): array
    {
        $query = AcademyPointAccount::query();
        if ($this->option('academy')) {
            $query->where('academy_id', $this->option('academy'));
        }
        $mismatches = [];
        $scanned = $query->count();
        $query->each(function (AcademyPointAccount $account) use (&$mismatches) {
            $expected = (float) AcademyPointTransaction::where('academy_point_account_id', $account->id)->sum(DB::raw('balance_after - balance_before'));
            $stored = (float) $account->balance;
            $diff = round($stored - $expected, 2);
            if (abs($diff) > 0.01) {
                $mismatches[] = ['id' => $account->academy_id, 'subject' => $account, 'stored' => $stored, 'expected' => $expected, 'diff' => $diff];
            }
        });

        return ['scanned' => $scanned, 'mismatches' => $mismatches];
    }

    /**
     * Verify that each completed ad delivery's gross reward equals the sum of
     * its splits: viewer (student points) + course + academy + platform shares.
     * The gross is recomputed from required_duration * per-second rate so a
     * tampered metadata block is caught.
     */
    private function adRevenueGross(): array
    {
        $perSecond = (int) config('campaign.gross_reward_per_view_per_second', 20);
        $query = CampaignDeliveryEvent::where('status', CampaignDeliveryEvent::STATUS_COMPLETED)
            ->where('event_type', 'rewarded_view')
            ->whereNotNull('metadata->reward_splits');
        $mismatches = [];
        $scanned = 0;
        $query->each(function (CampaignDeliveryEvent $delivery) use (&$mismatches, &$scanned, $perSecond) {
            $scanned++;
            $splits = $delivery->metadata['reward_splits'] ?? [];
            $gross = (int) ($delivery->required_duration * $perSecond);
            $distributed = (int) ($splits['student'] ?? 0) + (int) ($splits['course'] ?? 0) + (int) ($splits['academy'] ?? 0) + (int) ($splits['platform'] ?? 0);
            if ($distributed !== $gross) {
                $mismatches[] = ['id' => $delivery->id, 'subject' => $delivery, 'stored' => $distributed, 'expected' => $gross, 'diff' => $distributed - $gross];
            }
        });

        return ['scanned' => $scanned, 'mismatches' => $mismatches];
    }

    private function emitRisk(string $check, Model $subject, float $stored, float $expected, float $diff): void
    {
        $key = 'reconcile:'.$check.':'.$subject->getKey().':'.now()->format('Y-m-d');
        RiskEvent::firstOrCreate(['deduplication_key' => $key], ['rule_name' => 'reconcile_'.$check, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(), 'severity' => 'high', 'score' => 80, 'evidence' => compact('stored', 'expected', 'diff'), 'status' => 'open']);
    }
}
