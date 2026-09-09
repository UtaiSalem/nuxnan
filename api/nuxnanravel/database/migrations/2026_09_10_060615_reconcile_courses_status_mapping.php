<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ชำระนิยาม courses.status ให้ตรงกันทั้งระบบ
 *
 * ก่อนหน้านี้มีนิยามขัดกัน 3 แบบ (schema comment / lifecycleState / settings.vue)
 * canonical ใหม่ (ตรงกับ UI settings.vue และ Course::STATUS_*):
 *   1 = published (เผยแพร่)
 *   2 = draft (ฉบับร่าง)
 *   3 = archived (เก็บถาวร)
 *
 * ค่าเดิม status = 3 หมายถึง "Completed" (ตาม comment เดิม) แต่ lifecycleState
 * ปฏิบัติกับมันเป็น Active มาตลอด (เพราะ 3 ไม่เข้า branch 2/4) — ย้ายไป 1 (published)
 * เพื่อคงพฤติกรรม Active เดิมไว้ ไม่ให้คอร์สหายหลังเปลี่ยนความหมายของ 3 เป็น archived
 */
return new class extends Migration
{
    public function up(): void
    {
        // คง Active เดิม: status=3 (Completed เดิม, เคยถูกมองเป็น Active) -> 1 (published)
        DB::table('courses')->where('status', 3)->update(['status' => 1]);

        // อัปเดต comment ของ column ให้ตรงนิยามใหม่ (คง type/null/default เดิม: TINYINT NOT NULL DEFAULT 1)
        // เฉพาะ MySQL — SQLite (test DB) ไม่รองรับ ALTER ... MODIFY ... COMMENT
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `courses` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=published, 2=draft, 3=archived'");
        }
    }

    public function down(): void
    {
        // คืน comment เดิม — ส่วนข้อมูลที่ย้าย 3->1 ไม่สามารถระบุกลับได้ว่าแถวใดเคยเป็น 3
        // (การ reinterpret ค่าเป็น one-way โดยธรรมชาติ) จึงคืนได้แค่ comment
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `courses` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT '[ 1 => ''Active'', 2 => ''Draft'', 3 => ''Completed'', 4 => ''Closed'', ]'");
        }
    }
};
