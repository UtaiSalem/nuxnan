<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feeling extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_th',
        'icon',
        'icon_url',
        'category',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active feelings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for feelings only (not activities).
     */
    public function scopeFeelings($query)
    {
        return $query->where('category', 'feeling');
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get display name based on locale.
     */
    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'th' && $this->name_th) {
            return $this->name_th;
        }
        return $this->name;
    }
}
