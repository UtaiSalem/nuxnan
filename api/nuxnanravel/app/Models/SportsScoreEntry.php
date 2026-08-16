<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsScoreEntry extends Model
{
    protected $guarded = [];

    protected $casts = [
        'points' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(SportsEdition::class, 'edition_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(SportsDiscipline::class, 'discipline_id');
    }

    public function houseGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'house_group_id');
    }

    public function awardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }
}
