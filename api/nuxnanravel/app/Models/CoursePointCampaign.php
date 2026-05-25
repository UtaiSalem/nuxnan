<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePointCampaign extends Model
{
    protected $guarded = [];
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    const STATUS_ACTIVE   = 'active';
    const STATUS_PAUSED   = 'paused';
    const STATUS_ENDED    = 'ended';
    const STATUS_DEPLETED = 'depleted';

    const CAMPAIGN_TYPE_MANUAL  = 'manual_claim';
    const CAMPAIGN_TYPE_LESSON  = 'lesson_completion';

    public function isClaimable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) return false;
        if ($this->starts_at && now()->lt($this->starts_at)) return false;
        if ($this->ends_at && now()->gt($this->ends_at)) return false;
        if ($this->max_claims && $this->total_claimed >= $this->max_claims) return false;
        return true;
    }

    public function claims(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CoursePointCampaignClaim::class, 'campaign_id');
    }

    public function lesson(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
