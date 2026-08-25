<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Guardian;
use App\Models\GuardianContact;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClassroomStudentGuardianPayloadTest extends TestCase
{
    use RefreshDatabase;

    private function academyWithMember(array $permissions = []): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'test-role-'.uniqid(),
            'display_name_th' => 'Test role',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => 'admin',
            'status' => 2,
        ]);

        return [$academy, $user];
    }

    private function createStudentWithGuardian(Academy $academy, ?User $studentUser = null): array
    {
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ผู้ปกครอง',
            'status' => 'active',
        ]);

        $uniq = uniqid();
        $guardianData = [
            'title_prefix' => 'นาย',
            'first_name' => 'Somchai'.$uniq,
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'พ่อ',
            'status' => 'alive',
            'monthly_income' => 50000,
            'citizen_id' => '1234567890123',
        ];

        // This creates legacy StudentGuardian + links, and the new Guardian person record.
        $legacy = app(GuardianWriteService::class)->create($student, $guardianData);
        $guardian = Guardian::latest('id')->first();

        return [$student, $guardian, $legacy->id];
    }

    private function setupClassroom(Academy $academy, Student $student): Classroom
    {
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
            'status' => Classroom::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        ClassroomStudent::create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => 1,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        return $classroom;
    }

    public function test_owner_gets_full_payload_with_primary_phone()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        [$student, $guardian, $legacyId] = $this->createStudentWithGuardian($academy);
        $classroom = $this->setupClassroom($academy, $student);

        // Case 1 & 2: Multiple phones, picks primary
        GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'guardian_id' => $legacyId,
            'contact_type' => 'phone',
            'contact_value' => '0822222222',
            'is_primary' => false,
            'is_verified' => false,
        ]);
        GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'guardian_id' => $legacyId,
            'contact_type' => 'phone',
            'contact_value' => '0811111111',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        // Reset log count to check for sensitive_view log later
        DB::table('member_activity_logs')->truncate();

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}");

        $response->assertOk();

        $guardiansResponse = $response->json('student.guardians');
        $this->assertCount(1, $guardiansResponse);

        $g = $guardiansResponse[0];

        // Case 1: guardian_name is correctly formatted
        $this->assertEquals("นาย {$guardian->first_name} Jaidee", $g['guardian_name']);

        // Case 2: primary phone is selected
        $this->assertEquals('0811111111', $g['phone']);

        // Case 6: Token with access (owner) sees sensitive info
        $this->assertArrayHasKey('citizen_id', $g);
        $this->assertEquals('1234567890123', $g['citizen_id']);
        $this->assertArrayHasKey('monthly_income', $g);
        $this->assertEquals(50000, $g['monthly_income']);

        // Case 7: Original keys are still present
        $this->assertEquals('father', $g['guardian_type']);
        $this->assertEquals('พ่อ', $g['relationship']);
        $this->assertEquals($guardian->first_name, $g['first_name']);
        $this->assertEquals('Jaidee', $g['last_name']);
        $this->assertArrayHasKey('is_primary_contact', $g);
        $this->assertArrayHasKey('is_emergency_contact', $g);

        // Check if log was created (Case 6)
        $this->assertDatabaseHas('member_activity_logs', [
            'action' => 'guardian_sensitive_view',
        ]);
    }

    public function test_guardian_without_phone()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        [$student, $guardian, $legacyId] = $this->createStudentWithGuardian($academy);
        $classroom = $this->setupClassroom($academy, $student);

        // Case 3: Guardian without phone -> phone is null, no 500
        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}");

        $response->assertOk();
        $this->assertNull($response->json('student.guardians.0.phone'));
    }

    public function test_student_without_guardian_does_not_log_sensitive_view()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S123',
            'title_prefix_th' => 'ด.ช.',
            'first_name_th' => 'ไม่มี',
            'last_name_th' => 'ผู้ปกครอง',
            'status' => 'active',
        ]);
        $classroom = $this->setupClassroom($academy, $student);

        DB::table('member_activity_logs')->truncate();

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}");

        $response->assertOk();

        // Case 4: Student without guardian -> guardians is []
        $this->assertEquals([], $response->json('student.guardians'));

        // Case 4: No log created
        $this->assertDatabaseMissing('member_activity_logs', [
            'action' => 'guardian_sensitive_view',
        ]);
    }

    public function test_token_without_permission_does_not_see_sensitive_fields()
    {
        // Case 5: Token without guardians.sensitive.view
        [$academy, $user] = $this->academyWithMember(['classrooms.view']); // No sensitive view permission

        [$student, $guardian, $legacyId] = $this->createStudentWithGuardian($academy);
        $classroom = $this->setupClassroom($academy, $student);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}");

        $response->assertOk();

        $g = $response->json('student.guardians.0');

        $this->assertArrayNotHasKey('citizen_id', $g);
        $this->assertArrayNotHasKey('monthly_income', $g);
    }

    public function test_the_relation_the_payload_is_built_from_is_not_serialized()
    {
        // The payload is assembled from an eager-loaded guardianLinks.guardian. Left attached it
        // serializes beside it as guardian_links[].guardian — the entire person row, citizen id
        // and income included — regardless of who is asking.
        [$academy, $user] = $this->academyWithMember(['classrooms.view']);

        [$student, $guardian, $legacyId] = $this->createStudentWithGuardian($academy);
        $this->setupClassroom($academy, $student);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}");

        $response->assertOk();

        $this->assertArrayNotHasKey('guardian_links', $response->json('student'));
        $this->assertStringNotContainsString('monthly_income', $response->getContent());
    }
}
