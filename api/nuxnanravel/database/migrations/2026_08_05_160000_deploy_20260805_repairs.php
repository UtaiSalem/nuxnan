<?php

use App\Models\AcademyPointTransaction;
use App\Models\CoursePointCampaign;
use App\Models\CoursePointTransaction;
use App\Services\CoursePointAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * งานซ่อม schema + ข้อมูลของวันที่ 2026-08-05 ทั้งหมด รวมไว้ในไฟล์เดียวเพื่อรัน production ครั้งเดียว
 *
 * รวมจาก 7 migration ที่เคยแยกกัน (rollback ออกจาก dev แล้วยุบเมื่อ 2026-08-05):
 *   000001 repair_schema_drift_from_imported_dump
 *   100000 create_course_donate_claims_table
 *   105000 convert_money_tables_to_innodb
 *   110000 repair_academy_donate_claims_constraints
 *   120000 make_schema_portable_for_dumps
 *   130000 reconcile_existing_donations_for_direct_claim
 *   140000 repair_student_card_data
 *
 * ── ⚠️ ลำดับถูกแก้ระหว่างรวม ไม่ใช่แค่ต่อไฟล์กัน ────────────────────────────
 * ของเดิมสร้าง `course_donate_claims` (ขั้นที่ 3 เดิม = 100000) **ก่อน** แปลงตารางเป็น
 * InnoDB (105000/120000) ตารางนั้นมี foreign key ชี้ไป points_transactions และ
 * course_point_transactions — ถ้าตารางปลายทางยังเป็น MyISAM อยู่ FK จะสร้างไม่ได้
 * (error 1824) บน dev รอดมาได้เพราะตารางปลายทางเป็น InnoDB อยู่แล้ว แต่ production
 * คือเครื่องต้นทางของ dump ที่มี MyISAM ปนอยู่จริง จึงเสี่ยงล้มตอน deploy
 *
 * ลำดับใหม่จึงเป็น: แปลง engine ให้หมดก่อน → เติมคอลัมน์ที่หาย → สร้างตาราง+FK →
 * ซ่อม index/FK ที่ค้าง → สร้าง view → ปรับข้อมูลบริจาค → ซ่อมข้อมูลบัตรนักเรียน
 *
 * อีกจุดที่รวมแล้วดีขึ้น: view `user_daily_claim_counters` เดิมถูก DROP+CREATE สองรอบ
 * (100000 แล้ว 120000 ทับอีกที) ตอนนี้สร้างรอบเดียวด้วย SQL SECURITY INVOKER
 *
 * ── ทุกขั้นรันซ้ำได้ ────────────────────────────────────────────────────────
 * ตรวจสถานะก่อนทำทุกครั้ง (hasColumn / hasTable / เช็ค ENGINE / เช็ค transaction เดิม)
 * รันบน DB ที่ซ่อมไปแล้วจะไม่มีผลอะไร และข้ามงานที่เป็น MySQL-only เมื่อรันบน SQLite (เทสต์)
 */
