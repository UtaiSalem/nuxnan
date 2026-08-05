<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentCardRoomConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $user = User::create(['name' => 'Room owner', 'email' => uniqid().'@test.local', 'password' => bcrypt('x'), 'username' => uniqid(), 'reference_code' => uniqid(), 'personal_code' => uniqid()]);
        $academy = Academy::create(['name' => 'Room test '.uniqid(), 'user_id' => $user->id]);
        $year = AcademicYear::create(['academy_id' => $academy->id, 'name' => '2569', 'is_current' => true, 'start_date' => '2026-05-01', 'end_date' => '2027-03-31']);
        $rooms = collect(['ม.1', 'ม.11'])->mapWithKeys(fn ($grade, $i) => [$grade => Classroom::create([
            'academy_id' => $academy->id, 'academic_year_id' => $year->id, 'grade_level' => $grade, 'section' => (string) ($i + 1), 'name' => $grade.'/'.($i + 1),
        ])]);
        $students = $rooms->mapWithKeys(function ($room, $grade) use ($academy, $year) {
            $student = Student::create(['academy_id' => $academy->id, 'student_id' => 'S'.uniqid(), 'first_name_th' => 'Test', 'last_name_th' => $grade, 'status' => 'active']);
            ClassroomStudent::create(['academy_id' => $academy->id, 'student_id' => $student->id, 'classroom_id' => $room->id, 'academic_year_id' => $year->id, 'status' => 'active', 'student_number' => 1]);
            StudentCard::create(['academy_id' => $academy->id, 'student_id' => $student->id, 'student_number' => $student->student_id, 'class_level' => '99', 'class_section' => '99', 'student_status' => 'active']);

            return [$grade => $student];
        });

        return [$academy, $rooms, $students, $user];
    }

    public function test_dashboard_uses_current_classrooms_and_separates_one_from_eleven(): void
    {
        [$academy, $rooms, , $user] = $this->fixture();
        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/student-cards/dashboard")->assertOk();
        $this->assertSame(2, $response->json('totalStudents'));
        $this->assertCount(2, $response->json('levels'));
        $this->assertSame(['1', '11'], collect($response->json('levels'))->pluck('level')->sort()->values()->all());
    }

    public function test_dashboard_sorts_more_than_nine_sections_numerically(): void
    {
        [$academy, $rooms, , $user] = $this->fixture();
        $levelOne = $rooms->first();

        foreach (range(2, 11) as $section) {
            Classroom::create([
                'academy_id' => $academy->id,
                'academic_year_id' => $levelOne->academic_year_id,
                'grade_level' => 'เธก.1',
                'section' => (string) $section,
                'name' => 'เธก.1/'.$section,
            ]);
        }

        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/student-cards/dashboard")->assertOk();

        $this->assertSame(
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'],
            collect($response->json('levels'))->firstWhere('level', '1')['sections']
        );
    }

    public function test_orphaned_enrollment_is_not_a_dashboard_room(): void
    {
        [$academy, $rooms, , $user] = $this->fixture();
        $room = $rooms['ม.1'];
        DB::table('classrooms')->where('id', $room->id)->update(['status' => 'inactive']);
        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/student-cards/dashboard")->assertOk();
        $this->assertSame(1, $response->json('totalStudents'));
        $this->assertSame(['11'], collect($response->json('levels'))->pluck('level')->all());
    }

    public function test_statistics_photo_counts_cannot_make_without_photo_negative(): void
    {
        [$academy, , , $user] = $this->fixture();
        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/student-cards/statistics")->assertOk();
        $stats = $response->json('statistics');
        $this->assertGreaterThanOrEqual(0, $stats['withoutPhoto']);
        $this->assertSame($stats['totalStudents'], $stats['withPhoto'] + $stats['withoutPhoto']);
    }
}
