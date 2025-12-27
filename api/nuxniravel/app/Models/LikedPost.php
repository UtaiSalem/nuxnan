<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LikedPost extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'post_id'
    ];

    // protected $primaryKey = null;
    
    /**
     * Get the user that owns the LikePost
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    /**
     * Get the post that owns the LikePost
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
