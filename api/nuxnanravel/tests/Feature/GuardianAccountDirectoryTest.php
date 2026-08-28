<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianAccountLinkService;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianAccountDirectoryTest extends TestCase
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

    private function createStudentWithGuardian(Academy $academy, ?User $studentUser = null): array
    {
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'นักเรียน',
            'status' => 'active',
        ]);

        $guardianData = [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => '1234567890123',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'status' => 'alive',
        ];

        $link = app(GuardianWriteService::class)->create($student, $guardianData);

        return [$student, $link->guardian];
    }

    public function test_directory_payload_returns_all_three_keys_null_initially()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians");

        $response->assertOk();
        $guardians = $response->json('guardians');
        $this->assertCount(1, $guardians);

        $payload = $guardians[0];
        $this->assertNull($payload['linked_user_id']);
        $this->assertNull($payload['linked_user_name']);
        $this->assertNull($payload['pending_account_request']);
    }

    public function test_directory_payload_shows_pending_request_details()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create();

        $request = app(GuardianAccountLinkService::class)
            ->createRequest($academy, $student, $parentUser, $user, $guardian);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians");

        $response->assertOk();

        $payload = $response->json('guardians.0');
        $this->assertNull($payload['linked_user_id']);

        $this->assertNotNull($payload['pending_account_request']);
        $this->assertEquals($request->id, $payload['pending_account_request']['id']);
        $this->assertEquals($student->id, $payload['pending_account_request']['student_id']);
        $this->assertEquals(trim($student->first_name_th.' '.$student->last_name_th), $payload['pending_account_request']['student_name']);
    }

    public function test_directory_payload_shows_linked_account_after_accept()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create();

        $request = app(GuardianAccountLinkService::class)
            ->createRequest($academy, $student, $parentUser, $user, $guardian);

        app(GuardianAccountLinkService::class)->accept($request, $parentUser);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians");

        $response->assertOk();

        $payload = $response->json('guardians.0');
        $this->assertNull($payload['pending_account_request']);

        $this->assertEquals($parentUser->id, $payload['linked_user_id']);
        $this->assertEquals($parentUser->name, $payload['linked_user_name']);
    }

    public function test_statistics_includes_new_keys_while_keeping_old_keys()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $parentUser = User::factory()->create();
        $request = app(GuardianAccountLinkService::class)
            ->createRequest($academy, $student, $parentUser, $user, $guardian);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians/statistics");

        $response->assertOk();

        $stats = $response->json('statistics');

        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(1, $stats['by_type']['father']);
        $this->assertEquals(0, $stats['with_contact']);
        $this->assertEquals(1, $stats['without_contact']);
        $this->assertEquals(0, $stats['linked_accounts']);
        $this->assertEquals(1, $stats['pending_account_requests']);

        app(GuardianAccountLinkService::class)->accept($request, $parentUser);

        $stats = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians/statistics")
            ->json('statistics');

        $this->assertEquals(1, $stats['linked_accounts']);
        $this->assertEquals(0, $stats['pending_account_requests']);
    }

    public function test_directory_payload_does_not_contain_account_email_or_phone()
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create([
            'email' => 'test-parent@example.com',
            'phone_number' => '0899999999',
        ]);

        $request = app(GuardianAccountLinkService::class)
            ->createRequest($academy, $student, $parentUser, $user, $guardian);

        app(GuardianAccountLinkService::class)->accept($request, $parentUser);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians");

        $response->assertOk();

        $payload = $response->json('guardians.0');

        $response->assertJsonMissing(['email' => 'test-parent@example.com']);
        $response->assertJsonMissing(['phone_number' => '0899999999']);
        $this->assertCount(0, $payload['contacts']);
    }
}
