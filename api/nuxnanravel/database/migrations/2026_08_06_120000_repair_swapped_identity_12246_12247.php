<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * คืนตัวตนให้นักเรียนเลข 12247 และคืนห้องให้เลข 12246
 *
 * ที่มา — ทะเบียนนักเรียน ม.ปี 2569 เทอม 1 (เอกสารโรงเรียน) และ student_import_rows
 * batch 2 ตรงกันทั้งคู่ว่าเป็นคนละคน:
 *
 *   12246  เด็กหญิง อลียา ยังปากน้ำ    ม.1/1  เลขบัตร 1909803970194
 *   12247  เด็กชาย อาดิษ ประสารการ    ม.1/3  เลขบัตร 1909803960954
 *
 * แต่ระเบียนของ 12247 ถูกสวมชื่อ "อลียา ยังปากน้ำ" มาตั้งแต่ import PDF วันที่
 * 2026-06-20 (เคส PUA/mojibake ที่จับคู่ชื่อผิดแถว) ทุกฟิลด์อื่นของระเบียนนี้ —
 * เลขบัตรประชาชน ที่อยู่ ห้องเรียน — เป็นของอาดิษถูกต้องแล้ว มีแต่ชื่อที่ผิด
 *
 * ต่อมาบัตรของ 12247 ทั้งสองใบถูกเขียน student_number เป็น '12246' ด้วย raw SQL
 * นอกระบบ (ไม่มี audit row และ updated_at ไม่ขยับ) และวันที่ 2026-08-06 ทะเบียน
 * ห้องของ 12246 ถูกปิดด้วยเหตุผล "จัดห้องใหม่" ทำให้เธอไม่สังกัดห้องใดเลย
 *
 * migration นี้แก้ทั้งสามเรื่องกลับตามเอกสารทะเบียน
 *
 * ทุกแถวหาโดยใช้กุญแจทางธุรกิจ (รหัสนักเรียน + เลขบัตรประชาชน) ไม่ใช้ id ตรงๆ
 * ถ้าหาไม่เจอจะข้ามและบันทึก log แทนการล้ม — ฐานข้อมูลที่ไม่มีข้อมูลชุดนี้
 * (เช่น DB ของเทสต์) จะผ่านไปเฉยๆ
 */
