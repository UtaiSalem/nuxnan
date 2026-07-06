<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyGroupReorderTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $student;

    protected $academy;

    protected $groups;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->student = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->admin->id,
            'name' => 'Test Academy',
            'type' => 'school',
        ]);

        $this->groups = collect([
            AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Group 1', 'type' => 'classroom']),
            AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Group 2', 'type' => 'classroom']),
            AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Group 3', 'type' => 'classroom']),
        ]);
    }

    /** @test */
    public function admin_can_reorder_academy_groups()
    {
        $groupIds = $this->groups->pluck('id')->toArray();
        $newOrder = [$groupIds[2], $groupIds[0], $groupIds[1]];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/academies/{$this->academy->id}/groups/reorder", [
                'groups' => $newOrder,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('academy_groups', ['id' => $groupIds[2], 'sort_order' => 1]);
        $this->assertDatabaseHas('academy_groups', ['id' => $groupIds[0], 'sort_order' => 2]);
        $this->assertDatabaseHas('academy_groups', ['id' => $groupIds[1], 'sort_order' => 3]);
    }

    /** @test */
    public function non_admin_cannot_reorder_academy_groups()
    {
        $groupIds = $this->groups->pluck('id')->toArray();

        $response = $this->actingAs($this->student, 'api')
            ->patchJson("/api/academies/{$this->academy->id}/groups/reorder", [
                'groups' => $groupIds,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function reorder_rejects_group_from_other_academy()
    {
        $otherAcademy = Academy::create([
            'user_id' => $this->admin->id,
            'name' => 'Other Academy',
            'type' => 'school',
        ]);
        $otherGroup = AcademyGroup::create([
            'academy_id' => $otherAcademy->id,
            'name' => 'Other Group',
            'type' => 'classroom',
        ]);

        $groupIds = $this->groups->pluck('id')->toArray();
        $invalidOrder = [$groupIds[0], $groupIds[1], $otherGroup->id];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/academies/{$this->academy->id}/groups/reorder", [
                'groups' => $invalidOrder,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function creating_academy_group_sets_sort_order_to_max_plus_one()
    {
        $maxSortOrder = $this->groups->max('sort_order');

        $newGroup = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'New Group',
            'type' => 'classroom',
        ]);

        $this->assertEquals($maxSortOrder + 1, $newGroup->sort_order);
    }

    /** @test */
    public function reorder_is_idempotent()
    {
        $groupIds = $this->groups->pluck('id')->toArray();
        $newOrder = [$groupIds[1], $groupIds[2], $groupIds[0]];

        // First time
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/academies/{$this->academy->id}/groups/reorder", ['groups' => $newOrder])
            ->assertStatus(200);

        // Second time
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/academies/{$this->academy->id}/groups/reorder", ['groups' => $newOrder])
            ->assertStatus(200);

        $this->assertDatabaseHas('academy_groups', ['id' => $groupIds[1], 'sort_order' => 1]);
        $this->assertDatabaseHas('academy_groups', ['id' => $groupIds[2], 'sort_order' => 2]);
        $this->assertDatabaseHas('academy_groups', ['id' => $groupIds[0], 'sort_order' => 3]);
    }

    /** @test */
    public function reorder_with_subset_of_ids_returns_422()
    {
        $groupIds = $this->groups->pluck('id')->toArray();
        $subset = [$groupIds[2]];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/academies/{$this->academy->id}/groups/reorder", [
                'groups' => $subset,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function reorder_with_duplicate_ids_returns_422()
    {
        $groupIds = $this->groups->pluck('id')->toArray();
        $duplicates = [$groupIds[0], $groupIds[1], $groupIds[1]];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/academies/{$this->academy->id}/groups/reorder", [
                'groups' => $duplicates,
            ]);

        $response->assertStatus(422);
    }
}
