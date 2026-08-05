<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แปลงตารางฝั่งการเงิน/บริจาคจาก MyISAM เป็น InnoDB
 *
 * DB dump ที่ import มามีตารางเหล่านี้เป็น MyISAM ซึ่ง:
 *   - ไม่รองรับ transaction → `DB::transaction()` ในโค้ด claim/donation ไม่ roll back จริง
 *   - ไม่รองรับ row lock → `lockForUpdate()` ไม่กันการแย่งกันเขียน
 *   - ไม่รองรับ foreign key → constraint ที่ migration อื่นพยายามสร้างจะล้ม (error 1824)
 *
 * ทั้งสามข้อทำให้การรับประกันความถูกต้องของบัญชีแต้มเป็นแค่ภาพลวง
 * ตรวจ engine ก่อนทุกตาราง → รันซ้ำได้ และรันบน DB ที่เป็น InnoDB อยู่แล้วก็ไม่มีผล
 */
return new class extends Migration
{
    private array $tables = [
        'academy_donates',
        'academy_point_accounts',
        'academy_point_transactions',
        'course_donates',
        'course_point_withdrawal_requests',
        'revenue_share_policies',
        'risk_events',
    ];

    public function up(): void
    {
        // Storage engines are a MySQL concept; the test suite runs on SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->tables as $table) {
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

    public function down(): void
    {
        // Intentionally empty: MyISAM was never a deliberate choice for these tables,
        // and converting back would reintroduce silent transaction loss.
    }
};
