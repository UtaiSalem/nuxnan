<?php

namespace Tests\Feature\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CL-S7 Phase B — accessor ชั้นเรียนต้องมาจากแหล่งจริง (classroom_students active → classrooms)
 * ไม่ใช่คอลัมน์ denormalized students.class_level/class_section
 *
 * สัญญา (ยืนยันจากเทสต์เดิม + StudentEnrollmentService):
 *  1. class_level normalize เป็นตัวเลข ('ม.2' → '2') · class_section ดิบ
 *  2. ไม่มี enrollment active → คืนค่าคอลัมน์เดิม (graduate/drop set null ไว้) ห้าม fallback ไป enrollment ล่าสุด
 */
class StudentClassAccessorTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $tag = ''): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => 'u'.$tag.uniqid().'@x.test',
            'password' => bcrypt('x'),
            'username' => 'u'.$tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    /** @return array{0: Academy, 1: AcademicYear} */
    private function makeAcademy(): array
    {
        $academy = Academy::create([
            'name' => 'A_'.uniqid(),
            'user_id' => $this->makeUser('own')->id,
        ]);
        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        return [$academy, $year];
    }

    private function makeClassroom(Academy $academy, AcademicYear $year, string $level, string $section): Classroom
    {
        return Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => $level,
            'section' => $section,
            'name' => $level.'/'.$section,
        ]);
    }

    private function makeStudent(Academy $academy, array $extra = []): Student
    {
        return Student::create(array_merge([
            'academy_id' => $academy->id,
            'user_id' => $this->makeUser('stu')->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ชั้นเรียน'.uniqid(),
            'status' => 'active',
        ], $extra));
    }

    private function enroll(Student $s, Classroom $c, string $status, int $number = 1): ClassroomStudent
    {
        return ClassroomStudent::create([
            'academy_id' => $c->academy_id,
            'student_id' => $s->id,
            'classroom_id' => $c->id,
            'academic_year_id' => $c->academic_year_id,
            'status' => $status,
            'student_number' => $number,
        ]);
    }

    public function test_reads_from_active_enrollment_and_normalizes_grade_even_when_column_drifted(): void
    {
        [$academy, $year] = $this->makeAcademy();
        // คอลัมน์เดิม drift ไว้ที่ 9/9 แต่แหล่งจริงคือ ม.1/2
        $student = $this->makeStudent($academy, ['class_level' => '9', 'class_section' => '9']);
        $classroom = $this->makeClassroom($academy, $year, 'ม.1', '2');
        $this->enroll($student, $classroom, ClassroomStudent::STATUS_ACTIVE);

        $fresh = Student::find($student->id);

        // normalize: 'ม.1' → '1' (ตรงรูปแบบที่ normalizeGradeLevel เขียนลงคอลัมน์เดิม)
        $this->assertSame('1', $fresh->class_level);
        $this->assertSame('2', (string) $fresh->class_section);
    }

    public function test_returns_null_when_no_active_enrollment_and_does_not_resurrect_old_class(): void
    {
        [$academy, $year] = $this->makeAcademy();
        // graduate/drop ใน service จะ set คอลัมน์เป็น null — จำลองสภาพนั้น
        $student = $this->makeStudent($academy, ['class_level' => null, 'class_section' => null]);
        $classroom = $this->makeClassroom($academy, $year, 'ม.6', '3');
        $this->enroll($student, $classroom, ClassroomStudent::STATUS_GRADUATED);

        $fresh = Student::find($student->id);

        // ห้ามไปหยิบ enrollment ที่จบแล้วมาโชว์
        $this->assertNull($fresh->class_level);
        $this->assertNull($fresh->class_section);
    }

    public function test_falls_back_to_stored_column_when_no_enrollment_at_all(): void
    {
        [$academy] = $this->makeAcademy();
        $student = $this->makeStudent($academy, ['class_level' => '5', 'class_section' => '1']);

        $fresh = Student::find($student->id);

        // ยังไม่ถึง Phase E (คอลัมน์ยังอยู่) → ใช้ค่าเดิมเป็นทางสำรอง
        $this->assertSame('5', $fresh->class_level);
        $this->assertSame('1', (string) $fresh->class_section);
    }

    public function test_eager_loading_keeps_query_count_flat_no_n_plus_1(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year, 'ม.2', '1');
        foreach (range(1, 5) as $i) {
            $s = $this->makeStudent($academy);
            $this->enroll($s, $classroom, ClassroomStudent::STATUS_ACTIVE, $i);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $students = Student::where('academy_id', $academy->id)
            ->with('currentEnrollment.classroom')
            ->get();

        foreach ($students as $s) {
            $this->assertSame('2', $s->class_level);
        }

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(5, $students);
        // students + currentEnrollment + classrooms = ~3 ครั้ง คงที่ไม่ขึ้นกับจำนวนนักเรียน
        // ถ้าเป็น N+1 จะพุ่งเกิน 10
        $this->assertLessThanOrEqual(5, $count, "คาดว่า query คงที่ แต่ได้ {$count} ครั้ง (อาจเป็น N+1)");
    }
}
