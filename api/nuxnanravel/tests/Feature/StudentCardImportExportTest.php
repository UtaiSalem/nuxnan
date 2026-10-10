<?php

namespace Tests\Feature;

use App\Exports\StudentCardsExport;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * SCD-S1/S2/S3 — นำเข้า/ส่งออกบัตรนักเรียน + store() เติม enrollment
 *
 * ปิด gap G1 (import/export เดิมเป็น 501 stub) และ G2 (store ไม่สร้าง enrollment
 * ทำให้บัตรไม่โผล่ใน roster) · ทุก endpoint อยู่ใต้ students.manage
 */
class StudentCardImportExportTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $owner;

    private User $manager;

    private AcademicYear $year;

    private Classroom $roomA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $this->manager = $this->makeUser('manager');

        $this->academy = Academy::create([
            'name' => 'CardIOAcademy_'.uniqid(),
            'user_id' => $this->owner->id,
        ]);

        $this->joinAcademy($this->manager, $this->makeRole('manager-local', ['students.view', 'students.manage']));

        $this->year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $this->roomA = $this->makeClassroom('ม.6', '1', null);
    }

    // ── SCD-S2 export ────────────────────────────────────────────────

    public function test_export_template_downloads_with_fixed_name(): void
    {
        Excel::fake();

        $this->actingAs($this->manager, 'api')
            ->get($this->cardUrl('/admin/export?format=template'))
            ->assertOk();

        Excel::assertDownloaded('student-cards-template.xlsx');
    }

    public function test_export_data_returns_download_for_manager(): void
    {
        Excel::fake();

        $this->actingAs($this->manager, 'api')
            ->get($this->cardUrl('/admin/export'))
            ->assertOk();
    }

    public function test_export_class_maps_card_rows_to_heading_order(): void
    {
        $export = new StudentCardsExport([[
            'student_number' => 'STU-1',
            'title_name' => 'นาย',
            'first_name_thai' => 'สมชาย',
            'last_name_thai' => 'ใจดี',
            'first_name_english' => 'Somchai',
            'national_id' => '1234567890123',
            'birth_date' => '2010-05-01',
            'class_level' => '6',
            'class_section' => '1',
        ]]);

        $this->assertSame(StudentCardsExport::HEADINGS, $export->headings());
        $this->assertSame(
            ['STU-1', 'นาย', 'สมชาย', 'ใจดี', 'Somchai', '1234567890123', '2010-05-01', '6', '1'],
            $export->array()[0]
        );
    }

    public function test_export_requires_manage_permission(): void
    {
        $viewer = $this->makeUser('viewer');
        $this->joinAcademy($viewer, $this->makeRole('viewer-local', ['students.view']));

        $this->actingAs($viewer, 'api')
            ->get($this->cardUrl('/admin/export?format=template'))
            ->assertForbidden();
    }

    // ── SCD-S1 import ────────────────────────────────────────────────

    public function test_import_creates_student_card_and_enrollment(): void
    {
        $response = $this->actingAs($this->manager, 'api')
            ->post($this->cardUrl('/admin/import'), [
                'file' => $this->csvFile([
                    ['STU-IMP1', 'นาย', 'สมชาย', 'ใจดี', 'Somchai', '1100000000001', '2010-05-01', '6', '1'],
                ]),
            ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'summary' => ['created' => 1, 'updated' => 0, 'skipped' => 0],
        ]);

        $student = Student::where('academy_id', $this->academy->id)->where('student_id', 'STU-IMP1')->first();
        $this->assertNotNull($student);
        $this->assertDatabaseHas('student_cards', [
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'student_status' => 'active',
        ]);
        $this->assertDatabaseHas('classroom_students', [
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->roomA->id,
            'student_id' => $student->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
    }

    public function test_import_skips_existing_card_unless_update_existing(): void
    {
        $student = $this->makeStudent('STU-IMP2');
        $this->enroll($student, $this->roomA, 1);
        $this->makeCard($student, '6', '1');

        // update_existing = 0 → ข้าม
        $this->actingAs($this->manager, 'api')
            ->post($this->cardUrl('/admin/import'), [
                'file' => $this->csvFile([
                    ['STU-IMP2', 'นาง', 'เปลี่ยน', 'ชื่อ', '', '', '', '6', '1'],
                ]),
            ])
            ->assertOk()
            ->assertJson(['summary' => ['created' => 0, 'updated' => 0, 'skipped' => 1]]);

        $this->assertSame('ทดสอบ', $student->fresh()->first_name_th);

        // update_existing = 1 → อัปเดต
        $this->actingAs($this->manager, 'api')
            ->post($this->cardUrl('/admin/import'), [
                'file' => $this->csvFile([
                    ['STU-IMP2', 'นาง', 'เปลี่ยน', 'ชื่อ', '', '', '', '6', '1'],
                ]),
                'update_existing' => '1',
            ])
            ->assertOk()
            ->assertJson(['summary' => ['created' => 0, 'updated' => 1, 'skipped' => 0]]);

        $this->assertSame('เปลี่ยน', $student->fresh()->first_name_th);
    }

    public function test_import_rejects_file_with_wrong_headers(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'bad.csv',
            "ชื่อ,เบอร์โทร\nสมชาย,0999999999\n"
        );

        $this->actingAs($this->manager, 'api')
            ->post($this->cardUrl('/admin/import'), ['file' => $file])
            ->assertStatus(422);
    }

    public function test_import_requires_manage_permission(): void
    {
        $viewer = $this->makeUser('viewer');
        $this->joinAcademy($viewer, $this->makeRole('viewer-local', ['students.view']));

        $this->actingAs($viewer, 'api')
            ->post($this->cardUrl('/admin/import'), ['file' => $this->csvFile([['STU-X', '', 'a', 'b', '', '', '', '6', '1']])])
            ->assertForbidden();
    }

    // ── SCD-S3 store() + enrollment ──────────────────────────────────

    public function test_store_creates_card_and_enrolls_when_classroom_exists(): void
    {
        $response = $this->actingAs($this->manager, 'api')
            ->postJson($this->cardUrl('/admin'), [
                'student_number' => 'STU-STORE1',
                'title_name' => 'นาย',
                'first_name_thai' => 'ตั้ง',
                'last_name_thai' => 'บัตร',
                'class_level' => '6',
                'class_section' => '1',
            ]);

        $response->assertStatus(201)->assertJson(['success' => true, 'enrolled' => true]);

        $student = Student::where('academy_id', $this->academy->id)->where('student_id', 'STU-STORE1')->first();
        $this->assertNotNull($student);
        $this->assertDatabaseHas('classroom_students', [
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->roomA->id,
            'student_id' => $student->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('student_cards', [
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
        ]);
    }

    public function test_store_creates_card_without_enrollment_when_no_classroom(): void
    {
        $response = $this->actingAs($this->manager, 'api')
            ->postJson($this->cardUrl('/admin'), [
                'student_number' => 'STU-STORE2',
                'first_name_thai' => 'ไม่มี',
                'last_name_thai' => 'ห้อง',
                'class_level' => '3',   // ไม่มีห้อง ม.3 ในปีปัจจุบัน
                'class_section' => '9',
            ]);

        $response->assertStatus(201)->assertJson(['success' => true, 'enrolled' => false]);

        $student = Student::where('academy_id', $this->academy->id)->where('student_id', 'STU-STORE2')->first();
        $this->assertNotNull($student);
        $this->assertDatabaseMissing('classroom_students', [
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function csvFile(array $rows): UploadedFile
    {
        $lines = [implode(',', StudentCardsExport::HEADINGS)];
        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        return UploadedFile::fake()->createWithContent('cards.csv', implode("\n", $lines)."\n");
    }

    private function makeUser(string $tag): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => $tag.uniqid().'@x.test',
            'password' => bcrypt('x'),
            'username' => $tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    private function makeRole(string $name, array $permissions): AcademyRole
    {
        return AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => $name.'-'.uniqid(),
            'display_name_th' => $name,
            'permissions' => $permissions,
            'is_active' => true,
        ]);
    }

    private function joinAcademy(User $user, AcademyRole $role): void
    {
        AcademyMember::create([
            'user_id' => $user->id,
            'academy_id' => $this->academy->id,
            'role' => 'teacher',
            'academy_role_id' => $role->id,
            'status' => AcademyMember::STATUS_APPROVED,
        ]);
    }

    private function makeClassroom(string $gradeLevel, string $section, ?int $homeroomTeacherId): Classroom
    {
        return Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'grade_level' => $gradeLevel,
            'section' => $section,
            'name' => $gradeLevel.'/'.$section,
            'homeroom_teacher_id' => $homeroomTeacherId,
            'status' => Classroom::STATUS_ACTIVE,
            'is_active' => true,
        ]);
    }

    private function makeStudent(string $code): Student
    {
        return Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => $code,
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => $code,
            'status' => 'active',
        ]);
    }

    private function enroll(Student $student, Classroom $classroom, int $number): ClassroomStudent
    {
        return ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'student_number' => $number,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
    }

    private function makeCard(Student $student, string $level, string $section): StudentCard
    {
        return StudentCard::create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'นาย ทดสอบ '.$student->student_id,
            'class_level' => $level,
            'class_section' => $section,
            'student_status' => 'active',
        ]);
    }

    private function cardUrl(string $suffix = ''): string
    {
        return "/api/academies/{$this->academy->id}/student-cards".$suffix;
    }
}
