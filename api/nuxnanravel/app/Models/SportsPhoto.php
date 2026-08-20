<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SportsPhoto extends Model
{
    protected $table = 'sports_photos';

    protected $guarded = [];

    protected $appends = ['url', 'thumbnail_url'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : $this->url;
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(SportsAlbum::class, 'album_id');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(SportsEdition::class, 'edition_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
