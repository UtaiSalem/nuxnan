<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = [
        'name', 
        'description', 
        'icon', 
        'xp_reward', 
        'category',
        // Legacy fields for backward compatibility if needed, but we should migrate to new ones
        'icon_color', 
        'background_color', 
        'rarity', 
        'points', // Map to xp_reward if needed
        'max_progress'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('is_earned', 'progress', 'earned_at')
            ->withTimestamps();
    }

}
