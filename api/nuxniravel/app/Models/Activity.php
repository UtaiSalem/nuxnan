<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Activity extends Model
{
    use HasFactory;
    // use HasUlids;

    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_details',
        'activityable_id',
        'activityable_type'
    ];


    /**
     * Get the user that owns the Activity
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent activityable model (Post, CoursePost, Share, etc.)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function activityable(): MorphTo
    {
        return $this->morphTo();
    }

}
