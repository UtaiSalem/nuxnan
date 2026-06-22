<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomPointCycle extends Model
{
    protected $fillable = [
        'classroom_group_id',
        'academy_id',
        'cycle_type',
        'cycle_key',
        'cycle_start',
        'cycle_end',
        'total_points',
    ];

    protected $casts = [
        'cycle_start' => 'date',
        'cycle_end' => 'date',
    ];

    public function classroomGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'classroom_group_id');
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
}
