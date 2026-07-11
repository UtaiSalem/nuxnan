<?php

namespace Tests\Feature\Play\Typing;

use App\Models\PointRule;
use App\Models\PointsTransaction;
use App\Models\TypingDailyChallenge;
use App\Models\TypingSession;
use App\Models\TypingTournament;
use App\Models\TypingTournamentEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TypingRewardPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_typing_session_awards_xp_without_pp(): void
    {
        $user = User::factory()->create(['pp' => 25, 'xp' => 0, 'total_points_earned' => 0]);

        $response = $this->actingAs($user, 'api')->postJson('/api/typing/sessions', [
            'session_token' => Str::uuid()->toString(),
            'game_mode' => 'word_typing',
            'language' => 'en',
            'difficulty' => 'normal',
            'correct_chars' => 300,
            'total_chars' => 300,
            'correct_words' => 60,
            'total_words' => 60,
            'mistakes' => 0,
            'max_combo' => 20,
            'time_elapsed' => 60,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertGreaterThan(0, $user->fresh()->xp);
        $this->assertSame('25.00', $user->fresh()->pp);
        $this->assertFalse(PointsTransaction::where('source_type', 'typing_game')->exists());
    }

    public function test_daily_challenge_uses_owned_session_and_pays_once(): void
    {
        $user = User::factory()->create(['pp' => 0, 'xp' => 0, 'total_points_earned' => 0]);
        $challenge = TypingDailyChallenge::create([
            'challenge_date' => today(),
            'game_mode' => 'word_typing',
            'language' => 'en',
            'difficulty' => 'normal',
            'content_ids' => [],
            'target_wpm' => 30,
            'target_acc' => 90,
            'xp_reward' => 50,
            'pp_reward' => 10,
        ]);
        $session = $this->dailySession($user, $challenge);

        $response = $this->actingAs($user, 'api')->postJson("/api/typing/daily/{$challenge->id}/complete", [
            'session_id' => $session->id,
            'score' => 0,
            'wpm' => 0,
            'accuracy' => 0,
        ]);

        $response->assertOk()->assertJsonPath('completed', true);
        $this->assertSame('10.00', $user->fresh()->pp);
        $this->assertSame(50, $user->fresh()->xp);
        $this->assertDatabaseHas('points_transactions', [
            'idempotency_key' => "daily_challenge:{$challenge->id}:{$user->id}",
        ]);

        $this->actingAs($user, 'api')
            ->postJson("/api/typing/daily/{$challenge->id}/complete", ['session_id' => $session->id])
            ->assertStatus(422);
        $this->assertSame('10.00', $user->fresh()->pp);
    }

    public function test_daily_challenge_rejects_another_users_session(): void
    {
        $user = User::factory()->create(['pp' => 0, 'xp' => 0, 'total_points_earned' => 0]);
        $other = User::factory()->create();
        $challenge = TypingDailyChallenge::create([
            'challenge_date' => today(), 'game_mode' => 'word_typing', 'language' => 'en',
            'difficulty' => 'normal', 'content_ids' => [], 'target_wpm' => 30,
            'target_acc' => 90, 'xp_reward' => 50, 'pp_reward' => 10,
        ]);

        $this->actingAs($user, 'api')
            ->postJson("/api/typing/daily/{$challenge->id}/complete", [
                'session_id' => $this->dailySession($other, $challenge)->id,
            ])->assertStatus(422);

        $this->assertSame('0.00', $user->fresh()->pp);
    }

    public function test_tournament_claim_is_ranked_and_idempotent(): void
    {
        $user = User::factory()->create(['pp' => 0, 'xp' => 0, 'total_points_earned' => 0]);
        $tournament = TypingTournament::create([
            'name' => 'Test Tournament', 'slug' => 'test-tournament', 'type' => 'weekly',
            'language' => 'en', 'difficulty' => 'normal', 'game_mode' => 'time_attack',
            'time_limit' => 60, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay(),
            'status' => 'finished', 'prize_1st_xp' => 100, 'prize_1st_pp' => 20,
            'prize_2nd_xp' => 50, 'prize_2nd_pp' => 10, 'prize_3rd_xp' => 25,
            'prize_3rd_pp' => 5, 'prize_all_xp' => 5,
        ]);
        TypingTournamentEntry::create([
            'tournament_id' => $tournament->id, 'user_id' => $user->id,
            'rank' => 1, 'best_score' => 1000, 'attempts' => 1,
        ]);

        $this->actingAs($user, 'api')->postJson("/api/typing/tournaments/{$tournament->id}/claim")
            ->assertOk()->assertJsonPath('prize_pp', 20);
        $this->assertSame('20.00', $user->fresh()->pp);
        $this->assertSame(100, $user->fresh()->xp);

        $this->actingAs($user, 'api')->postJson("/api/typing/tournaments/{$tournament->id}/claim")
            ->assertStatus(422);
        $this->assertSame('20.00', $user->fresh()->pp);
        $this->assertSame(1, PointsTransaction::where('idempotency_key', "tournament_prize:{$tournament->id}:{$user->id}")->count());
    }

    public function test_daily_challenge_pays_despite_cross_source_daily_earnings(): void
    {
        // Rule with a daily cap present. The cap must be scoped to this rule's
        // source, so PP earned from an unrelated source the same day must not
        // block the challenge payout (regression guard for the aggregate bug).
        PointRule::create([
            'rule_key' => 'typing_daily_challenge',
            'rule_name' => 'Daily Typing Challenge',
            'action_type' => 'earn',
            'source_type' => 'typing_daily_challenge',
            'base_amount' => 0,
            'max_daily_earnings' => 5,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['pp' => 50, 'xp' => 0, 'total_points_earned' => 50]);

        // 50 PP already earned from an unrelated source today (e.g. a quiz pass).
        // The source-scoped daily check reads points_transactions, so record it there.
        PointsTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'amount' => 50,
            'balance_before' => 0,
            'balance_after' => 50,
            'source_type' => 'quiz_pass',
            'status' => 'completed',
        ]);

        $challenge = TypingDailyChallenge::create([
            'challenge_date' => today(), 'game_mode' => 'word_typing', 'language' => 'en',
            'difficulty' => 'normal', 'content_ids' => [], 'target_wpm' => 30,
            'target_acc' => 90, 'xp_reward' => 50, 'pp_reward' => 3,
        ]);
        $session = $this->dailySession($user, $challenge);

        $this->actingAs($user, 'api')
            ->postJson("/api/typing/daily/{$challenge->id}/complete", ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('completed', true);

        // 50 (quiz) + 3 (challenge) — challenge PP was not blocked by the quiz earnings.
        $this->assertSame('53.00', $user->fresh()->pp);
        $this->assertDatabaseHas('points_transactions', [
            'idempotency_key' => "daily_challenge:{$challenge->id}:{$user->id}",
            'source_type' => 'typing_daily_challenge',
        ]);
    }

    private function dailySession(User $user, TypingDailyChallenge $challenge): TypingSession
    {
        return TypingSession::create([
            'user_id' => $user->id, 'session_token' => Str::uuid()->toString(),
            'game_mode' => 'daily_challenge', 'language' => 'en', 'difficulty' => 'normal',
            'wpm' => 40, 'accuracy' => 95, 'score' => 500, 'challenge_id' => $challenge->id,
            'completed' => true, 'completed_at' => now(),
        ]);
    }
}
