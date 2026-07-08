<?php

namespace Tests\Feature\HomeVisit;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\HomeVisitZone;
use App\Models\Student;
use App\Models\StudentHomeVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_returns_200_and_scoped_counts_per_academy(): void
    {
        [$admin1, $academy1, $student1] = $this->setupAcademy('academy1');
        [$admin2, $academy2, $student2] = $this->setupAcademy('academy2');

        // Create visits in academy 1
        StudentHomeVisit::create([
            'academy_id' => $academy1->id,
            'student_id' => $student1->id,
            'visit_date' => '2026-07-03',
            'visitor_name' => 'Teacher 1',
            'visitor_position' => 'Teacher',
            'visit_status' => 'completed',
            'created_by' => $admin1->id,
        ]);

        // Create visits in academy 2 (2 visits)
        StudentHomeVisit::create([
            'academy_id' => $academy2->id,
            'student_id' => $student2->id,
            'visit_date' => '2026-07-03',
            'visitor_name' => 'Teacher 2',
            'visitor_position' => 'Teacher',
            'visit_status' => 'completed',
            'created_by' => $admin2->id,
        ]);
        StudentHomeVisit::create([
            'academy_id' => $academy2->id,
            'student_id' => $student2->id,
            'visit_date' => '2026-07-04',
            'visitor_name' => 'Teacher 2',
            'visitor_position' => 'Teacher',
            'visit_status' => 'pending',
            'created_by' => $admin2->id,
        ]);

        // Assert academy 1 statistics
        $response1 = $this->actingAs($admin1, 'api')
            ->getJson("/api/academies/{$academy1->id}/home-visits/admin/statistics");

        $response1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('statistics.totalStudents', 1)
            ->assertJsonPath('statistics.totalVisits', 1)
            ->assertJsonPath('statistics.completedVisits', 1);

        // Assert academy 2 statistics
        $response2 = $this->actingAs($admin2, 'api')
            ->getJson("/api/academies/{$academy2->id}/home-visits/admin/statistics");

        $response2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('statistics.totalStudents', 1)
            ->assertJsonPath('statistics.totalVisits', 2)
            ->assertJsonPath('statistics.completedVisits', 1)
            ->assertJsonPath('statistics.pendingVisits', 1);
    }

    public function test_students_filters_by_classroom_id_and_scoping(): void
    {
        [$admin, $academy, $student] = $this->setupAcademy('my_academy');

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        // Associate student with classroom
        $student->classroomEnrollments()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'student_number' => 1,
            'status' => 'active',
            'enrolled_at' => today(),
        ]);

        $student->academicInfos()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'current_grade' => 'ม.1',
            'current_class' => '1',
            'classroom_full' => 'ม.1/1',
            'academic_year' => '2569',
            'is_current' => true,
        ]);

        // Student 2 in same academy, not in classroom
        $student2 = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Student2',
            'last_name_th' => 'Test2',
        ]);

        // Search with classroom_id filter
        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/academies/{$academy->id}/home-visits/admin/students?classroom_id={$classroom->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('students.data'));
        $this->assertEquals($student->id, $response->json('students.data.0.id'));
    }

    public function test_update_student_transfers_enrollment(): void
    {
        [$admin, $academy, $student] = $this->setupAcademy('academy_update');

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $classroomA = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        $classroomB = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
        ]);

        // Start student in Classroom A
        $student->classroomEnrollments()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroomA->id,
            'academic_year_id' => $year->id,
            'student_number' => 1,
            'status' => 'active',
            'enrolled_at' => today(),
        ]);

        $student->academicInfos()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroomA->id,
            'current_grade' => 'ม.1',
            'current_class' => '1',
            'classroom_full' => 'ม.1/1',
            'academic_year' => '2569',
            'is_current' => true,
        ]);

        // Update to Classroom B
        $response = $this->actingAs($admin, 'api')
            ->putJson("/api/academies/{$academy->id}/home-visits/admin/students/{$student->id}", [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'classroom_id' => $classroomB->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Assert database updates
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $classroomA->id,
            'student_id' => $student->id,
            'status' => 'transferred',
        ]);

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $classroomB->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $student->id,
            'classroom_id' => $classroomB->id,
            'is_current' => true,
        ]);
    }

    public function test_dashboard_mock_returns_404_in_production(): void
    {
        [$admin, $academy] = $this->setupAcademy('prod_test');

        app()->detectEnvironment(fn () => 'production');

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/academies/{$academy->id}/home-visits/admin/dashboard/mock");

        $response->assertNotFound();
    }

    public function test_update_student_rejects_classroom_from_other_academy(): void
    {
        [$admin, $academy, $student] = $this->setupAcademy('a1_reject');
        [, $otherAcademy] = $this->setupAcademy('a2_reject');

        $otherYear = AcademicYear::create([
            'academy_id' => $otherAcademy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $otherClassroom = Classroom::create([
            'academy_id' => $otherAcademy->id,
            'academic_year_id' => $otherYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        $response = $this->actingAs($admin, 'api')
            ->putJson("/api/academies/{$academy->id}/home-visits/admin/students/{$student->id}", [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'classroom_id' => $otherClassroom->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('classroom_id');
    }

    public function test_export_visits_uses_classroom_full_from_academic_info(): void
    {
        [$admin, $academy, $student] = $this->setupAcademy('export_test');

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.5',
            'section' => '3',
            'name' => 'ม.5/3',
        ]);

        $student->academicInfos()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'current_grade' => 'ม.5',
            'current_class' => '3',
            'classroom_full' => 'ม.5/3',
            'academic_year' => '2569',
            'is_current' => true,
        ]);

        StudentHomeVisit::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'visit_date' => '2026-07-03',
            'visitor_name' => 'Teacher Export',
            'visitor_position' => 'Teacher',
            'visit_status' => 'completed',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->get("/api/academies/{$academy->id}/home-visits/admin/export/visits");

        $response->assertOk();

        $body = $response->streamedContent();
        $this->assertStringContainsString('ม.5/3', $body);
        $this->assertStringNotContainsString(',N/A,Teacher Export,', $body);
    }

    public function test_students_legacy_classroom_string_filter_falls_back_to_classroom_full(): void
    {
        [$admin, $academy, $student] = $this->setupAcademy('legacy_filter');

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1',
        ]);

        $student->academicInfos()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'current_grade' => 'ม.2',
            'current_class' => '1',
            'classroom_full' => 'ม.2/1',
            'academic_year' => '2569',
            'is_current' => true,
        ]);

        // Another student in a different classroom (to prove the filter narrows)
        $otherClassroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.3',
            'section' => '1',
            'name' => 'ม.3/1',
        ]);
        $other = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Other',
            'last_name_th' => 'Student',
        ]);
        $other->academicInfos()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $otherClassroom->id,
            'current_grade' => 'ม.3',
            'current_class' => '1',
            'classroom_full' => 'ม.3/1',
            'academic_year' => '2569',
            'is_current' => true,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/academies/{$academy->id}/home-visits/admin/students?classroom=".urlencode('ม.2/1'));

        $response->assertOk();
        $this->assertCount(1, $response->json('students.data'));
        $this->assertEquals($student->id, $response->json('students.data.0.id'));
    }

    public function test_visits_filters_by_classroom_id(): void
    {
        [$admin, $academy, $student] = $this->setupAcademy('visits_filter');

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.4',
            'section' => '2',
            'name' => 'ม.4/2',
        ]);

        $student->academicInfos()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'current_grade' => 'ม.4',
            'current_class' => '2',
            'classroom_full' => 'ม.4/2',
            'academic_year' => '2569',
            'is_current' => true,
        ]);

        StudentHomeVisit::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'visit_date' => '2026-07-05',
            'visitor_name' => 'Teacher V',
            'visitor_position' => 'Teacher',
            'visit_status' => 'completed',
            'created_by' => $admin->id,
        ]);

        // Second student outside the classroom, also has a visit
        $other = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Outside',
            'last_name_th' => 'Room',
        ]);
        StudentHomeVisit::create([
            'academy_id' => $academy->id,
            'student_id' => $other->id,
            'visit_date' => '2026-07-05',
            'visitor_name' => 'Teacher V2',
            'visitor_position' => 'Teacher',
            'visit_status' => 'completed',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/academies/{$academy->id}/home-visits/admin/visits?classroom_id={$classroom->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('visits.data'));
        $this->assertEquals($student->id, $response->json('visits.data.0.student.id'));
    }

    public function test_academy_admin_can_create_update_and_delete_a_scoped_visit(): void
    {
        [$admin, $academy, $student] = $this->setupAcademy('visit_crud');
        [, $otherAcademy, $otherStudent] = $this->setupAcademy('visit_crud_other');

        $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/home-visits/admin/visits", [
                'student_id' => $otherStudent->id,
                'visit_date' => '2026-07-09',
                'status' => 'pending',
            ])
            ->assertUnprocessable();

        $created = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/home-visits/admin/visits", [
                'student_id' => $student->id,
                'visit_date' => '2026-07-09',
                'purpose' => 'Initial visit',
                'status' => 'pending',
            ])
            ->assertCreated()
            ->assertJsonPath('visit.academy_id', $academy->id)
            ->assertJsonPath('visit.student_id', $student->id);

        $visitId = $created->json('visit.id');

        $this->actingAs($admin, 'api')
            ->putJson("/api/academies/{$academy->id}/home-visits/admin/visits/{$visitId}", [
                'visit_date' => '2026-07-10',
                'status' => 'completed',
            ])
            ->assertOk()
            ->assertJsonPath('visit.visit_status', 'completed');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/academies/{$otherAcademy->id}/home-visits/admin/visits/{$visitId}")
            ->assertForbidden();

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/academies/{$academy->id}/home-visits/admin/visits/{$visitId}")
            ->assertOk();

        $this->assertDatabaseMissing('student_home_visits', ['id' => $visitId]);
    }

    public function test_zones_are_scoped_to_academy_and_legacy_admin_route_is_gone(): void
    {
        [$admin, $academy] = $this->setupAcademy('zone_scope');
        [, $otherAcademy] = $this->setupAcademy('zone_scope_other');

        $zone = HomeVisitZone::create([
            'academy_id' => $academy->id,
            'zone_name' => 'North',
            'zone_code' => 'A'.$academy->id.'_NORTH',
        ]);
        HomeVisitZone::create([
            'academy_id' => $otherAcademy->id,
            'zone_name' => 'South',
            'zone_code' => 'A'.$otherAcademy->id.'_SOUTH',
        ]);

        $this->actingAs($admin, 'api')
            ->getJson("/api/academies/{$academy->id}/home-visits/zones")
            ->assertOk()
            ->assertJsonCount(1, 'zones')
            ->assertJsonPath('zones.0.id', $zone->id);

        $this->actingAs($admin, 'api')
            ->getJson('/api/home-visit/admin/visits')
            ->assertNotFound();
    }

    private function setupAcademy(string $tag): array
    {
        $admin = User::create([
            'name' => ucfirst($tag).' Admin',
            'email' => $tag.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'username' => 'u'.$tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);

        $academy = Academy::create([
            'name' => 'Academy '.ucfirst($tag),
            'user_id' => $admin->id,
        ]);

        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 1,
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Firstname'.$tag,
            'last_name_th' => 'Lastname'.$tag,
        ]);

        return [$admin, $academy, $student];
    }
}
