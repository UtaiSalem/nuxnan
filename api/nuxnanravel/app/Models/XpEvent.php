<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpEvent extends Model
{
    protected $fillable = [
        'academy_id',
        'user_id',
        'classroom_group_id',
        'source',
        'xp',
        'classroom_pts',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classroomGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'classroom_group_id');
    }
}
