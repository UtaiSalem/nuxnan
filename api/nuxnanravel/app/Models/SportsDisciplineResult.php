<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsDisciplineResult extends Model
{
    protected $guarded = [];

    protected $casts = [
        'confirmed_at' => 'datetime',
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

    public function scoreEntry(): BelongsTo
    {
        return $this->belongsTo(SportsScoreEntry::class, 'score_entry_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
