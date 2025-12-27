<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HomeVisitZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_name',
        'description',
        'zone_code',
        'color',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('zone_name');
    }

    /**
     * Relationship to home visits
     */
    public function homeVisits()
    {
        return $this->hasMany(StudentHomeVisit::class, 'zone_id');
    }

    /**
     * Get active home visits count
     */
    public function getActiveVisitsCountAttribute()
    {
        return $this->homeVisits()->count();
    }
}

