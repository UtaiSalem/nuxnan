<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\ReportDefinition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Report definition management: update / toggle-status / duplicate / delete.
 * Covers the duplicateDefinition bug — the controller called
 * ReportDefinition::duplicate() with no argument while the model signature is
 * duplicate(string $newName), i.e. every duplicate request threw ArgumentCountError (500).
 */
class ReportDefinitionTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $u = User::factory()->create();
        $u->assignRole('SUPER_ADMIN');

        return $u;
    }

    private function definition(Academy $academy, User $actor): ReportDefinition
    {
        return ReportDefinition::create([
            'academy_id' => $academy->id,
            'created_by' => $actor->id,
            'code' => 'def-'.uniqid(),
            'name' => 'นิยามทดสอบ',
            'category' => ReportDefinition::CATEGORY_ACADEMIC,
            'report_type' => ReportDefinition::TYPE_TABLE,
            'data_source' => 'custom',
            'columns' => ['name'],
            'is_active' => true,
        ]);
    }

    public function test_definition_update_toggle_duplicate_delete(): void
    {
        $actor = $this->actor();
        $academy = Academy::factory()->create();
        $def = $this->definition($academy, $actor);
        $base = "/api/academies/{$academy->id}/reports/definitions/{$def->id}";

        // update name
        $this->actingAs($actor, 'api')->patchJson($base, ['name' => 'ชื่อใหม่'])
            ->assertStatus(200)->assertJsonPath('success', true);
        $this->assertEquals('ชื่อใหม่', $def->fresh()->name);

        // toggle status (true → false)
        $this->actingAs($actor, 'api')->postJson("{$base}/toggle-status")
            ->assertStatus(200);
        $this->assertFalse((bool) $def->fresh()->is_active);

        // duplicate (regression: was 500 ArgumentCountError)
        $dup = $this->actingAs($actor, 'api')->postJson("{$base}/duplicate");
        $dup->assertStatus(201)->assertJsonPath('success', true);
        $newId = $dup->json('data.id');
        $this->assertNotEquals($def->id, $newId);
        $this->assertStringContainsString('(สำเนา)', ReportDefinition::find($newId)->name);
        $this->assertEquals(2, ReportDefinition::where('academy_id', $academy->id)->count());

        // delete original
        $this->actingAs($actor, 'api')->deleteJson($base)
            ->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('report_definitions', ['id' => $def->id]);
    }
}
