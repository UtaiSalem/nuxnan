<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserUsageEvent;
use App\Models\PointRule;
use App\Models\QuestDefinition;
use App\Models\UserQuestProgress;
use App\Models\GamificationRuleLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamificationRuleEngine
{
    /**
     * Evaluate a usage event against points rules and quests.
     */
    public function evaluate(UserUsageEvent $event): void
    {
        $user = $event->user;

        // 1. Process Points Rules
        $this->processPointsRules($user, $event);

        // 2. Process Quests
        $this->processQuests($user, $event);

        // Mark event as processed
        $event->update(['processed_at' => now()]);
        
        // 3. Update Daily Summary
        \App\Jobs\UpdateActivitySummary::dispatch($user, $event->occurred_at->toDateString());
    }

    protected function processPointsRules(User $user, UserUsageEvent $event): void
    {
        $rules = PointRule::where('source_type', $event->event_type)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            // Log that no rules were found for this event type
            $this->logResult($user, $event, 'no_rule', 'skipped', 0, 0, "No active rules for event type: {$event->event_type}");
            return;
        }

        foreach ($rules as $rule) {
            $this->evaluateRule($user, $rule, $event);
        }
    }

    protected function evaluateRule(User $user, PointRule $rule, UserUsageEvent $event): void
    {
        $pointsService = app(PointsService::class);

        // Check eligibility (daily limits, cooldown, etc.)
        if (!$pointsService->canEarnFromRule($user, $rule)) {
            $this->logResult($user, $event, $rule->rule_key, 'skipped', 0, 0, 'Eligibility check failed (limits or cooldown)');
            return;
        }

        $amount = $rule->base_amount * $rule->multiplier;
        
        // Award XP (using a default formula for now: 10 XP per 1 point)
        $xpAmount = (int) ($amount * 10);

        // Award points and XP together
        $pointsService->earn(
            $user,
            $amount,
            $event->event_type,
            $event->source_id,
            $rule->rule_name,
            ['usage_event_id' => $event->id],
            $xpAmount
        );
        
        $this->logResult($user, $event, $rule->rule_key, 'awarded', $amount, $xpAmount);
    }

    protected function processQuests(User $user, UserUsageEvent $event): void
    {
        $quests = QuestDefinition::where('event_type', $event->event_type)
            ->where('is_active', true)
            ->get();

        foreach ($quests as $quest) {
            $this->updateQuestProgress($user, $quest, $event);
        }
    }

    protected function updateQuestProgress(User $user, QuestDefinition $quest, UserUsageEvent $event): void
    {
        $date = $event->occurred_at->toDateString();
        
        $progress = UserQuestProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'quest_id' => $quest->id,
                'quest_date' => $date,
            ],
            ['progress' => 0, 'is_completed' => false]
        );

        if ($progress->is_completed) {
            return;
        }

        $progress->increment('progress');

        if ($progress->progress >= $quest->target_count) {
            $progress->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            // Award Quest Rewards
            $this->awardQuestRewards($user, $quest);
        }
    }

    protected function awardQuestRewards(User $user, QuestDefinition $quest): void
    {
        $pointsService = app(PointsService::class);
        $xpReward = (int) ($quest->xp_reward ?? 0);
        
        if ($quest->points_reward > 0) {
            $pointsService->earn(
                $user,
                (float) $quest->points_reward,
                'quest_reward',
                $quest->id,
                "รางวัลภารกิจ: {$quest->title}",
                null,
                $xpReward
            );
        } elseif ($xpReward > 0) {
            $pointsService->addXp($user, $xpReward);
        }
    }

    protected function logResult(User $user, UserUsageEvent $event, string $ruleKey, string $result, float $points, int $xp, ?string $reason = null): void
    {
        GamificationRuleLog::create([
            'user_id' => $user->id,
            'usage_event_id' => $event->id,
            'rule_key' => $ruleKey,
            'result' => $result,
            'points_awarded' => $points,
            'xp_awarded' => $xp,
            'reason' => $reason,
            'evaluated_at' => now(),
        ]);
    }
}
