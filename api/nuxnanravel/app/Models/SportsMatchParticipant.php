<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsMatchParticipant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(SportsMatch::class, 'match_id');
    }

    public function houseGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'house_group_id');
    }
}
