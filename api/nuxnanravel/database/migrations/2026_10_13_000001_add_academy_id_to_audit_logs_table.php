<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เมนู #22 ประวัติกิจกรรม (Q2) — ให้ audit_logs ผูกโรงเรียนได้
 *
 * เดิม audit_logs ไม่มี academy_id จึงทำ "ประวัติการแก้ไขทั้งโรงเรียน" ไม่ได้
 * (getEntityLogs scope ได้แค่ราย entity ผ่าน ACADEMY_SCOPED_ENTITIES)
 *
 * คอลัมน์นี้ nullable โดยตั้งใจ — ปลอดภัย/ย้อนกลับได้ ไม่ต้อง backfill ตารางใหญ่ตอน migrate
 * AuditLogService::log() จะเติมค่าให้ log ใหม่อัตโนมัติเมื่อ entity มี academy_id
 * (log เก่าจะเป็น null → ประวัติการแก้ไขในหน้า #22 เริ่มนับจากนี้ไป · backfill เป็นงาน owner-gated แยก)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'academy_id')) {
                $table->unsignedBigInteger('academy_id')->nullable()->after('user_id');
                $table->index(['academy_id', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'academy_id')) {
                $table->dropIndex(['academy_id', 'created_at']);
                $table->dropColumn('academy_id');
            }
        });
    }
};
