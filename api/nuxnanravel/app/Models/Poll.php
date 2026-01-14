<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'points_pool',
        'points_per_vote',
        'points_distributed',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'is_multiple_choice' => 'boolean',
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

    public function options(): MorphMany
    {
        return $this->morphMany(QuestionOption::class, 'optionable');
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    public function likes()
    {
        return $this->hasMany(LikedPoll::class);
    }

    public function dislikes()
    {
        return $this->hasMany(DislikedPoll::class);
    }

    public function comments()
    {
        return $this->hasMany(PollComment::class);
    }
}
