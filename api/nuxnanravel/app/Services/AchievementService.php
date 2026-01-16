<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AchievementService
{
    /**
     * Get all achievements.
     */
    public function getAllAchievements(): array
    {
        return Achievement::active()->get()->toArray();
    }

    /**
     * Get user achievements with progress.
     */
    public function getUserAchievements(User $user): array
    {
        $userAchievements = UserAchievement::with('achievement')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($userAchievement) {
                return [
                    'id' => $userAchievement->id,
                    'achievement_id' => $userAchievement->achievement_id,
                    'name' => $userAchievement->achievement->name ?? '',
                    'description' => $userAchievement->achievement->description ?? '',
                    'icon' => $userAchievement->achievement->icon ?? '',
                    'type' => $userAchievement->achievement->type ?? '',
                    'points_reward' => $userAchievement->achievement->points_reward ?? 0,
                    'badge_url' => $userAchievement->achievement->badge_url ?? '',
                    'progress' => $userAchievement->progress,
                    'is_completed' => $userAchievement->is_completed,
                    'completed_at' => $userAchievement->completed_at ? $userAchievement->completed_at->format('Y-m-d H:i:s') : null,
                    'progress_percentage' => $userAchievement->progress_percentage,
                ];
            })
            ->toArray();

        return $userAchievements;
    }

    /**
     * Get or create user achievement.
     */
    public function getOrCreateUserAchievement(User $user, int $achievementId): UserAchievement
    {
        return UserAchievement::firstOrCreate(
            [
                'user_id' => $user->id,
                'achievement_id' => $achievementId,
            ],
            [
                'progress' => 0,
                'is_completed' => false,
                'completed_at' => null,
            ]
        );
    }

    /**
     * Update user achievement progress.
     */
    public function updateProgress(User $user, int $achievementId, int $progress, ?array $metadata = null): UserAchievement
    {
        return DB::transaction(function () use ($user, $achievementId, $progress, $metadata) {
            $userAchievement = $this->getOrCreateUserAchievement($user, $achievementId);

            $achievement = Achievement::find($achievementId);
            $criteria = $achievement->criteria ?? [];
            $target = $criteria['target'] ?? 100;

            // Update progress
            $userAchievement->progress = min($target, $progress);
            $userAchievement->metadata = $metadata ?? $userAchievement->metadata;

            // Check if completed
            if ($userAchievement->progress >= $target && !$userAchievement->is_completed) {
                $userAchievement->is_completed = true;
                $userAchievement->completed_at = now();

                // Award points if achievement has points reward
                if ($achievement->points_reward > 0) {
                    $pointsService = new PointsService();
                    $pointsService->earn(
                        $user,
                        $achievement->points_reward,
                        'achievement',
                        $achievementId,
                        "บรรลุความสำเร็จ: {$achievement->name}",
                        ['achievement_name' => $achievement->name]
                    );
                }
            }

            $userAchievement->save();

            Log::info('Achievement progress updated', [
                'user_id' => $user->id,
                'achievement_id' => $achievementId,
                'progress' => $progress,
                'is_completed' => $userAchievement->is_completed,
            ]);

            return $userAchievement;
        });
    }

    /**
     * Check and update achievements based on user actions.
     */
    public function checkAndUpdateAchievements(User $user, string $actionType, ?array $context = null): array
    {
        $achievements = Achievement::active()->type($actionType)->get();
        $unlockedAchievements = [];

        foreach ($achievements as $achievement) {
            $userAchievement = $this->getOrCreateUserAchievement($user, $achievement->id);

            if ($userAchievement->is_completed) {
                continue;
            }

            $criteria = $achievement->criteria ?? [];
            $newProgress = $this->calculateProgress($user, $criteria, $context);

            if ($newProgress > $userAchievement->progress) {
                $updatedUserAchievement = $this->updateProgress(
                    $user,
                    $achievement->id,
                    $newProgress,
                    $context
                );

                if ($updatedUserAchievement->is_completed) {
                    $unlockedAchievements[] = [
                        'id' => $achievement->id,
                        'name' => $achievement->name,
                        'points_reward' => $achievement->points_reward,
                        'badge_url' => $achievement->badge_url,
                    ];
                }
            }
        }

        return $unlockedAchievements;
    }

    /**
     * Calculate progress based on criteria.
     */
    protected function calculateProgress(User $user, array $criteria, ?array $context = null): int
    {
        $type = $criteria['type'] ?? 'count';
        $target = $criteria['target'] ?? 1;
        $source = $criteria['source'] ?? null;

        return match($type) {
            'count' => $this->calculateCountProgress($user, $source, $target),
            'points' => $user->total_points_earned,
            'streak' => $user->pointStreak->current_streak ?? 0,
            'level' => $user->level ?? 1,
            default => 0,
        };
    }

    /**
     * Calculate count-based progress.
     */
    protected function calculateCountProgress(User $user, ?string $source, int $target): int
    {
        return match($source) {
            'posts' => $user->posts()->count(),
            'likes_received' => \App\Models\LikedPost::where('post_owner_id', $user->id)->count(),
            'comments_received' => \App\Models\PostComment::whereHas('post', fn($q) => $q->where('user_id', $user->id))->count(),
            'friends' => $user->friends()->count(),
            'lessons_completed' => \App\Models\LessonProgress::where('user_id', $user->id)->count(),
            'quizzes_completed' => \App\Models\CourseQuizResult::where('user_id', $user->id)->count(),
            'assignments_submitted' => \App\Models\AssignmentAnswer::where('user_id', $user->id)->count(),
            'courses_completed' => \App\Models\CourseMember::where('user_id', $user->id)
                ->where('edited_grade', 'passed')
                ->count(),
            default => 0,
        };
    }

    /**
     * Get achievement statistics.
     */
    public function getAchievementStats(User $user): array
    {
        $totalAchievements = Achievement::active()->count();
        $completedAchievements = UserAchievement::where('user_id', $user->id)
            ->completed()
            ->count();

        $totalPointsFromAchievements = UserAchievement::where('user_id', $user->id)
            ->completed()
            ->with('achievement')
            ->get()
            ->sum(fn($userAchievement) => $userAchievement->achievement->points_reward ?? 0);

        return [
            'total_achievements' => $totalAchievements,
            'completed_achievements' => $completedAchievements,
            'completion_percentage' => $totalAchievements > 0 
                ? round(($completedAchievements / $totalAchievements) * 100, 2)
                : 0,
            'total_points_from_achievements' => $totalPointsFromAchievements,
        ];
    }

    /**
     * Get achievements by type.
     */
    public function getAchievementsByType(string $type): array
    {
        return Achievement::active()->type($type)->get()->toArray();
    }

    /**
     * Create default achievements.
     */
    public function createDefaultAchievements(): void
    {
        $defaultAchievements = [
            // Points Achievements
            [
                'name' => 'First Steps',
                'description' => 'ได้แต้มครั้งแรก',
                'icon' => '/icons/first-steps.png',
                'type' => 'points',
                'criteria' => ['type' => 'points', 'target' => 1],
                'points_reward' => 10,
                'badge_url' => '/badges/first-steps.png',
            ],
            [
                'name' => 'Rising Star',
                'description' => 'สะสม 1,000 แต้ม',
                'icon' => '/icons/rising-star.png',
                'type' => 'points',
                'criteria' => ['type' => 'points', 'target' => 1000],
                'points_reward' => 100,
                'badge_url' => '/badges/rising-star.png',
            ],
            [
                'name' => 'Point Master',
                'description' => 'สะสม 10,000 แต้ม',
                'icon' => '/icons/point-master.png',
                'type' => 'points',
                'criteria' => ['type' => 'points', 'target' => 10000],
                'points_reward' => 1000,
                'badge_url' => '/badges/point-master.png',
            ],
            [
                'name' => 'Point Legend',
                'description' => 'สะสม 100,000 แต้ม',
                'icon' => '/icons/point-legend.png',
                'type' => 'points',
                'criteria' => ['type' => 'points', 'target' => 100000],
                'points_reward' => 10000,
                'badge_url' => '/badges/point-legend.png',
            ],

            // Action Achievements
            [
                'name' => 'Social Butterfly',
                'description' => 'โพสต์ 10 ครั้ง',
                'icon' => '/icons/social-butterfly.png',
                'type' => 'actions',
                'criteria' => ['type' => 'count', 'source' => 'posts', 'target' => 10],
                'points_reward' => 50,
                'badge_url' => '/badges/social-butterfly.png',
            ],
            [
                'name' => 'Like Magnet',
                'description' => 'ได้รับ 100 ไลค์',
                'icon' => '/icons/like-magnet.png',
                'type' => 'actions',
                'criteria' => ['type' => 'count', 'source' => 'likes_received', 'target' => 100],
                'points_reward' => 100,
                'badge_url' => '/badges/like-magnet.png',
            ],
            [
                'name' => 'Comment King',
                'description' => 'คอมเมนต์ 50 ครั้ง',
                'icon' => '/icons/comment-king.png',
                'type' => 'actions',
                'criteria' => ['type' => 'count', 'source' => 'comments_received', 'target' => 50],
                'points_reward' => 100,
                'badge_url' => '/badges/comment-king.png',
            ],
            [
                'name' => 'Sharing is Caring',
                'description' => 'แชร์ 20 ครั้ง',
                'icon' => '/icons/sharing-caring.png',
                'type' => 'actions',
                'criteria' => ['type' => 'count', 'source' => 'shares', 'target' => 20],
                'points_reward' => 100,
                'badge_url' => '/badges/sharing-caring.png',
            ],

            // Streak Achievements
            [
                'name' => 'Consistent',
                'description' => 'เข้า 3 วันต่อเนื่อง',
                'icon' => '/icons/consistent.png',
                'type' => 'streak',
                'criteria' => ['type' => 'streak', 'target' => 3],
                'points_reward' => 30,
                'badge_url' => '/badges/consistent.png',
            ],
            [
                'name' => 'Dedicated',
                'description' => 'เข้า 7 วันต่อเนื่อง',
                'icon' => '/icons/dedicated.png',
                'type' => 'streak',
                'criteria' => ['type' => 'streak', 'target' => 7],
                'points_reward' => 100,
                'badge_url' => '/badges/dedicated.png',
            ],
            [
                'name' => 'Loyal',
                'description' => 'เข้า 30 วันต่อเนื่อง',
                'icon' => '/icons/loyal.png',
                'type' => 'streak',
                'criteria' => ['type' => 'streak', 'target' => 30],
                'points_reward' => 500,
                'badge_url' => '/badges/loyal.png',
            ],
            [
                'name' => 'Unstoppable',
                'description' => 'เข้า 100 วันต่อเนื่อง',
                'icon' => '/icons/unstoppable.png',
                'type' => 'streak',
                'criteria' => ['type' => 'streak', 'target' => 100],
                'points_reward' => 2000,
                'badge_url' => '/badges/unstoppable.png',
            ],

            // Social Achievements
            [
                'name' => 'Friendly',
                'description' => 'เพิ่มเพื่อน 10 คน',
                'icon' => '/icons/friendly.png',
                'type' => 'social',
                'criteria' => ['type' => 'count', 'source' => 'friends', 'target' => 10],
                'points_reward' => 50,
                'badge_url' => '/badges/friendly.png',
            ],
            [
                'name' => 'Popular',
                'description' => 'มีเพื่อน 100 คน',
                'icon' => '/icons/popular.png',
                'type' => 'social',
                'criteria' => ['type' => 'count', 'source' => 'friends', 'target' => 100],
                'points_reward' => 500,
                'badge_url' => '/badges/popular.png',
            ],
            [
                'name' => 'Influencer',
                'description' => 'มีเพื่อน 1,000 คน',
                'icon' => '/icons/influencer.png',
                'type' => 'social',
                'criteria' => ['type' => 'count', 'source' => 'friends', 'target' => 1000],
                'points_reward' => 2000,
                'badge_url' => '/badges/influencer.png',
            ],

            // Learning Achievements
            [
                'name' => 'First Lesson',
                'description' => 'เข้าเรียนบทเรียนแรก',
                'icon' => '/icons/first-lesson.png',
                'type' => 'learning',
                'criteria' => ['type' => 'count', 'source' => 'lessons_completed', 'target' => 1],
                'points_reward' => 20,
                'badge_url' => '/badges/first-lesson.png',
            ],
            [
                'name' => 'Quiz Master',
                'description' => 'ทำแบบทดสอบ 10 ครั้ง',
                'icon' => '/icons/quiz-master.png',
                'type' => 'learning',
                'criteria' => ['type' => 'count', 'source' => 'quizzes_completed', 'target' => 10],
                'points_reward' => 200,
                'badge_url' => '/badges/quiz-master.png',
            ],
            [
                'name' => 'Assignment Pro',
                'description' => 'ส่งงาน 20 ครั้ง',
                'icon' => '/icons/assignment-pro.png',
                'type' => 'learning',
                'criteria' => ['type' => 'count', 'source' => 'assignments_submitted', 'target' => 20],
                'points_reward' => 300,
                'badge_url' => '/badges/assignment-pro.png',
            ],
            [
                'name' => 'Course Graduate',
                'description' => 'จบคอร์ส 1 คอร์ส',
                'icon' => '/icons/course-graduate.png',
                'type' => 'learning',
                'criteria' => ['type' => 'count', 'source' => 'courses_completed', 'target' => 1],
                'points_reward' => 1000,
                'badge_url' => '/badges/course-graduate.png',
            ],
        ];

        foreach ($defaultAchievements as $achievementData) {
            Achievement::firstOrCreate(
                ['name' => $achievementData['name']],
                $achievementData
            );
        }

        Log::info('Default achievements created', [
            'count' => count($defaultAchievements),
        ]);
    }
}
