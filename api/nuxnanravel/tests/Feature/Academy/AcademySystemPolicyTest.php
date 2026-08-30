<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademySystemPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Academy $academy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->academy = Academy::factory()->create(['user_id' => $this->owner->id]);
        AcademySetting::create([
            'academy_id' => $this->academy->id,
            'privacy' => 'public',
            'join_mode' => 'open',
        ]);
    }

    public function test_owner_can_toggle_card_request_flow()
    {
        // enable
        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", [
            'name' => $this->academy->name,
            'card_request_flow_enabled' => true,
        ]);
        $response->assertStatus(200);

        $this->assertTrue(AcademySetting::where('academy_id', $this->academy->id)->first()->card_request_flow_enabled);

        // disable
        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", [
            'name' => $this->academy->name,
            'card_request_flow_enabled' => false,
        ]);
        $response->assertStatus(200);

        $this->assertFalse(AcademySetting::where('academy_id', $this->academy->id)->first()->card_request_flow_enabled);
    }

    public function test_card_request_flow_is_echoed_under_setting_key()
    {
        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", [
            'name' => $this->academy->name,
            'card_request_flow_enabled' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('academy.setting.card_request_flow_enabled', true);
    }

    public function test_owner_can_turn_donation_off_and_on()
    {
        // disable
        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", [
            'name' => $this->academy->name,
            'donation_enabled' => false,
        ]);
        $response->assertStatus(200);

        $freshAcademy = $this->academy->fresh();
        $this->assertFalse($freshAcademy->donation_enabled);
        $this->assertFalse($freshAcademy->donationEnabled());

        // enable
        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", [
            'name' => $this->academy->name,
            'donation_enabled' => true,
        ]);
        $response->assertStatus(200);

        $freshAcademy = $this->academy->fresh();
        $this->assertTrue($freshAcademy->donation_enabled);
        $this->assertTrue($freshAcademy->donationEnabled());
    }

    public function test_donation_enabled_in_payload_is_the_resolved_value()
    {
        // Initially null in DB => resolved to config default (true)
        $this->assertNull($this->academy->donation_enabled);

        $response = $this->actingAs($this->owner, 'api')->getJson("/api/academies/by-id/{$this->academy->id}");
        $response->assertStatus(200)
            ->assertJsonPath('academy.donation_enabled', true);

        // Change to false
        $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", [
            'name' => $this->academy->name,
            'donation_enabled' => false,
        ]);

        $response = $this->actingAs($this->owner, 'api')->getJson("/api/academies/by-id/{$this->academy->id}");
        $response->assertStatus(200)
            ->assertJsonPath('academy.donation_enabled', false);
    }

    public function test_donation_switch_actually_blocks_the_donate_endpoint()
    {
        // Disable donations
        $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", [
            'name' => $this->academy->name,
            'donation_enabled' => false,
        ]);

        $donor = User::factory()->create(['pp' => 1000]);

        $response = $this->actingAs($donor, 'api')->postJson("/api/academies/{$this->academy->id}/donations/points", [
            'points_amount' => 100,
        ]);

        // Assert it failed
        $response->assertStatus(403);

        // Ensure PP is not deducted
        $this->assertEquals(1000, $donor->fresh()->pp);
    }

    public function test_owner_can_set_student_editable_fields()
    {
        $payload = [
            'name' => $this->academy->name,
            'student_editable_fields' => [
                'mode' => 'whitelist',
                'fields' => ['nickname', 'religion'],
            ],
        ];

        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", $payload);
        $response->assertStatus(200);

        $this->assertEquals(
            ['mode' => 'whitelist', 'fields' => ['nickname', 'religion']],
            $this->academy->fresh()->student_editable_fields
        );
    }

    public function test_field_outside_catalog_is_rejected_with_422()
    {
        $originalFields = $this->academy->student_editable_fields;

        $payload = [
            'name' => $this->academy->name,
            'student_editable_fields' => [
                'mode' => 'whitelist',
                'fields' => ['nickname', 'not_a_real_field'],
            ],
        ];

        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('student_editable_fields.fields.1');

        $this->assertEquals($originalFields, $this->academy->fresh()->student_editable_fields);
    }

    public function test_invalid_mode_is_rejected_with_422()
    {
        $payload = [
            'name' => $this->academy->name,
            'student_editable_fields' => [
                'mode' => 'everything',
                'fields' => ['nickname'],
            ],
        ];

        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('student_editable_fields.mode');
    }

    public function test_unchecking_every_field_stores_an_empty_array_not_a_missing_key()
    {
        $payload = [
            'name' => $this->academy->name,
            'student_editable_fields' => [
                'mode' => 'blacklist',
                // 'fields' is missing
            ],
        ];

        $response = $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/settings", $payload);
        $response->assertStatus(200);

        $stored = $this->academy->fresh()->student_editable_fields;

        $this->assertEquals('blacklist', $stored['mode']);
        $this->assertTrue(array_key_exists('fields', $stored));
        $this->assertEquals([], $stored['fields']);
    }

    public function test_resource_exposes_the_full_field_catalog()
    {
        $response = $this->actingAs($this->owner, 'api')->getJson("/api/academies/by-id/{$this->academy->id}");
        $response->assertStatus(200);

        $this->assertEquals(
            Academy::STUDENT_EDITABLE_FIELD_CATALOG,
            $response->json('academy.student_editable_field_catalog')
        );
    }

    /**
     * SET-S6 — หน้าตั้งค่าส่งเป็น multipart/form-data ซึ่งส่งได้แต่สตริง
     *
     * กฎ `boolean` ของ Laravel รับ true/false/1/0/"1"/"0" แต่ **ไม่รับ "true"/"false"**
     * ตอนแรก frontend ส่ง String(value) ออกไปเป็น "true"/"false" แล้วได้ 422 ทันทีที่กดบันทึก
     * — เทสต์เดิมทั้งชุดจับไม่ได้เพราะใช้ postJson() ที่ส่ง boolean จริง เจอตอนกดผ่านฟอร์มจริงเท่านั้น
     *
     * เคสนี้ล็อกสัญญาไว้ว่า multipart ต้องส่ง "1"/"0" แล้วต้องผ่าน
     */
    public function test_multipart_form_accepts_string_boolean_flags()
    {
        $response = $this->actingAs($this->owner, 'api')->post("/api/academies/{$this->academy->id}/settings", [
            'name' => $this->academy->name,
            'card_request_flow_enabled' => '1',
            'donation_enabled' => '0',
            'student_editable_fields' => ['mode' => 'blacklist', 'fields' => ['citizen_id']],
        ]);

        $response->assertStatus(200);

        $this->assertTrue(AcademySetting::where('academy_id', $this->academy->id)->first()->card_request_flow_enabled);
        $this->assertFalse($this->academy->fresh()->donation_enabled);
        $this->assertEquals(
            ['mode' => 'blacklist', 'fields' => ['citizen_id']],
            $this->academy->fresh()->student_editable_fields
        );
    }
}
