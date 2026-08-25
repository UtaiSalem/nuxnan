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

class GuardianContactCrudTest extends TestCase
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

    /** @return array{0: Student, 1: Guardian, 2: int} student, guardian person, and the link id */
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
            'first_name' => 'Somchai'.$uniq,
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'status' => 'alive',
        ];

        // This creates the Guardian person and the link between them.
        $link = app(GuardianWriteService::class)->create($student, $guardianData);
        $guardian = Guardian::latest('id')->first();

        return [$student, $guardian, $link->id];
    }

    public function test_can_add_contact_successfully()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts", [
                'contact_type' => 'phone',
                'contact_value' => '0812345678',
                'is_primary' => true,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('guardian_contacts', [
            'guardian_person_id' => $guardian->id,
            'contact_type' => 'phone',
            'contact_value' => '0812345678',
            'is_primary' => 1,
            'is_verified' => 0,
        ]);
    }

    public function test_cannot_add_duplicate_contact()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts", [
                'contact_type' => 'phone',
                'contact_value' => '0812345678',
            ])->assertCreated();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts", [
                'contact_type' => 'phone',
                'contact_value' => '0812345678',
            ]);

        $response->assertStatus(409);
    }

    public function test_is_verified_is_ignored_and_set_to_false()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts", [
                'contact_type' => 'phone',
                'contact_value' => '0812345678',
                'is_verified' => true, // Malicious input
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('guardian_contacts', [
            'contact_value' => '0812345678',
            'is_verified' => 0,
        ]);
    }

    public function test_invalid_email_format()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts", [
                'contact_type' => 'email',
                'contact_value' => 'not-an-email',
            ]);

        $response->assertStatus(422);
    }

    public function test_invalid_contact_type_outside_enum()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts", [
                'contact_type' => 'telegram',
                'contact_value' => 'username',
            ]);

        $response->assertStatus(422);
    }

    public function test_update_contact_value_resets_is_verified()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts", [
                'contact_type' => 'phone',
                'contact_value' => '0812345678',
            ]);

        $contactId = $response->json('data.id');

        GuardianContact::find($contactId)->update(['is_verified' => true]);

        $updateResponse = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts/{$contactId}", [
                'contact_value' => '0899999999',
            ]);

        $updateResponse->assertOk();
        $this->assertDatabaseHas('guardian_contacts', [
            'id' => $contactId,
            'is_verified' => 0,
        ]);
    }

    public function test_set_primary_only_affects_same_type()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$student, $guardian, $legacyId] = $this->createStudentWithGuardian($academy);

        $phone1 = GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'contact_type' => 'phone',
            'contact_value' => '0811111111',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $email = GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'contact_type' => 'email',
            'contact_value' => 'test@example.com',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $phone2 = GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'contact_type' => 'phone',
            'contact_value' => '0822222222',
            'is_primary' => false,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts/{$phone2->id}/set-primary");

        $response->assertOk();

        $this->assertDatabaseHas('guardian_contacts', [
            'id' => $phone1->id,
            'is_primary' => 0,
        ]);
        $this->assertDatabaseHas('guardian_contacts', [
            'id' => $phone2->id,
            'is_primary' => 1,
        ]);
        $this->assertDatabaseHas('guardian_contacts', [
            'id' => $email->id,
            'is_primary' => 1, // Email should still be primary!
        ]);
    }

    public function test_can_delete_contact()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$student, $guardian, $legacyId] = $this->createStudentWithGuardian($academy);

        $contact = GuardianContact::create([
            'guardian_person_id' => $guardian->id,
            'contact_type' => 'phone',
            'contact_value' => '0811111111',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts/{$contact->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('guardian_contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_cannot_modify_contact_of_another_guardian()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$studentA, $guardianA, $legacyId] = $this->createStudentWithGuardian($academy);
        [$studentB, $guardianB] = $this->createStudentWithGuardian($academy);

        $contactB = GuardianContact::create([
            'guardian_person_id' => $guardianB->id,
            'guardian_id' => $legacyId,
            'contact_type' => 'phone',
            'contact_value' => '0811111111',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardian-people/{$guardianA->id}/contacts/{$contactB->id}", [
                'contact_value' => '0899999999',
            ]);

        $response->assertStatus(404);
    }

    public function test_cannot_access_guardian_of_another_academy()
    {
        [$academyA, $userA] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$studentA, $guardianA] = $this->createStudentWithGuardian($academyA);

        $academyB = Academy::factory()->create();

        $response = $this->actingAs($userA, 'api')
            ->getJson("/api/academies/{$academyB->id}/guardian-people/{$guardianA->id}/contacts");

        $response->assertStatus(403); // Middleware blocks wrong academy or 404 from our check
    }

    public function test_cannot_access_guardian_of_another_academy_when_in_same_academy_member()
    {
        [$academyA, $userA] = $this->academyWithMember(['guardians.manage', 'guardians.view']);
        [$academyB, $userB] = $this->academyWithMember(['guardians.manage', 'guardians.view']);

        [$studentB, $guardianB] = $this->createStudentWithGuardian($academyB);

        $response = $this->actingAs($userA, 'api')
            ->getJson("/api/academies/{$academyA->id}/guardian-people/{$guardianB->id}/contacts");

        $response->assertStatus(404);
    }

    public function test_view_only_token_cannot_store_but_can_get()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']); // no manage
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts")
            ->assertOk();

        $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts", [
                'contact_type' => 'phone',
                'contact_value' => '0812345678',
            ])
            ->assertForbidden();
    }

    public function test_student_cannot_access()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy, $studentUser);

        $response = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/contacts");

        $response->assertForbidden();
    }
}
