<?php

namespace Tests\Feature\Audit;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\EmergencyAlert;
use App\Models\MeetingSlot;
use App\Models\SchoolAnnouncement;
use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogCallSitesTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $user->id]);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $user->id, 'role' => 'teacher', 'status' => 'active']);

        return [$user, $academy];
    }

    private function assertAudit(string $action, object $entity): void
    {
        $this->assertDatabaseHas('audit_logs', [
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
        ]);
    }

    public function test_announcement_created(): void
    {
        [$u,$a] = $this->context();
        $r = $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/announcements", ['title' => 'A', 'content' => 'C']);
        $r->assertSuccessful();
        $this->assertAudit('announcement.created', SchoolAnnouncement::latest('id')->first());
    }

    public function test_announcement_updated(): void
    {
        [$u,$a] = $this->context();
        $m = SchoolAnnouncement::create(['academy_id' => $a->id, 'created_by' => $u->id, 'title' => 'A', 'content' => 'C']);
        $this->actingAs($u, 'api')->patchJson("/api/academies/{$a->id}/announcements/{$m->id}", ['title' => 'B'])->assertSuccessful();
        $this->assertAudit('announcement.updated', $m);
    }

    public function test_announcement_published(): void
    {
        [$u,$a] = $this->context();
        $m = SchoolAnnouncement::create(['academy_id' => $a->id, 'created_by' => $u->id, 'title' => 'A', 'content' => 'C', 'is_published' => false]);
        $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/announcements/{$m->id}/publish")->assertSuccessful();
        $this->assertAudit('announcement.published', $m);
    }

    public function test_announcement_unpublished(): void
    {
        [$u,$a] = $this->context();
        $m = SchoolAnnouncement::create(['academy_id' => $a->id, 'created_by' => $u->id, 'title' => 'A', 'content' => 'C', 'is_published' => true]);
        $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/announcements/{$m->id}/unpublish")->assertSuccessful();
        $this->assertAudit('announcement.unpublished', $m);
    }

    public function test_announcement_deleted(): void
    {
        [$u,$a] = $this->context();
        $m = SchoolAnnouncement::create(['academy_id' => $a->id, 'created_by' => $u->id, 'title' => 'A', 'content' => 'C']);
        $this->actingAs($u, 'api')->deleteJson("/api/academies/{$a->id}/announcements/{$m->id}")->assertSuccessful();
        $this->assertAudit('announcement.deleted', $m);
    }

    private function event(User $u, Academy $a): SchoolEvent
    {
        return SchoolEvent::create(['academy_id' => $a->id, 'created_by' => $u->id, 'title' => 'E', 'event_type' => 'activity', 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDays(2), 'status' => 'draft']);
    }

    public function test_event_created(): void
    {
        [$u,$a] = $this->context();
        $r = $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/events", ['title' => 'E', 'event_type' => 'activity', 'start_datetime' => now()->addDay()->toDateTimeString(), 'end_datetime' => now()->addDays(2)->toDateTimeString()]);
        $r->assertSuccessful();
        $this->assertAudit('event.created', SchoolEvent::latest('id')->first());
    }

    public function test_event_updated(): void
    {
        [$u,$a] = $this->context();
        $m = $this->event($u, $a);
        $this->actingAs($u, 'api')->patchJson("/api/academies/{$a->id}/events/{$m->id}", ['title' => 'F'])->assertSuccessful();
        $this->assertAudit('event.updated', $m);
    }

    public function test_event_published(): void
    {
        [$u,$a] = $this->context();
        $m = $this->event($u, $a);
        $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/events/{$m->id}/publish")->assertSuccessful();
        $this->assertAudit('event.published', $m);
    }

    public function test_event_cancelled(): void
    {
        [$u,$a] = $this->context();
        $m = $this->event($u, $a);
        $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/events/{$m->id}/cancel")->assertSuccessful();
        $this->assertAudit('event.cancelled', $m);
    }

    public function test_event_deleted(): void
    {
        [$u,$a] = $this->context();
        $m = $this->event($u, $a);
        $this->actingAs($u, 'api')->deleteJson("/api/academies/{$a->id}/events/{$m->id}")->assertSuccessful();
        $this->assertAudit('event.deleted', $m);
    }

    private function alert(User $u, Academy $a): EmergencyAlert
    {
        return EmergencyAlert::create(['academy_id' => $a->id, 'created_by' => $u->id, 'title' => 'Alert', 'message' => 'M', 'alert_type' => 'other', 'severity' => 'high', 'is_active' => true]);
    }

    public function test_alert_created(): void
    {
        [$u,$a] = $this->context();
        $r = $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/emergency-alerts", ['title' => 'Alert', 'message' => 'M', 'alert_type' => 'other', 'severity' => 'high']);
        $r->assertSuccessful();
        $this->assertAudit('emergency_alert.created', EmergencyAlert::latest('id')->first());
    }

    public function test_alert_updated(): void
    {
        [$u,$a] = $this->context();
        $m = $this->alert($u, $a);
        $this->actingAs($u, 'api')->patchJson("/api/academies/{$a->id}/emergency-alerts/{$m->id}", ['title' => 'New'])->assertSuccessful();
        $this->assertAudit('emergency_alert.updated', $m);
    }

    public function test_alert_deactivated(): void
    {
        [$u,$a] = $this->context();
        $m = $this->alert($u, $a);
        $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/emergency-alerts/{$m->id}/deactivate")->assertSuccessful();
        $this->assertAudit('emergency_alert.deactivated', $m);
    }

    public function test_alert_deleted(): void
    {
        [$u,$a] = $this->context();
        $m = $this->alert($u, $a);
        $this->actingAs($u, 'api')->deleteJson("/api/academies/{$a->id}/emergency-alerts/{$m->id}")->assertSuccessful();
        $this->assertAudit('emergency_alert.deleted', $m);
    }

    private function slot(User $u, Academy $a): MeetingSlot
    {
        return MeetingSlot::create(['academy_id' => $a->id, 'teacher_id' => $u->id, 'meeting_date' => now()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00', 'slot_duration' => 30, 'is_available' => true]);
    }

    public function test_meeting_slot_created(): void
    {
        [$u,$a] = $this->context();
        $r = $this->actingAs($u, 'api')->postJson("/api/academies/{$a->id}/meetings/slots", ['meeting_date' => now()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00', 'slot_duration' => 30]);
        $r->assertSuccessful();
        $this->assertAudit('meeting_slot.created', MeetingSlot::latest('id')->first());
    }

    public function test_meeting_slot_updated(): void
    {
        [$u,$a] = $this->context();
        $m = $this->slot($u, $a);
        $this->actingAs($u, 'api')->patchJson("/api/academies/{$a->id}/meetings/slots/{$m->id}", ['start_time' => '09:30'])->assertSuccessful();
        $this->assertAudit('meeting_slot.updated', $m);
    }

    public function test_meeting_slot_deleted(): void
    {
        [$u,$a] = $this->context();
        $m = $this->slot($u, $a);
        $this->actingAs($u, 'api')->deleteJson("/api/academies/{$a->id}/meetings/slots/{$m->id}")->assertSuccessful();
        $this->assertAudit('meeting_slot.deleted', $m);
    }
}
