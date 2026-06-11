<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicReadProgress extends Model
{
    protected $table = 'topic_read_progress';

    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'topic_id',
        'status',
        'started_at',
        'completed_at',
        'required_seconds_snapshot',
        'elapsed_seconds',
        'violation_count',
        'last_violation_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_violation_at' => 'datetime',
    ];

    const STATUS_NOT_STARTED = 'not_started';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function markAsStarted(int $requiredSeconds): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'required_seconds_snapshot' => $requiredSeconds,
        ]);
    }

    public function markAsCompleted(): void
    {
        $completedAt = now();
        $startedAt = $this->started_at ?? $completedAt;
        $elapsedSeconds = max(0, $completedAt->timestamp - $startedAt->timestamp);

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => $completedAt,
            'elapsed_seconds' => (int) $elapsedSeconds,
        ]);
    }

    public function recordViolation(): void
    {
        $this->increment('violation_count', 1, ['last_violation_at' => now()]);
    }

    public function canComplete(): bool
    {
        if (! $this->started_at) {
            return false;
        }

        return now()->timestamp >= ($this->started_at->timestamp + $this->required_seconds_snapshot);
    }

    public function remainingSeconds(): int
    {
        if (! $this->started_at) {
            return $this->required_seconds_snapshot;
        }
        $elapsed = now()->timestamp - $this->started_at->timestamp;

        return max(0, $this->required_seconds_snapshot - $elapsed);
    }
}
