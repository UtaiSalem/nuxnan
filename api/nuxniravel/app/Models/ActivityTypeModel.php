<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityTypeModel extends Model
{
    use HasFactory;

    protected $table = 'activities_types';

    protected $fillable = [
        'name',
        'name_th',
        'icon',
        'icon_url',
        'preposition',
        'preposition_th',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active activity types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    /**
     * Get preposition based on locale.
     */
    public function getDisplayPrepositionAttribute(): string
    {
        if (app()->getLocale() === 'th' && $this->preposition_th) {
            return $this->preposition_th;
        }
        return $this->preposition;
    }
}
