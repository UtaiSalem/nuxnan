<?php

namespace Tests\Feature\Api\Academy;

use App\Models\Academy;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #12 คอร์สเรียน — CO-S6
 * ครอบคลุม authz ของ CourseController@update/destroy (CO-S1) และ contract ของการสร้างคอร์ส (CO-S5)
 *
 * Q2: academy admin แก้/ลบคอร์สของครูคนอื่น "ในโรงเรียนตัวเอง" ได้ · ครูแก้/ลบของตัวเองได้ · กันข้ามโรงเรียน
 * Q3: ครูทุกคนที่อนุมัติสร้างได้ (ทดสอบผ่านเจ้าของโรงเรียนซึ่งเป็น admin)
 */
class AcademyCourseCrudTest extends TestCase
{
    use RefreshDatabase;

    /** สร้างโรงเรียนพร้อมเจ้าของ (เจ้าของ = academy admin ผ่าน Academy::isAdmin) */
    private function academyOwnedBy(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        return [$academy, $owner];
    }

    /** สร้างคอร์สในโรงเรียนที่ระบุ โดยมีเจ้าของคอร์สเป็น $courseOwner + มี courseSettings เหมือนคอร์สจริง */
    private function courseInAcademy(Academy $academy, User $courseOwner, array $attrs = []): Course
    {
        $course = Course::factory()->create(array_merge([
            'academy_id' => $academy->id,
            'user_id' => $courseOwner->id,
            'instructor_id' => $courseOwner->id,
        ], $attrs));

        // store() สร้าง courseSettings ให้เสมอ — mirror ไว้กัน update() อ่าน property บน null (ดู G8)
        $course->courseSettings()->create(['auto_accept_members' => 0]);

        return $course;
    }

    public function test_academy_owner_can_update_another_teachers_course(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $teacher = User::factory()->create();
        $course = $this->courseInAcademy($academy, $teacher);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/courses/{$course->id}", ['name' => 'แก้โดยแอดมินโรงเรียน'])
            ->assertStatus(200);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'แก้โดยแอดมินโรงเรียน',
        ]);
    }

    public function test_academy_owner_can_delete_another_teachers_course(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $teacher = User::factory()->create();
        $course = $this->courseInAcademy($academy, $teacher);

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/courses/{$course->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_course_owner_can_update_own_course(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $teacher = User::factory()->create();
        $course = $this->courseInAcademy($academy, $teacher);

        $this->actingAs($teacher, 'api')
            ->patchJson("/api/courses/{$course->id}", ['name' => 'แก้โดยครูเจ้าของ'])
            ->assertStatus(200);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'แก้โดยครูเจ้าของ',
        ]);
    }

    public function test_admin_of_another_academy_cannot_update_course(): void
    {
        [$academyA, $ownerA] = $this->academyOwnedBy();
        $teacher = User::factory()->create();
        $course = $this->courseInAcademy($academyA, $teacher, ['name' => 'ชื่อเดิม']);

        // เจ้าของอีกโรงเรียน ไม่ควรแตะคอร์สของโรงเรียน A ได้
        [, $ownerB] = $this->academyOwnedBy();

        $this->actingAs($ownerB, 'api')
            ->patchJson("/api/courses/{$course->id}", ['name' => 'พยายามแก้ข้ามโรงเรียน'])
            ->assertStatus(403);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'ชื่อเดิม',
        ]);
    }

    public function test_outsider_cannot_delete_course(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $teacher = User::factory()->create();
        $course = $this->courseInAcademy($academy, $teacher);

        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->deleteJson("/api/courses/{$course->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_create_course_requires_name(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/courses", ['description' => 'ไม่มีชื่อ'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_course_persists_name_and_maps_draft_status(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/courses", [
                'name' => 'คณิตศาสตร์ ม.1',
                'description' => 'คำอธิบาย',
                'status' => 'draft',
            ])
            ->assertStatus(200);

        // status 'draft' ต้อง map เป็น tinyint 2 ผ่าน mutator (CO-S5)
        $this->assertDatabaseHas('courses', [
            'academy_id' => $academy->id,
            'name' => 'คณิตศาสตร์ ม.1',
            'status' => Course::STATUS_DRAFT,
        ]);
    }
}
