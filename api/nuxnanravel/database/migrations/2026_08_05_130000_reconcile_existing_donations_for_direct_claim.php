<?php

use App\Models\AcademyPointTransaction;
use App\Models\CoursePointCampaign;
use App\Models\CoursePointTransaction;
use App\Services\CoursePointAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ปรับข้อมูลเงินบริจาคที่มีอยู่ก่อนหน้าให้เข้ากับระบบกดรับโดยตรง
 *
 * เงินบริจาคที่เข้ามาก่อนโค้ดใหม่ยังไม่ถูกกันยอด (`reserved_balance`) เจ้าของจึงถอน
 * แต้มที่เป็นของนักเรียนออกไปได้ และแคมเปญ manual_claim ที่ค้างอยู่ก็แย่งแต้มก้อน
 * เดียวกันไปแจกซ้ำได้ migration นี้ปิดช่องทั้งสอง
 *
 * ปลอดภัยต่อการ deploy:
 *   - ข้ามบัญชีที่เคยมีรายการ donation_reserve แล้ว (รันซ้ำได้)
 *   - ถ้ายอดคงเหลือในกองไม่พอกันยอด จะ "ข้ามพร้อมบันทึก log" ไม่ throw
 *     เพราะเป็นเคสที่ต้องให้คนตัดสินใจ ไม่ควรบล็อก deploy
 *   - แตะเฉพาะแคมเปญชนิด manual_claim แคมเปญรางวัลบทเรียน/ควิซไม่ถูกแตะ
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->reconcileCourses();
        $this->reconcileAcademies();
    }

    public function down(): void
    {
        // Intentionally empty: releasing the reservation would hand students' points
        // back to the owner, and reopening ended campaigns cannot be done safely.
    }

    private function reconcileCourses(): void
    {
        $outstandingByCourse = DB::table('course_donates')
            ->where('donation_type', 'point')
            ->whereIn('status', ['approved', 'completed'])
            ->where('remaining_points', '>', 0)
            ->groupBy('course_id')
            ->selectRaw('course_id, SUM(remaining_points) AS outstanding')
            ->pluck('outstanding', 'course_id');

        foreach ($outstandingByCourse as $courseId => $outstanding) {
            $outstanding = (int) $outstanding;
            $account = DB::table('course_point_accounts')->where('course_id', $courseId)->first();

            if (! $account) {
                Log::warning('reconcile: course has donations but no point account', ['course_id' => $courseId]);

                continue;
            }

            $alreadyDone = DB::table('course_point_transactions')
                ->where('course_point_account_id', $account->id)
                ->where('type', CoursePointTransaction::TYPE_DONATION_RESERVE)
                ->exists();

            if ($alreadyDone) {
                continue;
            }

            $this->endManualCampaigns((int) $courseId);

            // Re-read: ending a capped campaign releases part of the reservation.
            $account = DB::table('course_point_accounts')->where('course_id', $courseId)->first();
            $available = (int) $account->balance - (int) $account->reserved_balance;

            if ($outstanding > $available) {
                Log::warning('reconcile: skipped course, fund cannot cover outstanding donations', [
                    'course_id' => $courseId,
                    'outstanding' => $outstanding,
                    'available' => $available,
                ]);

                continue;
            }

            DB::table('course_point_accounts')->where('id', $account->id)->update([
                'reserved_balance' => (int) $account->reserved_balance + $outstanding,
                'version' => (int) $account->version + 1,
                'updated_at' => now(),
            ]);

            DB::table('course_point_transactions')->insert([
                'course_point_account_id' => $account->id,
                'course_id' => $courseId,
                'user_id' => null,
                'type' => CoursePointTransaction::TYPE_DONATION_RESERVE,
                'amount' => $outstanding,
                'balance_before' => (int) $account->balance,
                'balance_after' => (int) $account->balance,
                'metadata' => json_encode(['reason' => 'backfill_existing_donations']),
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('reconcile: reserved outstanding course donations', [
                'course_id' => $courseId,
                'reserved' => $outstanding,
            ]);
        }
    }

    private function endManualCampaigns(int $courseId): void
    {
        $open = DB::table('course_point_campaigns')
            ->where('course_id', $courseId)
            ->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_MANUAL)
            ->whereIn('status', [CoursePointCampaign::STATUS_ACTIVE, CoursePointCampaign::STATUS_PAUSED])
            ->pluck('id');

        foreach ($open as $campaignId) {
            try {
                app(CoursePointAccountService::class)->cancelCampaign($campaignId);
            } catch (Throwable $e) {
                Log::warning('reconcile: could not end manual campaign', [
                    'campaign_id' => $campaignId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function reconcileAcademies(): void
    {
        $outstandingByAcademy = DB::table('academy_donates')
            ->where('donation_type', 'point')
            ->whereIn('status', ['approved', 'completed'])
            ->where('remaining_points', '>', 0)
            ->groupBy('academy_id')
            ->selectRaw('academy_id, SUM(remaining_points) AS outstanding')
            ->pluck('outstanding', 'academy_id');

        foreach ($outstandingByAcademy as $academyId => $outstanding) {
            $outstanding = (int) $outstanding;
            $account = DB::table('academy_point_accounts')->where('academy_id', $academyId)->first();

            if (! $account) {
                Log::warning('reconcile: academy has donations but no point account', ['academy_id' => $academyId]);

                continue;
            }

            $alreadyDone = DB::table('academy_point_transactions')
                ->where('academy_point_account_id', $account->id)
                ->where('type', AcademyPointTransaction::TYPE_DONATION_RESERVE)
                ->exists();

            if ($alreadyDone) {
                continue;
            }

            $available = (int) $account->balance - (int) $account->reserved_balance;

            if ($outstanding > $available) {
                Log::warning('reconcile: skipped academy, fund cannot cover outstanding donations', [
                    'academy_id' => $academyId,
                    'outstanding' => $outstanding,
                    'available' => $available,
                ]);

                continue;
            }

            DB::table('academy_point_accounts')->where('id', $account->id)->update([
                'reserved_balance' => (int) $account->reserved_balance + $outstanding,
                'version' => (int) $account->version + 1,
                'updated_at' => now(),
            ]);

            DB::table('academy_point_transactions')->insert([
                'academy_point_account_id' => $account->id,
                'academy_id' => $academyId,
                'user_id' => null,
                'type' => AcademyPointTransaction::TYPE_DONATION_RESERVE,
                'amount' => $outstanding,
                'balance_before' => (int) $account->balance,
                'balance_after' => (int) $account->balance,
                'metadata' => json_encode(['reason' => 'backfill_existing_donations']),
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('reconcile: reserved outstanding academy donations', [
                'academy_id' => $academyId,
                'reserved' => $outstanding,
            ]);
        }
    }
};
