<?php

namespace App\Models;

use App\Models\Post;
use App\Models\User;
use App\Models\PostImageComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        return asset('storage/images/posts/' . $this->filename);
    }

    /**
     * Get all of the comments for the PostImage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function image_comments(): HasMany
    {
        return $this->hasMany(PostImageComment::class);
    }

    /**
     * The likedPostImage that belong to the Comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likedPostImage(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_image_likes', 'post_image_id', 'user_id');
    }

    /**
     * The dislikedComment that belong to the Comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function dislikedPostImage(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_image_dislikes', 'post_image_id', 'user_id');
    }
}
