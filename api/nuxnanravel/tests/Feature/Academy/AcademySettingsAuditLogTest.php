<?php

namespace Tests\Feature\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\AcademySetting;
use App\Models\Classroom;
use App\Models\MemberActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademySettingsAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected $academy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->academy = Academy::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'S9 Audit Log Academy',
        ]);

        AcademySetting::create([
            'academy_id' => $this->academy->id,
            'privacy' => 'public',
            'join_mode' => 'open',
        ]);
    }

    protected function payload(array $extra = []): array
    {
        return ['name' => $this->academy->name] + $extra;
    }

    public function test_settings_only_change_is_logged()
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload([
                'privacy' => 'private',
            ])
        );

        $response->assertStatus(200);

        $logs = MemberActivityLog::where('action', MemberActivityLog::ACTION_SETTINGS_UPDATE)
            ->where('academy_id', $this->academy->id)
            ->get();

        $this->assertCount(1, $logs);
        $log = $logs->first();

        $this->assertEquals(MemberActivityLog::ACTION_SETTINGS_UPDATE, $log->action);
        $this->assertEquals(MemberActivityLog::CATEGORY_SETTINGS, $log->action_category);
        $this->assertEquals($this->owner->id, $log->user_id);
        $this->assertEquals('แก้ไขการตั้งค่าโรงเรียน', $log->description);

        $this->assertEquals(['settings.privacy'], array_keys($log->new_values));
        $this->assertEquals('public', $log->old_values['settings.privacy']);
        $this->assertEquals('private', $log->new_values['settings.privacy']);
    }

    public function test_no_op_save_writes_no_log()
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload([
                'privacy' => 'public',
            ])
        );

        $response->assertStatus(200);
        $this->assertDatabaseCount('member_activity_logs', 0);
    }

    public function test_academy_column_change_is_logged()
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload([
                'slogan' => 'New Slogan for Academy',
            ])
        );

        $response->assertStatus(200);

        $log = MemberActivityLog::where('action', MemberActivityLog::ACTION_SETTINGS_UPDATE)->first();
        $this->assertNotNull($log);
        $this->assertEquals('New Slogan for Academy', $log->new_values['slogan']);
        $this->assertEquals($this->academy->slogan, $log->old_values['slogan']);
    }

    public function test_established_year_resent_as_string_is_not_a_change()
    {
        $this->academy->update(['established_year' => 2510]);

        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload([
                'established_year' => '2510',
                'slogan' => 'Another slogan',
            ])
        );

        $response->assertStatus(200);

        $logs = MemberActivityLog::where('action', MemberActivityLog::ACTION_SETTINGS_UPDATE)->get();
        $this->assertCount(1, $logs);

        $log = $logs->first();
        $this->assertFalse(array_key_exists('established_year', $log->new_values));
        $this->assertTrue(array_key_exists('slogan', $log->new_values));
    }

    public function test_logging_failure_does_not_break_the_save()
    {
        Schema::drop('member_activity_logs');

        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload([
                'privacy' => 'private',
            ])
        );

        $response->assertStatus(200);

        $this->academy->refresh();
        $this->assertEquals('private', $this->academy->academySetting->privacy);
    }

    public function test_removed_academy_audit_logs_index_route_is_gone()
    {
        $response = $this->actingAs($this->owner, 'api')->getJson(
            "/api/academies/{$this->academy->id}/audit-logs"
        );

        $response->assertStatus(404);
    }

    public function test_entity_audit_logs_requires_permission()
    {
        $memberUser = User::factory()->create();

        $role = AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => 'no_perm_role',
            'display_name_th' => 'ไม่มีสิทธิ์',
            'permissions' => [],
            'is_system' => false,
            'is_active' => true,
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $memberUser->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2, // APPROVED
        ]);

        $response = $this->actingAs($memberUser, 'api')->getJson(
            "/api/academies/{$this->academy->id}/audit-logs/entity?entity_type=Classroom&entity_id=1"
        );

        $response->assertStatus(403);
    }

    public function test_entity_audit_logs_rejects_entity_type_outside_whitelist()
    {
        $response = $this->actingAs($this->owner, 'api')->getJson(
            "/api/academies/{$this->academy->id}/audit-logs/entity?entity_type=User&entity_id=1"
        );

        $response->assertStatus(422);
    }

    public function test_entity_audit_logs_404_for_entity_of_another_academy()
    {
        $academyB = Academy::factory()->create();

        $academicYearA = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);

        $academicYearB = AcademicYear::create([
            'academy_id' => $academyB->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);

        $classroomA = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $academicYearA->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        $classroomB = Classroom::create([
            'academy_id' => $academyB->id,
            'academic_year_id' => $academicYearB->id,
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
        ]);

        $response1 = $this->actingAs($this->owner, 'api')->getJson(
            "/api/academies/{$this->academy->id}/audit-logs/entity?entity_type=Classroom&entity_id={$classroomB->id}"
        );
        $response1->assertStatus(404);

        $response2 = $this->actingAs($this->owner, 'api')->getJson(
            "/api/academies/{$this->academy->id}/audit-logs/entity?entity_type=Classroom&entity_id={$classroomA->id}"
        );
        $response2->assertStatus(200);
    }
}
