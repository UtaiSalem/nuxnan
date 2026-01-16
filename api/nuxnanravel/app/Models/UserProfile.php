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
        // Personal Information
        'phone_number',
        'address',
        'city',
        'country',
        'postal_code',
        // Professional Information
        'job_title',
        'company',
        'industry',
        'skills',
        'experience_years',
        // Privacy Settings
        'show_email',
        'show_phone',
        'show_birthdate',
        'show_location',
        'allow_friend_requests',
        'allow_messages',
        'show_online_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birthdate' => 'date',
        'join_date' => 'datetime',
        'last_login' => 'datetime',
        'social_media_links' => 'array',
        'privacy_settings' => 'array',
        'interests' => 'array',
        'metadata' => 'array',
        'followers' => 'integer',
        'following' => 'integer',
        'friends' => 'integer',
        'skills' => 'array',
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
        'show_birthdate' => 'boolean',
        'show_location' => 'boolean',
        'allow_friend_requests' => 'boolean',
        'allow_messages' => 'boolean',
        'show_online_status' => 'boolean',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
