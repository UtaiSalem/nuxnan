<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ออกบัตรย้อนหลังให้คำร้องทำบัตรที่ "จัดทำเสร็จ" ไปแล้วแต่ไม่มีบัตร (ต่อจากการแก้ G5)
 *
 * ก่อนแก้ G5 ปุ่ม "ทำเสร็จ/ส่งมอบแล้ว" บนหน้าที่ไม่ต้องล็อกอินเปลี่ยนแค่สถานะคำร้องเป็น completed
 * ไม่ได้สร้างแถว student_cards → result_card_id ว่าง และบัตรเดิมยังใช้วันออกบัตร/วันหมดอายุชุดเก่า
 * migration นี้ทำแบบเดียวกับ StudentCardRequestService::issueCard() โดยใช้ completed_at เป็นวันออกบัตร
 *
 * เลือกเฉพาะคำร้อง status = completed ที่ result_card_id ว่าง (ไม่ hardcode id) ทำตามลำดับเวลาที่จัดทำเสร็จ
 * ข้ามแล้วรายงาน (ไม่ล้มทั้งชุด) เมื่อ:
 *  - ไม่พบนักเรียน หรือคำร้องไม่มี completed_at
 *  - นักเรียนมีบัตร active ที่ออก/สร้างหลังวันที่จัดทำเสร็จอยู่แล้ว (ห้ามเอาบัตรที่เก่ากว่าไปทับ)
 *  - ไม่มีบัตร active และไม่ได้เรียนอยู่ในปีการศึกษาปัจจุบันของโรงเรียนนั้นแล้ว (กันออกบัตรให้คนที่จบ/ย้ายออก)
 *  - ค่าที่จะเขียนยาวเกินคอลัมน์ (DB เปิด STRICT_TRANS_TABLES)
 *
 * down() ย้อนจาก classroom_repair_backups ของ batch นี้: คืน result_card_id → ลบบัตรที่สร้าง → ปลุกบัตรเดิม
 * ใช้สำหรับย้อนกลับไม่นานหลังรัน up() — ถ้าหลังจากนั้นมีการออกบัตรใบใหม่ไปแล้ว จะไม่ปลุกบัตรเดิมให้ชนกัน
 *
 * ⚠️ is_active_flag เป็น generated column และ uq_student_card_active ยอมให้มีบัตร active ได้ใบเดียว
 * ต่อ (student_id, academy_id) จึงต้องปิดใบเดิมก่อนสร้างใบใหม่ และลบใบใหม่ก่อนปลุกใบเดิมเสมอ
 */
