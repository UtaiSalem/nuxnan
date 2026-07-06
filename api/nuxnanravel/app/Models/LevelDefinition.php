<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelDefinition extends Model
{
    protected $fillable = [
        'level', 'name', 'name_th', 'xp_required', 'icon', 'color', 'badge_url', 'perks',
    ];

    protected $casts = [
        'perks' => 'array',
    ];
}
