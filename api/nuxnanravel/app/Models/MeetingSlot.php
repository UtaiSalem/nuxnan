<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MeetingSlot Model - ช่วงเวลาประชุมผู้ปกครอง-ครู
 */
class MeetingSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'teacher_id',
        'meeting_date',
        'start_time',
        'end_time',
        'slot_duration',
        'location',
        'meeting_type',
        'meeting_url',
        'is_available',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'is_available' => 'boolean',
    ];

    // Meeting type constants
    const TYPE_IN_PERSON = 'in_person';

    const TYPE_VIDEO_CALL = 'video_call';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(MeetingBooking::class, 'slot_id');
    }

    // Accessors
    public function getAvailableTimesAttribute(): array
    {
        $times = [];
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        $bookedTimes = $this->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('booked_time')
            ->map(fn ($t) => Carbon::parse($t)->format('H:i'))
            ->toArray();

        while ($start < $end) {
            $timeStr = $start->format('H:i');
            if (! in_array($timeStr, $bookedTimes)) {
                $times[] = $timeStr;
            }
            $start->addMinutes($this->slot_duration);
        }

        return $times;
    }

    public function getBookedCountAttribute(): int
    {
        return $this->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
    }

    public function getTotalSlotsAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return (int) ($start->diffInMinutes($end) / $this->slot_duration);
    }

    // Methods
    public function isTimeAvailable(string $time): bool
    {
        return in_array($time, $this->available_times);
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('meeting_date', '>=', now()->toDateString());
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('meeting_date', $date);
    }
}
