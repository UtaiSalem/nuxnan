<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\User;
use App\Services\StudentCardSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentRosterImportIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create operator user explicitly
        User::create([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'username' => 'testuser',
        ]);
    }

    public function test_full_xlsx_import_flow(): void
    {
        // 1. Create mock academy and academic year
        $academy = Academy::create([
            'id' => 1,
            'name' => 'Test Academy',
            'slug' => 'test-academy',
            'user_id' => 1,
        ]);

        $year = AcademicYear::create([
            'id' => 2,
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);

        // 2. Create target classroom
        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '5',
            'name' => 'ม.1/5',
        ]);

        // 3. Create existing student to trigger UPDATE_PERSONAL and MOVE_CLASSROOM
        $existingStudent = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S001',
            'citizen_id' => null,
            'title_prefix_th' => 'เด็กชาย',
            'first_name_th' => 'สมเกียรติ',
            'last_name_th' => 'รักเรียน',
            'status' => 'active',
        ]);

        // Create active enrollment in another room for the same year to trigger MOVE_CLASSROOM
        $oldRoom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        ClassroomStudent::create([
            'classroom_id' => $oldRoom->id,
            'student_id' => $existingStudent->id,
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
            'student_number' => 10,
        ]);

        // 4. Build dummy spreadsheet
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'เลขประจำตัวประชาชน',
            'เลขประจำตัวนักเรียน ', // trailing space
            'ชั้นเรียน',
            'คำนำหน้าชื่อ',
            'ชื่อ',
            'นามสกุล',
            'เพศ',
            'ว.ด.ป. เกิด',
            'ชื่อโรงเรียนเดิม',
            'จังหวัดโรงเรียนเดิม',
            'ชั้นเรียน.1',
            'บ้านเลขที่',
            'ตำบล/แขวง',
            'ความสูง (ซม.)',
            'น้ำหนัก (กก.)',
        ];

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }

        // Row 2: Match and Move Room
        $rowData2 = [
            '1234567890123',
            'S001',
            'ม.1/5',
            'เด็กชาย',
            'สมเกียรติ',
            'รักเรียน',
            'ชาย',
            '07 พ.ค. 2569',
            'โรงเรียนเก่า A',
            'กรุงเทพฯ',
            'ป.6',
            '99/1',
            'ทดสอบ',
            170,
            60,
        ];
        foreach ($rowData2 as $colIndex => $val) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 2, $val);
        }

        // Row 3: New Student
        $rowData3 = [
            '2234567890123',
            'S002',
            'ม.1/5',
            'เด็กหญิง',
            'สมศรี',
            'เก่งเรียน',
            'หญิง',
            '15 พ.ค. 2569',
            'โรงเรียนเก่า B',
            'นนทบุรี',
            'ป.6',
            '88/2',
            'ทดสอบใหม่',
            160,
            50,
        ];
        foreach ($rowData3 as $colIndex => $val) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 3, $val);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'roster_integration_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        try {
            // 5. Run preview command
            $this->artisan('roster:preview', [
                'file' => $tempFile,
                '--academy' => $academy->id,
                '--year' => $year->id,
            ])->assertExitCode(0);

            // Verify batch created
            $batch = StudentImportBatch::where('import_type', 'roster_update')->latest()->first();
            $this->assertNotNull($batch);
            $this->assertEquals('validated', $batch->status);

            // Assert actions mapped correctly
            $rows = $batch->rows()->get();
            $this->assertCount(2, $rows);

            $row1 = $rows->where('row_number', 2)->first();
            $this->assertEquals('move_classroom', $row1->action);
            $this->assertEquals($existingStudent->id, $row1->matched_student_id);

            $row2 = $rows->where('row_number', 3)->first();
            $this->assertEquals('new_student', $row2->action);

            // 6. Commit the batch
            $cardSync = Mockery::mock(StudentCardSyncService::class);
            $cardSync->shouldReceive('commitSync')
                ->once()
                ->with(
                    Mockery::type(Academy::class),
                    Mockery::type(AcademicYear::class),
                    Mockery::type(User::class),
                )
                ->andReturn([]);
            $this->app->instance(StudentCardSyncService::class, $cardSync);

            $this->artisan('roster:commit', [
                'batch_id' => $batch->id,
                '--chunk' => 10,
            ])->expectsConfirmation(
                'Are you sure you want to commit 2 student roster updates to the database?',
                'yes'
            )->assertExitCode(0);

            // Verify students updated & enrolled
            $existingStudent->refresh();
            $this->assertEquals('1234567890123', $existingStudent->citizen_id);
            $this->assertEquals('ม.1', $existingStudent->currentAcademicInfo->current_grade);
            $this->assertEquals('5', $existingStudent->currentAcademicInfo->current_class);
            $this->assertEquals('โรงเรียนเก่า A', $existingStudent->currentAcademicInfo->previous_school_name);
            $this->assertDatabaseHas('student_addresses', [
                'student_id' => $existingStudent->id,
                'address_type' => 'current',
                'house_number' => '99/1',
                'is_current' => true,
            ]);
            $this->assertDatabaseHas('student_health_info', [
                'student_id' => $existingStudent->id,
                'height_cm' => 170,
                'weight_kg' => 60,
            ]);

            $newStudent = Student::where('student_id', 'S002')->first();
            $this->assertNotNull($newStudent);
            $this->assertEquals('สมศรี', $newStudent->first_name_th);
            $this->assertEquals('โรงเรียนเก่า B', $newStudent->currentAcademicInfo->previous_school_name);

        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
