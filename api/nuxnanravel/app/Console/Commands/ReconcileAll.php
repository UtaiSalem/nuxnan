<?php

namespace App\Console\Commands;

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
    protected $signature = 'reconcile:all {--emit-risk : create RiskEvent on mismatch} {--user= : limit to one user id} {--course= : limit to one course id}';

    protected $description = 'Reconcile user wallets and course point ledgers';

    public function handle(): int
    {
        $checks = [
            'user_wallet' => $this->userWallets(),
            'course_balance' => $this->courseBalances(),
            'course_reserved' => $this->courseReserved(),
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

    private function emitRisk(string $check, Model $subject, float $stored, float $expected, float $diff): void
    {
        $key = 'reconcile:'.$check.':'.$subject->getKey().':'.now()->format('Y-m-d');
        RiskEvent::firstOrCreate(['deduplication_key' => $key], ['rule_name' => 'reconcile_'.$check, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(), 'severity' => 'high', 'score' => 80, 'evidence' => compact('stored', 'expected', 'diff'), 'status' => 'open']);
    }
}
