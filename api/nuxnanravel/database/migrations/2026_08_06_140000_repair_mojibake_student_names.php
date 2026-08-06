<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ซ่อมชื่อนักเรียนที่เพี้ยนจากการ import PDF
 *
 * แบ่งเป็นสองส่วนที่แยกกันชัดเจน
 *
 *   ส่วนที่ 1 — แก้ตามเอกสารทะเบียน (4 ราย)
 *   สระ/วรรณยุกต์หลุดออกจากตัวอักษร หรือถูกตัดคร่อมช่องชื่อ-นามสกุล
 *   ซ่อมเองด้วยกฎไม่ได้ ต้องใช้ค่าจากทะเบียน "รายชื่อนักเรียนมัธยมปี 69 เทอม 1"
 *
 *     12645  'เด็กชาย ฟ ิ ตรี' + 'หีมเบ็ญหมาน'      → เด็กชาย / ฟิตรี / หีมเบ็ญหมาน
 *     12676  'นาย ศุภณัฐ ส' + 'ามะเนี๊ยะ'            → นาย / ศุภณัฐ / สำมะเนี๊ยะ
 *     12770  'นางสาว ร๊อฎวา เฮ็งป ิ' + 'ยา'          → นางสาว / ร๊อฎวา / เฮ็งปิยา
 *     10448  'ซันวาณีย์' + 'โตีะหีม'                 → นางสาว / ซันวาณีย์ / โต๊ะหีม
 *
 *   ส่วนที่ 2 — แยกคำนำหน้าออกจากชื่อ (กฎทั่วไป)
 *   import ยัดคำนำหน้าไว้ในคอลัมน์ first_name_th และปล่อย title_prefix_th ว่าง
 *   ทำให้ชื่อที่แสดงกลายเป็น "เด็กชาย เด็กชาย สมชาย" เมื่อ resource ประกอบใหม่
 *   ส่วนนี้ไม่ต้องพึ่งเอกสาร ใช้กฎเดียวจบ จึงครอบคลุมนักเรียนที่ไม่ได้อยู่ในทะเบียนด้วย
 *
 * ทั้งสองส่วนแตะเฉพาะแถวที่ค่าปัจจุบัน "ยังเพี้ยนอยู่จริง" ถ้ามีคนแก้ไปแล้วจะข้าม
 * ชื่อที่แก้แล้วถูก sync ลงคอลัมน์ snapshot ของบัตรนักเรียนด้วย
 */
