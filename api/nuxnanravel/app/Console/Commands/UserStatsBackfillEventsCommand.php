<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserUsageEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserStatsBackfillEventsCommand extends Command
{
    protected $signature = 'user-stats:backfill-events
                            {--user= : Backfill specific user ID}
                            {--apply : Write events to database (default is dry-run)}';
                            // NOTE: dry-run by default, same convention as user-stats:recalculate

    protected $description = 'Backfill usage events from existing activity logs (lessons, quizzes)';

    public function handle()
    {
        $userId = $this->option('user');
        $apply  = $this->option('apply');
        $dryRun = !$apply;

        if ($dryRun) {
            $this->warn("DRY RUN MODE: No events will be created. Pass --apply to write.");
        }

        $this->backfillLessons($userId, $dryRun);
        $this->backfillQuizzes($userId, $dryRun);

        $this->info("Backfill complete.");
    }

    protected function backfillLessons($userId, $dryRun)
    {
        $this->info("Checking lesson progress for backfilling...");

        $query = DB::table('lesson_progress')
            ->where('status', 'completed')
            ->whereNotNull('completed_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $count = 0;
        $query->orderBy('id')->chunk(100, function ($records) use ($dryRun, &$count) {
            foreach ($records as $record) {
                $idempotencyKey = "lesson_complete:{$record->user_id}:{$record->lesson_id}:" . Carbon::parse($record->completed_at)->getTimestamp();
                
                if (!$dryRun) {
                    $exists = UserUsageEvent::where('idempotency_key', $idempotencyKey)->exists();
                    if (!$exists) {
                        UserUsageEvent::create([
                            'user_id' => $record->user_id,
                            'event_type' => 'lesson_complete',
                            'source_type' => 'lesson',
                            'source_id' => $record->lesson_id,
                            'occurred_at' => $record->completed_at,
                            'idempotency_key' => $idempotencyKey,
                        ]);
                        $count++;
                    }
                } else {
                    $count++;
                }
            }
        });

        $this->info("Lessons backfilled: $count");
    }

    protected function backfillQuizzes($userId, $dryRun)
    {
        $this->info("Checking quiz results for backfilling...");

        $query = DB::table('course_quiz_results')
            ->join('course_quizzes', 'course_quiz_results.quiz_id', '=', 'course_quizzes.id')
            ->select('course_quiz_results.*', 'course_quizzes.passing_score')
            ->whereNotNull('course_quiz_results.completed_at');

        if ($userId) {
            $query->where('course_quiz_results.user_id', $userId);
        }

        $submitCount = 0;
        $passCount   = 0;

        $query->orderBy('course_quiz_results.id')->chunk(100, function ($records) use ($dryRun, &$submitCount, &$passCount) {
            foreach ($records as $record) {
                // 1. quiz_submit — every completed quiz attempt, regardless of pass/fail
                $submitKey = "quiz_submit:{$record->user_id}:{$record->quiz_id}:{$record->id}";
                if (!$dryRun) {
                    if (!UserUsageEvent::where('idempotency_key', $submitKey)->exists()) {
                        UserUsageEvent::create([
                            'user_id'         => $record->user_id,
                            'event_type'      => 'quiz_submit',
                            'source_type'     => 'course_quiz',
                            'source_id'       => $record->quiz_id,
                            'occurred_at'     => $record->completed_at,
                            'idempotency_key' => $submitKey,
                            'context'         => ['score' => $record->score, 'result_id' => $record->id],
                        ]);
                        $submitCount++;
                    }
                } else {
                    $submitCount++;
                }

                // 2. quiz_pass — only if score >= passing_score
                $isPass = $record->score >= ($record->passing_score ?? 0);
                if ($isPass) {
                    $passKey = "quiz_pass:{$record->user_id}:{$record->quiz_id}:{$record->id}";
                    if (!$dryRun) {
                        if (!UserUsageEvent::where('idempotency_key', $passKey)->exists()) {
                            UserUsageEvent::create([
                                'user_id'         => $record->user_id,
                                'event_type'      => 'quiz_pass',
                                'source_type'     => 'course_quiz',
                                'source_id'       => $record->quiz_id,
                                'occurred_at'     => $record->completed_at,
                                'idempotency_key' => $passKey,
                                'context'         => ['score' => $record->score, 'result_id' => $record->id],
                            ]);
                            $passCount++;
                        }
                    } else {
                        $passCount++;
                    }
                }
            }
        });

        $this->info("Quiz submits backfilled: {$submitCount}");
        $this->info("Quiz passes backfilled:  {$passCount}");
    }
}
