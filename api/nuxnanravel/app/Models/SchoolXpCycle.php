<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolXpCycle extends Model
{
    protected $fillable = [
        'academy_id',
        'cycle_type',
        'cycle_key',
        'cycle_start',
        'cycle_end',
        'total_xp',
        'level',
    ];

    protected $casts = [
        'cycle_start' => 'date',
        'cycle_end' => 'date',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
}
