<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportsEdition extends Model
{
    protected $guarded = ['active_key'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolEvent(): BelongsTo
    {
        return $this->belongsTo(SchoolEvent::class);
    }

    public function houses(): HasMany
    {
        return $this->hasMany(SportsEditionHouse::class, 'edition_id');
    }

    public function houseGroups(): BelongsToMany
    {
        return $this->belongsToMany(AcademyGroup::class, 'sports_edition_houses', 'edition_id', 'house_group_id');
    }

    public function houseGroupIds(): array
    {
        return $this->houses()->pluck('house_group_id')->map(fn ($id) => (int) $id)->all();
    }
}
