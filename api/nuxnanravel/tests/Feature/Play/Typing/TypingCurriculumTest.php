<?php

namespace Tests\Feature\Play\Typing;

use App\Models\TypingStage;
use App\Models\TypingUserStage;
use App\Models\User;
use Database\Seeders\TypingCurriculumSeeder;
use Tests\TestCase;

class TypingCurriculumTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TypingCurriculumSeeder::class);
    }

    public function test_user_can_fetch_curriculum_and_stage_1_is_auto_unlocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/typing/curriculum?language=th&level=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_stages', 15)
            ->assertJsonPath('data.stages.0.stage_number', 1)
            ->assertJsonPath('data.stages.0.is_unlocked', true)
            ->assertJsonPath('data.stages.1.is_unlocked', false);
    }

    public function test_user_cannot_access_locked_stage(): void
    {
        $user = User::factory()->create();
        $stage2 = TypingStage::where('language', 'th')->where('level', 1)->where('stage_number', 2)->first();

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/typing/curriculum/stages/{$stage2->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stage']);
    }

    public function test_user_fails_stage_if_accuracy_is_below_minimum_and_does_not_unlock_next(): void
    {
        $user = User::factory()->create();
        $stage1 = TypingStage::where('language', 'th')->where('level', 1)->where('stage_number', 1)->first();

        // Unlock stage 1
        TypingUserStage::create([
            'user_id' => $user->id,
            'stage_id' => $stage1->id,
            'is_unlocked' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/typing/curriculum/stages/{$stage1->id}/submit", [
                'wpm' => 20,
                'accuracy' => 85.0, // below 90%
                'time_elapsed' => 25,
                'correct_chars' => 85,
                'total_chars' => 100,
                'mistakes' => 15,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.attempt_passed', false)
            ->assertJsonPath('data.passed', false)
            ->assertJsonPath('data.next_stage_unlocked', false);

        $stage2 = TypingStage::where('language', 'th')->where('level', 1)->where('stage_number', 2)->first();
        $this->assertDatabaseMissing('typing_user_stages', [
            'user_id' => $user->id,
            'stage_id' => $stage2->id,
            'is_unlocked' => true,
        ]);
    }

    public function test_user_passes_stage_when_meeting_criteria_and_unlocks_next_stage(): void
    {
        $user = User::factory()->create();
        $stage1 = TypingStage::where('language', 'th')->where('level', 1)->where('stage_number', 1)->first();

        // Unlock stage 1
        TypingUserStage::create([
            'user_id' => $user->id,
            'stage_id' => $stage1->id,
            'is_unlocked' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/typing/curriculum/stages/{$stage1->id}/submit", [
                'wpm' => 25,
                'accuracy' => 98.0, // >= 90% and high for 3 stars
                'time_elapsed' => 20,
                'correct_chars' => 98,
                'total_chars' => 100,
                'mistakes' => 2,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.attempt_passed', true)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.next_stage_unlocked', true)
            ->assertJsonPath('data.stars', 3);

        $stage2 = TypingStage::where('language', 'th')->where('level', 1)->where('stage_number', 2)->first();
        $this->assertDatabaseHas('typing_user_stages', [
            'user_id' => $user->id,
            'stage_id' => $stage2->id,
            'is_unlocked' => true,
        ]);
    }

    public function test_exam_stage_requires_two_passes_in_last_three_attempts_to_pass(): void
    {
        $user = User::factory()->create();
        $stage15 = TypingStage::where('language', 'th')->where('level', 1)->where('stage_number', 15)->first();

        TypingUserStage::create([
            'user_id' => $user->id,
            'stage_id' => $stage15->id,
            'is_unlocked' => true,
        ]);

        // Attempt 1: Meets criteria (96% acc, 16 WPM, 60s)
        $resp1 = $this->actingAs($user, 'api')
            ->postJson("/api/typing/curriculum/stages/{$stage15->id}/submit", [
                'wpm' => 16,
                'accuracy' => 96.0,
                'time_elapsed' => 60,
                'correct_chars' => 300,
                'total_chars' => 312,
                'mistakes' => 12,
            ]);

        $resp1->assertOk()
            ->assertJsonPath('data.attempt_passed', true)
            ->assertJsonPath('data.passed', false) // not yet officially passed (1/2 needed)
            ->assertJsonPath('data.exam_status.passes_count', 1);

        // Attempt 2: Meets criteria again -> unlocks Level 2 Stage 1
        $resp2 = $this->actingAs($user, 'api')
            ->postJson("/api/typing/curriculum/stages/{$stage15->id}/submit", [
                'wpm' => 17,
                'accuracy' => 97.0,
                'time_elapsed' => 60,
                'correct_chars' => 310,
                'total_chars' => 320,
                'mistakes' => 10,
                'weak_keys' => ['ฟ', 'ว'],
            ]);

        $resp2->assertOk()
            ->assertJsonPath('data.attempt_passed', true)
            ->assertJsonPath('data.passed', true) // Now 2/2 -> officially passed!
            ->assertJsonPath('data.exam_status.passes_count', 2)
            ->assertJsonPath('data.next_stage_unlocked', true);

        $level2Stage1 = TypingStage::where('language', 'th')->where('level', 2)->where('stage_number', 1)->first();
        $this->assertNotNull($level2Stage1);
        $this->assertDatabaseHas('typing_user_stages', [
            'user_id' => $user->id,
            'stage_id' => $level2Stage1->id,
            'is_unlocked' => true,
        ]);
    }

    public function test_thai_and_english_progress_are_stored_independently(): void
    {
        $user = User::factory()->create();

        $thStage1 = TypingStage::where('language', 'th')->where('level', 1)->where('stage_number', 1)->first();
        TypingUserStage::create([
            'user_id' => $user->id,
            'stage_id' => $thStage1->id,
            'is_unlocked' => true,
        ]);

        // Pass Thai Stage 1
        $this->actingAs($user, 'api')
            ->postJson("/api/typing/curriculum/stages/{$thStage1->id}/submit", [
                'wpm' => 20,
                'accuracy' => 95.0,
                'time_elapsed' => 20,
                'correct_chars' => 95,
                'total_chars' => 100,
                'mistakes' => 5,
            ])->assertOk();

        // Check English curriculum: English Stage 1 should be unlocked, Stage 2 still locked
        $enResp = $this->actingAs($user, 'api')
            ->getJson('/api/typing/curriculum?language=en&level=1');

        $enResp->assertOk()
            ->assertJsonPath('data.passed_stages', 0)
            ->assertJsonPath('data.stages.0.is_unlocked', true)
            ->assertJsonPath('data.stages.1.is_unlocked', false);
    }

    public function test_all_seeded_stages_only_use_target_keys_in_exercise_pool(): void
    {
        $stages = TypingStage::all();
        $this->assertGreaterThanOrEqual(60, $stages->count());

        foreach ($stages as $stage) {
            $allowed = array_flip($stage->target_keys);
            foreach ($stage->exercise_pool as $prompt) {
                $chars = mb_str_split($prompt);
                foreach ($chars as $ch) {
                    $this->assertArrayHasKey(
                        $ch,
                        $allowed,
                        "Stage {$stage->language} L{$stage->level}-S{$stage->stage_number} contains character '{$ch}' not in target_keys"
                    );
                }
            }
        }
    }
}
