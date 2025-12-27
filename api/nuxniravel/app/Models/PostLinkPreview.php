<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostLinkPreview extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'url',
        'title',
        'description',
        'image_url',
        'site_name',
        'site_icon',
        'type',
        'video_url',
        'video_type',
        'video_width',
        'video_height',
        'author_name',
        'author_url',
        'provider_name',
        'provider_url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'video_width' => 'integer',
        'video_height' => 'integer',
    ];

    /**
     * Get the post that this link preview belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Check if this is a video type.
     */
    public function getIsVideoAttribute(): bool
    {
        return in_array($this->type, ['video', 'video.other', 'video.movie', 'video.episode', 'video.tv_show']);
    }

    /**
     * Check if this is an article type.
     */
    public function getIsArticleAttribute(): bool
    {
        return in_array($this->type, ['article', 'blog', 'news']);
    }

    /**
     * Get domain from URL.
     */
    public function getDomainAttribute(): ?string
    {
        $parsed = parse_url($this->url);
        return $parsed['host'] ?? null;
    }
}