return new class extends Migration
{
    /**
     * ตารางฝั่งการเงิน/บริจาคที่ต้องเป็น InnoDB ให้ได้ก่อนใคร
     *
     * MyISAM ไม่รองรับ transaction / row lock / foreign key ทำให้ `DB::transaction()`
     * และ `lockForUpdate()` ในโค้ด claim เป็นแค่ภาพลวง — บัญชีแต้มพังเงียบ ๆ ได้
     */
    private array $moneyTables = [
        'academy_donates',
        'academy_point_accounts',
        'academy_point_transactions',
        'course_donates',
        'course_point_withdrawal_requests',
        'revenue_share_policies',
        'risk_events',
    ];

    private const BACKUP_IDENTITY = 'bk_student_card_identity_20260805';

    private const BACKUP_BIRTH_STRING = 'bk_student_card_birth_date_string_20260805';

    /**
     * ต้นทางกว้างกว่าปลายทาง (students.student_id varchar(20) → student_cards.student_number
     * varchar(8)) และ MySQL เปิด STRICT_TRANS_TABLES ถ้าไม่กันไว้ migration จะ error
     * กลางคันบน production ที่ข้อมูลไม่เหมือน dev — เลือกข้ามแถวนั้นแล้วรายงานแทนการล้มทั้งชุด
     */
    private const MAX_STUDENT_NUMBER = 8;

    private const MAX_NATIONAL_ID = 13;

    public function up(): void
    {
        // ── ขั้นที่ 1: engine ต้องพร้อมก่อน ไม่งั้น FK ในขั้นที่ 3-4 สร้างไม่ได้
        $this->convertMoneyTablesToInnodb();
        $this->convertRemainingMyisamTables();

        // ── ขั้นที่ 2: เติมคอลัมน์ที่ dump ทำหาย (บางตัวมีขั้นถัดไปเรียกใช้)
        $this->repairSchemaDriftFromImportedDump();

        // ── ขั้นที่ 3-5: ตาราง claim + index/FK ที่ค้าง + view
        $this->createCourseDonateClaimsTable();
        $this->repairAcademyDonateClaimsConstraints();
        $this->recreateClaimCountersView();

        // ── ขั้นที่ 6: ปรับข้อมูลบริจาคเดิมให้เข้ากับระบบกดรับโดยตรง
        //    ต้องอยู่หลังขั้นที่ 2 เพราะอ่าน course_donates.remaining_points ที่เพิ่งถูกเติม
        $this->reconcileExistingDonationsForDirectClaim();

        // ── ขั้นที่ 7: ซ่อมข้อมูลบัตรนักเรียนให้ตรงกับทะเบียนนักเรียน
        $this->repairStudentCardData();
    }

    /**
     * ย้อนได้เฉพาะส่วนที่ย้อนแล้วปลอดภัย
     *
     * ส่วนที่ **ไม่** ย้อน และเหตุผล:
     *   - engine InnoDB → การกลับไป MyISAM คือการเอา silent transaction loss กลับมา
     *   - คอลัมน์ที่เติมใน repairSchemaDrift → เป็นของ migration ตัวอื่น down() ของตัวนั้น
     *     drop อยู่แล้ว ถ้า drop ซ้ำที่นี่ การ rollback ยาวจะพังตอนวิ่งถึงเจ้าของจริง
     *   - index/FK ที่ซ่อมให้ academy_donate_claims → ถอดออกคือการเอาตารางพังกลับมา
     *   - การกันยอด reserved_balance → ปลดออกคือการคืนแต้มของนักเรียนให้เจ้าของ
     *   - `course_donate_claims` → drop เฉพาะตอนยังไม่มีแถว ถ้ามี claim จริงแล้วจะไม่แตะ
     *     (ของเดิม 100000 drop ทิ้งทุกกรณี ซึ่งเป็นกับดักบน production)
     */
    public function down(): void
    {
        $this->restoreStudentCardData();

        if (Schema::hasTable('course_donate_claims')) {
            $claimCount = DB::table('course_donate_claims')->count();

            if ($claimCount > 0) {
                echo sprintf(
                    "  [เก็บไว้] course_donate_claims มี %d แถว จึงไม่ drop — ลบเองถ้าตั้งใจจริง\n",
                    $claimCount
                );
            } else {
                Schema::drop('course_donate_claims');
                $this->recreateClaimCountersView();
            }
        }
    }

    // ================================================================
    // ขั้นที่ 1 — storage engine
    // ================================================================

    private function convertMoneyTablesToInnodb(): void
    {
        // Storage engines are a MySQL concept; the test suite runs on SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->moneyTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $engine = DB::table('information_schema.TABLES')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $table)
                ->value('ENGINE');

            if (strtoupper((string) $engine) === 'INNODB') {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` ENGINE = InnoDB");
        }
    }

    /**
     * เก็บกวาด MyISAM ที่เหลือทั้ง DB — Laravel สมมติว่าทุกตารางรองรับ transaction
     * และ row lock ตาราง MyISAM ทำให้ DB::transaction() เงียบ ๆ ไม่ roll back
     */
    private function convertRemainingMyisamTables(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $myisam = DB::table('information_schema.TABLES')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->where('ENGINE', 'MyISAM')
            ->pluck('TABLE_NAME');

        foreach ($myisam as $table) {
            DB::statement("ALTER TABLE `{$table}` ENGINE = InnoDB");
        }
    }

    // ================================================================
    // ขั้นที่ 2 — คอลัมน์ที่หายไปกับ dump
    // ================================================================

    /**
     * ตาราง `migrations` ระบุว่า migration เหล่านี้รันไปแล้ว (batch 47 รวด 227 ตัว)
     * แต่คอลัมน์ที่มันเพิ่มไม่มีอยู่จริงใน DB — `php artisan migrate` จึงข้ามตลอด
     * ตรงนี้เติมเฉพาะคอลัมน์ที่มีโค้ดใช้งานจริงกลับเข้าไป
     */
    private function repairSchemaDriftFromImportedDump(): void
    {
        // 2026_07_23_180000_add_donor_view_to_course_claims
        if (! Schema::hasColumn('course_donates', 'remaining_points')) {
            Schema::table('course_donates', function (Blueprint $table) {
                $table->unsignedBigInteger('remaining_points')->nullable()->default(0)->after('points_amount');
            });

            DB::table('course_donates')
                ->where('donation_type', 'point')
                ->whereIn('status', ['approved', 'completed'])
                ->update(['remaining_points' => DB::raw('points_amount')]);
        }

        // 2026_01_13_150811_add_points_columns_to_polls_table
        Schema::table('polls', function (Blueprint $table) {
            if (! Schema::hasColumn('polls', 'points_pool')) {
                $table->integer('points_pool')->default(0)->after('image_url');
            }
            if (! Schema::hasColumn('polls', 'points_per_vote')) {
                $table->integer('points_per_vote')->default(0)->after('points_pool');
            }
            if (! Schema::hasColumn('polls', 'points_distributed')) {
                $table->integer('points_distributed')->default(0)->after('points_per_vote');
            }
        });

        // 2025_11_14_100000_add_logo_and_headers_to_courses_table
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'logo')) {
                $table->string('logo')->nullable()->after('cover');
            }
            if (! Schema::hasColumn('courses', 'cover_header')) {
                $table->string('cover_header')->nullable()->after('logo');
            }
            if (! Schema::hasColumn('courses', 'cover_subheader')) {
                $table->text('cover_subheader')->nullable()->after('cover_header');
            }
        });

        // 2026_02_01_010000_add_columns_to_roles_table
        $rolesNeedBackfill = ! Schema::hasColumn('roles', 'display_name');
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('roles', 'status')) {
                $table->boolean('status')->default(true)->after('description');
            }
        });

        if ($rolesNeedBackfill) {
            DB::table('roles')->whereNull('display_name')->update(['display_name' => DB::raw('name')]);
        }

        // 2026_02_05_011941_create_gamification_tables — `points` คือคอลัมน์ที่มีจริง
        // ส่วน `xp_reward` เป็นชื่อที่ Badge::$fillable กับ UserProfileResource ใช้
        if (! Schema::hasColumn('badges', 'xp_reward')) {
            Schema::table('badges', function (Blueprint $table) {
                $table->integer('xp_reward')->default(0)->after('icon');
            });

            if (Schema::hasColumn('badges', 'points')) {
                DB::table('badges')->where('points', '>', 0)->update(['xp_reward' => DB::raw('points')]);
            }
        }

        // 2025_11_27_203000_create_user_profiles_table — ชื่อเดิมฝั่ง nuxni
        // ตอนนี้ `cover_image` ทำงานแทนอยู่ เติมไว้กัน mass-assign ล้ม
        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'cover_image_url')) {
                $table->string('cover_image_url')->nullable()->after('cover_image');
            }
        });
    }

    // ================================================================
    // ขั้นที่ 3-5 — ตาราง claim, index/FK, view
    // ================================================================

    /**
     * ⚠️ ห้ามเพิ่มขั้นตอนแก้ข้อมูลไว้ในเมธอดนี้
     *
     * เงินบริจาคของวิชาแบบเก่าถือ `remaining_points` ที่ค้างอยู่ (flow แคมเปญเดิมไม่เคย
     * หักลด) ทั้งที่แต้มถูกโอนเข้ากองไปแล้วและถูกแจกออกไปบางส่วน การล้างค่าพวกนั้น
     * เงียบ ๆ จะทำให้แต้มของผู้บริจาคจริงค้างเติ่ง แต่ละ environment จึงต้อง reconcile
     * อย่างตั้งใจแทน — คืนแต้มผู้บริจาค กลับรายการ claim แล้วค่อยล้างแถว
     * ดูสคริปต์ที่ใช้กับ DB dev เมื่อ 2026-08-05 ที่ scratchpad/reverse-course-donations.php
     */
    private function createCourseDonateClaimsTable(): void
    {
        if (Schema::hasTable('course_donate_claims')) {
            return;
        }

        Schema::create('course_donate_claims', function (Blueprint $table) {
            $table->id();
            // The legacy course_donates table is not FK-compatible in some deployed schemas.
            $table->unsignedBigInteger('course_donate_id');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('claimer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('suggester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('amount_claimer')->default(210);
            $table->unsignedInteger('amount_suggester')->default(30);
            $table->unsignedInteger('amount_course')->default(20);
            $table->unsignedInteger('amount_platform')->default(10);
            $table->foreignId('claimer_transaction_id')->constrained('points_transactions')->cascadeOnDelete();
            $table->foreignId('suggester_transaction_id')->nullable()->constrained('points_transactions')->nullOnDelete();
            $table->foreignId('course_transaction_id')->constrained('course_point_transactions')->cascadeOnDelete();
            $table->foreignId('platform_transaction_id')->constrained('points_transactions')->cascadeOnDelete();
            $table->timestamp('claimed_at');
            $table->timestamps();
            // Explicit names: the auto-generated ones exceed MySQL's 64-char identifier limit.
            $table->index(['course_donate_id', 'claimer_id', 'claimed_at'], 'cdc_donate_claimer_at_idx');
            $table->index(['claimer_id', 'claimed_at'], 'cdc_claimer_at_idx');
        });
    }

    /**
     * migration ต้นทาง (2026_07_26_000002) ตั้งชื่อ index ยาวเกิน 64 ตัวอักษร จึงล้มบน
     * MySQL แล้วทิ้งตารางที่มีแต่คอลัมน์กับ PRIMARY ไว้ — ไม่มี index ไม่มี foreign key
     * และ `hasTable` guard ของมันก็ข้ามตารางนี้ตลอดไป
     */
    private function repairAcademyDonateClaimsConstraints(): void
    {
        // Uses SHOW INDEX / information_schema, which are MySQL-only. On SQLite the
        // table is always created correctly by 2026_07_26_000002, so nothing to repair.
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('academy_donate_claims')) {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM academy_donate_claims'))->pluck('Key_name')->unique();

        Schema::table('academy_donate_claims', function (Blueprint $table) use ($indexes) {
            if (! $indexes->contains('adc_donate_claimer_at_idx')) {
                $table->index(['academy_donate_id', 'claimer_id', 'claimed_at'], 'adc_donate_claimer_at_idx');
            }
            if (! $indexes->contains('adc_claimer_at_idx')) {
                $table->index(['claimer_id', 'claimed_at'], 'adc_claimer_at_idx');
            }
        });

        $existing = collect(DB::select(
            "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'academy_donate_claims'
             AND REFERENCED_TABLE_NAME IS NOT NULL"
        ))->pluck('COLUMN_NAME');

        // academy_donate_id stays without a constraint on purpose — the legacy
        // academy_donates table is not FK-compatible in some deployed schemas,
        // which is why the original migration left it out too.
        $foreignKeys = [
            'academy_id' => ['academies', 'cascade'],
            'claimer_id' => ['users', 'cascade'],
            'suggester_id' => ['users', 'null'],
            'claimer_transaction_id' => ['points_transactions', 'cascade'],
            'suggester_transaction_id' => ['points_transactions', 'null'],
            'school_transaction_id' => ['academy_point_transactions', 'cascade'],
            'platform_transaction_id' => ['points_transactions', 'cascade'],
        ];

        Schema::table('academy_donate_claims', function (Blueprint $table) use ($existing, $foreignKeys) {
            foreach ($foreignKeys as $column => [$references, $onDelete]) {
                if ($existing->contains($column)) {
                    continue;
                }
                $foreign = $table->foreign($column)->references('id')->on($references);
                $onDelete === 'cascade' ? $foreign->cascadeOnDelete() : $foreign->nullOnDelete();
            }
        });
    }

    /**
     * view แบบ DEFINER จะล้มด้วย error 1449 ทันทีที่ dump ถูก import ไปเครื่องที่ไม่มี
     * user เจ้าของเดิม ซึ่งเกิดขึ้นแล้วทั้งขา production -> local (user @'%') และจะเกิด
     * ขากลับ local -> production (user @localhost) — INVOKER ไม่ตรวจว่า definer มีอยู่จริง
     *
     * สาขา course ถูกตัดออกเมื่อถูกเรียกจาก down() หลัง drop ตาราง claim ไปแล้ว
     */
    private function recreateClaimCountersView(): void
    {
        $security = DB::getDriverName() === 'mysql' ? 'SQL SECURITY INVOKER ' : '';

        $branches = [
            "SELECT user_id, created_at AS claimed_at, 'public' AS tier FROM donate_recipients",
            "SELECT claimer_id AS user_id, claimed_at, 'academy' AS tier FROM academy_donate_claims",
        ];

        if (Schema::hasTable('course_donate_claims')) {
            $branches[] = "SELECT claimer_id AS user_id, claimed_at, 'course' AS tier FROM course_donate_claims";
        }

        DB::statement('DROP VIEW IF EXISTS user_daily_claim_counters');
        DB::statement("CREATE {$security}VIEW user_daily_claim_counters AS ".implode(' UNION ALL ', $branches));
    }

    // ================================================================
    // ขั้นที่ 6 — ปรับข้อมูลบริจาคเดิม
    // ================================================================

    /**
     * เงินบริจาคที่เข้ามาก่อนโค้ดใหม่ยังไม่ถูกกันยอด (`reserved_balance`) เจ้าของจึงถอน
     * แต้มที่เป็นของนักเรียนออกไปได้ และแคมเปญ manual_claim ที่ค้างอยู่ก็แย่งแต้มก้อน
     * เดียวกันไปแจกซ้ำได้ — ปิดช่องทั้งสอง
     *
     * ปลอดภัยต่อการ deploy: ข้ามบัญชีที่เคยมีรายการ donation_reserve แล้ว (รันซ้ำได้)
     * และถ้ายอดคงเหลือในกองไม่พอกันยอด จะข้ามพร้อมบันทึก log ไม่ throw เพราะเป็นเคส
     * ที่ต้องให้คนตัดสินใจ ไม่ควรบล็อก deploy
     */
    private function reconcileExistingDonationsForDirectClaim(): void
    {
        $this->reconcileCourses();
        $this->reconcileAcademies();
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

    // ================================================================
    // ขั้นที่ 7 — ข้อมูลบัตรนักเรียน
    // ================================================================

    /**
     *   7.1  ยึดค่าจาก students: student_number / national_id / birth_date  (บัตร active)
     *   7.2  เติมชื่ออังกฤษกลับไปที่ต้นทาง เมื่อ master ว่างแต่บัตรมีชื่อรวมช่องเดียว
     *   7.3  ล้างรูปแบบ birth_date_string ให้เป็น DD/MM/YYYY  (ทุกสถานะ)
     *
     * ที่มา (ตรวจ DB จริง 2026-08-05): บัตร active 7 ใบเก็บค่าที่ถูก "ตัดสั้น" กว่าต้นทาง
     * เช่นเลขประจำตัวประชาชน 13 หลักเหลือ 11-12 หลัก และ 1,689 แถวเก็บวันเกิดเป็น
     * MM/DD/YYYY เช่น card#2 = '06/11/2013' ทั้งที่ birth_date = 2013-06-11 (11 มิ.ย.)
     *
     * ทำไมเขียนทับได้: `App\Observers\StudentObserver::updated()` เขียนคอลัมน์ชุดนี้จาก
     * students ลงบัตร active อยู่แล้วทุกครั้งที่นักเรียนถูกแก้ไข — ตรงนี้แค่ทำให้แถวที่ยัง
     * ไม่เคยถูกแตะตั้งแต่ import ตามให้ทัน ไม่ได้สร้างสถานะใหม่
     *
     * ⚠️ เทียบวันเกิดด้วย `<=>` (null-safe) ใน SQL ห้ามเทียบใน PHP เพราะ
     * Student::date_of_birth ถูก cast เป็น Carbon แต่ StudentCard::birth_date เป็น string ดิบ
     * เทียบตรง ๆ จะ "ต่าง" ทุกแถวแล้วเขียนทับเกินจำเป็น (เคยพลาดมาแล้วตอนแก้มือบน dev)
     */
    private function repairStudentCardData(): void
    {
        // ทุกคำสั่งในขั้นนี้ใช้ไวยากรณ์เฉพาะ MySQL — `UPDATE ... INNER JOIN`, ตัวดำเนินการ
        // null-safe `<=>`, DATE_FORMAT, SUBSTRING_INDEX — ซึ่ง SQLite ที่เทสต์ใช้ parse ไม่ผ่าน
        // แล้วทำให้ migration ล้มทั้งชุด (RefreshDatabase รัน migration ทุกตัว)
        // ข้อมูลที่ต้องซ่อมอยู่บน MySQL ของจริงเท่านั้น DB เทสต์เริ่มจากว่างอยู่แล้ว
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->createStudentCardBackupTables();
        $this->snapshotIdentityRows();

        $studentNumber = $this->syncStudentNumber();
        $nationalId = $this->syncNationalId();
        $birthDate = $this->syncBirthDate();
        $englishName = $this->backfillMasterEnglishName();

        // ต้องอยู่หลัง 7.1 เสมอ เพราะ 7.1 อาจแก้ birth_date ของบางแถว
        // แล้วสตริงของแถวนั้นต้องถูก render ใหม่ตามวันที่ใหม่
        $this->snapshotBirthDateStringRows();
        $birthString = $this->normalizeBirthDateString();

        $this->reportSkippedTooLong();

        echo sprintf(
            "  student_card: student_number=%d national_id=%d birth_date=%d en_name_backfill=%d birth_date_string=%d\n",
            $studentNumber,
            $nationalId,
            $birthDate,
            $englishName,
            $birthString
        );
    }

    private function restoreStudentCardData(): void
    {
        // MySQL-only เช่นเดียวกับ up() — ตาราง backup ไม่เคยถูกสร้างบน SQLite อยู่แล้ว
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // ย้อนกลับด้านของ up(): คืนสตริงก่อน แล้วค่อยคืนค่าระบุตัวตน
        // (backup ของ 7.1 เก็บสตริงต้นฉบับของแถวตัวเองไว้ด้วย จึงต้องทับทีหลัง)
        if (Schema::hasTable(self::BACKUP_BIRTH_STRING)) {
            $backup = self::BACKUP_BIRTH_STRING;

            DB::statement("
                UPDATE student_cards c
                INNER JOIN {$backup} b ON b.card_id = c.id
                SET c.birth_date_string = b.birth_date_string
            ");

            Schema::drop(self::BACKUP_BIRTH_STRING);
        }

        if (Schema::hasTable(self::BACKUP_IDENTITY)) {
            $backup = self::BACKUP_IDENTITY;

            DB::statement("
                UPDATE student_cards c
                INNER JOIN {$backup} b ON b.card_id = c.id
                SET c.student_number    = b.student_number,
                    c.national_id       = b.national_id,
                    c.birth_date        = b.birth_date,
                    c.birth_date_string = b.birth_date_string
            ");

            // คืนเฉพาะแถวที่ up() ไป backfill ต้นทางไว้จริง ๆ ไม่อย่างนั้นจะไปล้าง
            // ชื่ออังกฤษของนักเรียนที่ถูกกรอกเพิ่มหลัง migration นี้รัน
            DB::statement("
                UPDATE students s
                INNER JOIN {$backup} b ON b.student_id = s.id
                SET s.first_name_en = b.first_name_en,
                    s.last_name_en  = b.last_name_en
                WHERE b.backfilled_english_name = 1
            ");

            Schema::drop(self::BACKUP_IDENTITY);
        }
    }

    private function createStudentCardBackupTables(): void
    {
        Schema::dropIfExists(self::BACKUP_IDENTITY);
        Schema::dropIfExists(self::BACKUP_BIRTH_STRING);

        Schema::create(self::BACKUP_IDENTITY, function (Blueprint $table) {
            $table->unsignedBigInteger('card_id')->primary();
            $table->unsignedBigInteger('student_id')->index();
            $table->string('student_number', 8)->nullable();
            $table->string('national_id', 13)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_date_string', 14)->nullable();
            $table->string('first_name_en', 100)->nullable();
            $table->string('last_name_en', 100)->nullable();
            $table->boolean('backfilled_english_name')->default(false);
            $table->timestamp('captured_at')->useCurrent();
        });

        Schema::create(self::BACKUP_BIRTH_STRING, function (Blueprint $table) {
            $table->unsignedBigInteger('card_id')->primary();
            $table->string('birth_date_string', 14)->nullable();
            $table->timestamp('captured_at')->useCurrent();
        });
    }

    /**
     * เก็บภาพก่อนแก้ของทุกแถวที่ "จะ" ถูกแตะใน 7.1-7.2
     * (เงื่อนไขต้องตรงกับ UPDATE ด้านล่างทุกประการ ไม่งั้น backup จะไม่ครบ)
     */
    private function snapshotIdentityRows(): void
    {
        $backup = self::BACKUP_IDENTITY;
        $maxNumber = self::MAX_STUDENT_NUMBER;
        $maxNid = self::MAX_NATIONAL_ID;

        DB::statement("
            INSERT INTO {$backup}
                (card_id, student_id, student_number, national_id, birth_date,
                 birth_date_string, first_name_en, last_name_en, backfilled_english_name, captured_at)
            SELECT c.id, s.id, c.student_number, c.national_id, c.birth_date,
                   c.birth_date_string, s.first_name_en, s.last_name_en,
                   CASE WHEN {$this->englishBackfillCondition()} THEN 1 ELSE 0 END,
                   NOW()
            FROM student_cards c
            INNER JOIN students s ON s.id = c.student_id
            WHERE c.student_status = 'active'
              AND (
                    (COALESCE(c.student_number, '') <> COALESCE(s.student_id, '')
                        AND CHAR_LENGTH(COALESCE(s.student_id, '')) <= {$maxNumber})
                 OR (COALESCE(c.national_id, '') <> COALESCE(s.citizen_id, '')
                        AND CHAR_LENGTH(COALESCE(s.citizen_id, '')) <= {$maxNid})
                 OR NOT (c.birth_date <=> s.date_of_birth)
                 OR {$this->englishBackfillCondition()}
              )
        ");
    }

    private function syncStudentNumber(): int
    {
        $max = self::MAX_STUDENT_NUMBER;

        return DB::update("
            UPDATE student_cards c
            INNER JOIN students s ON s.id = c.student_id
            SET c.student_number = s.student_id
            WHERE c.student_status = 'active'
              AND COALESCE(c.student_number, '') <> COALESCE(s.student_id, '')
              AND CHAR_LENGTH(COALESCE(s.student_id, '')) <= {$max}
        ");
    }

    private function syncNationalId(): int
    {
        $max = self::MAX_NATIONAL_ID;

        return DB::update("
            UPDATE student_cards c
            INNER JOIN students s ON s.id = c.student_id
            SET c.national_id = s.citizen_id
            WHERE c.student_status = 'active'
              AND COALESCE(c.national_id, '') <> COALESCE(s.citizen_id, '')
              AND CHAR_LENGTH(COALESCE(s.citizen_id, '')) <= {$max}
        ");
    }

    private function syncBirthDate(): int
    {
        return DB::update("
            UPDATE student_cards c
            INNER JOIN students s ON s.id = c.student_id
            SET c.birth_date        = s.date_of_birth,
                c.birth_date_string = CASE
                    WHEN s.date_of_birth IS NULL THEN NULL
                    ELSE DATE_FORMAT(s.date_of_birth, '%d/%m/%Y')
                END
            WHERE c.student_status = 'active'
              AND NOT (c.birth_date <=> s.date_of_birth)
        ");
    }

    /**
     * เคสกลับทาง: ต้นทางว่างทั้งชื่อและนามสกุลอังกฤษ แต่บัตรเก็บ "ชื่อ นามสกุล" รวมช่องเดียว
     * (คอลัมน์เก่าที่ import มา) — ถ้ายึด master ตรง ๆ ชื่ออังกฤษจะหายทั้งคู่ จึงแยกกลับไปเติมต้นทางแทน
     *
     * จำกัดไว้ที่ "เว้นวรรคเดียว = 2 คำ" เท่านั้น ชื่อที่มีคำกลางหรือรูปแบบแปลกให้เว้นไว้ให้คนตัดสิน
     */
    private function backfillMasterEnglishName(): int
    {
        return DB::update("
            UPDATE students s
            INNER JOIN student_cards c ON c.student_id = s.id
            SET s.first_name_en = SUBSTRING_INDEX(TRIM(c.first_name_english), ' ', 1),
                s.last_name_en  = SUBSTRING_INDEX(TRIM(c.first_name_english), ' ', -1)
            WHERE {$this->englishBackfillCondition()}
        ");
    }

    /**
     * ใช้ทั้งตอน snapshot และตอน update — ต้องเป็นนิพจน์เดียวกันเป๊ะ ไม่งั้น backup จะไม่ครบ
     */
    private function englishBackfillCondition(): string
    {
        return "(
            c.student_status = 'active'
            AND COALESCE(s.first_name_en, '') = ''
            AND COALESCE(s.last_name_en, '') = ''
            AND TRIM(COALESCE(c.first_name_english, '')) <> ''
            AND TRIM(c.first_name_english) LIKE '% %'
            AND TRIM(c.first_name_english) NOT LIKE '% % %'
            AND CHAR_LENGTH(SUBSTRING_INDEX(TRIM(c.first_name_english), ' ', 1)) <= 100
            AND CHAR_LENGTH(SUBSTRING_INDEX(TRIM(c.first_name_english), ' ', -1)) <= 100
        )";
    }

    private function snapshotBirthDateStringRows(): void
    {
        $backup = self::BACKUP_BIRTH_STRING;

        DB::statement("
            INSERT INTO {$backup} (card_id, birth_date_string, captured_at)
            SELECT id, birth_date_string, NOW()
            FROM student_cards
            WHERE birth_date IS NOT NULL
              AND NOT (birth_date_string <=> DATE_FORMAT(birth_date, '%d/%m/%Y'))
        ");
    }

    private function normalizeBirthDateString(): int
    {
        return DB::update("
            UPDATE student_cards
            SET birth_date_string = DATE_FORMAT(birth_date, '%d/%m/%Y')
            WHERE birth_date IS NOT NULL
              AND NOT (birth_date_string <=> DATE_FORMAT(birth_date, '%d/%m/%Y'))
        ");
    }

    /**
     * แถวที่ต้นทางยาวเกินคอลัมน์ปลายทาง — ข้ามไว้ ไม่เขียน แต่ต้องบอกให้รู้ว่ามี
     * เพราะเป็นข้อมูลเพี้ยนที่ยังค้างอยู่และต้องตามแก้ด้วยมือ
     */
    private function reportSkippedTooLong(): void
    {
        $skipped = DB::select('
            SELECT c.id AS card_id, s.student_id AS master_student_id, s.citizen_id AS master_citizen_id
            FROM student_cards c
            INNER JOIN students s ON s.id = c.student_id
            WHERE c.student_status = \'active\'
              AND (
                    (COALESCE(c.student_number, \'\') <> COALESCE(s.student_id, \'\')
                        AND CHAR_LENGTH(COALESCE(s.student_id, \'\')) > '.self::MAX_STUDENT_NUMBER.')
                 OR (COALESCE(c.national_id, \'\') <> COALESCE(s.citizen_id, \'\')
                        AND CHAR_LENGTH(COALESCE(s.citizen_id, \'\')) > '.self::MAX_NATIONAL_ID.')
              )
        ');

        foreach ($skipped as $row) {
            echo sprintf(
                "  [SKIPPED — ค่าต้นทางยาวเกินคอลัมน์บัตร ต้องแก้ด้วยมือ] card#%d student_id=%s citizen_id=%s\n",
                $row->card_id,
                $row->master_student_id,
                $row->master_citizen_id
            );
        }
    }
};
