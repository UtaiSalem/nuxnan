<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ทำให้ schema ย้ายข้ามเครื่องด้วย dump ได้โดยไม่พัง
 *
 * 1) แปลงตาราง MyISAM ที่เหลือทั้งหมดเป็น InnoDB — Laravel สมมติว่าทุกตารางรองรับ
 *    transaction และ row lock ตาราง MyISAM ทำให้ DB::transaction() เงียบ ๆ ไม่ roll back
 *    (ชุดตารางการเงินถูกแปลงไปแล้วใน 2026_08_05_105000 ตัวนี้เก็บกวาดที่เหลือ)
 *
 * 2) สร้าง view ใหม่ด้วย SQL SECURITY INVOKER — view แบบ DEFINER จะล้มด้วย error 1449
 *    ทันทีที่ dump ถูก import ไปเครื่องที่ไม่มี user เจ้าของเดิม ซึ่งเกิดขึ้นแล้วทั้งขา
 *    production -> local (user @'%') และจะเกิดขากลับ local -> production (user @localhost)
 *    INVOKER ไม่ตรวจว่า definer มีอยู่จริง จึงย้ายข้ามเครื่องได้
 */
return new class extends Migration
{
    public function up(): void
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

        DB::statement('DROP VIEW IF EXISTS user_daily_claim_counters');
        DB::statement("CREATE SQL SECURITY INVOKER VIEW user_daily_claim_counters AS SELECT user_id, created_at AS claimed_at, 'public' AS tier FROM donate_recipients UNION ALL SELECT claimer_id AS user_id, claimed_at, 'academy' AS tier FROM academy_donate_claims UNION ALL SELECT claimer_id AS user_id, claimed_at, 'course' AS tier FROM course_donate_claims");
    }

    public function down(): void
    {
        // Intentionally empty: neither MyISAM nor a DEFINER-bound view was a deliberate
        // choice, and restoring either would reintroduce a silent failure mode.
    }
};
