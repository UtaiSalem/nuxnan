<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsHouseStanding extends Model
{
    protected $table = 'sports_house_standings';

    protected $guarded = [];

    protected $casts = [
        'total_points' => 'decimal:2',
        'computed_at' => 'datetime',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(SportsEdition::class, 'edition_id');
    }

    public function houseGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'house_group_id');
    }
}
