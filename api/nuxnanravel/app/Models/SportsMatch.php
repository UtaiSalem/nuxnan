<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportsMatch extends Model
{
    protected $table = 'sports_matches';

    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(SportsEdition::class, 'edition_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(SportsDiscipline::class, 'discipline_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SportsMatchParticipant::class, 'match_id');
    }

    public function winnerHouseGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'winner_house_group_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(SportsMatch::class, 'next_match_id');
    }

    public function activitySession(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class, 'activity_session_id');
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->where('status', 'finished');
    }
}
