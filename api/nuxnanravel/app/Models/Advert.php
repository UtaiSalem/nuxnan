<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\User;
use App\Models\AdvertViewer;
use App\Models\Activity;

class Advert extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function activity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'activityable');
    }

    public function getMediaImageAttribute($value)
    {
        // Return full URL to prevent frontend from adding prefix
        return $value ? url('/storage/images/adverts/medias/' . $value) : null;
    }

    public function getSlipAttribute($value)
    {
        return $value ? '/storage/images/adverts/slips/'.$value : null;
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'advert_viewers', 'advert_id', 'user_id')->withTimestamps();
    }

    public function advertViewers(): HasMany
    {
        return $this->hasMany(AdvertViewer::class, 'advert_id');
    }
}
