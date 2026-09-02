<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ทิ้งคิว gamification ที่ค้างสะสม แล้วเริ่มนับใหม่จากวันที่รัน migration นี้
 *
 * เครื่อง dev ไม่มี queue worker รันเลยตั้งแต่ 2026-05-25 ทำให้ ProcessUsageEvent
 * ค้างสะสม 16,404 งาน (ชี้ไป user_usage_events 11,943 แถวที่ไม่ซ้ำกัน)
 * เจ้าของโปรเจคตัดสินใจว่าจะไม่แจกแต้ม/XP ย้อนหลัง
 *
 * migration นี้ทำ 2 อย่าง:
 *   1. ลบ job บนคิว backlog ทิ้งทั้งหมด (สำรองไว้ที่
 *      storage/app/backups/backlog-jobs-2026-09-03.jsonl ก่อนแล้ว)
 *   2. ปิดบัญชี user_usage_events ที่ยังไม่ประมวลผล โดยเซ็ต processed_at
 *      และใส่ธง gamification_backlog_discarded_at ลงใน context
 *      เพื่อบอกตรง ๆ ว่า "ถูกทิ้ง ไม่ได้ประมวลผลจริง" ไม่ใช่ "ประมวลผลสำเร็จ"
 *
 * ไม่มีการลบแถวใน user_usage_events (เป็น audit trail กิจกรรมผู้ใช้)
 * และไม่มีการแตะแต้ม/XP/เลเวล/quest ของใครทั้งสิ้น
 */
return new class extends Migration
{
    private const PARKED_QUEUE = 'backlog';

    private const DISCARD_FLAG = 'gamification_backlog_discarded_at';

    private const BACKUP_PATH = 'storage/app/backups/backlog-jobs-2026-09-03.jsonl';

    public function up(): void
    {
        $cutoff = now();

        // 1. ทิ้ง job ที่พักไว้
        $deleted = 0;
        if (Schema::hasTable('jobs')) {
            $deleted = DB::table('jobs')->where('queue', self::PARKED_QUEUE)->delete();
        }

        // 2. ปิดบัญชี event ที่ค้าง
        $marked = 0;
        if (Schema::hasTable('user_usage_events')) {
            $ids = DB::table('user_usage_events')
                ->whereNull('processed_at')
                ->where('occurred_at', '<=', $cutoff)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            foreach (array_chunk($ids, 1000) as $chunk) {
                $rows = DB::table('user_usage_events')
                    ->whereIn('id', $chunk)
                    ->get(['id', 'context']);

                foreach ($rows as $row) {
                    $context = json_decode((string) $row->context, true);

                    if (! is_array($context)) {
                        $context = [];
                    }

                    $context[self::DISCARD_FLAG] = $cutoff->toDateTimeString();

                    DB::table('user_usage_events')
                        ->where('id', $row->id)
                        ->update([
                            'processed_at' => $cutoff,
                            'context' => json_encode((object) $context, JSON_UNESCAPED_UNICODE),
                            'updated_at' => $cutoff,
                        ]);

                    $marked++;
                }
            }
        }

        echo "  discarded {$deleted} parked job(s); closed {$marked} unprocessed usage event(s)\n";
    }

    public function down(): void
    {
        $restored = 0;

        if (Schema::hasTable('user_usage_events')) {
            $ids = DB::table('user_usage_events')
                ->where('context', 'like', '%'.self::DISCARD_FLAG.'%')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            foreach (array_chunk($ids, 1000) as $chunk) {
                $rows = DB::table('user_usage_events')
                    ->whereIn('id', $chunk)
                    ->get(['id', 'context']);

                foreach ($rows as $row) {
                    $context = json_decode((string) $row->context, true);

                    if (! is_array($context) || ! array_key_exists(self::DISCARD_FLAG, $context)) {
                        continue;
                    }

                    unset($context[self::DISCARD_FLAG]);

                    DB::table('user_usage_events')
                        ->where('id', $row->id)
                        ->update([
                            'processed_at' => null,
                            'context' => json_encode((object) $context, JSON_UNESCAPED_UNICODE),
                        ]);

                    $restored++;
                }
            }
        }

        echo "  reopened {$restored} usage event(s)\n";
        echo '  NOTE: job ที่ถูกลบกู้คืนอัตโนมัติไม่ได้ — ไฟล์สำรองอยู่ที่ '.self::BACKUP_PATH."\n";
    }
};
