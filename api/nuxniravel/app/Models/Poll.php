<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Poll extends Model
{
    use HasFactory;
    // use HasUlids;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'user_id',
        'is_public',
        'max_votes',
        'is_multiple_choice',
        'total_votes',
        'image_url',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    /**
     * Get all of the activityies for the Post
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activityable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
