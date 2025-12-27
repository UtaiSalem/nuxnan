<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostBackground extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'background_color',
        'background_gradient',
        'background_image',
        'text_color',
        'text_alignment',
        'font_family',
        'category',
        'is_active',
        'is_premium',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active backgrounds.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for free backgrounds (non-premium).
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Scope for premium backgrounds.
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get CSS style for the background.
     */
    public function getCssStyleAttribute(): array
    {
        $style = [
            'color' => $this->text_color,
            'textAlign' => $this->text_alignment,
        ];

        if ($this->font_family) {
            $style['fontFamily'] = $this->font_family;
        }

        switch ($this->type) {
            case 'color':
                $style['backgroundColor'] = $this->background_color;
                break;
            case 'gradient':
                $style['backgroundImage'] = $this->background_gradient;
                break;
            case 'image':
                $style['backgroundImage'] = "url('{$this->background_image}')";
                $style['backgroundSize'] = 'cover';
                $style['backgroundPosition'] = 'center';
                break;
        }

        return $style;
    }
}
