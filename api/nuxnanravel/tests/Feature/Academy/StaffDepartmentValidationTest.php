<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyMember;
use App\Models\Position;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ยืนยันว่า "แผนก" ของระบบบุคลากรผูกกับ AcademyGroup (type='department') ไม่ใช่ตาราง departments ที่ไม่มีจริง
 * (เดิม StaffController/PayrollController validate exists:departments,id + Position/StaffProfile::department()
 *  อ้าง Department::class ที่ไม่มี → พังตอน runtime)
 */
class StaffDepartmentValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Academy, 1: User, 2: AcademyGroup} */
    private function context(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $department = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'ฝ่ายวิชาการ', 'type' => 'department']);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $owner->id, 'academy_role_id' => null, 'status' => 2]);

        return [$academy, $owner, $department];
    }

    public function test_store_position_accepts_department_that_is_an_academy_group(): void
    {
        [$academy, $owner, $department] = $this->context();

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/staff/positions", [
                'name' => 'ครูผู้สอน',
                'department_id' => $department->id,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('positions', [
            'academy_id' => $academy->id,
            'name' => 'ครูผู้สอน',
            'department_id' => $department->id,
        ]);
    }

    public function test_store_position_rejects_group_that_is_not_a_department(): void
    {
        [$academy, $owner] = $this->context();
        $classroom = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'ม.1/1', 'type' => 'classroom']);

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/staff/positions", [
                'name' => 'ครูประจำชั้น',
                'department_id' => $classroom->id,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('department_id');
    }

    public function test_store_position_rejects_department_from_another_academy(): void
    {
        [$academy, $owner] = $this->context();
        $otherAcademy = Academy::factory()->create();
        $foreignDept = AcademyGroup::create(['academy_id' => $otherAcademy->id, 'name' => 'ฝ่ายของโรงเรียนอื่น', 'type' => 'department']);

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/staff/positions", [
                'name' => 'ครู',
                'department_id' => $foreignDept->id,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('department_id');
    }

    public function test_position_department_relation_resolves_to_academy_group(): void
    {
        [$academy, , $department] = $this->context();

        $position = Position::create([
            'academy_id' => $academy->id,
            'department_id' => $department->id,
            'name' => 'หัวหน้าฝ่าย',
        ]);

        $this->assertInstanceOf(AcademyGroup::class, $position->department);
        $this->assertSame('ฝ่ายวิชาการ', $position->department->name);
    }

    public function test_staff_profile_department_relation_resolves_to_academy_group(): void
    {
        [$academy, $owner, $department] = $this->context();

        $staff = StaffProfile::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'department_id' => $department->id,
            'employee_id' => 'EMP0001',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'hire_date' => '2026-01-01',
        ]);

        $this->assertInstanceOf(AcademyGroup::class, $staff->department);
        $this->assertSame('ฝ่ายวิชาการ', $staff->department->name);
    }
}
