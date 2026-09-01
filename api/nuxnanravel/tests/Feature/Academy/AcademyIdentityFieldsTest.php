<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademyIdentityFieldsTest extends TestCase
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
            'name' => 'S7 Identity Academy',
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

    public function test_owner_can_update_identity_fields()
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload([
                'slogan' => 'New Slogan',
                'type' => 'foundation',
                'established_year' => 2510,
                'director' => $this->owner->id,
                'social_media_links' => [
                    'facebook' => 'https://facebook.com/x',
                    'line' => 'https://line.me/R/ti/p/@x',
                ],
            ])
        );

        $response->assertStatus(200);

        $this->academy->refresh();
        $this->assertEquals('New Slogan', $this->academy->slogan);
        $this->assertEquals('foundation', $this->academy->type);
        $this->assertEquals(2510, $this->academy->established_year);
        $this->assertEquals($this->owner->id, $this->academy->director);

        $this->assertIsArray($this->academy->social_media_links);
        $this->assertCount(2, $this->academy->social_media_links);
        $this->assertEquals('https://facebook.com/x', $this->academy->social_media_links['facebook']);
        $this->assertEquals('https://line.me/R/ti/p/@x', $this->academy->social_media_links['line']);
    }

    public function test_type_outside_catalog_is_rejected()
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['type' => 'wizard-school'])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_established_year_must_be_buddhist_era()
    {
        $response1 = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['established_year' => 1967])
        );
        $response1->assertStatus(422);
        $response1->assertJsonValidationErrors(['established_year']);

        $futureYear = now()->year + 544;
        $response2 = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['established_year' => $futureYear])
        );
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['established_year']);
    }

    public function test_director_must_be_an_approved_member_of_this_academy()
    {
        // Non-member
        $nonMember = User::factory()->create();
        $response1 = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['director' => $nonMember->id])
        );
        $response1->assertStatus(422);
        $response1->assertJsonValidationErrors(['director']);

        // Pending member
        $pendingMember = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $pendingMember->id,
            'status' => AcademyMember::STATUS_PENDING,
            'role' => 'student',
        ]);
        $response2 = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['director' => $pendingMember->id])
        );
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['director']);

        // Approved member
        $approvedMember = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $approvedMember->id,
            'status' => AcademyMember::STATUS_APPROVED,
            'role' => 'teacher',
        ]);
        $response3 = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['director' => $approvedMember->id])
        );
        $response3->assertStatus(200);
        $this->assertEquals($approvedMember->id, $this->academy->fresh()->director);
    }

    public function test_owner_can_be_director_even_without_member_row()
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['director' => $this->owner->id])
        );

        $response->assertStatus(200);
        $this->assertEquals($this->owner->id, $this->academy->fresh()->director);
    }

    public function test_social_media_links_reject_unknown_channel_and_invalid_url()
    {
        $response1 = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['social_media_links' => ['myspace' => 'https://myspace.com/x']])
        );
        $response1->assertStatus(422);
        $response1->assertJsonValidationErrors(['social_media_links']);

        $response2 = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['social_media_links' => ['facebook' => 'not-a-url']])
        );
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['social_media_links.facebook']);
    }

    public function test_clearing_every_social_link_stores_an_empty_set()
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/academies/{$this->academy->id}/settings",
            $this->payload(['social_media_links' => ['facebook' => '', 'line' => '']])
        );

        $response->assertStatus(200);
        $this->academy->refresh();
        $this->assertEmpty($this->academy->social_media_links);
    }

    public function test_academy_resource_survives_a_director_that_no_longer_exists()
    {
        DB::table('academies')->where('id', $this->academy->id)->update(['director' => '999999']);

        $response1 = $this->actingAs($this->owner, 'api')->getJson(
            '/api/academies/'.rawurlencode($this->academy->name)
        );
        $response1->assertStatus(200);
        $response1->assertJsonPath('academy.director', null);

        DB::table('academies')->where('id', $this->academy->id)->update(['director' => 'ผอ.สมชาย']);

        $response2 = $this->actingAs($this->owner, 'api')->getJson(
            '/api/academies/'.rawurlencode($this->academy->name)
        );
        $response2->assertStatus(200);
        $response2->assertJsonPath('academy.director', null);
    }

    public function test_resource_ships_both_catalogs()
    {
        $response = $this->actingAs($this->owner, 'api')->getJson(
            '/api/academies/'.rawurlencode($this->academy->name)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('academy.academy_type_catalog', Academy::ACADEMY_TYPE_CATALOG);
        $response->assertJsonPath('academy.social_link_catalog', Academy::SOCIAL_LINK_CATALOG);
    }

    public function test_multipart_form_saves_identity_fields()
    {
        $response = $this->actingAs($this->owner, 'api')->post(
            "/api/academies/{$this->academy->id}/settings",
            [
                'name' => $this->academy->name,
                'type' => 'private',
                'established_year' => '2515',
                'director' => (string) $this->owner->id,
                'social_media_links' => [
                    'facebook' => 'https://facebook.com/y',
                    'line' => '',
                    'youtube' => '',
                    'tiktok' => '',
                    'instagram' => '',
                    'x' => '',
                ],
            ]
        );

        $response->assertStatus(200);

        $this->academy->refresh();
        $this->assertEquals('private', $this->academy->type);
        $this->assertEquals(2515, $this->academy->established_year);
        $this->assertCount(1, $this->academy->social_media_links);
        $this->assertEquals('https://facebook.com/y', $this->academy->social_media_links['facebook']);
    }

    public function test_approval_flow_column_is_gone()
    {
        $this->assertFalse(Schema::hasColumn('academies', 'approval_flow'));
    }
}
