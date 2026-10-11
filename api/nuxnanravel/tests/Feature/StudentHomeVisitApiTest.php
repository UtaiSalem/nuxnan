<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Student;
use App\Models\StudentHomeVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentHomeVisitApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_list_visits_without_staff_observations(): void
    {
        [$owner, $admin, $academy, $student] = $this->context();
        StudentHomeVisit::create($this->visitData($academy->id, $student->id, $admin->id));

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.observations', null);
    }

    public function test_admin_can_create_update_and_delete_visit(): void
    {
        [, $admin, $academy, $student] = $this->context();

        $create = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits", [
                'visit_date' => '2026-07-03',
                'visit_time' => '09:30',
                'visitor_name' => 'Teacher One',
                'visitor_position' => 'Teacher',
                'visit_status' => 'completed',
                'observations' => 'Safe home environment',
            ]);

        $create->assertCreated()->assertJsonPath('data.visit_status', 'completed');
        $visitId = $create->json('data.id');

        $this->actingAs($admin, 'api')
            ->putJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits/{$visitId}", [
                'visit_date' => '2026-07-03',
                'visitor_name' => 'Teacher One',
                'visitor_position' => 'Homeroom teacher',
                'visit_status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('data.visit_status', 'cancelled');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits/{$visitId}")
            ->assertOk();

        $this->assertDatabaseMissing('student_home_visits', ['id' => $visitId]);
    }

    public function test_non_member_cannot_view_or_create_visits(): void
    {
        [, , $academy, $student] = $this->context();
        $stranger = $this->makeUser('stranger');

        $this->actingAs($stranger, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits")
            ->assertForbidden();

        $this->actingAs($stranger, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits", [
                'visit_date' => '2026-07-03',
                'visitor_name' => 'No Access',
                'visitor_position' => 'Unknown',
                'visit_status' => 'completed',
            ])
            ->assertForbidden();
    }

    /**
     * HV-S3/G7 — custom role ที่ถือ home_visits.manage (ไม่ใช่ owner/admin/director/teacher)
     * และเป็นสมาชิกอนุมัติแล้ว (status=2 canonical) ต้อง สร้าง/ดู observations/ลบ ได้
     */
    public function test_custom_role_with_home_visits_manage_can_create_and_see_observations(): void
    {
        [, , $academy, $student] = $this->context();

        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'registrar-'.uniqid(),
            'display_name_th' => 'นายทะเบียนเยี่ยมบ้าน',
            'permissions' => ['home_visits.view', 'home_visits.manage'],
        ]);
        $staff = $this->makeUser('registrar');
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $staff->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        $create = $this->actingAs($staff, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits", [
                'visit_date' => '2026-07-03',
                'visitor_name' => 'Registrar',
                'visitor_position' => 'Registrar',
                'visit_status' => 'completed',
                'observations' => 'Visible to staff only',
            ]);
        $create->assertCreated()->assertJsonPath('data.observations', 'Visible to staff only');
        $visitId = $create->json('data.id');

        // list → เห็น observations (ระดับ staff)
        $this->actingAs($staff, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits")
            ->assertOk()
            ->assertJsonPath('data.data.0.observations', 'Visible to staff only');

        // manage → ลบได้
        $this->actingAs($staff, 'api')
            ->deleteJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits/{$visitId}")
            ->assertOk();
        $this->assertDatabaseMissing('student_home_visits', ['id' => $visitId]);
    }

    /**
     * HV-S5 — โฟลว์ master-profile (ระบบ A) ต้องรับ participants + รูปหลักฐาน
     * ให้ครู (พอร์ทัล JWT) บันทึกได้เทียบเท่าพอร์ทัลเก่า
     */
    public function test_staff_can_attach_participants_and_images_via_master_flow(): void
    {
        Storage::fake('public');
        [, $admin, $academy, $student] = $this->context();

        $create = $this->actingAs($admin, 'api')
            ->post("/api/academies/{$academy->id}/students/{$student->id}/home-visits", [
                'visit_date' => '2026-07-03',
                'visitor_name' => 'ครูประจำชั้น',
                'visitor_position' => 'ครู',
                'visit_status' => 'completed',
                'participants' => [
                    ['participant_name' => 'ครูประจำชั้น'],
                    ['participant_name' => 'ครูแนะแนว', 'notes' => 'ผู้ช่วย'],
                ],
                'images' => [UploadedFile::fake()->image('ev.jpg')],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.participants')
            ->assertJsonCount(1, 'data.images');

        $visitId = $create->json('data.id');
        $imagePath = $create->json('data.images.0.image_path');
        Storage::disk('public')->assertExists($imagePath);

        // update: participants แทนที่ (เหลือ 1) + ลบรูปเดิม (PUT spoof)
        $imageId = $create->json('data.images.0.id');
        $this->actingAs($admin, 'api')
            ->post("/api/academies/{$academy->id}/students/{$student->id}/home-visits/{$visitId}", [
                '_method' => 'PUT',
                'visit_date' => '2026-07-03',
                'visitor_name' => 'ครูประจำชั้น',
                'visitor_position' => 'ครู',
                'visit_status' => 'completed',
                'participants' => [
                    ['participant_name' => 'ผู้อำนวยการ'],
                ],
                'images_to_delete' => [$imageId],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.participants')
            ->assertJsonCount(0, 'data.images');

        Storage::disk('public')->assertMissing($imagePath);
    }

    private function context(): array
    {
        $owner = $this->makeUser('owner');
        $admin = $this->makeUser('admin');
        $academy = Academy::create(['name' => 'Academy_'.uniqid(), 'user_id' => $admin->id]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 1,
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
        ]);

        return [$owner, $admin, $academy, $student];
    }

    private function makeUser(string $tag): User
    {
        return User::create([
            'name' => ucfirst($tag),
            'email' => $tag.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'username' => $tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    private function visitData(int $academyId, int $studentId, int $creatorId): array
    {
        return [
            'academy_id' => $academyId,
            'student_id' => $studentId,
            'visit_date' => '2026-07-03',
            'visitor_name' => 'Teacher One',
            'visitor_position' => 'Teacher',
            'visit_status' => 'completed',
            'observations' => 'Private observation',
            'created_by' => $creatorId,
        ];
    }
}
