<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ย้ายนักเรียนเลข 12246 (เด็กหญิง อลียา ยังปากน้ำ) จาก ม.1/1 ไป ม.1/3
 *
 * ⚠️ หมายเหตุ: เอกสารทะเบียน "รายชื่อนักเรียนมัธยมปี 69 เทอม 1" ระบุว่า 12246
 * อยู่ ม.1/1 การย้ายครั้งนี้เป็นคำสั่งจากผู้ดูแลซึ่งทราบสถานการณ์จริงของห้องเรียน
 * ถ้าเอกสารทะเบียนถูกต้องกว่า ให้ rollback ด้วย down()
 *
 * ทำตามลำดับเดียวกับ StudentEnrollmentService::transferStudent() คือปิดทะเบียน
 * ห้องเดิมเป็น transferred แล้วเปิดทะเบียนห้องใหม่ พร้อมอัพเดต snapshot ที่
 * students / student_academic_info / student_cards ให้ตามไปด้วย — แต่เขียนตรง
 * ด้วย query builder ไม่ผ่าน service เพื่อไม่ให้ยิง event แจ้งเตือนตอนรัน migration
 */
return new class extends Migration
{
    private const BACKUP = 'bk_room_move_20260806';

    private const STUDENT = ['code' => '12246', 'citizen_id' => '1909803970194'];

    private const FROM_ROOM = ['grade_level' => 'ม.1', 'section' => '1'];

    private const TO_ROOM = ['grade_level' => 'ม.1', 'section' => '3'];

    private const REASON = 'ย้ายห้องตามคำสั่งผู้ดูแล';

    public function up(): void
    {
        $this->createBackupTable();

        $student = DB::table('students')
            ->where('student_id', self::STUDENT['code'])
            ->where('citizen_id', self::STUDENT['citizen_id'])
            ->first();

        if (! $student) {
            Log::info('room-move-20260806: ไม่พบนักเรียน ข้าม migration');

            return;
        }

        $from = $this->currentRoom($student->academy_id, self::FROM_ROOM);
        $to = $this->currentRoom($student->academy_id, self::TO_ROOM);

        if (! $from || ! $to) {
            Log::warning('room-move-20260806: ไม่พบห้องต้นทางหรือปลายทางของปีปัจจุบัน ข้าม migration');

            return;
        }

        if (DB::table('classroom_students')->where('student_id', $student->id)->where('classroom_id', $to->id)->where('status', 'active')->exists()) {
            return; // ย้ายไปแล้ว
        }

        DB::transaction(function () use ($student, $from, $to) {
            $this->closeOldEnrollment($student, $from);
            $seat = $this->openNewEnrollment($student, $to);
            $this->syncStudentRow($student, $to);
            $this->syncAcademicInfo($student, $to);
            $this->syncCard($student, $to, $seat);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP)) {
            return;
        }

        DB::transaction(function () {
            foreach (DB::table(self::BACKUP)->orderByDesc('id')->get() as $row) {
                if ($row->action === 'insert') {
                    DB::table($row->table_name)->where('id', $row->row_id)->delete();

                    continue;
                }

                $values = json_decode($row->old_values, true);
                if (is_array($values) && $values !== []) {
                    DB::table($row->table_name)->where('id', $row->row_id)->update($values);
                }
            }
        });

        Schema::dropIfExists(self::BACKUP);
    }

    // ================================================================

    private function closeOldEnrollment(object $student, object $from): void
    {
        $enrollment = DB::table('classroom_students')
            ->where('student_id', $student->id)
            ->where('classroom_id', $from->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            return;
        }

        $this->backup('classroom_students', $enrollment->id, [
            'status' => $enrollment->status,
            'left_at' => $enrollment->left_at,
            'leave_reason' => $enrollment->leave_reason,
            'updated_at' => $enrollment->updated_at,
        ]);

        DB::table('classroom_students')->where('id', $enrollment->id)->update([
            'status' => 'transferred',
            'left_at' => today(),
            'leave_reason' => self::REASON,
            'updated_at' => now(),
        ]);
    }

    private function openNewEnrollment(object $student, object $to): int
    {
        $seat = (int) DB::table('classroom_students')
            ->where('classroom_id', $to->id)
            ->where('status', 'active')
            ->max('student_number') + 1;

        $existing = DB::table('classroom_students')
            ->where('student_id', $student->id)
            ->where('classroom_id', $to->id)
            ->first();

        // เคยอยู่ห้องนี้แล้วกลับเข้ามาใหม่ — ใช้แถวเดิมเพื่อไม่ชน unique(classroom_id, student_id)
        if ($existing) {
            $this->backup('classroom_students', $existing->id, [
                'status' => $existing->status,
                'student_number' => $existing->student_number,
                'enrolled_at' => $existing->enrolled_at,
                'left_at' => $existing->left_at,
                'leave_reason' => $existing->leave_reason,
                'updated_at' => $existing->updated_at,
            ]);

            DB::table('classroom_students')->where('id', $existing->id)->update([
                'status' => 'active',
                'student_number' => $seat,
                'enrolled_at' => today(),
                'left_at' => null,
                'leave_reason' => null,
                'updated_at' => now(),
            ]);

            return $seat;
        }

        $id = DB::table('classroom_students')->insertGetId([
            'academy_id' => $to->academy_id,
            'academic_year_id' => $to->academic_year_id,
            'classroom_id' => $to->id,
            'student_id' => $student->id,
            'student_number' => $seat,
            'status' => 'active',
            'enrolled_at' => today(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->backup('classroom_students', $id, [], 'insert');

        return $seat;
    }

    private function syncStudentRow(object $student, object $to): void
    {
        $this->backup('students', $student->id, [
            'class_level' => $student->class_level,
            'class_section' => $student->class_section,
            'updated_at' => $student->updated_at,
        ]);

        DB::table('students')->where('id', $student->id)->update([
            'class_level' => preg_replace('/^[^0-9]+/u', '', $to->grade_level),
            'class_section' => $to->section,
            'updated_at' => now(),
        ]);
    }

    private function syncAcademicInfo(object $student, object $to): void
    {
        $info = DB::table('student_academic_info')
            ->where('student_id', $student->id)
            ->where('is_current', true)
            ->first();

        if (! $info) {
            return;
        }

        $this->backup('student_academic_info', $info->id, [
            'classroom_id' => $info->classroom_id,
            'current_grade' => $info->current_grade,
            'current_class' => $info->current_class,
            'classroom_full' => $info->classroom_full,
            'updated_at' => $info->updated_at,
        ]);

        DB::table('student_academic_info')->where('id', $info->id)->update([
            'classroom_id' => $to->id,
            'current_grade' => $to->grade_level,
            'current_class' => $to->section,
            'classroom_full' => $to->grade_level.'/'.$to->section,
            'updated_at' => now(),
        ]);
    }

    /**
     * คอลัมน์ห้องบนบัตรเป็น snapshot — หน้าเว็บอ่านจากทะเบียนห้องอยู่แล้ว
     * แต่ไฟล์ export และการพิมพ์บัตรยังใช้ค่าพวกนี้
     */
    private function syncCard(object $student, object $to, int $seat): void
    {
        $card = DB::table('student_cards')
            ->where('student_id', $student->id)
            ->where('academy_id', $student->academy_id)
            ->where('student_status', 'active')
            ->first();

        if (! $card) {
            return;
        }

        $level = (int) preg_replace('/^[^0-9]+/u', '', $to->grade_level);

        $updates = [
            'class_level' => $level,
            'class_section' => (int) $to->section,
            'level_and_room' => $level.'/'.$to->section,
            'order_no' => $seat,
            'updated_at' => now(),
        ];
        $old = [
            'class_level' => $card->class_level,
            'class_section' => $card->class_section,
            'level_and_room' => $card->level_and_room,
            'order_no' => $card->order_no,
            'updated_at' => $card->updated_at,
        ];

        if (Schema::hasColumn('student_cards', 'classroom_id')) {
            $updates['classroom_id'] = $to->id;
            $old['classroom_id'] = $card->classroom_id;
        }

        $this->backup('student_cards', $card->id, $old);

        DB::table('student_cards')->where('id', $card->id)->update($updates);
    }

    // ================================================================

    private function currentRoom(int $academyId, array $room): ?object
    {
        $yearId = DB::table('academic_years')
            ->where('academy_id', $academyId)
            ->where('is_current', true)
            ->value('id');

        if (! $yearId) {
            return null;
        }

        return DB::table('classrooms')
            ->where('academy_id', $academyId)
            ->where('academic_year_id', $yearId)
            ->where('grade_level', $room['grade_level'])
            ->where('section', $room['section'])
            ->first();
    }

    private function createBackupTable(): void
    {
        if (Schema::hasTable(self::BACKUP)) {
            return;
        }

        Schema::create(self::BACKUP, function ($table) {
            $table->id();
            $table->string('table_name', 64);
            $table->unsignedBigInteger('row_id');
            $table->string('action', 16)->default('update');
            $table->json('old_values');
            $table->timestamp('captured_at')->useCurrent();
            $table->index(['table_name', 'row_id']);
        });
    }

    private function backup(string $table, int $rowId, array $oldValues, string $action = 'update'): void
    {
        DB::table(self::BACKUP)->insert([
            'table_name' => $table,
            'row_id' => $rowId,
            'action' => $action,
            'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'captured_at' => now(),
        ]);
    }
};
