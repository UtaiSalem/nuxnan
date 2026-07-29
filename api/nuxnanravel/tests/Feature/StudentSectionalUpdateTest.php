<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentChangeRequest;
use App\Models\StudentHealthInfo;
use App\Models\User;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentSectionalUpdateTest extends TestCase
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

    private function setupStudentAndAcademy(): array
    {
        $owner = $this->makeUser('owner');
        $admin = $this->makeUser('adm');

        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $admin->id,
            'student_editable_fields' => [
                'mode' => 'blacklist',
                'fields' => ['citizen_id', 'student_id', 'academic', 'health'],
            ],
        ]);

        AcademyMember::create([
            'user_id' => $admin->id,
            'academy_id' => $academy->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S'.uniqid(),
            'citizen_id' => '1234567890123',
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'ใจดี',
            'nickname' => 'ชาย',
        ]);

        return compact('owner', 'admin', 'academy', 'student');
    }

    public function test_owner_can_update_personal_fields_not_in_blacklist_directly()
    {
        $data = $this->setupStudentAndAcademy();
        $owner = $data['owner'];
        $academy = $data['academy'];
        $student = $data['student'];

        $response = $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/personal", [
                'nickname' => 'สมชายหล่อ',
                'religion' => 'พุทธ',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertEquals('สมชายหล่อ', $student->fresh()->nickname);
        $this->assertEquals('พุทธ', $student->fresh()->religion);

        // Assert no change requests created
        $this->assertEquals(0, StudentChangeRequest::count());
    }

    public function test_owner_updating_blacklisted_fields_creates_change_request()
    {
        $data = $this->setupStudentAndAcademy();
        $owner = $data['owner'];
        $academy = $data['academy'];
        $student = $data['student'];

        $address = StudentAddress::create([
            'student_id' => $student->id,
            'academy_id' => $academy->id,
            'address_type' => 'current',
            'house_number' => '123',
            'subdistrict' => 'A',
            'district' => 'B',
            'province' => 'C',
        ]);

        // Address fields are whitelisted/not blacklisted by default except when we create/store new ones.
        // Let's test health information which is strictly blacklisted under 'health'.
        $health = StudentHealthInfo::create([
            'student_id' => $student->id,
            'height_cm' => 170,
            'weight_kg' => 60,
        ]);

        $response = $this->actingAs($owner, 'api')
            ->putJson("/api/academies/{$academy->id}/students/{$student->id}/health/{$health->id}", [
                'height' => 175,
                'weight' => 65,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // Assert original data remains unchanged
        $this->assertEquals(170, $health->fresh()->height_cm);

        // Assert change requests are created for height and weight
        $this->assertEquals(2, StudentChangeRequest::where('status', 'pending')->count());
    }

    public function test_admin_can_update_blacklisted_fields_directly()
    {
        $data = $this->setupStudentAndAcademy();
        $admin = $data['admin'];
        $academy = $data['academy'];
        $student = $data['student'];

        $health = StudentHealthInfo::create([
            'student_id' => $student->id,
            'height_cm' => 170,
            'weight_kg' => 60,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->putJson("/api/academies/{$academy->id}/students/{$student->id}/health/{$health->id}", [
                'height' => 180,
                'weight' => 70,
            ]);

        $response->assertStatus(200);

        // Assert direct update occurred
        $this->assertEquals(180, $health->fresh()->height_cm);
        $this->assertEquals(70, $health->fresh()->weight_kg);

        // Assert no change requests created
        $this->assertEquals(0, StudentChangeRequest::count());
    }

    public function test_admin_can_approve_and_reject_change_requests()
    {
        $data = $this->setupStudentAndAcademy();
        $admin = $data['admin'];
        $owner = $data['owner'];
        $academy = $data['academy'];
        $student = $data['student'];

        $health = StudentHealthInfo::create([
            'student_id' => $student->id,
            'height_cm' => 170,
            'weight_kg' => 60,
        ]);

        // Create pending request
        $changeRequest = StudentChangeRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'model_type' => 'StudentHealthInfo',
            'model_id' => $health->id,
            'field' => 'health.height_cm',
            'old_value' => 170,
            'new_value' => 190,
            'status' => 'pending',
            'requested_by' => $owner->id,
        ]);

        // stranger cannot approve
        $stranger = $this->makeUser('str');
        $response = $this->actingAs($stranger, 'api')
            ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/change-requests/{$changeRequest->id}/approve");
        $response->assertStatus(403);

        // admin can approve
        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/change-requests/{$changeRequest->id}/approve");

        $response->assertStatus(200);
        $this->assertEquals(190, $health->fresh()->height_cm);
        $this->assertEquals('approved', $changeRequest->fresh()->status);
    }

    public function test_approving_guardian_person_field_dual_writes(): void
    {
        $data = $this->setupStudentAndAcademy();
        $guardian = app(GuardianWriteService::class)->create($data['student'], [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123', 'guardian_type' => 'father', 'relationship' => 'father', 'occupation' => 'เดิม',
        ]);
        $request = StudentChangeRequest::create([
            'academy_id' => $data['academy']->id, 'student_id' => $data['student']->id, 'model_type' => 'StudentGuardian',
            'model_id' => $guardian->id, 'field' => 'guardian.occupation', 'old_value' => 'เดิม', 'new_value' => 'ครู',
            'status' => 'pending', 'requested_by' => $data['owner']->id,
        ]);

        $this->actingAs($data['admin'], 'api')->patchJson("/api/academies/{$data['academy']->id}/students/{$data['student']->id}/change-requests/{$request->id}/approve")->assertOk();

        $this->assertDatabaseHas('student_guardians', ['id' => $guardian->id, 'occupation' => 'ครู']);
        $personId = DB::table('student_guardian_links')->whereJsonContains('legacy_row_ids', $guardian->id)->value('guardian_id');
        $this->assertDatabaseHas('guardians', ['id' => $personId, 'occupation' => 'ครู']);
    }

    public function test_approving_guardian_relationship_field_dual_writes(): void
    {
        $data = $this->setupStudentAndAcademy();
        $guardian = app(GuardianWriteService::class)->create($data['student'], ['first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123', 'guardian_type' => 'father']);
        $request = StudentChangeRequest::create([
            'academy_id' => $data['academy']->id, 'student_id' => $data['student']->id, 'model_type' => 'StudentGuardian',
            'model_id' => $guardian->id, 'field' => 'guardian.guardian_type', 'old_value' => 'father', 'new_value' => 'mother',
            'status' => 'pending', 'requested_by' => $data['owner']->id,
        ]);

        $this->actingAs($data['admin'], 'api')->patchJson("/api/academies/{$data['academy']->id}/students/{$data['student']->id}/change-requests/{$request->id}/approve")->assertOk();

        $this->assertDatabaseHas('student_guardians', ['id' => $guardian->id, 'guardian_type' => 'mother']);
        $this->assertDatabaseHas('student_guardian_links', ['guardian_id' => DB::table('student_guardian_links')->whereJsonContains('legacy_row_ids', $guardian->id)->value('guardian_id'), 'guardian_type' => 'mother']);
    }

    public function test_approving_student_address_field_keeps_existing_behavior(): void
    {
        $data = $this->setupStudentAndAcademy();
        $address = StudentAddress::create(['student_id' => $data['student']->id, 'academy_id' => $data['academy']->id, 'address_type' => 'current', 'house_number' => '123']);
        $request = StudentChangeRequest::create(['academy_id' => $data['academy']->id, 'student_id' => $data['student']->id, 'model_type' => 'StudentAddress', 'model_id' => $address->id, 'field' => 'address.house_number', 'old_value' => '123', 'new_value' => '456', 'status' => 'pending', 'requested_by' => $data['owner']->id]);

        $this->actingAs($data['admin'], 'api')->patchJson("/api/academies/{$data['academy']->id}/students/{$data['student']->id}/change-requests/{$request->id}/approve")->assertOk();
        $this->assertDatabaseHas('student_addresses', ['id' => $address->id, 'house_number' => '456']);
    }

    public function test_approving_non_whitelisted_model_type_returns_400(): void
    {
        $data = $this->setupStudentAndAcademy();
        $request = StudentChangeRequest::create(['academy_id' => $data['academy']->id, 'student_id' => $data['student']->id, 'model_type' => 'NotAllowed', 'model_id' => 1, 'field' => 'x', 'new_value' => 'x', 'status' => 'pending', 'requested_by' => $data['owner']->id]);

        $this->actingAs($data['admin'], 'api')->patchJson("/api/academies/{$data['academy']->id}/students/{$data['student']->id}/change-requests/{$request->id}/approve")->assertStatus(400);
    }
}
