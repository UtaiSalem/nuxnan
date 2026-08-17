<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportsDiscipline extends Model
{
    protected $guarded = [];

    protected $casts = [
        'scoring_table' => 'array',
        'max_score' => 'decimal:2',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(SportsEdition::class, 'edition_id');
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function scoreEntries(): HasMany
    {
        return $this->hasMany(SportsScoreEntry::class, 'discipline_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(SportsMatch::class, 'discipline_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(SportsDisciplineResult::class, 'discipline_id');
    }
}
