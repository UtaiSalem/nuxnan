<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['full_url'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function getFullUrlAttribute(): string
    {
        return asset('storage/images/posts/'.$this->filename);
    }

    /**
     * Get all of the comments for the PostImage
     */
    public function image_comments(): HasMany
    {
        return $this->hasMany(PostImageComment::class);
    }

    /**
     * The likedPostImage that belong to the Comment
     */
    public function likedPostImage(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_image_likes', 'post_image_id', 'user_id');
    }

    /**
     * The dislikedComment that belong to the Comment
     */
    public function dislikedPostImage(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_image_dislikes', 'post_image_id', 'user_id');
    }
}
