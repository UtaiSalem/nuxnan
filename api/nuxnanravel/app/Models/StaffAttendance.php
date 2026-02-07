<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffAttendance Model - การเข้างานบุคลากร
 */
class StaffAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_profile_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'break_start_time',
        'break_end_time',
        'work_hours',
        'overtime_hours',
        'status',
        'check_in_location',
        'check_out_location',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'break_start_time' => 'datetime:H:i',
        'break_end_time' => 'datetime:H:i',
        'work_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';
    const STATUS_EARLY_LEAVE = 'early_leave';
    const STATUS_HALF_DAY = 'half_day';
    const STATUS_ON_LEAVE = 'on_leave';
    const STATUS_HOLIDAY = 'holiday';
    const STATUS_WORK_FROM_HOME = 'work_from_home';

    const STATUSES = [
        self::STATUS_PRESENT => 'ปกติ',
        self::STATUS_ABSENT => 'ขาดงาน',
        self::STATUS_LATE => 'สาย',
        self::STATUS_EARLY_LEAVE => 'ออกก่อนเวลา',
        self::STATUS_HALF_DAY => 'ทำงานครึ่งวัน',
        self::STATUS_ON_LEAVE => 'ลา',
        self::STATUS_HOLIDAY => 'วันหยุด',
        self::STATUS_WORK_FROM_HOME => 'ทำงานที่บ้าน',
    ];

    // Relationships
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    // Methods
    public function checkIn(?string $location = null): void
    {
        $this->check_in_time = now()->format('H:i:s');
        $this->check_in_location = $location;
        $this->save();
    }

    public function checkOut(?string $location = null): void
    {
        $this->check_out_time = now()->format('H:i:s');
        $this->check_out_location = $location;
        $this->calculateWorkHours();
        $this->save();
    }

    public function calculateWorkHours(): void
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return;
        }

        $checkIn = \Carbon\Carbon::parse($this->check_in_time);
        $checkOut = \Carbon\Carbon::parse($this->check_out_time);
        
        $totalMinutes = $checkOut->diffInMinutes($checkIn);
        
        // Subtract break time if exists
        if ($this->break_start_time && $this->break_end_time) {
            $breakStart = \Carbon\Carbon::parse($this->break_start_time);
            $breakEnd = \Carbon\Carbon::parse($this->break_end_time);
            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
            $totalMinutes -= $breakMinutes;
        }

        $this->work_hours = round($totalMinutes / 60, 2);
    }

    // Scopes
    public function scopeByStaff($query, $staffProfileId)
    {
        return $query->where('staff_profile_id', $staffProfileId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PRESENT,
            self::STATUS_LATE,
            self::STATUS_EARLY_LEAVE,
            self::STATUS_WORK_FROM_HOME,
        ]);
    }
}
