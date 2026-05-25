<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAccess extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    // Access types
    const TYPE_FREE   = 'free';
    const TYPE_POINTS = 'points';
    const TYPE_MONEY  = 'money';
    const TYPE_MANUAL = 'manual';
    const TYPE_ADMIN  = 'admin';

    // Status
    const STATUS_ACTIVE   = 'active';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_REVOKED  = 'revoked';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
