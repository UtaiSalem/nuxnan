<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'bio',
        'birthdate',
        'gender',
        'location',
        'website',
        'interests',
        'profile_picture',
        'cover_image_url',
        'cover_image',
        'social_media_links',
        'privacy_settings',
        'followers',
        'following',
        'friends',
        'join_date',
        'last_login',
        'metadata',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
