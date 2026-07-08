<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\User;
use App\Services\Import\RosterReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RosterReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'id' => 1,
            'name' => 'Operator Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'username' => 'operatoradmin',
        ]);
    }

    public function test_roster_reconciliation_decision_matrix(): void
    {
        $academy = Academy::create([
            'id' => 1,
            'name' => 'Test School',
            'slug' => 'test-school',
            'user_id' => 1,
        ]);

        $year2568 = AcademicYear::create([
            'id' => 1,
            'academy_id' => $academy->id,
            'name' => '2568',
            'is_current' => false,
            'start_date' => '2025-05-01',
            'end_date' => '2026-03-31',
        ]);

        $year2569 = AcademicYear::create([
            'id' => 2,
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);

        // Target classroom: ม.1/1
        $class1 = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year2569->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        // Classroom 2568
        $class2568 = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year2568->id,
            'grade_level' => 'ป.6',
            'section' => '1',
            'name' => 'ป.6/1',
        ]);

        // Student for promotion: was in ป.6/1, now in ม.1/1
        $promoStudent = Student::create([
            'academy_id' => $academy->id,
            'student_id' => '1001',
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'รักดี',
            'status' => 'active',
        ]);
        ClassroomStudent::create([
            'classroom_id' => $class2568->id,
            'student_id' => $promoStudent->id,
            'academy_id' => $academy->id,
            'academic_year_id' => $year2568->id,
            'status' => 'active',
            'student_number' => 1,
        ]);

        // Student that will be missing (flag_missing)
        $missingStudent = Student::create([
            'academy_id' => $academy->id,
            'student_id' => '1003',
            'first_name_th' => 'มานี',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);
        ClassroomStudent::create([
            'classroom_id' => $class2568->id,
            'student_id' => $missingStudent->id,
            'academy_id' => $academy->id,
            'academic_year_id' => $year2568->id,
            'status' => 'active',
            'student_number' => 2,
        ]);

        // Import Batch
        $batch = StudentImportBatch::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year2569->id,
            'import_type' => 'roster_reconciliation',
            'filename' => 'dummy.json',
            'original_filename' => 'dummy.json',
            'status' => 'uploaded',
            'created_by' => 1,
            'idempotency_key' => 'test-idempotency-key-hash',
        ]);

        // Prepare JSON structure
        $jsonData = [
            'academic_year' => '2569',
            'classrooms' => [
                [
                    'grade_level' => 'ม.1',
                    'section' => '1',
                    'name' => 'ม.1/1',
                    'homeroom_teacher_raw' => 'Operator Admin',
                    'students' => [
                        [
                            'student_code' => '1001',
                            'full_name' => 'เด็กชายสมชาย รักดี',
                        ],
                        [
                            'student_code' => '1002', // New intake
                            'full_name' => 'เด็กชายสมยศ เรียนเก่ง',
                        ],
                    ],
                ],
            ],
        ];

        $service = app(RosterReconciliationService::class);
        $service->preview($academy, $year2569, $batch, $jsonData);

        $this->assertEquals('validated', $batch->fresh()->status);
        $rows = $batch->rows()->get();

        // 2 matched from JSON + 1 missing from 2568 = 3 total rows
        $this->assertCount(3, $rows);

        $promoRow = $rows->where('action', 'promote_student')->first();
        $this->assertNotNull($promoRow);
        $this->assertEquals($promoStudent->id, $promoRow->matched_student_id);

        $newIntakeRow = $rows->where('action', 'new_intake')->first();
        $this->assertNotNull($newIntakeRow);
        $this->assertEquals('สมยศ', $newIntakeRow->normalized_data['personal']['first_name_th']);

        // Process rows (commit)
        $operator = User::find(1);
        foreach ($rows as $row) {
            $service->processRow($row, $operator);
        }

        // Decision 0.3: missing students remain active for manual review.
        $this->assertSame('active', $missingStudent->fresh()->status);
        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $missingStudent->id,
            'classroom_id' => $class2568->id,
            'academic_year_id' => $year2568->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('classroom_students', [
            'student_id' => $missingStudent->id,
            'academic_year_id' => $year2569->id,
        ]);
        $this->assertSame('imported', $rows->where('action', 'flag_missing')->first()->fresh()->status);

        // Verify promotion student enrolled in 2569 ม.1/1
        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $promoStudent->id,
            'classroom_id' => $class1->id,
            'academic_year_id' => $year2569->id,
            'status' => 'active',
        ]);

        // Verify new intake student created and enrolled
        $newStudent = Student::where('student_id', '1002')->first();
        $this->assertNotNull($newStudent);
        $this->assertEquals('สมยศ', $newStudent->first_name_th);
        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $newStudent->id,
            'classroom_id' => $class1->id,
            'academic_year_id' => $year2569->id,
            'status' => 'active',
        ]);
    }

    public function test_roster_reconciliation_additional_scenarios(): void
    {
        $academy = Academy::create([
            'id' => 1,
            'name' => 'Test School',
            'slug' => 'test-school',
            'user_id' => 1,
        ]);

        $year2568 = AcademicYear::create([
            'id' => 1,
            'academy_id' => $academy->id,
            'name' => '2568',
            'is_current' => false,
            'start_date' => '2025-05-01',
            'end_date' => '2026-03-31',
        ]);

        $year2569 = AcademicYear::create([
            'id' => 2,
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);

        $classroom2568_M6 = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year2568->id,
            'grade_level' => 'ม.6',
            'section' => '1',
            'name' => 'ม.6/1',
        ]);

        $classroom2569 = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year2569->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        // Scenario 1: Unchanged student number update
        $unchangedStudent = Student::create([
            'academy_id' => $academy->id,
            'student_id' => '1004',
            'first_name_th' => 'มาโนช',
            'last_name_th' => 'มีตังค์',
            'status' => 'active',
        ]);

        ClassroomStudent::create([
            'classroom_id' => $classroom2569->id,
            'student_id' => $unchangedStudent->id,
            'academy_id' => $academy->id,
            'academic_year_id' => $year2569->id,
            'status' => 'active',
            'student_number' => 5, // Old number
        ]);

        // Scenario 2: M.6 auto-graduate
        $gradStudent = Student::create([
            'academy_id' => $academy->id,
            'student_id' => '1005',
            'first_name_th' => 'สมศรี',
            'last_name_th' => 'เรียนจบ',
            'status' => 'active',
        ]);

        ClassroomStudent::create([
            'classroom_id' => $classroom2568_M6->id,
            'student_id' => $gradStudent->id,
            'academy_id' => $academy->id,
            'academic_year_id' => $year2568->id,
            'status' => 'active',
            'student_number' => 1,
        ]);

        // Scenario 3: Ambiguous teacher
        $teacher1 = User::create([
            'name' => 'สมคิด รักดี',
            'email' => 't1@example.com',
            'password' => bcrypt('password'),
            'username' => 'somkid1',
        ]);
        $teacher2 = User::create([
            'name' => 'สมคิด รักดี',
            'email' => 't2@example.com',
            'password' => bcrypt('password'),
            'username' => 'somkid2',
        ]);

        // Link users to academy members with teacher role
        DB::table('academy_members')->insert([
            ['academy_id' => $academy->id, 'user_id' => $teacher1->id, 'role' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['academy_id' => $academy->id, 'user_id' => $teacher2->id, 'role' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Import Batch
        $batch = StudentImportBatch::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year2569->id,
            'import_type' => 'roster_reconciliation',
            'filename' => 'dummy_extra.json',
            'original_filename' => 'dummy_extra.json',
            'status' => 'uploaded',
            'created_by' => 1,
            'idempotency_key' => 'test-idempotency-key-extra',
        ]);

        $jsonData = [
            'academic_year' => '2569',
            'classrooms' => [
                [
                    'grade_level' => 'ม.1',
                    'section' => '1',
                    'name' => 'ม.1/1',
                    'homeroom_teacher_raw' => 'สมคิด รักดี',
                    'students' => [
                        [
                            'student_code' => '1004',
                            'full_name' => 'นายมาโนช มีตังค์',
                        ],
                    ],
                ],
            ],
        ];

        $service = app(RosterReconciliationService::class);
        $service->preview($academy, $year2569, $batch, $jsonData);

        $rows = $batch->rows()->get();

        // 1 unchanged (1004) + 1 auto_graduate (1005) = 2 rows
        $this->assertCount(2, $rows);

        $unchangedRow = $rows->where('action', 'unchanged')->first();
        $this->assertNotNull($unchangedRow);
        // Student number is stored as 1 (counter)
        $this->assertEquals(1, $unchangedRow->normalized_data['admission']['student_number']);

        $gradRow = $rows->where('action', 'auto_graduate')->first();
        $this->assertNotNull($gradRow);

        // Check teacher assignments mapping
        $teacherAssignments = $batch->fresh()->default_values['classroom_teacher_assignments'];
        $this->assertCount(1, $teacherAssignments);
        $this->assertEquals('ambiguous', $teacherAssignments[0]['match_status']);
        $this->assertNull($teacherAssignments[0]['matched_user_id']);
        $this->assertContains($teacher1->id, $teacherAssignments[0]['candidate_user_ids']);
        $this->assertContains($teacher2->id, $teacherAssignments[0]['candidate_user_ids']);

        // Process rows
        $operator = User::create([
            'name' => 'Operator Admin 2',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
            'username' => 'operatoradmin2',
        ]);

        foreach ($rows as $row) {
            $service->processRow($row, $operator);
        }

        // Verify unchanged student number got updated
        $this->assertEquals(1, ClassroomStudent::where('student_id', $unchangedStudent->id)
            ->where('classroom_id', $classroom2569->id)
            ->where('status', 'active')
            ->first()->student_number);

        // Verify M.6 auto-graduate status
        $this->assertEquals('graduated', $gradStudent->fresh()->status);
        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $gradStudent->id,
            'classroom_id' => $classroom2568_M6->id,
            'status' => 'graduated',
        ]);
    }
}
