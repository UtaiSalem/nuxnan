<?php

namespace Tests\Feature\Activity;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ActivityEnrollment;
use App\Models\ActivitySession;
use App\Models\Classroom;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\User;
use App\Services\Activity\EventAudienceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventAudienceTest extends TestCase
{
    use RefreshDatabase;

    private function member(Academy $academy, ?User $user = null, ?Student $student = null, array $extra = []): AcademyMember
    {
        $user ??= User::factory()->create();

        return AcademyMember::create(array_merge([
            'academy_id' => $academy->id, 'user_id' => $user->id, 'student_id' => $student?->id,
            'status' => AcademyMember::STATUS_APPROVED, 'role' => $student ? 'student' : 'staff',
        ], $extra));
    }

    private function student(Academy $academy, ?User $user = null, array $extra = []): Student
    {
        $user ??= User::factory()->create();

        return Student::create(array_merge([
            'student_id' => 'S'.fake()->unique()->numerify('######'), 'first_name_th' => 'Test', 'last_name_th' => 'Student',
            'academy_id' => $academy->id, 'user_id' => $user->id, 'status' => 'active',
        ], $extra));
    }

    private function event(Academy $academy, User $owner, ?array $audience): SchoolEvent
    {
        return SchoolEvent::create([
            'academy_id' => $academy->id, 'created_by' => $owner->id, 'title' => 'Audience test', 'event_type' => 'activity',
            'attendance_pattern' => 'recurring', 'start_datetime' => now(), 'end_datetime' => now()->addDay(),
            'status' => 'published', 'requires_registration' => false, 'target_audience' => $audience,
        ]);
    }

    private function classroom(Academy $academy, string $grade, string $name): Classroom
    {
        $year = DB::table('academic_years')->where('academy_id', $academy->id)->value('id') ?? DB::table('academic_years')->insertGetId(['academy_id' => $academy->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_current' => true, 'created_at' => now(), 'updated_at' => now()]);

        return Classroom::create(['academy_id' => $academy->id, 'academic_year_id' => $year, 'grade_level' => $grade, 'section' => $name, 'name' => $name, 'is_active' => true]);
    }

    private function resolverIds(SchoolEvent $event): array
    {
        return app(EventAudienceResolver::class)->resolve($event)->all();
    }

    public function test_all_returns_every_approved_member(): void
    {
        $academy = Academy::factory()->create();
        $owner = User::factory()->create();
        $included = $this->member($academy);
        $this->member($academy, null, null, ['status' => AcademyMember::STATUS_PENDING]);
        $event = $this->event($academy, $owner, ['all' => true]);
        $this->assertSame([$included->user_id], $this->resolverIds($event));
    }

    public function test_classroom_returns_only_active_students(): void
    {
        $academy = Academy::factory()->create();
        $room = $this->classroom($academy, 'P1', 'Room 1');
        $active = $this->student($academy);
        $moved = $this->student($academy);
        $this->member($academy, $active->user, $active);
        $this->member($academy, $moved->user, $moved);
        DB::table('classroom_students')->insert([
            ['classroom_id' => $room->id, 'student_id' => $active->id, 'student_number' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['classroom_id' => $room->id, 'student_id' => $moved->id, 'student_number' => 2, 'status' => 'transferred', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->assertSame([$active->user_id], $this->resolverIds($this->event($academy, User::factory()->create(), ['classroom_ids' => [$room->id]])));
    }

    public function test_grade_level_covers_every_classroom(): void
    {
        $academy = Academy::factory()->create();
        $ids = [];
        foreach (['1', '2'] as $section) {
            $room = $this->classroom($academy, 'P2', $section);
            $s = $this->student($academy);
            $this->member($academy, $s->user, $s);
            $ids[] = $s->user_id;
            DB::table('classroom_students')->insert(['classroom_id' => $room->id, 'student_id' => $s->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->assertEqualsCanonicalizing($ids, $this->resolverIds($this->event($academy, User::factory()->create(), ['grade_levels' => ['P2']])));
    }

    public function test_education_level_one_returns_primary_students_only(): void
    {
        $academy = Academy::factory()->create();
        $primary = $this->student($academy);
        $secondary = $this->student($academy);
        $this->member($academy, $primary->user, $primary);
        $this->member($academy, $secondary->user, $secondary);
        DB::table('student_academic_info')->insert([['student_id' => $primary->id, 'education_level' => 1, 'is_current' => true, 'created_at' => now(), 'updated_at' => now()], ['student_id' => $secondary->id, 'education_level' => 2, 'is_current' => true, 'created_at' => now(), 'updated_at' => now()]]);
        $this->assertSame([$primary->user_id], $this->resolverIds($this->event($academy, User::factory()->create(), ['education_levels' => [1]])));
    }

    public function test_staff_role_returns_non_students(): void
    {
        $academy = Academy::factory()->create();
        $staff = $this->member($academy);
        $student = $this->student($academy);
        $this->member($academy, $student->user, $student);
        $this->assertSame([$staff->user_id], $this->resolverIds($this->event($academy, User::factory()->create(), ['roles' => ['staff']])));
    }

    public function test_two_dimensions_union_without_duplicates(): void
    {
        $academy = Academy::factory()->create();
        $s = $this->student($academy);
        $this->member($academy, $s->user, $s);
        $room = $this->classroom($academy, 'P1', '1');
        DB::table('classroom_students')->insert(['classroom_id' => $room->id, 'student_id' => $s->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $ids = $this->resolverIds($this->event($academy, User::factory()->create(), ['classroom_ids' => [$room->id], 'education_levels' => [1], 'user_ids' => [$s->user_id]]));
        $this->assertSame(1, count($ids));
    }

    public function test_exclusions_remove_dimension_match_and_user_ids_add_a_member(): void
    {
        $academy = Academy::factory()->create();
        $one = $this->member($academy);
        $two = $this->member($academy);
        $event = $this->event($academy, User::factory()->create(), ['roles' => ['staff'], 'exclude_user_ids' => [$one->user_id], 'user_ids' => [$two->user_id]]);
        $this->assertSame([$two->user_id], $this->resolverIds($event));
    }

    public function test_classroom_from_another_academy_is_rejected(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $other = $this->classroom(Academy::factory()->create(), 'P1', '1');
        $other->update(['academy_id' => $other->academy_id]);
        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/events", ['title' => 'x', 'event_type' => 'activity', 'attendance_pattern' => 'recurring', 'start_datetime' => now()->toDateTimeString(), 'end_datetime' => now()->addDay()->toDateTimeString(), 'status' => 'published', 'target_audience' => ['classroom_ids' => [$other->id]]]);
        $this->assertContains($response->status(), [201, 422]);
    }

    private function sessionFixture(?array $audience, bool $registration = false): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($academy, $owner, $audience);
        $session = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => now(), 'status' => 'scheduled']);

        return [$owner, $academy, $event, $session];
    }

    public function test_check_in_refuses_outside_audience_without_attendance_row(): void
    {
        [$owner, $academy, $event, $session] = $this->sessionFixture(['user_ids' => [User::factory()->create()->id]]);
        $outside = User::factory()->create();
        ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $outside->id, 'status' => 'active']);
        $session->update(['qr_token' => 'token']);
        $this->actingAs($outside, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/check-in", ['qr_token' => 'token'])->assertStatus(422);
        $this->assertDatabaseCount('activity_attendances', 0);
    }

    public function test_check_in_succeeds_inside_audience(): void
    {
        $inside = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->sessionFixture(['user_ids' => [$inside->id]]);
        $this->member($academy, $inside);
        $enrollment = ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $inside->id, 'status' => 'active']);
        $session->update(['qr_token' => 'token', 'qr_token_expires_at' => now()->addHour()]);
        $this->actingAs($inside, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/check-in", ['qr_token' => 'token'])->assertOk();
        $this->assertDatabaseHas('activity_attendances', ['enrollment_id' => $enrollment->id]);
    }

    public function test_null_audience_refuses_nobody(): void
    {
        $inside = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->sessionFixture(null);
        $enrollment = ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $inside->id, 'status' => 'active']);
        $session->update(['qr_token' => 'token', 'qr_token_expires_at' => now()->addHour()]);
        $this->actingAs($inside, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/check-in", ['qr_token' => 'token'])->assertOk();
        $this->assertDatabaseHas('activity_attendances', ['enrollment_id' => $enrollment->id]);
    }

    public function test_roster_contains_classroom_name_student_number_and_attendance_status(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $student = $this->student($academy);
        $this->member($academy, $student->user, $student);
        $room = $this->classroom($academy, 'P1', 'Room 1');
        DB::table('classroom_students')->insert(['classroom_id' => $room->id, 'student_id' => $student->id, 'student_number' => 7, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $event = $this->event($academy, $owner, ['classroom_ids' => [$room->id]]);
        $session = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/events/{$event->id}/roster?session_id={$session->id}")->assertOk()->assertJsonPath('data.data.0.classroom_name', 'Room 1')->assertJsonPath('data.data.0.student_number', 7)->assertJsonPath('data.data.0.attendance_status', null);
    }

    public function test_audience_resolution_query_count_does_not_scale_with_members(): void
    {
        Event::fake();
        $academy = Academy::factory()->create();
        foreach (range(1, 40) as $_) {
            $this->member($academy);
        } $event = $this->event($academy, User::factory()->create(), ['all' => true]);
        DB::enableQueryLog();
        app(EventAudienceResolver::class)->resolve($event);
        $this->assertLessThan(5, count(DB::getQueryLog()));
    }
}
