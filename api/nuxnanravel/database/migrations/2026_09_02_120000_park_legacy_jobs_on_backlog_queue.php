<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * พัก job ที่ค้างสะสมไว้บนคิวชื่อ backlog แทนที่จะปล่อยให้ worker ตัวแรกที่เปิดขึ้นมา
 * ระบายรวดเดียว
 *
 * เครื่องนี้ไม่เคยมี queue worker รันเลยตั้งแต่ 2026-05-25 ทำให้ jobs สะสม
 * ProcessUsageEvent ไว้ราว 15,869 งาน การจะประมวลผลย้อนหลัง (แจกแต้ม/quest ย้อนหลัง)
 * เป็นการตัดสินใจของเจ้าของโปรเจคที่ยังไม่ได้เคาะ migration นี้จึงแค่ "ย้ายคิว"
 * ไม่ลบและไม่ประมวลผลอะไรทั้งสิ้น
 *
 * ตั้งแต่นี้ไป worker ใน dev ให้รันด้วย: php artisan queue:work --queue=default
 * ซึ่งจะไม่แตะคิว backlog
 */
return new class extends Migration
{
    private const PARKED_QUEUE = 'backlog';

    private const LIVE_QUEUE = 'default';

    public function up(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        // ตัดที่เวลา ณ ตอนรัน — งานที่ dispatch เข้ามาหลังจากนี้ต้องอยู่บน default ตามเดิม
        $cutoff = time();

        $moved = DB::table('jobs')
            ->where('queue', self::LIVE_QUEUE)
            ->where('created_at', '<=', $cutoff)
            ->update(['queue' => self::PARKED_QUEUE]);

        echo "  parked {$moved} legacy job(s) onto the '".self::PARKED_QUEUE."' queue\n";
    }

    public function down(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        $restored = DB::table('jobs')
            ->where('queue', self::PARKED_QUEUE)
            ->update(['queue' => self::LIVE_QUEUE]);

        echo "  restored {$restored} job(s) back onto the '".self::LIVE_QUEUE."' queue\n";
    }
};
