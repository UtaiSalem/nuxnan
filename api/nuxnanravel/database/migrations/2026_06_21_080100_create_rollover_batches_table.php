<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — rollover_batches
 *
 * บันทึก batch ของการเลื่อนชั้นประจำปี ใช้สำหรับ:
 * - แสดงประวัติ rollover ของแต่ละ academy
 * - รองรับ undo ภายใน 24 ชม. (Decision §10.1) — undo ปิดเมื่อ undo_closed_at != null
 *   หรือเมื่อ committed_at + 24h > now()
 * - เก็บ plan summary + totals เพื่อตรวจสอบย้อนหลัง
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rollover_batches')) {
            return;
        }

        Schema::create('rollover_batches', function (Blueprint $table) {
            $table->char('id', 36)->primary(); // UUID batch id (matches classroom_students.rollover_batch_id)

            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('from_academic_year_id');
            $table->unsignedBigInteger('to_academic_year_id');

            $table->string('status', 32)->default('committed'); // committed | undone

            $table->json('plan_summary')->nullable();
            $table->json('totals')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('committed_at')->nullable();
            $table->unsignedBigInteger('committed_by_user_id')->nullable();

            $table->timestamp('undo_closed_at')->nullable()
                ->comment('NULL = undo still permitted within 24h; SET = undo permanently closed');
            $table->timestamp('undone_at')->nullable();
            $table->unsignedBigInteger('undone_by_user_id')->nullable();

            $table->timestamps();

            $table->index('academy_id', 'rb_academy_idx');
            $table->index(['academy_id', 'from_academic_year_id', 'to_academic_year_id'], 'rb_academy_year_pair_idx');
            $table->index('committed_at', 'rb_committed_at_idx');

            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('from_academic_year_id')->references('id')->on('academic_years')->onDelete('restrict');
            $table->foreign('to_academic_year_id')->references('id')->on('academic_years')->onDelete('restrict');
            $table->foreign('committed_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('undone_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rollover_batches');
    }
};