return new class extends Migration
{
    private const BACKUP_TABLE = 'classroom_repair_backups';

    private string $batch = '2026_11_22_000000';

    public function up(): void
    {
        foreach (['student_cards', 'student_card_requests', 'students', 'classroom_students', 'classrooms', 'academic_years'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            Schema::create(self::BACKUP_TABLE, function (Blueprint $table) {
                $table->id();
                $table->string('batch', 64);
                $table->string('table_name', 64);
                $table->unsignedBigInteger('record_id');
                $table->json('payload');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['batch', 'table_name'], 'crb_batch_tbl_idx');
            });
        }

        $limits = $this->columnLimits();
        $issued = [];
        $skipped = [];

        DB::transaction(function () use ($limits, &$issued, &$skipped) {
            $requests = DB::table('student_card_requests')
                ->where('status', 'completed')
                ->whereNull('result_card_id')
                ->orderBy('completed_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($requests as $request) {
                $reason = $this->issue($request, $limits, $issued);
                if ($reason !== null) {
                    $skipped[] = "คำร้อง #{$request->id}: {$reason}";
                }
            }
        });

        $this->report('ออกบัตรย้อนหลังให้คำร้องที่จัดทำเสร็จแล้ว '.count($issued).' รายการ · ข้าม '.count($skipped).' รายการ', $skipped, [
            'issued' => count($issued),
            'card_ids' => array_keys($issued),
        ]);
    }

    public function down(): void
    {
        foreach ([self::BACKUP_TABLE, 'student_cards', 'student_card_requests'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $skipped = [];

        DB::transaction(function () use (&$skipped) {
            // ย้อนจากแถวล่าสุดไปแถวแรก: คืน result_card_id → ลบบัตรที่สร้าง → ปลุกบัตรเดิม
            // ลำดับนี้ทำให้ไม่มีจังหวะที่นักเรียนมีบัตร active สองใบพร้อมกัน
            $rows = DB::table(self::BACKUP_TABLE)
                ->where('batch', $this->batch)
                ->orderByDesc('id')
                ->get();

            foreach ($rows as $row) {
                $payload = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);

                if ($row->table_name === 'student_card_requests') {
                    DB::table('student_card_requests')
                        ->where('id', $row->record_id)
                        ->update(['result_card_id' => $payload['result_card_id']]);
                } elseif ($row->table_name === 'student_cards:created') {
                    DB::table('student_cards')->where('id', $row->record_id)->delete();
                } elseif ($row->table_name === 'student_cards') {
                    $reason = $this->restoreCard((int) $row->record_id, $payload);
                    if ($reason !== null) {
                        $skipped[] = $reason;
                    }
                }
            }

            DB::table(self::BACKUP_TABLE)->where('batch', $this->batch)->delete();
        });

        $this->report('ย้อนการออกบัตรย้อนหลังแล้ว', $skipped);
    }

    /**
     * ออกบัตรให้คำร้องหนึ่งรายการ — คืนเหตุผลที่ข้าม หรือ null เมื่อออกบัตรสำเร็จ
     *
     * @param  array<string, int>  $limits  ความยาวสูงสุดของคอลัมน์ข้อความใน student_cards
     * @param  array<int, int>  $issued  บัตรที่ migration รอบนี้สร้างแล้ว (card id => request id)
     */
    private function issue(object $request, array $limits, array &$issued): ?string
    {
        $student = $request->student_id
            ? DB::table('students')->where('id', $request->student_id)->first()
            : null;
        if (! $student) {
            return 'ไม่พบข้อมูลนักเรียน';
        }
        if (! $request->completed_at) {
            return 'ไม่มีวันที่จัดทำเสร็จ (completed_at ว่าง)';
        }

        $completedAt = Carbon::parse($request->completed_at);
        $activeCards = DB::table('student_cards')
            ->where('student_id', $student->id)
            ->where('academy_id', $request->academy_id)
            ->where('student_status', 'active')
            ->orderBy('id')
            ->get();

        foreach ($activeCards as $card) {
            // ใบที่รอบนี้เพิ่งออกให้คำร้องก่อนหน้าของนักเรียนคนเดียวกัน — แทนที่ต่อได้ตามลำดับเวลา
            if (isset($issued[$card->id])) {
                continue;
            }
            $isNewer = ($card->created_at && Carbon::parse($card->created_at)->gt($completedAt))
                || ($card->card_issue_date && Carbon::parse($card->card_issue_date)->gt($completedAt));
            if ($isNewer) {
                return "มีบัตร #{$card->id} ที่ออกหลังวันที่จัดทำเสร็จอยู่แล้ว";
            }
        }

        if ($activeCards->isEmpty() && ! $this->isStillEnrolled((int) $student->id, $request->academy_id)) {
            return 'ไม่มีบัตรที่ใช้งานอยู่ และไม่ได้เรียนอยู่ในปีการศึกษาปัจจุบันแล้ว';
        }

        $card = $this->cardRow($request, $student, $completedAt);
        foreach ($card as $column => $value) {
            if (is_string($value) && isset($limits[$column]) && mb_strlen($value) > $limits[$column]) {
                return "ค่า {$column} ยาว ".mb_strlen($value)." ตัวอักษร เกินขนาดคอลัมน์ ({$limits[$column]})";
            }
        }

        $now = now();
        foreach ($activeCards as $old) {
            $this->backup('student_cards', (int) $old->id, [
                'student_status' => $old->student_status,
                'updated_at' => $old->updated_at,
            ]);
            DB::table('student_cards')
                ->where('id', $old->id)
                ->update(['student_status' => 'expired', 'updated_at' => $now]);
        }

        $cardId = (int) DB::table('student_cards')->insertGetId($card + ['created_at' => $now, 'updated_at' => $now]);
        $this->backup('student_cards:created', $cardId, ['request_id' => $request->id]);

        $this->backup('student_card_requests', (int) $request->id, ['result_card_id' => $request->result_card_id]);
        DB::table('student_card_requests')
            ->where('id', $request->id)
            ->update(['result_card_id' => $cardId]);

        $issued[$cardId] = (int) $request->id;

        return null;
    }

    /**
     * ค่าบนบัตรใบใหม่ — ต้องตรงกับ StudentCardRequestService::issueCard() ทุกคอลัมน์
     * ต่างกันแค่วันออกบัตร = completed_at ของคำร้อง (ไม่ใช่วันที่รัน migration)
     */
    private function cardRow(object $request, object $student, Carbon $issueDate): array
    {
        $birthDate = $student->date_of_birth ? Carbon::parse($student->date_of_birth) : null;

        return [
            'student_id' => $student->id,
            'academy_id' => $request->academy_id,
            'academic_year_id' => $request->academic_year_id,
            'student_number' => $student->student_id,
            'full_name_thai' => $request->full_name_snapshot,
            'title_name' => $student->title_prefix_th,
            'first_name_thai' => $student->first_name_th,
            'last_name_thai' => $student->last_name_th,
            'first_name_english' => $student->first_name_en,
            'national_id' => $student->citizen_id,
            'birth_date' => $birthDate?->toDateString(),
            'birth_date_string' => $birthDate?->format('d/m/Y'),
            'class_level' => preg_replace('/\D+/u', '', (string) $request->grade_level_snapshot) ?: null,
            'class_section' => (int) $request->section_snapshot ?: null,
            'level_and_room' => trim($request->grade_level_snapshot.'/'.$request->section_snapshot, '/'),
            'card_issue_date' => $issueDate->toDateTimeString(),
            'card_expiry_date' => $issueDate->copy()->addYears(3)->toDateTimeString(),
            'student_status' => 'active',
            'profile_image' => $student->profile_image,
        ];
    }

    /**
     * ยังเรียนอยู่ในห้องที่ active ของปีการศึกษาปัจจุบัน ในโรงเรียนเดียวกับคำร้อง
     */
    private function isStillEnrolled(int $studentId, $academyId): bool
    {
        return DB::table('classroom_students as cs')
            ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
            ->join('academic_years as ay', 'ay.id', '=', 'c.academic_year_id')
            ->where('cs.student_id', $studentId)
            ->where('c.academy_id', $academyId)
            ->where('cs.status', 'active')
            ->where('c.status', 'active')
            ->where('ay.is_current', 1)
            ->exists();
    }

    /**
     * ปลุกบัตรเดิมกลับเป็นสถานะก่อนรัน up() — ไม่ปลุกถ้านักเรียนมีบัตร active ใบอื่นอยู่แล้ว
     */
    private function restoreCard(int $cardId, array $payload): ?string
    {
        $card = DB::table('student_cards')->where('id', $cardId)->first();
        if (! $card) {
            return "ไม่พบบัตร #{$cardId} แล้ว จึงไม่ได้คืนสถานะ";
        }

        $hasOtherActive = $payload['student_status'] === 'active' && DB::table('student_cards')
            ->where('student_id', $card->student_id)
            ->where('academy_id', $card->academy_id)
            ->where('student_status', 'active')
            ->where('id', '!=', $cardId)
            ->exists();
        if ($hasOtherActive) {
            return "ไม่ได้ปลุกบัตร #{$cardId} เพราะนักเรียนมีบัตร active ใบอื่นแล้ว";
        }

        DB::table('student_cards')->where('id', $cardId)->update([
            'student_status' => $payload['student_status'],
            'updated_at' => $payload['updated_at'],
        ]);

        return null;
    }

    private function backup(string $table, int $recordId, array $payload): void
    {
        DB::table(self::BACKUP_TABLE)->insert([
            'batch' => $this->batch,
            'table_name' => $table,
            'record_id' => $recordId,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    /**
     * ความยาวสูงสุดของคอลัมน์ข้อความใน student_cards จาก information_schema
     * อ่านจากฐานข้อมูลจริงเพราะ production อาจยังไม่ได้ขยายบางคอลัมน์ · SQLite ไม่บังคับความยาว จึงไม่ต้องตรวจ
     *
     * @return array<string, int>
     */
    private function columnLimits(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [];
        }

        $columns = DB::select(
            'SELECT COLUMN_NAME AS name, CHARACTER_MAXIMUM_LENGTH AS max_length
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CHARACTER_MAXIMUM_LENGTH IS NOT NULL',
            ['student_cards']
        );

        $limits = [];
        foreach ($columns as $column) {
            $limits[$column->name] = (int) $column->max_length;
        }

        return $limits;
    }

    private function report(string $summary, array $skipped, array $context = []): void
    {
        Log::info($summary, ['batch' => $this->batch, 'skipped' => $skipped] + $context);

        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            return;
        }

        echo "  {$summary}".PHP_EOL;
        foreach ($skipped as $line) {
            echo "    - {$line}".PHP_EOL;
        }
    }
};