return new class extends Migration
{
    private const BACKUP = 'bk_name_repair_20260806';

    private const PREFIXES = ['เด็กชาย', 'เด็กหญิง', 'นางสาว', 'นาย', 'นาง'];

    /** ค่าที่ถูกต้องตามเอกสารทะเบียน พร้อมค่าเพี้ยนที่คาดว่าจะเจอ */
    private const CORRECTIONS = [
        [
            'code' => '12645',
            'expect' => ['first' => 'เด็กชาย ฟ ิ ตรี', 'last' => 'หีมเบ็ญหมาน'],
            'fixed' => ['title_prefix_th' => 'เด็กชาย', 'first_name_th' => 'ฟิตรี', 'last_name_th' => 'หีมเบ็ญหมาน'],
        ],
        [
            'code' => '12676',
            'expect' => ['first' => 'นาย ศุภณัฐ ส', 'last' => 'ามะเนี๊ยะ'],
            'fixed' => ['title_prefix_th' => 'นาย', 'first_name_th' => 'ศุภณัฐ', 'last_name_th' => 'สำมะเนี๊ยะ'],
        ],
        [
            'code' => '12770',
            'expect' => ['first' => 'นางสาว ร๊อฎวา เฮ็งป ิ', 'last' => 'ยา'],
            'fixed' => ['title_prefix_th' => 'นางสาว', 'first_name_th' => 'ร๊อฎวา', 'last_name_th' => 'เฮ็งปิยา'],
        ],
        [
            'code' => '10448',
            'expect' => ['first' => 'ซันวาณีย์', 'last' => 'โตีะหีม'],
            'fixed' => ['title_prefix_th' => 'นางสาว', 'first_name_th' => 'ซันวาณีย์', 'last_name_th' => 'โต๊ะหีม'],
        ],
    ];

    public function up(): void
    {
        $this->createBackupTable();

        $repaired = 0;

        DB::transaction(function () use (&$repaired) {
            $repaired += $this->applyRosterCorrections();
            $repaired += $this->splitLeadingTitlePrefix();
        });

        Log::info('name-repair-20260806: ซ่อมชื่อนักเรียน', ['rows' => $repaired]);
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP)) {
            return;
        }

        DB::transaction(function () {
            foreach (DB::table(self::BACKUP)->orderByDesc('id')->get() as $row) {
                $values = json_decode($row->old_values, true);
                if (is_array($values) && $values !== []) {
                    DB::table($row->table_name)->where('id', $row->row_id)->update($values);
                }
            }
        });

        Schema::dropIfExists(self::BACKUP);
    }

    // ================================================================

    private function applyRosterCorrections(): int
    {
        $count = 0;

        foreach (self::CORRECTIONS as $correction) {
            $student = DB::table('students')->where('student_id', $correction['code'])->first();

            if (! $student) {
                continue;
            }

            // ถ้าค่าปัจจุบันไม่ใช่ค่าเพี้ยนที่คาดไว้ แปลว่ามีคนแก้ไปแล้ว — อย่าเขียนทับ
            if ($student->first_name_th !== $correction['expect']['first'] || $student->last_name_th !== $correction['expect']['last']) {
                Log::info('name-repair-20260806: ข้ามเพราะค่าปัจจุบันไม่ตรงกับที่คาดไว้', ['code' => $correction['code']]);

                continue;
            }

            $this->writeName($student, $correction['fixed']);
            $count++;
        }

        return $count;
    }

    /**
     * แถวที่ title_prefix_th ว่าง แต่ first_name_th ขึ้นต้นด้วยคำนำหน้า
     * ย้ายคำนำหน้าไปไว้ในช่องของมัน
     */
    private function splitLeadingTitlePrefix(): int
    {
        $candidates = DB::table('students')
            ->where(function ($query) {
                $query->whereNull('title_prefix_th')->orWhere('title_prefix_th', '');
            })
            ->where(function ($query) {
                foreach (self::PREFIXES as $prefix) {
                    $query->orWhere('first_name_th', 'like', $prefix.' %');
                }
            })
            ->get();

        $count = 0;

        foreach ($candidates as $student) {
            $prefix = $this->leadingPrefix($student->first_name_th);

            if (! $prefix) {
                continue;
            }

            $remainder = trim(mb_substr($student->first_name_th, mb_strlen($prefix)));

            // ชื่อต้องไม่หายไปทั้งหมด — first_name_th เป็น NOT NULL
            if ($remainder === '') {
                Log::warning('name-repair-20260806: ตัดคำนำหน้าแล้วไม่เหลือชื่อ ข้ามแถว', ['student_id' => $student->student_id]);

                continue;
            }

            $this->writeName($student, [
                'title_prefix_th' => $prefix,
                'first_name_th' => $remainder,
                'last_name_th' => $student->last_name_th,
            ]);
            $count++;
        }

        return $count;
    }

    /** คำนำหน้าที่ยาวที่สุดที่ตรงก่อน เพื่อไม่ให้ 'นาย' ไปตัดหน้า 'นางสาว' */
    private function leadingPrefix(string $name): ?string
    {
        $prefixes = self::PREFIXES;
        usort($prefixes, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($prefixes as $prefix) {
            if (mb_strpos($name, $prefix.' ') === 0) {
                return $prefix;
            }
        }

        return null;
    }

    private function writeName(object $student, array $fixed): void
    {
        $this->backup('students', $student->id, [
            'title_prefix_th' => $student->title_prefix_th,
            'first_name_th' => $student->first_name_th,
            'last_name_th' => $student->last_name_th,
            'updated_at' => $student->updated_at,
        ]);

        DB::table('students')->where('id', $student->id)->update($fixed + ['updated_at' => now()]);

        $this->syncCards($student->id, $fixed);
    }

    /**
     * คอลัมน์ชื่อบนบัตรเป็น snapshot — หน้าเว็บอ่านจาก students อยู่แล้ว
     * แต่ไฟล์ export และการพิมพ์บัตรยังใช้ค่าพวกนี้
     */
    private function syncCards(int $studentId, array $fixed): void
    {
        $fullName = trim("{$fixed['title_prefix_th']} {$fixed['first_name_th']} {$fixed['last_name_th']}");

        foreach (DB::table('student_cards')->where('student_id', $studentId)->get() as $card) {
            $this->backup('student_cards', $card->id, [
                'title_name' => $card->title_name,
                'first_name_thai' => $card->first_name_thai,
                'last_name_thai' => $card->last_name_thai,
                'full_name_thai' => $card->full_name_thai,
                'updated_at' => $card->updated_at,
            ]);

            DB::table('student_cards')->where('id', $card->id)->update([
                'title_name' => $fixed['title_prefix_th'],
                'first_name_thai' => $fixed['first_name_th'],
                'last_name_thai' => $fixed['last_name_th'],
                'full_name_thai' => $fullName,
                'updated_at' => now(),
            ]);
        }
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
