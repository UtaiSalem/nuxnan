<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 2026_08_06_100100 สำรองทั้งแถวลง classroom_repair_backups
 * payload จึงมี is_active_flag ซึ่งเป็น generated column — เขียนกลับไม่ได้
 * down() ต้องกรองคอลัมน์พวกนี้ออกก่อน ไม่งั้น rollback พังกลางลูป
 */
class ClassroomRepairRollbackTest extends TestCase
{
    use RefreshDatabase;

    private const BATCH = '2026_08_06_100100';

    private const MIGRATION = 'migrations/2026_08_06_100100_repair_orphaned_classroom_enrollments.php';

    private Academy $academy;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Repair owner',
            'email' => uniqid().'@test.local',
            'password' => bcrypt('x'),
            'username' => uniqid(),
            'reference_code' => uniqid(),
            'personal_code' => uniqid(),
        ]);
        $this->academy = Academy::create(['name' => 'Repair test '.uniqid(), 'user_id' => $user->id]);
        $this->year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);
    }

    private function backup(string $table, int $id): void
    {
        DB::table('classroom_repair_backups')->insert([
            'batch' => self::BATCH,
            'table_name' => $table,
            'record_id' => $id,
            'payload' => json_encode((array) DB::table($table)->where('id', $id)->first(), JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

    public function test_rollback_restores_a_graduated_card_without_writing_generated_columns(): void
    {
        $student = Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
            'status' => 'active',
            'class_level' => '4',
            'class_section' => '8',
        ]);
        $card = StudentCard::create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'student_number' => $student->student_id,
            'class_level' => '4',
            'class_section' => '8',
            'student_status' => 'active',
            'academic_year_id' => $this->year->id,
        ]);

        // สภาพหลัง up(): สำรองทั้งแถวไว้ก่อน แล้วค่อยเปลี่ยนสถานะเป็น graduated
        $this->backup('students', $student->id);
        $this->backup('student_cards', $card->id);

        $payload = json_decode(
            DB::table('classroom_repair_backups')->where('table_name', 'student_cards')->value('payload'),
            true
        );
        $this->assertArrayHasKey('is_active_flag', $payload, 'payload ต้องยังมี generated column ให้ down() กรองทิ้ง');

        DB::table('student_cards')->where('id', $card->id)->update(['student_status' => 'graduated']);
        DB::table('students')->where('id', $student->id)->update([
            'status' => 'graduated',
            'class_level' => null,
            'class_section' => null,
        ]);

        $migration = require database_path(self::MIGRATION);
        $migration->down();

        $this->assertSame('active', $card->fresh()->student_status);
        $this->assertSame('active', $student->fresh()->status);
        $this->assertSame('4', $student->fresh()->class_level);
        $this->assertSame(0, DB::table('classroom_repair_backups')->where('batch', self::BATCH)->count());
    }
}
