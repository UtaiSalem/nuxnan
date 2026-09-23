<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassScheduleException extends Model
{
    const TYPE_CANCELLED = 'cancelled';

    const TYPE_SUBSTITUTE = 'substitute';

    const TYPE_ROOM_CHANGE = 'room_change';

    const TYPES = [
        self::TYPE_CANCELLED,
        self::TYPE_SUBSTITUTE,
        self::TYPE_ROOM_CHANGE,
    ];

    const TYPE_LABELS = [
        self::TYPE_CANCELLED => 'งดคาบ',
        self::TYPE_SUBSTITUTE => 'สอนแทน',
        self::TYPE_ROOM_CHANGE => 'ย้ายห้อง',
    ];

    protected $fillable = [
        'academy_id',
        'class_schedule_id',
        'date',
        'type',
        'substitute_teacher_id',
        'room',
        'reason',
        'created_by',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dateString(): string
    {
        return substr((string) $this->date, 0, 10);
    }

    public function toOverlayArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->dateString(),
            'type' => $this->type,
            'type_label' => self::TYPE_LABELS[$this->type] ?? $this->type,
            'substitute_teacher' => $this->substituteTeacher ? [
                'id' => $this->substituteTeacher->id,
                'name' => $this->substituteTeacher->name,
            ] : null,
            'room' => $this->room,
            'reason' => $this->reason,
        ];
    }
}
