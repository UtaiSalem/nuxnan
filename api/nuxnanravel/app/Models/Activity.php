<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    use HasFactory;
    // use HasUlids;

    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_details',
        'activityable_id',
        'activityable_type',
        'privacy_settings',
    ];

    protected $casts = [
        'privacy_settings' => 'integer',
    ];

    /**
     * Get the user that owns the Activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent activityable model (Post, CoursePost, Share, etc.)
     */
    public function activityable(): MorphTo
    {
        return $this->morphTo();
    }
}
