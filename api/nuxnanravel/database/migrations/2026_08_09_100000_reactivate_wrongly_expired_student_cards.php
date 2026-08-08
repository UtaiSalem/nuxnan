<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ปลุกบัตรที่ถูก expire ผิดกลับมา
 *
 * ClassroomStudentObserver ซิงก์สถานะบัตรตาม classroom_students ให้อยู่แล้ว แต่ทำงาน
 * ผ่าน Eloquent event เท่านั้น migration/สคริปต์ที่เขียนด้วย query builder จึงข้ามมันได้
 * ผลคือบัตรถูกตั้งเป็น expired ทั้งที่เจ้าของยังเรียนอยู่ในห้องจริง
 *
 * ⚠️ is_active_flag เป็น generated column ห้ามเขียนค่าลงไปเอง และ uq_student_card_active
 * ยอมให้มีบัตร active ได้ใบเดียวต่อ (student_id, academy_id) จึงต้องเลือกใบเดียวเสมอ
 */
return new class extends Migration
{
    private string $batch = '2026_08_09_100000';

    public function up(): void
    {
        foreach (['student_cards', 'classroom_students', 'classrooms', 'academic_years'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        if (! Schema::hasTable('classroom_repair_backups')) {
            Schema::create('classroom_repair_backups', function (Blueprint $table) {
                $table->id();
                $table->string('batch', 64);
                $table->string('table_name', 64);
                $table->unsignedBigInteger('record_id');
                $table->json('payload');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['batch', 'table_name'], 'crb_batch_tbl_idx');
            });
        }

        DB::transaction(function () {
            $candidates = DB::table('student_cards as sc')
                ->where('sc.student_status', 'expired')
                // เจ้าของบัตรยังเรียนอยู่จริงในปีปัจจุบัน และเป็นโรงเรียนเดียวกับบัตรใบนี้
                ->whereExists(fn ($query) => $query->selectRaw('1')
                    ->from('classroom_students as cs')
                    ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
                    ->join('academic_years as ay', 'ay.id', '=', 'c.academic_year_id')
                    ->whereColumn('cs.student_id', 'sc.student_id')
                    ->whereColumn('c.academy_id', 'sc.academy_id')
                    ->where('cs.status', 'active')
                    ->where('c.status', 'active')
                    ->where('ay.is_current', 1))
                // มีบัตร active อยู่แล้วก็ไม่ต้องปลุกใบเก่า ไม่งั้นชน uq_student_card_active
                ->whereNotExists(fn ($query) => $query->selectRaw('1')
                    ->from('student_cards as other')
                    ->whereColumn('other.student_id', 'sc.student_id')
                    ->whereColumn('other.academy_id', 'sc.academy_id')
                    ->where('other.student_status', 'active'))
                ->orderByDesc('sc.academic_year_id')
                ->orderByDesc('sc.card_issue_date')
                ->orderByDesc('sc.id')
                ->get(['sc.id', 'sc.student_id', 'sc.academy_id', 'sc.student_status']);

            // ถ้ามีหลายใบเข้าเงื่อนไขในคู่เดียวกัน ปลุกได้ใบเดียว — เรียงลำดับเดียวกับ
            // ClassroomStudentObserver::reactivateCard() เพื่อให้ผลเหมือนกันทั้งสองทาง
            $selected = $candidates
                ->unique(fn ($card) => $card->student_id.'-'.$card->academy_id)
                ->values();

            if ($selected->isEmpty()) {
                Log::info('No wrongly expired student cards to reactivate', ['batch' => $this->batch]);

                return;
            }

            foreach ($selected as $card) {
                // เก็บเฉพาะคอลัมน์ที่แก้ ห้ามเก็บทั้งแถว เพราะ is_active_flag เป็น generated column
                // การ update() กลับทั้งแถวใน down() จะ error 3105
                DB::table('classroom_repair_backups')->insert([
                    'batch' => $this->batch,
                    'table_name' => 'student_cards',
                    'record_id' => $card->id,
                    'payload' => json_encode(['student_status' => $card->student_status], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);

                DB::table('student_cards')
                    ->where('id', $card->id)
                    ->update(['student_status' => 'active', 'updated_at' => now()]);
            }

            Log::info('Reactivated wrongly expired student cards', [
                'batch' => $this->batch,
                'count' => $selected->count(),
                'card_ids' => $selected->pluck('id')->values()->all(),
            ]);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('classroom_repair_backups') || ! Schema::hasTable('student_cards')) {
            return;
        }

        DB::transaction(function () {
            $rows = DB::table('classroom_repair_backups')
                ->where('batch', $this->batch)
                ->where('table_name', 'student_cards')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                $payload = json_decode($row->payload, true);
                DB::table('student_cards')
                    ->where('id', $row->record_id)
                    ->update(['student_status' => $payload['student_status'], 'updated_at' => now()]);
            }

            DB::table('classroom_repair_backups')->where('batch', $this->batch)->delete();
        });
    }
};