return new class extends Migration
{
    private const BACKUP = 'bk_identity_repair_20260806';

    /** รหัสนักเรียน => เลขบัตรประชาชน ใช้เป็นกุญแจค้นหาแถว */
    private const ADIZ = ['code' => '12247', 'citizen_id' => '1909803960954'];

    private const ALEEYA = ['code' => '12246', 'citizen_id' => '1909803970194'];

    /** ชื่อที่ถูกต้องของเลข 12247 ตามทะเบียนโรงเรียนและไฟล์นำเข้า */
    private const ADIZ_IDENTITY = [
        'title_prefix_th' => 'เด็กชาย',
        'first_name_th' => 'อาดิษ',
        'last_name_th' => 'ประสารการ',
        'first_name_en' => 'Adiz',
        'last_name_en' => 'Prasankran',
        'date_of_birth' => '2013-10-24',
    ];

    /** ห้องที่เลข 12246 ต้องกลับไปสังกัด */
    private const ALEEYA_ROOM = ['grade_level' => 'ม.1', 'section' => '1'];

    public function up(): void
    {
        $this->createBackupTable();

        $adiz = $this->findStudent(self::ADIZ);
        $aleeya = $this->findStudent(self::ALEEYA);

        if (! $adiz && ! $aleeya) {
            Log::info('identity-repair-20260806: ไม่พบนักเรียนทั้งสองระเบียน ข้ามทั้ง migration');

            return;
        }

        DB::transaction(function () use ($adiz, $aleeya) {
            if ($adiz) {
                $this->restoreAdizName($adiz);
                $this->restoreAdizCards($adiz);
                $this->scrubDeletedUserName($adiz);
            }

            if ($aleeya) {
                $this->restoreAleeyaEnrollment($aleeya);
                $this->restoreAleeyaCard($aleeya);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP)) {
            return;
        }

        DB::transaction(function () {
            // ย้อนกลับเรียงจากใหม่ไปเก่า เผื่อมีหลายแถวของตารางเดียวกัน
            $rows = DB::table(self::BACKUP)->orderByDesc('id')->get();

            foreach ($rows as $row) {
                $values = json_decode($row->old_values, true);
                if (! is_array($values) || $values === []) {
                    continue;
                }

                DB::table($row->table_name)->where('id', $row->row_id)->update($values);
            }
        });

        Schema::dropIfExists(self::BACKUP);
    }

    // ================================================================
    // ขั้นตอนย่อย
    // ================================================================

    private function restoreAdizName(object $adiz): void
    {
        $identity = self::ADIZ_IDENTITY;

        // STRICT_TRANS_TABLES เปิดอยู่ ค่าที่ยาวเกินคอลัมน์จะทำให้ migration ล้มกลางคัน
        $limits = ['title_prefix_th' => 20, 'first_name_th' => 100, 'last_name_th' => 100, 'first_name_en' => 100, 'last_name_en' => 100];
        foreach ($limits as $column => $max) {
            if (mb_strlen($identity[$column]) > $max) {
                throw new RuntimeException("identity-repair: ค่า {$column} ยาวเกิน {$max} ตัวอักษร");
            }
        }

        $this->backup('students', $adiz->id, [
            'title_prefix_th' => $adiz->title_prefix_th,
            'first_name_th' => $adiz->first_name_th,
            'last_name_th' => $adiz->last_name_th,
            'first_name_en' => $adiz->first_name_en,
            'last_name_en' => $adiz->last_name_en,
            'date_of_birth' => $adiz->date_of_birth,
            'updated_at' => $adiz->updated_at,
        ]);

        DB::table('students')->where('id', $adiz->id)->update($identity + ['updated_at' => now()]);
    }

    /**
     * บัตรของ 12247 ทั้งสองใบถือ student_number '12246' และชื่อของอลียา
     * ต้องคืนให้ตรงกับระเบียนหลัก คอลัมน์พวกนี้เป็น snapshot ที่หน้าเว็บไม่ได้อ่าน
     * (StudentCardResource อ่านจาก students) แต่ไฟล์ export และการพิมพ์บัตรใช้อยู่
     */
    private function restoreAdizCards(object $adiz): void
    {
        $identity = self::ADIZ_IDENTITY;
        $fullName = trim("{$identity['title_prefix_th']} {$identity['first_name_th']} {$identity['last_name_th']}");

        foreach (DB::table('student_cards')->where('student_id', $adiz->id)->get() as $card) {
            $this->backup('student_cards', $card->id, [
                'student_number' => $card->student_number,
                'title_name' => $card->title_name,
                'first_name_thai' => $card->first_name_thai,
                'last_name_thai' => $card->last_name_thai,
                'full_name_thai' => $card->full_name_thai,
                'first_name_english' => $card->first_name_english,
                'birth_date' => $card->birth_date,
                'birth_date_string' => $card->birth_date_string,
                'updated_at' => $card->updated_at,
            ]);

            DB::table('student_cards')->where('id', $card->id)->update([
                'student_number' => self::ADIZ['code'],
                'title_name' => $identity['title_prefix_th'],
                'first_name_thai' => $identity['first_name_th'],
                'last_name_thai' => $identity['last_name_th'],
                'full_name_thai' => $fullName,
                'first_name_english' => $identity['first_name_en'],
                'birth_date' => $identity['date_of_birth'],
                'birth_date_string' => date('d/m/Y', strtotime($identity['date_of_birth'])),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * บัญชีผู้ใช้ที่ผูกกับ 12247 ถูกลบไปแล้ว (name = 'Deleted User') แต่ username
     * ยังเก็บชื่อของอลียาพร้อมเลข 12247 อยู่ ลบชื่อที่ผิดออก — ไม่ใส่ชื่ออาดิษกลับ
     * เพราะไม่ควรผูกชื่อนักเรียนจริงกับบัญชีที่ถูกลบไปแล้ว
     */
    private function scrubDeletedUserName(object $adiz): void
    {
        if (! $adiz->user_id) {
            return;
        }

        $user = DB::table('users')->where('id', $adiz->user_id)->first();

        if (! $user || $user->name !== 'Deleted User') {
            return;
        }

        $scrubbed = 'deleted_'.$user->id;

        if ($user->username === $scrubbed) {
            return;
        }

        if (DB::table('users')->where('username', $scrubbed)->where('id', '!=', $user->id)->exists()) {
            Log::warning('identity-repair-20260806: username ที่จะใช้แทนถูกใช้ไปแล้ว ข้ามการล้าง', ['user_id' => $user->id]);

            return;
        }

        $this->backup('users', $user->id, ['username' => $user->username, 'updated_at' => $user->updated_at]);

        DB::table('users')->where('id', $user->id)->update(['username' => $scrubbed, 'updated_at' => now()]);
    }

    /**
     * คืนทะเบียนห้อง ม.1/1 ให้เลข 12246 — ถูกปิดเมื่อ 2026-08-06 ด้วยเหตุผล
     * "จัดห้องใหม่" ตอนที่ยังเข้าใจว่าซ้ำกับระเบียนของ 12247
     */
    private function restoreAleeyaEnrollment(object $aleeya): void
    {
        $classroom = $this->currentRoom($aleeya->academy_id, self::ALEEYA_ROOM);

        if (! $classroom) {
            Log::warning('identity-repair-20260806: ไม่พบห้อง ม.1/1 ของปีปัจจุบัน ข้ามการคืนทะเบียน');

            return;
        }

        $enrollment = DB::table('classroom_students')
            ->where('student_id', $aleeya->id)
            ->where('classroom_id', $classroom->id)
            ->where('status', '!=', 'active')
            ->orderByDesc('id')
            ->first();

        if (! $enrollment) {
            return;
        }

        // นักเรียนต้องไม่ค้าง active สองห้องพร้อมกัน
        $openElsewhere = DB::table('classroom_students')
            ->where('student_id', $aleeya->id)
            ->where('status', 'active')
            ->where('classroom_id', '!=', $classroom->id)
            ->exists();

        if ($openElsewhere) {
            Log::warning('identity-repair-20260806: 12246 มีทะเบียน active อยู่ห้องอื่นแล้ว ข้ามการคืนทะเบียน');

            return;
        }

        $this->backup('classroom_students', $enrollment->id, [
            'status' => $enrollment->status,
            'left_at' => $enrollment->left_at,
            'leave_reason' => $enrollment->leave_reason,
            'updated_at' => $enrollment->updated_at,
        ]);

        DB::table('classroom_students')->where('id', $enrollment->id)->update([
            'status' => 'active',
            'left_at' => null,
            'leave_reason' => null,
            'updated_at' => now(),
        ]);

        $this->backup('students', $aleeya->id, [
            'class_level' => $aleeya->class_level,
            'class_section' => $aleeya->class_section,
            'updated_at' => $aleeya->updated_at,
        ]);

        DB::table('students')->where('id', $aleeya->id)->update([
            'class_level' => preg_replace('/^[^0-9]+/u', '', $classroom->grade_level),
            'class_section' => $classroom->section,
            'updated_at' => now(),
        ]);
    }

    /**
     * บัตรของ 12246 หมดอายุตามทะเบียนที่ถูกปิด ปลุกกลับมาใบเดียว
     * (uq_student_card_active ยอมให้ active ได้ใบเดียวต่อ academy)
     */
    private function restoreAleeyaCard(object $aleeya): void
    {
        $hasActive = DB::table('student_cards')
            ->where('student_id', $aleeya->id)
            ->where('academy_id', $aleeya->academy_id)
            ->where('student_status', 'active')
            ->exists();

        if ($hasActive) {
            return;
        }

        $card = DB::table('student_cards')
            ->where('student_id', $aleeya->id)
            ->where('academy_id', $aleeya->academy_id)
            ->where('student_status', 'expired')
            ->orderByDesc('academic_year_id')
            ->orderByDesc('card_issue_date')
            ->orderByDesc('id')
            ->first();

        if (! $card) {
            return;
        }

        $this->backup('student_cards', $card->id, [
            'student_status' => $card->student_status,
            'updated_at' => $card->updated_at,
        ]);

        DB::table('student_cards')->where('id', $card->id)->update([
            'student_status' => 'active',
            'updated_at' => now(),
        ]);
    }

    // ================================================================
    // ตัวช่วย
    // ================================================================

    private function findStudent(array $key): ?object
    {
        return DB::table('students')
            ->where('student_id', $key['code'])
            ->where('citizen_id', $key['citizen_id'])
            ->first();
    }

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
            $table->json('old_values');
            $table->timestamp('captured_at')->useCurrent();
            $table->index(['table_name', 'row_id']);
        });
    }

    private function backup(string $table, int $rowId, array $oldValues): void
    {
        DB::table(self::BACKUP)->insert([
            'table_name' => $table,
            'row_id' => $rowId,
            'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'captured_at' => now(),
        ]);
    }
};
