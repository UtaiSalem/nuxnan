<?php

namespace App\Services;

use App\Models\TypingSession;
use App\Models\TypingStage;
use App\Models\TypingUserStage;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TypingCurriculumService
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Get curriculum overview for a given language and level with user progress
     */
    public function getCurriculum(User $user, string $language, int $level = 1): array
    {
        $stages = TypingStage::where('language', $language)
            ->where('level', $level)
            ->orderBy('stage_number')
            ->get();

        if ($stages->isEmpty()) {
            return [
                'language' => $language,
                'level' => $level,
                'stages' => [],
                'total_stages' => 0,
                'passed_stages' => 0,
                'total_stars' => 0,
            ];
        }

        // Fetch user progress for these stages
        $userStages = TypingUserStage::where('user_id', $user->id)
            ->whereIn('stage_id', $stages->pluck('id'))
            ->get()
            ->keyBy('stage_id');

        // Check if stage 1 is unlocked. If not unlocked yet:
        // Level 1: always auto-unlock stage 1 for a new user
        // Level > 1: unlock stage 1 only if previous level's final stage (exam) is passed
        $firstStage = $stages->firstWhere('stage_number', 1);
        if ($firstStage && (! isset($userStages[$firstStage->id]) || ! $userStages[$firstStage->id]->is_unlocked)) {
            $canUnlockFirst = ($level === 1);
            if (! $canUnlockFirst) {
                $prevExam = TypingStage::where('language', $language)
                    ->where('level', $level - 1)
                    ->orderByDesc('stage_number')
                    ->first();
                if ($prevExam) {
                    $prevProgress = TypingUserStage::where('user_id', $user->id)
                        ->where('stage_id', $prevExam->id)
                        ->where('is_passed', true)
                        ->first();
                    $canUnlockFirst = (bool) $prevProgress;
                }
            }

            if ($canUnlockFirst) {
                $unlockedStage1 = TypingUserStage::updateOrCreate(
                    ['user_id' => $user->id, 'stage_id' => $firstStage->id],
                    ['is_unlocked' => true]
                );
                $userStages[$firstStage->id] = $unlockedStage1;
            }
        }

        $stagesList = $stages->map(function (TypingStage $stage) use ($userStages) {
            $progress = $userStages->get($stage->id);

            return [
                'id' => $stage->id,
                'language' => $stage->language,
                'level' => $stage->level,
                'stage_number' => $stage->stage_number,
                'title' => $stage->title,
                'description' => $stage->description,
                'stage_type' => $stage->stage_type,
                'target_keys' => $stage->target_keys,
                'min_accuracy' => $stage->min_accuracy,
                'min_wpm' => $stage->min_wpm,
                'min_duration_sec' => $stage->min_duration_sec,
                'pass_count_needed' => $stage->pass_count_needed,
                'pass_window_size' => $stage->pass_window_size,
                'xp_reward' => $stage->xp_reward,
                'is_unlocked' => $progress ? (bool) $progress->is_unlocked : false,
                'is_passed' => $progress ? (bool) $progress->is_passed : false,
                'stars' => $progress ? (int) $progress->stars : 0,
                'best_accuracy' => $progress ? (float) $progress->best_accuracy : 0.0,
                'best_wpm' => $progress ? (int) $progress->best_wpm : 0,
                'attempts_count' => $progress ? (int) $progress->attempts_count : 0,
                'recent_attempts' => $progress ? ($progress->recent_attempts ?? []) : [],
            ];
        });

        $totalStages = $stagesList->count();
        $passedStages = $stagesList->where('is_passed', true)->count();
        $totalStars = $stagesList->sum('stars');

        return [
            'language' => $language,
            'level' => $level,
            'stages' => $stagesList,
            'total_stages' => $totalStages,
            'passed_stages' => $passedStages,
            'total_stars' => $totalStars,
        ];
    }

    /**
     * Get a single stage detail for the user (guards locked access)
     */
    public function getStage(User $user, int $stageId): array
    {
        $stage = TypingStage::findOrFail($stageId);

        $progress = TypingUserStage::where('user_id', $user->id)
            ->where('stage_id', $stage->id)
            ->first();

        // If stage 1 of level 1 and not yet created, auto-unlock
        if (! $progress && $stage->level === 1 && $stage->stage_number === 1) {
            $progress = TypingUserStage::create([
                'user_id' => $user->id,
                'stage_id' => $stage->id,
                'is_unlocked' => true,
            ]);
        }

        if (! $progress || ! $progress->is_unlocked) {
            throw ValidationException::withMessages([
                'stage' => ['ด่านนี้ยังไม่ได้รับการปลดล็อก กรุณาผ่านด่านก่อนหน้าก่อนเข้าฝึก'],
            ]);
        }

        $exercisePool = $stage->exercise_pool;

        // If it's a weak keys review stage (stage 14), augment with user's actual weak keys
        if ($stage->stage_type === 'review') {
            $weakKeys = $this->getWeakKeys($user, $stage->language);
            if (! empty($weakKeys)) {
                // Generate targeted drills combining weak keys
                $customDrill = implode(' ', array_slice($weakKeys, 0, 5));
                if (! in_array($customDrill, $exercisePool)) {
                    array_unshift($exercisePool, $customDrill);
                }
            }
        }

        return [
            'stage' => [
                'id' => $stage->id,
                'language' => $stage->language,
                'level' => $stage->level,
                'stage_number' => $stage->stage_number,
                'title' => $stage->title,
                'description' => $stage->description,
                'stage_type' => $stage->stage_type,
                'target_keys' => $stage->target_keys,
                'exercise_pool' => $exercisePool,
                'min_accuracy' => $stage->min_accuracy,
                'min_wpm' => $stage->min_wpm,
                'min_duration_sec' => $stage->min_duration_sec,
                'pass_count_needed' => $stage->pass_count_needed,
                'pass_window_size' => $stage->pass_window_size,
                'xp_reward' => $stage->xp_reward,
            ],
            'progress' => [
                'is_unlocked' => (bool) $progress->is_unlocked,
                'is_passed' => (bool) $progress->is_passed,
                'stars' => (int) $progress->stars,
                'best_accuracy' => (float) $progress->best_accuracy,
                'best_wpm' => (int) $progress->best_wpm,
                'best_cpm' => (int) $progress->best_cpm,
                'attempts_count' => (int) $progress->attempts_count,
                'recent_attempts' => $progress->recent_attempts ?? [],
            ],
        ];
    }

    /**
     * Submit an attempt for a stage, evaluate criteria, update stars and progression
     */
    public function submitAttempt(User $user, int $stageId, array $data): array
    {
        $stage = TypingStage::findOrFail($stageId);

        $userStage = TypingUserStage::where('user_id', $user->id)
            ->where('stage_id', $stage->id)
            ->first();

        if (! $userStage || ! $userStage->is_unlocked) {
            throw ValidationException::withMessages([
                'stage' => ['ด่านนี้ยังไม่ได้รับการปลดล็อก'],
            ]);
        }

        // 1. Authoritative server-side metric computation from raw character counts and duration
        // Standard definition:
        // - 1 character = 1 Unicode code point (Thai consonants, vowels, tone marks each count as 1 keystroke)
        // - WPM = round((correct_chars / 5) / (time_elapsed / 60))
        // - Accuracy = round((correct_chars / total_chars) * 100, 2)
        // - Backspace/deletion is disabled in sequential keystroke drills; every keystroke counts toward total_chars
        $timeElapsed = max(1, (int) ($data['time_elapsed'] ?? 1));
        $correctChars = max(0, (int) ($data['correct_chars'] ?? 0));
        $totalChars = max($correctChars, (int) ($data['total_chars'] ?? $correctChars));

        $wpm = $totalChars > 0
            ? (int) round(($correctChars / 5) / ($timeElapsed / 60))
            : (int) ($data['wpm'] ?? 0);
        $accuracy = $totalChars > 0
            ? round(($correctChars / $totalChars) * 100, 2)
            : (float) ($data['accuracy'] ?? 0.0);
        $cpm = (int) round(($correctChars / $timeElapsed) * 60);

        // Evaluate whether THIS individual attempt met minimum targets
        $reasons = [];
        $accPassed = ($accuracy >= $stage->min_accuracy);
        $wpmPassed = ($stage->min_wpm <= 0 || $wpm >= $stage->min_wpm);
        // Allow slight tolerance of 2 seconds for timer sync jitter if min_duration_sec is set
        $durationPassed = ($stage->min_duration_sec <= 0 || $timeElapsed >= max(1, $stage->min_duration_sec - 2));

        if (! $accPassed) {
            $reasons[] = "ความแม่นยำยังไม่ถึงเกณฑ์ (ทำได้ {$accuracy}%, ต้องการอย่างน้อย {$stage->min_accuracy}%)";
        }
        if (! $wpmPassed) {
            $diff = $stage->min_wpm - $wpm;
            $reasons[] = "ความเร็วยังไม่ถึงเกณฑ์ (ทำได้ {$wpm} WPM, ต้องการอย่างน้อย {$stage->min_wpm} WPM, ขาดอีก {$diff} WPM)";
        }
        if (! $durationPassed) {
            $reasons[] = "เวลาการฝึกยังไม่ครบตามเกณฑ์ ({$timeElapsed} วิ / {$stage->min_duration_sec} วิ)";
        }

        $thisAttemptPassed = ($accPassed && $wpmPassed && $durationPassed);

        // 2. Update recent attempts history window
        $weakKeysAttempt = array_values(array_filter(
            is_array($data['weak_keys'] ?? null) ? $data['weak_keys'] : [],
            fn ($k) => is_string($k) && $k !== ''
        ));

        $recentAttempts = $userStage->recent_attempts ?? [];
        $recentAttempts[] = [
            'passed' => $thisAttemptPassed,
            'accuracy' => $accuracy,
            'wpm' => $wpm,
            'cpm' => $cpm,
            'time_elapsed' => $timeElapsed,
            'weak_keys' => $weakKeysAttempt,
            'date' => now()->toIso8601String(),
        ];

        // Keep only up to window size (or max 5)
        $windowSize = max(3, $stage->pass_window_size);
        if (count($recentAttempts) > $windowSize) {
            $recentAttempts = array_slice($recentAttempts, -$windowSize);
        }

        // 3. Determine overall stage pass
        $stageOfficiallyPassed = false;
        $examWindow = array_slice($recentAttempts, -$stage->pass_window_size);
        $currentPassesCount = collect($examWindow)->where('passed', true)->count();

        if ($stage->pass_count_needed > 1) {
            // Boss Exam (e.g. 2 out of last 3)
            if ($currentPassesCount >= $stage->pass_count_needed) {
                $stageOfficiallyPassed = true;
                $reasons[] = "ผ่านการสอบเลื่อนระดับแล้ว! ({$currentPassesCount}/{$stage->pass_window_size} ครั้งล่าสุด) ปลดล็อกระดับถัดไปเรียบร้อย";
            } else {
                $stageOfficiallyPassed = false;
                if ($thisAttemptPassed) {
                    $reasons[] = 'ครั้งนี้ผ่านเกณฑ์แล้ว! ต้องสอบผ่านอีก '.($stage->pass_count_needed - $currentPassesCount)." ครั้งใน {$stage->pass_window_size} ครั้งล่าสุดเพื่อปลดล็อกระดับถัดไป (ปัจจุบัน {$currentPassesCount}/{$stage->pass_window_size})";
                } else {
                    $reasons[] = "การสอบท้ายระดับต้องผ่าน {$stage->pass_count_needed} ใน {$stage->pass_window_size} ครั้งล่าสุด (ปัจจุบันผ่าน {$currentPassesCount}/{$stage->pass_window_size})";
                }
            }
        } else {
            // Normal stage: this single attempt passing passes the stage
            $stageOfficiallyPassed = $thisAttemptPassed;
            if ($thisAttemptPassed) {
                $reasons[] = "ผ่านเกณฑ์ความแม่นยำ ({$accuracy}% ≥ {$stage->min_accuracy}%)".($stage->min_wpm > 0 ? " และความเร็ว ({$wpm} WPM ≥ {$stage->min_wpm} WPM)" : '');
            }
        }

        // If previously passed, maintain passed status
        $wasPassedBefore = (bool) $userStage->is_passed;
        $isPassedNow = $wasPassedBefore || $stageOfficiallyPassed;

        // 4. Calculate stars (1 - 3)
        $stars = 0;
        if ($thisAttemptPassed) {
            $stars = 1; // Passed baseline
            // 2 Stars: high accuracy + speed boost
            $twoStarAcc = min(98.0, $stage->min_accuracy + 2.0);
            $twoStarWpm = $stage->min_wpm + 3;
            if ($accuracy >= $twoStarAcc && $wpm >= $twoStarWpm) {
                $stars = 2;
            }
            // 3 Stars: near perfection + higher speed
            $threeStarAcc = min(100.0, $stage->min_accuracy + 4.0);
            $threeStarWpm = $stage->min_wpm + 5;
            if ($accuracy >= $threeStarAcc && $wpm >= $threeStarWpm) {
                $stars = 3;
            }
        }

        $bestStars = max((int) $userStage->stars, $stars);
        $bestAcc = max((float) $userStage->best_accuracy, $accuracy);
        $bestWpm = max((int) $userStage->best_wpm, $wpm);
        $bestCpm = max((int) $userStage->best_cpm, $cpm);

        // Update UserStage record
        $userStage->update([
            'is_passed' => $isPassedNow,
            'stars' => $bestStars,
            'best_accuracy' => $bestAcc,
            'best_wpm' => $bestWpm,
            'best_cpm' => $bestCpm,
            'attempts_count' => $userStage->attempts_count + 1,
            'recent_attempts' => $recentAttempts,
            'passed_at' => ($stageOfficiallyPassed && ! $userStage->passed_at) ? now() : $userStage->passed_at,
        ]);

        // 5. Unlock next stage if this stage is newly or already passed
        $nextStageUnlocked = false;
        $nextStage = null;

        if ($isPassedNow) {
            // Find next stage in the same level
            $nextStage = TypingStage::where('language', $stage->language)
                ->where('level', $stage->level)
                ->where('stage_number', $stage->stage_number + 1)
                ->first();

            if ($nextStage) {
                TypingUserStage::updateOrCreate(
                    ['user_id' => $user->id, 'stage_id' => $nextStage->id],
                    ['is_unlocked' => true]
                );
                $nextStageUnlocked = true;
            } else {
                // Final stage of this level passed -> unlock Level + 1, stage 1!
                $nextLevelStage1 = TypingStage::where('language', $stage->language)
                    ->where('level', $stage->level + 1)
                    ->where('stage_number', 1)
                    ->first();
                if ($nextLevelStage1) {
                    TypingUserStage::updateOrCreate(
                        ['user_id' => $user->id, 'stage_id' => $nextLevelStage1->id],
                        ['is_unlocked' => true]
                    );
                    $nextStageUnlocked = true;
                    $nextStage = $nextLevelStage1;
                }
            }
        }

        // 6. Award XP on first pass
        $xpEarned = 0;
        if ($stageOfficiallyPassed && ! $wasPassedBefore) {
            $xpEarned = $stage->xp_reward;
            $this->pointsService->addXp($user, $xpEarned);
        }

        // 7. Store Raw Session if session_token provided
        if (! empty($data['session_token'])) {
            TypingSession::create([
                'user_id' => $user->id,
                'stage_id' => $stage->id,
                'session_token' => $data['session_token'],
                'game_mode' => 'key_training',
                'language' => $stage->language,
                'difficulty' => 'beginner',
                'wpm' => $wpm,
                'raw_wpm' => $wpm,
                'accuracy' => $accuracy,
                'score' => (int) ($data['score'] ?? ($wpm * 10)),
                'max_combo' => (int) ($data['max_combo'] ?? 0),
                'correct_chars' => $correctChars,
                'total_chars' => (int) ($data['total_chars'] ?? $correctChars),
                'correct_words' => (int) ($data['correct_words'] ?? round($correctChars / 5)),
                'total_words' => (int) ($data['total_words'] ?? round($correctChars / 5)),
                'mistakes' => (int) ($data['mistakes'] ?? 0),
                'time_elapsed' => max(1, $timeElapsed),
                'xp_earned' => $xpEarned,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        return [
            'stage_id' => $stage->id,
            'stage_number' => $stage->stage_number,
            'passed' => $isPassedNow,
            'attempt_passed' => $thisAttemptPassed,
            'stars' => $stars,
            'best_stars' => $bestStars,
            'accuracy' => $accuracy,
            'wpm' => $wpm,
            'cpm' => $cpm,
            'reasons' => $reasons,
            'next_stage_unlocked' => $nextStageUnlocked,
            'next_stage_id' => $nextStage?->id,
            'xp_earned' => $xpEarned,
            'recent_attempts' => $recentAttempts,
            'exam_status' => [
                'needed' => $stage->pass_count_needed,
                'window' => $stage->pass_window_size,
                'passes_count' => $currentPassesCount,
            ],
        ];
    }

    /**
     * Get user's weak keys (< 85% accuracy) from recent attempts or defaults for targeted practice
     */
    public function getWeakKeys(User $user, string $language): array
    {
        $userStages = TypingUserStage::where('user_id', $user->id)
            ->whereHas('stage', fn ($q) => $q->where('language', $language))
            ->get();

        $freq = [];
        foreach ($userStages as $us) {
            foreach ($us->recent_attempts ?? [] as $att) {
                foreach ($att['weak_keys'] ?? [] as $k) {
                    if (is_string($k) && $k !== '' && $k !== ' ') {
                        $freq[$k] = ($freq[$k] ?? 0) + 1;
                    }
                }
            }
        }

        if (! empty($freq)) {
            arsort($freq);

            return array_slice(array_keys($freq), 0, 5);
        }

        return $language === 'th'
            ? ['ฟ', 'ว', 'เ', '้', 'ง']
            : ['a', ';', 'g', 'h', 's'];
    }
}
