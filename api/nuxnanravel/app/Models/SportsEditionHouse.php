<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsEditionHouse extends Model
{
    protected $table = 'sports_edition_houses';

    protected $guarded = [];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(SportsEdition::class, 'edition_id');
    }

    public function houseGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'house_group_id');
    }
}
