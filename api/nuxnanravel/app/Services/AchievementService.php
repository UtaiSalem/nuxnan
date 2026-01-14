<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\PointsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Check and unlock achievements for a user.
     */
    public function checkAndUnlockAchievements(int $userId): array
    {
        $unlockedAchievements = [];
        
        // Get all active achievements
        $achievements = Achievement::active()->get();
        
        foreach ($achievements as $achievement) {
            // Check if user already has this achievement
            $userAchievement = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->first();
            
            if ($userAchievement && $userAchievement->is_completed) {
                continue; // Already unlocked
            }
            
            // Check if criteria is met
            $user = User::find($userId);
            if (!$user) {
                continue;
            }
            
            if ($achievement->isCriteriaMet($user)) {
                // Unlock achievement
                $unlocked = $this->unlockAchievement($userId, $achievement->id);
                
                if ($unlocked) {
                    $unlockedAchievements[] = [
                        'id' => $achievement->id,
                        'name' => $achievement->name,
                        'description' => $achievement->description,
                        'icon' => $achievement->icon,
                        'badge_url' => $achievement->badge_url,
                        'points_reward' => $achievement->points_reward,
                    ];
                }
            }
        }
        
        return $unlockedAchievements;
    }
    
    /**
     * Unlock an achievement for a user.
     */
    public function unlockAchievement(int $userId, int $achievementId): bool
    {
        return DB::transaction(function () use ($userId, $achievementId) {
            $achievement = Achievement::find($achievementId);
            
            if (!$achievement) {
                return false;
            }
            
            // Get or create user achievement
            $userAchievement = UserAchievement::firstOrCreate(
                [
                    'user_id' => $userId,
                    'achievement_id' => $achievementId,
                ],
                [
                    'progress' => 0,
                    'is_completed' => false,
                ]
            );
            
            if ($userAchievement->is_completed) {
                return false; // Already unlocked
            }
            
            // Mark as completed
            $userAchievement->markAsCompleted();
            
            // Award points if achievement has points reward
            if ($achievement->points_reward > 0) {
                $pointsService = new PointsService();
                $pointsService->earn($userId, [
                    'source_type' => 'achievement',
                    'source_id' => $achievementId,
                    'amount' => $achievement->points_reward,
                    'description' => "บรรลุความสำเร็จ: {$achievement->name}",
                    'metadata' => [
                        'achievement_id' => $achievementId,
                        'achievement_name' => $achievement->name,
                    ],
                ]);
            }
            
            return true;
        });
    }
    
    /**
     * Get user achievements with progress.
     */
    public function getUserAchievements(int $userId): array
    {
        $userAchievements = UserAchievement::where('user_id', $userId)
            ->with('achievement')
            ->get();
        
        $result = [];
        
        foreach ($userAchievements as $userAchievement) {
            $achievement = $userAchievement->achievement;
            
            $result[] = [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'badge_url' => $achievement->badge_url,
                'type' => $achievement->type,
                'type_label' => $achievement->type_label,
                'points_reward' => $achievement->points_reward,
                'progress' => $userAchievement->progress,
                'progress_percentage' => $userAchievement->getProgressPercentage(),
                'is_completed' => $userAchievement->is_completed,
                'completed_at' => $userAchievement->completed_at ? $userAchievement->completed_at->toIso8601String() : null,
                'time_since_completed' => $userAchievement->getTimeSinceCompleted(),
            ];
        }
        
        return $result;
    }
    
    /**
     * Get available achievements for a user (not yet unlocked).
     */
    public function getAvailableAchievements(int $userId): array
    {
        $unlockedIds = UserAchievement::where('user_id', $userId)
            ->where('is_completed', true)
            ->pluck('achievement_id')
            ->toArray();
        
        $achievements = Achievement::active()
            ->whereNotIn('id', $unlockedIds)
            ->get();
        
        $result = [];
        
        foreach ($achievements as $achievement) {
            $result[] = [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'badge_url' => $achievement->badge_url,
                'type' => $achievement->type,
                'type_label' => $achievement->type_label,
                'points_reward' => $achievement->points_reward,
                'criteria' => $achievement->criteria,
            ];
        }
        
        return $result;
    }
    
    /**
     * Update achievement progress for a user.
     */
    public function updateProgress(int $userId, int $achievementId, int $progress): bool
    {
        $userAchievement = UserAchievement::where('user_id', $userId)
            ->where('achievement_id', $achievementId)
            ->first();
        
        if (!$userAchievement) {
            // Create new user achievement
            $userAchievement = UserAchievement::create([
                'user_id' => $userId,
                'achievement_id' => $achievementId,
                'progress' => $progress,
                'is_completed' => false,
            ]);
        } else {
            // Update progress
            $userAchievement->updateProgress($progress);
        }
        
        // Check if achievement is now completed
        if ($userAchievement->is_completed && !$userAchievement->wasRecentlyCreated) {
            // Award points
            $achievement = $userAchievement->achievement;
            if ($achievement->points_reward > 0) {
                $pointsService = new PointsService();
                $pointsService->earn($userId, [
                    'source_type' => 'achievement',
                    'source_id' => $achievementId,
                    'amount' => $achievement->points_reward,
                    'description' => "บรรลุความสำเร็จ: {$achievement->name}",
                    'metadata' => [
                        'achievement_id' => $achievementId,
                        'achievement_name' => $achievement->name,
                    ],
                ]);
            }
        }
        
        return true;
    }
    
    /**
     * Get achievement statistics.
     */
    public function getAchievementStats(int $userId): array
    {
        $totalAchievements = Achievement::active()->count();
        $unlockedAchievements = UserAchievement::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();
        
        $totalPointsFromAchievements = UserAchievement::where('user_id', $userId)
            ->where('is_completed', true)
            ->with('achievement')
            ->get()
            ->sum(function ($userAchievement) {
                return $userAchievement->achievement->points_reward ?? 0;
            });
        
        return [
            'total_achievements' => $totalAchievements,
            'unlocked_achievements' => $unlockedAchievements,
            'locked_achievements' => $totalAchievements - $unlockedAchievements,
            'completion_percentage' => $totalAchievements > 0 
                ? round(($unlockedAchievements / $totalAchievements) * 100, 2) 
                : 0,
            'total_points_from_achievements' => $totalPointsFromAchievements,
        ];
    }
}
