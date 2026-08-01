<?php

namespace Tests\Feature\Activity;

use App\Models\Academy;
use App\Models\EventRegistration;
use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneTimeEventAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function event(User $owner, Academy $academy, array $overrides = []): SchoolEvent
    {
        return SchoolEvent::create(array_merge([
            'academy_id' => $academy->id,
            'created_by' => $owner->id,
            'title' => 'One-time activity',
            'event_type' => 'activity',
            'attendance_pattern' => 'one_time',
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHour(),
            'status' => 'published',
            'requires_registration' => true,
        ], $overrides));
    }

    public function test_student_can_register_and_duplicate_registration_is_not_created(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy);

        $first = $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/register");
        $first->assertCreated();

        $this->actingAs($student, 'api')
            ->postJson("/api/academies/{$academy->id}/events/{$event->id}/register")
            ->assertStatus(400);

        $this->assertDatabaseCount('event_registrations', 1);
    }

    public function test_student_registration_is_refused_when_registration_is_not_required(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy, ['requires_registration' => false]);

        $this->actingAs($student, 'api')
            ->postJson("/api/academies/{$academy->id}/events/{$event->id}/register")
            ->assertStatus(400);
        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_organiser_can_list_and_mark_attendance_both_ways(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy);
        $registration = EventRegistration::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => 'pending']);

        $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/events/{$event->id}/registrations")->assertOk();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/registrations/{$registration->id}/attendance", ['attended' => true])->assertOk();
        $this->assertDatabaseHas('event_registrations', ['id' => $registration->id, 'status' => 'attended']);
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/registrations/{$registration->id}/attendance", ['attended' => false])->assertOk();
        $this->assertDatabaseHas('event_registrations', ['id' => $registration->id, 'status' => 'no_show']);
    }

    public function test_non_manager_cannot_mark_attendance_and_wrong_academy_is_not_found(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $otherAcademy = Academy::factory()->create();
        $event = $this->event($owner, $academy);
        $registration = EventRegistration::create(['event_id' => $event->id, 'user_id' => $student->id]);

        $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/registrations/{$registration->id}/attendance", ['attended' => true])->assertForbidden();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$otherAcademy->id}/events/{$event->id}/registrations/{$registration->id}/attendance", ['attended' => true])->assertNotFound();
    }

    public function test_registration_is_refused_when_max_participants_is_reached(): void
    {
        $owner = User::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy, ['max_participants' => 1]);

        $this->actingAs($first, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/register")->assertCreated();
        $this->actingAs($second, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/register")->assertStatus(400);
    }
}
