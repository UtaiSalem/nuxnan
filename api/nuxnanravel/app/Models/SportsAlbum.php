<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportsAlbum extends Model
{
    protected $table = 'sports_albums';

    protected $guarded = [];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(SportsEdition::class, 'edition_id');
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'academy_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(SportsDiscipline::class, 'discipline_id');
    }

    public function houseGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'house_group_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(SportsPhoto::class, 'album_id')->orderBy('display_order')->orderBy('id');
    }

    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(SportsPhoto::class, 'cover_photo_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
