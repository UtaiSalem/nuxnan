<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CourseCreateThresholdTest extends TestCase
{
    use RefreshDatabase;

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

    private function personalCourse(int $points, string $tag): TestResponse
    {
        config(['features.create_course_threshold' => 120000]);
        $user = $this->makeUser($tag, $points);

        return $this->actingAs($user, 'api')->json('POST', '/api/courses', ['name' => 'Course '.$tag]);
    }

    private function academyCourse(int $status, ?string $role, ?string $academyRoleName, string $tag): TestResponse
    {
        $owner = $this->makeUser('owner'.$tag);
        $user = $this->makeUser($tag);
        $academy = Academy::create(['name' => 'School '.$tag, 'user_id' => $owner->id]);
        $academyRole = $academyRoleName ? AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => $academyRoleName,
            'display_name_th' => $academyRoleName,
            'is_system' => false,
            'is_active' => true,
        ]) : null;

        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'status' => $status,
            'role' => $role,
            'academy_role_id' => $academyRole?->id,
        ]);

        return $this->actingAs($user, 'api')->json('POST', "/api/academies/{$academy->id}/courses", ['name' => 'Course '.$tag]);
    }

    public function test_personal_course_creation_is_blocked_below_threshold(): void
    {
        $this->personalCourse(119999, 'low')->assertStatus(403)->assertJson(['required_points' => 120000]);
    }

    public function test_personal_course_creation_at_threshold_is_not_blocked(): void
    {
        $this->assertNotSame(403, $this->personalCourse(120000, 'exact')->status());
    }

    public function test_personal_course_creation_above_threshold_is_not_blocked(): void
    {
        $this->assertNotSame(403, $this->personalCourse(500000, 'high')->status());
    }

    public function test_approved_teacher_can_create_academy_course_without_points(): void
    {
        $this->assertNotSame(403, $this->academyCourse(2, 'teacher', 'teacher', 'approved-teacher')->status());
    }

    public function test_discharged_teacher_cannot_create_academy_course(): void
    {
        $this->academyCourse(5, 'teacher', 'teacher', 'discharged')->assertStatus(403);
    }

    public function test_rejected_teacher_cannot_create_academy_course(): void
    {
        $this->academyCourse(3, 'teacher', 'teacher', 'rejected')->assertStatus(403);
    }

    public function test_approved_student_cannot_create_academy_course(): void
    {
        $this->academyCourse(2, 'student', 'student', 'student')->assertStatus(403);
    }

    public function test_approved_member_without_role_cannot_create_academy_course(): void
    {
        $this->academyCourse(2, null, null, 'no-role')->assertStatus(403);
    }

    public function test_academy_owner_can_create_without_membership_or_points(): void
    {
        $owner = $this->makeUser('academy-owner');
        $academy = Academy::create(['name' => 'Owner School', 'user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'api')->json('POST', "/api/academies/{$academy->id}/courses", ['name' => 'Owner Course']);

        $this->assertNotSame(403, $response->status());
    }

    public function test_non_member_cannot_create_academy_course(): void
    {
        $owner = $this->makeUser('non-member-owner');
        $user = $this->makeUser('non-member');
        $academy = Academy::create(['name' => 'Private School', 'user_id' => $owner->id]);

        $this->actingAs($user, 'api')
            ->json('POST', "/api/academies/{$academy->id}/courses", ['name' => 'Blocked Course'])
            ->assertStatus(403);
    }
}
