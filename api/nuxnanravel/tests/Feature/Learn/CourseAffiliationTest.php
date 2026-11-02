<?php

namespace Tests\Feature\Learn;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseAffiliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, ['academy_id' => null, 'name' => $name, 'is_system' => true, 'is_active' => true]));
        }
    }

    private function makeUser(string $tag = '', int $points = 0): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => 'u'.$tag.uniqid().'@x.test',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'username' => 'u'.$tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
            'pp' => $points,
        ]);
    }

    private function makeAcademy(?User $owner = null): Academy
    {
        return Academy::factory()->create(['user_id' => ($owner ?? $this->makeUser('owner'))->id]);
    }

    private function addMember(Academy $academy, User $user, string $roleName, int $status = 2): AcademyMember
    {
        $role = AcademyRole::where('name', $roleName)->whereNull('academy_id')->firstOrFail();

        return AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => $status,
        ]);
    }

    private function makeCourse(User $owner, ?int $academyId = null): Course
    {
        return Course::factory()->create([
            'user_id' => $owner->id,
            'instructor_id' => $owner->id,
            'academy_id' => $academyId,
        ]);
    }

    public function test_teacher_can_attach_own_personal_course(): void
    {
        $academy = $this->makeAcademy();
        $teacher = $this->makeUser('teacher');
        $this->addMember($academy, $teacher, 'teacher');
        $course = $this->makeCourse($teacher);

        $this->actingAs($teacher, 'api')
            ->patchJson("/api/courses/{$course->id}/academy", ['academy_id' => $academy->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame($academy->id, $course->fresh()->academy_id);
        $this->assertSame(1, (int) $academy->fresh()->courses_offered);
    }

    public function test_academy_owner_without_member_row_can_attach(): void
    {
        $owner = $this->makeUser('owner');
        $academy = $this->makeAcademy($owner);
        $course = $this->makeCourse($owner);

        $this->assertFalse(AcademyMember::where('academy_id', $academy->id)->where('user_id', $owner->id)->exists());

        $this->actingAs($owner, 'api')
            ->patchJson("/api/courses/{$course->id}/academy", ['academy_id' => $academy->id])
            ->assertOk();

        $this->assertSame($academy->id, $course->fresh()->academy_id);
    }

    public function test_pending_member_cannot_attach(): void
    {
        $academy = $this->makeAcademy();
        $teacher = $this->makeUser('pending');
        $this->addMember($academy, $teacher, 'teacher', 1);
        $course = $this->makeCourse($teacher);

        $this->actingAs($teacher, 'api')
            ->patchJson("/api/courses/{$course->id}/academy", ['academy_id' => $academy->id])
            ->assertForbidden();

        $this->assertNull($course->fresh()->academy_id);
    }

    public function test_student_member_cannot_attach(): void
    {
        $academy = $this->makeAcademy();
        $student = $this->makeUser('student');
        $this->addMember($academy, $student, 'student');
        $course = $this->makeCourse($student);

        $this->actingAs($student, 'api')
            ->patchJson("/api/courses/{$course->id}/academy", ['academy_id' => $academy->id])
            ->assertForbidden();

        $this->assertNull($course->fresh()->academy_id);
    }

    public function test_non_course_admin_cannot_attach(): void
    {
        $academy = $this->makeAcademy();
        $teacher = $this->makeUser('teacher');
        $this->addMember($academy, $teacher, 'teacher');
        $course = $this->makeCourse($this->makeUser('course-owner'));

        $this->actingAs($teacher, 'api')
            ->patchJson("/api/courses/{$course->id}/academy", ['academy_id' => $academy->id])
            ->assertForbidden();

        $this->assertNull($course->fresh()->academy_id);
    }

    public function test_cannot_attach_when_already_affiliated(): void
    {
        $owner = $this->makeUser('owner');
        $current = $this->makeAcademy();
        $target = $this->makeAcademy($owner);
        $course = $this->makeCourse($owner, $current->id);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/courses/{$course->id}/academy", ['academy_id' => $target->id])
            ->assertStatus(422);

        $this->assertSame($current->id, $course->fresh()->academy_id);
    }

    public function test_archived_academy_cannot_host(): void
    {
        $owner = $this->makeUser('owner');
        $academy = $this->makeAcademy($owner);
        $academy->forceFill(['archived_at' => now()])->save();
        $course = $this->makeCourse($owner);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/courses/{$course->id}/academy", ['academy_id' => $academy->id])
            ->assertForbidden();

        $this->assertNull($course->fresh()->academy_id);

        $ids = collect($this->actingAs($owner, 'api')
            ->getJson('/api/me/course-host-academies')
            ->assertOk()
            ->json('academies'))->pluck('id')->all();

        $this->assertNotContains($academy->id, $ids);
    }

    public function test_host_academies_lists_owner_admin_and_teacher_schools_only(): void
    {
        $user = $this->makeUser('lister');
        $a = $this->makeAcademy($user);
        $b = $this->makeAcademy();
        $c = $this->makeAcademy();
        $d = $this->makeAcademy();
        $this->addMember($b, $user, 'teacher');
        $this->addMember($c, $user, 'student');
        $this->addMember($d, $user, 'teacher', 1);

        $ids = collect($this->actingAs($user, 'api')
            ->getJson('/api/me/course-host-academies')
            ->assertOk()
            ->json('academies'))->pluck('id')->sort()->values()->all();

        $expected = collect([$a->id, $b->id])->sort()->values()->all();

        $this->assertSame($expected, $ids);
    }

    public function test_course_owner_can_detach(): void
    {
        $owner = $this->makeUser('owner');
        $academy = $this->makeAcademy();
        $this->addMember($academy, $owner, 'teacher');
        $course = $this->makeCourse($owner, $academy->id);
        $this->makeCourse($this->makeUser('other'), $academy->id);
        $academy->update(['courses_offered' => 2]);

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/courses/{$course->id}/academy")
            ->assertOk();

        $this->assertNull($course->fresh()->academy_id);
        $this->assertSame(1, (int) $academy->fresh()->courses_offered);
    }

    public function test_academy_admin_can_detach_teacher_course(): void
    {
        $owner = $this->makeUser('owner');
        $academy = $this->makeAcademy($owner);
        $teacher = $this->makeUser('teacher');
        $this->addMember($academy, $teacher, 'teacher');
        $course = $this->makeCourse($teacher, $academy->id);

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/courses/{$course->id}/academy")
            ->assertOk();

        $this->assertNull($course->fresh()->academy_id);
    }

    public function test_other_teacher_cannot_detach(): void
    {
        $academy = $this->makeAcademy();
        $teacher = $this->makeUser('teacher');
        $other = $this->makeUser('other-teacher');
        $this->addMember($academy, $teacher, 'teacher');
        $this->addMember($academy, $other, 'teacher');
        $course = $this->makeCourse($teacher, $academy->id);

        $this->actingAs($other, 'api')
            ->deleteJson("/api/courses/{$course->id}/academy")
            ->assertForbidden();

        $this->assertSame($academy->id, $course->fresh()->academy_id);
    }

    public function test_detach_blocked_when_in_curriculum(): void
    {
        $owner = $this->makeUser('owner');
        $academy = $this->makeAcademy($owner);
        $course = $this->makeCourse($owner, $academy->id);

        $curriculumId = DB::table('curriculums')->insertGetId([
            'academy_id' => $academy->id,
            'name' => 'Curriculum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('curriculum_courses')->insert([
            'curriculum_id' => $curriculumId,
            'course_id' => $course->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/courses/{$course->id}/academy")
            ->assertStatus(422);

        $this->assertSame($academy->id, $course->fresh()->academy_id);
    }

    public function test_store_rejects_academy_id_for_outsider(): void
    {
        config(['features.create_course_threshold' => 120000]);
        $academy = $this->makeAcademy();
        $user = $this->makeUser('outsider', 200000);
        $before = Course::count();

        $this->actingAs($user, 'api')
            ->postJson('/api/courses', [
                'name' => 'Sneaky Course',
                'academy_id' => $academy->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'status' => '1',
            ])
            ->assertForbidden();

        $this->assertSame($before, Course::count());
    }

    public function test_store_accepts_academy_id_for_teacher(): void
    {
        config(['features.create_course_threshold' => 120000]);
        $academy = $this->makeAcademy();
        $teacher = $this->makeUser('teacher', 200000);
        $this->addMember($academy, $teacher, 'teacher');

        $response = $this->actingAs($teacher, 'api')
            ->postJson('/api/courses', [
                'name' => 'School Course',
                'academy_id' => $academy->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'status' => '1',
            ])
            ->assertOk();

        $course = Course::findOrFail($response->json('newCourse.id'));
        $this->assertSame($academy->id, $course->academy_id);
        $this->assertSame(1, (int) $academy->fresh()->courses_offered);
    }

    public function test_update_ignores_academy_and_owner_fields(): void
    {
        $owner = $this->makeUser('owner');
        $course = $this->makeCourse($owner);
        $otherAcademy = $this->makeAcademy();
        $otherUser = $this->makeUser('other');

        $this->actingAs($owner, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => 'x',
                'academy_id' => $otherAcademy->id,
                'user_id' => $otherUser->id,
            ])
            ->assertOk();

        $fresh = $course->fresh();
        $this->assertSame('x', $fresh->name);
        $this->assertNull($fresh->academy_id);
        $this->assertSame($owner->id, $fresh->user_id);
    }
}
