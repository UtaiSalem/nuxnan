<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\AcademyMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected $superAdmin;

    protected $academyAdmin;

    protected $member;

    protected $outsider;

    protected $academy;

    protected $otherOwner;

    protected $otherAcademy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->academy = Academy::factory()->create(['user_id' => $this->owner->id]);

        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('SUPER_ADMIN');

        $this->academyAdmin = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $this->academyAdmin->id, 'status' => 2]);
        AcademyAdmin::create(['academy_id' => $this->academy->id, 'user_id' => $this->academyAdmin->id]);

        $this->member = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $this->member->id, 'status' => 2]);

        $this->outsider = User::factory()->create();

        $this->otherOwner = User::factory()->create();
        $this->otherAcademy = Academy::factory()->create(['user_id' => $this->otherOwner->id]);
    }

    public function test_owner_can_archive_academy()
    {
        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/archive");

        $response->assertStatus(200);
        $this->assertNotNull($this->academy->fresh()->archived_at);
    }

    public function test_super_admin_who_is_not_owner_can_archive()
    {
        $response = $this->actingAs($this->superAdmin, 'api')
            ->postJson("/api/academies/{$this->academy->id}/archive");

        $response->assertStatus(200);
        $this->assertNotNull($this->academy->fresh()->archived_at);
    }

    public function test_academy_admin_cannot_archive()
    {
        $this->assertTrue($this->academy->isAdmin($this->academyAdmin));

        $response = $this->actingAs($this->academyAdmin, 'api')
            ->postJson("/api/academies/{$this->academy->id}/archive");

        $response->assertStatus(403);
        $this->assertNull($this->academy->fresh()->archived_at);
    }

    public function test_plain_member_and_outsider_cannot_archive()
    {
        $responseMember = $this->actingAs($this->member, 'api')
            ->postJson("/api/academies/{$this->academy->id}/archive");
        $responseMember->assertStatus(403);

        $responseOutsider = $this->actingAs($this->outsider, 'api')
            ->postJson("/api/academies/{$this->academy->id}/archive");
        $responseOutsider->assertStatus(403);

        $this->assertNull($this->academy->fresh()->archived_at);
    }

    public function test_archiving_twice_returns_conflict()
    {
        $this->academy->forceFill(['archived_at' => now()])->save();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/archive");

        $response->assertStatus(409)
            ->assertJsonPath('code', 'already_archived');
    }

    public function test_restoring_academy_that_is_not_archived_returns_conflict()
    {
        $response = $this->actingAs($this->owner, 'api')
            ->deleteJson("/api/academies/{$this->academy->id}/archive");

        $response->assertStatus(409)
            ->assertJsonPath('code', 'not_archived');
    }

    public function test_owner_can_restore_archived_academy()
    {
        $this->academy->forceFill(['archived_at' => now()])->save();

        $response = $this->actingAs($this->owner, 'api')
            ->deleteJson("/api/academies/{$this->academy->id}/archive");

        $response->assertStatus(200);
        $this->assertNull($this->academy->fresh()->archived_at);
    }

    public function test_archived_academy_disappears_from_all_academies_listing()
    {
        $this->academy->forceFill(['archived_at' => now()])->save();

        $response = $this->actingAs($this->outsider, 'api')
            ->getJson('/api/academies/all-academies');

        $response->assertStatus(200);

        $data = $response->json('academies.data');
        if (is_null($data)) {
            $data = $response->json('academies');
        }

        $this->assertIsArray($data);
        $ids = array_column($data, 'id');
        $this->assertNotContains($this->academy->id, $ids);
    }

    public function test_archived_academy_disappears_from_membered_academies_listing()
    {
        $this->academy->forceFill(['archived_at' => now()])->save();

        $response = $this->actingAs($this->member, 'api')
            ->getJson("/api/academies/users/{$this->member->id}/membered-academies");

        $response->assertStatus(200);

        $data = $response->json('academies.data') ?? $response->json('academies');

        $this->assertIsArray($data);
        $this->assertNotContains($this->academy->id, array_column($data, 'id'));
    }

    public function test_outsider_gets_academy_archived_code_on_content_endpoint()
    {
        $this->academy->forceFill(['archived_at' => now()])->save();

        $response = $this->actingAs($this->outsider, 'api')
            ->getJson("/api/academies/{$this->academy->id}/activities");

        $response->assertStatus(403)
            ->assertJsonPath('code', 'academy_archived');
    }

    public function test_owner_still_reaches_archived_academy_content()
    {
        $this->academy->forceFill(['archived_at' => now()])->save();

        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/activities");

        $response->assertStatus(200);
    }

    public function test_archived_index_only_returns_academies_the_caller_can_restore()
    {
        $this->academy->forceFill(['archived_at' => now()])->save();
        $this->otherAcademy->forceFill(['archived_at' => now()])->save();

        // owner
        $responseOwner = $this->actingAs($this->owner, 'api')->getJson('/api/academies/archived');
        $responseOwner->assertStatus(200);
        $dataOwner = $responseOwner->json('academies.data') ?? $responseOwner->json('academies');
        $this->assertContains($this->academy->id, array_column($dataOwner, 'id'));
        $this->assertNotContains($this->otherAcademy->id, array_column($dataOwner, 'id'));

        // otherOwner
        $responseOther = $this->actingAs($this->otherOwner, 'api')->getJson('/api/academies/archived');
        $responseOther->assertStatus(200);
        $dataOther = $responseOther->json('academies.data') ?? $responseOther->json('academies');
        $this->assertNotContains($this->academy->id, array_column($dataOther, 'id'));
        $this->assertContains($this->otherAcademy->id, array_column($dataOther, 'id'));

        // superAdmin
        $responseSuper = $this->actingAs($this->superAdmin, 'api')->getJson('/api/academies/archived');
        $responseSuper->assertStatus(200);
        $dataSuper = $responseSuper->json('academies.data') ?? $responseSuper->json('academies');
        $this->assertContains($this->academy->id, array_column($dataSuper, 'id'));
        $this->assertContains($this->otherAcademy->id, array_column($dataSuper, 'id'));

        // outsider
        $responseOutsider = $this->actingAs($this->outsider, 'api')->getJson('/api/academies/archived');
        $responseOutsider->assertStatus(200);
        $dataOutsider = $responseOutsider->json('academies.data') ?? $responseOutsider->json('academies');
        $this->assertEmpty($dataOutsider);
    }
}
