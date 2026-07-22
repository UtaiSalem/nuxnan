<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoursePointCampaign extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_PAUSED = 'paused';

    const STATUS_ENDED = 'ended';

    const STATUS_DEPLETED = 'depleted';

    const CAMPAIGN_TYPE_MANUAL = 'manual_claim';

    const CAMPAIGN_TYPE_LESSON = 'lesson_completion';

    const CAMPAIGN_TYPE_QUIZ = 'quiz_completion';

    public function isClaimable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && now()->gt($this->ends_at)) {
            return false;
        }
        if ($this->max_claims && $this->total_claimed >= $this->max_claims) {
            return false;
        }

        return true;
    }

    public function claims(): HasMany
    {
        return $this->hasMany(CoursePointCampaignClaim::class, 'campaign_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(CourseQuiz::class);
    }
}
