<?php

namespace Tests\Feature\Activity;

use App\Models\Academy;
use App\Models\ActivityAttendance;
use App\Models\ActivityEnrollment;
use App\Models\ActivitySession;
use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitySessionCrudTest extends TestCase
{
    use RefreshDatabase;

    private function event(User $owner, Academy $academy, array $overrides = []): SchoolEvent
    {
        return SchoolEvent::create(array_merge(['academy_id' => $academy->id, 'created_by' => $owner->id, 'title' => 'Activity', 'event_type' => 'activity', 'attendance_pattern' => 'recurring', 'start_datetime' => now(), 'status' => 'published', 'requires_registration' => false], $overrides));
    }

    private function sessionSetup(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy);

        return [$owner, $academy, $event, "/api/academies/{$academy->id}/events/{$event->id}/sessions"];
    }

    public function test_manager_creates_session_and_it_belongs_to_the_event(): void
    {
        [$u, $a, $e, $url] = $this->sessionSetup();
        $this->actingAs($u, 'api')->postJson($url, ['start_datetime' => now()->addDay()->toDateTimeString()])->assertCreated();
        $this->assertDatabaseHas('activity_sessions', ['event_id' => $e->id]);
    }

    public function test_store_refuses_end_before_start(): void
    {
        [$u,,, $url] = $this->sessionSetup();
        $this->actingAs($u, 'api')->postJson($url, ['start_datetime' => '2026-01-02 10:00:00', 'end_datetime' => '2026-01-02 09:00:00'])->assertStatus(422);
    }

    public function test_store_ignores_client_supplied_event_id_and_qr_token(): void
    {
        [$u,,$e,$url] = $this->sessionSetup();
        $other = $this->event($u, Academy::factory()->create(['user_id' => $u->id]));
        $this->actingAs($u, 'api')->postJson($url, ['event_id' => $other->id, 'qr_token' => 'bad', 'start_datetime' => now()->addDay()->toDateTimeString()])->assertCreated();
        $this->assertDatabaseHas('activity_sessions', ['event_id' => $e->id, 'qr_token' => null]);
    }

    public function test_index_lists_sessions_in_time_order_with_attendance_counts(): void
    {
        [$u,,$e,$url] = $this->sessionSetup();
        $s1 = ActivitySession::create(['event_id' => $e->id, 'start_datetime' => now()->addDays(2), 'status' => 'scheduled']);
        $s2 = ActivitySession::create(['event_id' => $e->id, 'start_datetime' => now()->addDay(), 'status' => 'scheduled']);
        $s3 = ActivitySession::create(['event_id' => $e->id, 'start_datetime' => now()->addDays(3), 'status' => 'scheduled']);
        $en = ActivityEnrollment::create(['event_id' => $e->id, 'user_id' => $u->id, 'status' => 'active']);
        ActivityAttendance::create(['session_id' => $s1->id, 'enrollment_id' => $en->id, 'user_id' => $u->id, 'status' => 'late']);
        $json = $this->actingAs($u, 'api')->getJson($url)->assertOk()->json('data.data');
        $this->assertSame([$s2->id, $s1->id, $s3->id], array_column($json, 'id'));
        $this->assertSame(1, $json[1]['present_count']);
        $this->assertSame(1, $json[1]['attendances_count']);
    }

    public function test_index_filters_by_date_range_and_status(): void
    {
        [$u,,$e,$url] = $this->sessionSetup();
        ActivitySession::create(['event_id' => $e->id, 'start_datetime' => '2026-01-10 10:00:00', 'status' => 'completed']);
        ActivitySession::create(['event_id' => $e->id, 'start_datetime' => '2026-02-10 10:00:00', 'status' => 'scheduled']);
        $this->actingAs($u, 'api')->getJson($url.'?from=2026-01-01&to=2026-01-31&status=completed')->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_update_changes_title_and_time(): void
    {
        [$u,,$e,$url] = $this->sessionSetup();
        $s = ActivitySession::create(['event_id' => $e->id, 'start_datetime' => now()->addDay(), 'status' => 'scheduled']);
        $this->actingAs($u, 'api')->patchJson("$url/{$s->id}", ['title' => 'New', 'start_datetime' => now()->addDays(2)->toDateTimeString()])->assertOk();
        $this->assertDatabaseHas('activity_sessions', ['id' => $s->id, 'title' => 'New']);
    }

    public function test_update_refuses_end_datetime_before_stored_start(): void
    {
        [$u,,$e,$url] = $this->sessionSetup();
        $s = ActivitySession::create(['event_id' => $e->id, 'start_datetime' => '2026-02-01 10:00:00', 'status' => 'scheduled']);
        $this->actingAs($u, 'api')->patchJson("$url/{$s->id}", ['end_datetime' => '2026-02-01 09:00:00'])->assertStatus(422);
    }

    // status / is_makeup_class are NOT NULL columns — an explicit null must be a 422, not a 500.
    public function test_update_refuses_explicit_null_for_not_null_columns(): void
    {
        [$u,,$e,$url] = $this->sessionSetup();
        $s = ActivitySession::create(['event_id' => $e->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        $this->actingAs($u, 'api')->patchJson("$url/{$s->id}", ['status' => null])->assertStatus(422);
        $this->actingAs($u, 'api')->patchJson("$url/{$s->id}", ['is_makeup_class' => null])->assertStatus(422);
    }

    public function test_destroy_soft_deletes_a_session_without_attendance(): void
    {
        [$u,,$e,$url] = $this->sessionSetup();
        $s = ActivitySession::create(['event_id' => $e->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        $this->actingAs($u, 'api')->deleteJson("$url/{$s->id}")->assertOk();
        $this->assertSoftDeleted('activity_sessions', ['id' => $s->id]);
    }

    public function test_destroy_is_refused_when_attendance_exists(): void
    {
        [$u,,$e,$url] = $this->sessionSetup();
        $s = ActivitySession::create(['event_id' => $e->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        $en = ActivityEnrollment::create(['event_id' => $e->id, 'user_id' => $u->id, 'status' => 'active']);
        ActivityAttendance::create(['session_id' => $s->id, 'enrollment_id' => $en->id, 'user_id' => $u->id, 'status' => 'present']);
        $this->actingAs($u, 'api')->deleteJson("$url/{$s->id}")->assertStatus(409);
        $this->assertDatabaseHas('activity_sessions', ['id' => $s->id]);
    }

    public function test_non_manager_cannot_list_or_create_sessions(): void
    {
        [$u,$a,$e,$url] = $this->sessionSetup();
        $other = User::factory()->create();
        $this->actingAs($other, 'api')->getJson($url)->assertForbidden();
        $this->actingAs($other, 'api')->postJson($url, ['start_datetime' => now()->toDateTimeString()])->assertForbidden();
    }

    public function test_session_routes_of_another_academy_event_are_not_found(): void
    {
        [$ownerA,$academyA,$eventA] = $this->sessionSetup();
        $ownerB = User::factory()->create();
        $academyB = Academy::factory()->create(['user_id' => $ownerB->id]);
        $s = ActivitySession::create(['event_id' => $eventA->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        $base = "/api/academies/{$academyB->id}/events/{$eventA->id}/sessions";
        $this->actingAs($ownerB, 'api')->getJson($base)->assertNotFound();
        $this->actingAs($ownerB, 'api')->postJson($base, ['start_datetime' => now()->toDateTimeString()])->assertNotFound();
        $this->actingAs($ownerB, 'api')->postJson("$base/{$s->id}/records", ['records' => []])->assertNotFound();
    }
}
