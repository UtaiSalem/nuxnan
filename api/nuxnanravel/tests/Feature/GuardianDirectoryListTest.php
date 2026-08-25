<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Guardian;
use App\Models\GuardianContact;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianDirectoryListTest extends TestCase
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
            'status' => 2,
        ]);

        return [$academy, $user];
    }

    /** @return array{0: Student, 1: Guardian, 2: int} student, guardian person, and the legacy row id the contacts FK needs */
    private function createStudentWithGuardian(Academy $academy, ?User $studentUser = null): array
    {
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ'.uniqid(),
            'last_name_th' => 'นักเรียน',
            'status' => 'active',
        ]);

        $guardianData = [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => '1234567890123',
            'monthly_income' => 50000,
            'guardian_type' => 'father',
            'relationship' => 'father',
            'status' => 'alive',
        ];

        $legacy = app(GuardianWriteService::class)->create($student, $guardianData);
        $guardian = Guardian::latest('id')->first();

        return [$student, $guardian, $legacy->id];
    }

    public function test_guardian_with_multiple_children_shows_as_one_row()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);

        $student1 = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S1',
            'title_prefix_th' => 'ด.ช.',
            'first_name_th' => 'ลูกหนึ่ง',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S2',
            'title_prefix_th' => 'ด.ช.',
            'first_name_th' => 'ลูกสอง',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);

        $student3 = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S3',
            'title_prefix_th' => 'ด.ญ.',
            'first_name_th' => 'ลูกสาม',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);

        $guardianData = [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => '1111111111111',
            'guardian_type' => 'father',
        ];

        app(GuardianWriteService::class)->create($student1, $guardianData);

        // Emulate adding the same guardian to other students (dual write link + sync person)
        $guardianPerson = Guardian::latest('id')->first();

        // Link to student 2
        app(GuardianWriteService::class)->create($student2, [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => $guardianPerson->citizen_id, // Match person
            'guardian_type' => 'other', // Different type for test
        ]);

        // Link to student 3
        app(GuardianWriteService::class)->create($student3, [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => $guardianPerson->citizen_id,
            'guardian_type' => 'father',
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians");

        $response->assertOk();
        $guardians = $response->json('guardians');

        $this->assertCount(1, $guardians);
        $this->assertEquals(3, $guardians[0]['children_count']);
        $this->assertCount(3, $guardians[0]['children']);
    }

    public function test_sensitive_fields_are_hidden_in_list()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view', 'guardians.sensitive.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians");

        $response->assertOk();
        $response->assertJsonMissingPath('guardians.0.citizen_id');
        $response->assertJsonMissingPath('guardians.0.monthly_income');
    }

    public function test_contacts_and_primary_phone_included()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian, $legacyId] = $this->createStudentWithGuardian($academy);

        GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'guardian_id' => $legacyId,
            'contact_type' => 'phone',
            'contact_value' => '0811111111',
            'is_primary' => false,
            'is_verified' => false,
        ]);

        GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'guardian_id' => $legacyId,
            'contact_type' => 'mobile',
            'contact_value' => '0822222222',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians");

        $response->assertOk();

        $this->assertEquals('0822222222', $response->json('guardians.0.primary_phone'));
        $this->assertCount(2, $response->json('guardians.0.contacts'));
    }

    public function test_filter_by_type()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);

        $student1 = Student::create(['academy_id' => $academy->id, 'student_id' => 'S1', 'first_name_th' => '1', 'last_name_th' => '1', 'status' => 'active']);
        $student2 = Student::create(['academy_id' => $academy->id, 'student_id' => 'S2', 'first_name_th' => '2', 'last_name_th' => '2', 'status' => 'active']);

        app(GuardianWriteService::class)->create($student1, [
            'first_name' => 'OtherGuy',
            'last_name' => 'Bob',
            'guardian_type' => 'other',
        ]);

        app(GuardianWriteService::class)->create($student2, [
            'first_name' => 'Father',
            'last_name' => 'John',
            'guardian_type' => 'father',
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians?type=father");

        $response->assertOk();
        $this->assertCount(1, $response->json('guardians'));
        $this->assertEquals('Father', $response->json('guardians.0.first_name'));
    }

    public function test_search_by_phone()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian, $legacyId] = $this->createStudentWithGuardian($academy);

        GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'guardian_id' => $legacyId,
            'contact_type' => 'phone',
            'contact_value' => '0998887777',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians?search=099888");

        $response->assertOk();
        $this->assertCount(1, $response->json('guardians'));
        $this->assertEquals('Somchai', $response->json('guardians.0.first_name'));
    }

    public function test_guardians_from_other_academies_not_included()
    {
        [$academyA, $userA] = $this->academyWithMember(['guardians.view']);
        [$studentA, $guardianA] = $this->createStudentWithGuardian($academyA);

        $academyB = Academy::factory()->create();
        $studentB = Student::create(['academy_id' => $academyB->id, 'student_id' => 'S2', 'first_name_th' => '2', 'last_name_th' => '2', 'status' => 'active']);
        app(GuardianWriteService::class)->create($studentB, [
            'first_name' => 'Other',
            'last_name' => 'Academy',
            'guardian_type' => 'father',
        ]);

        $response = $this->actingAs($userA, 'api')
            ->getJson("/api/academies/{$academyA->id}/guardians");

        $response->assertOk();
        $this->assertCount(1, $response->json('guardians'));
        $this->assertEquals('Somchai', $response->json('guardians.0.first_name'));
    }
}
