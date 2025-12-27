<?php

namespace App\Models;

use App\Models\User;
use App\Models\PostImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PostImageComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    // protected $appends = ['full_url'];

    /**
     * Get the user that owns the PostComment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postImage(): BelongsTo
    {
        return $this->belongsTo(PostImage::class);
    }


    /**
     * The likedPostImage that belong to the Comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function liked(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_image_comment_likes', 'post_image_comment_id', 'user_id');
    }

    /**
     * The dislikedComment that belong to the Comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function disliked(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_image_comment_dislikes', 'post_image_comment_id', 'user_id');
    }


}
