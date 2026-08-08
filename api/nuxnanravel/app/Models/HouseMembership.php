<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseMembership extends Model
{
    protected $table = 'house_memberships';

    protected $guarded = [];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'house_group_id');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(SportsEdition::class, 'edition_id');
    }
}
