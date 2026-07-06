<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyPostImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['full_url'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(AcademyPost::class);
    }

    /**
     * Get all of the comments for the AcademyPostImage
     */
    public function image_comments(): HasMany
    {
        return $this->hasMany(AcademyPostImageComment::class, 'academy_post_image_id');
    }

    public function getFullUrlAttribute(): string
    {
        return asset('storage/images/academies/posts/'.$this->filename);
    }

    /**
     * The postImageLikes that belong to the AcademyPostImage
     */
    public function postImageLikes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_post_image_likes', 'post_image_id', 'user_id');
    }

    /**
     * The postImageDislikes that belong to the AcademyPostImage
     */
    public function postImageDislikes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_post_image_dislikes', 'post_image_id', 'user_id');
    }
}
