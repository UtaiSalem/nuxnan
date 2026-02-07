<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SchedulePeriod Model - คาบเรียน (Template)
 */
class SchedulePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'period_number',
        'name',
        'start_time',
        'end_time',
        'period_type',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'period_number' => 'integer',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    // Period types
    const TYPE_CLASS = 'class';
    const TYPE_BREAK = 'break';
    const TYPE_LUNCH = 'lunch';
    const TYPE_ACTIVITY = 'activity';

    const TYPES = [
        self::TYPE_CLASS => 'คาบเรียน',
        self::TYPE_BREAK => 'พักเบรก',
        self::TYPE_LUNCH => 'พักกลางวัน',
        self::TYPE_ACTIVITY => 'กิจกรรม',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->period_type] ?? '';
    }

    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    public function getDurationMinutesAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeClasses($query)
    {
        return $query->where('period_type', self::TYPE_CLASS);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('start_time');
    }
}
