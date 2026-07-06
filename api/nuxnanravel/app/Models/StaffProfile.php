<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StaffProfile Model - ข้อมูลบุคลากร
 */
class StaffProfile extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'user_id',
        'department_id',
        'position_id',
        'employee_id',
        'citizen_id',
        'title_prefix',
        'first_name',
        'last_name',
        'nickname',
        'date_of_birth',
        'gender',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'hire_date',
        'contract_start_date',
        'contract_end_date',
        'employment_type',
        'status',
        'resignation_date',
        'resignation_reason',
        'education_history',
        'work_history',
        'certifications',
        'skills',
        'profile_image',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'resignation_date' => 'date',
        'education_history' => 'array',
        'work_history' => 'array',
        'certifications' => 'array',
        'skills' => 'array',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';

    const STATUS_ON_LEAVE = 'on_leave';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_RESIGNED = 'resigned';

    const STATUS_TERMINATED = 'terminated';

    const STATUSES = [
        self::STATUS_ACTIVE => 'ปฏิบัติงาน',
        self::STATUS_ON_LEAVE => 'ลา',
        self::STATUS_SUSPENDED => 'พักงาน',
        self::STATUS_RESIGNED => 'ลาออก',
        self::STATUS_TERMINATED => 'เลิกจ้าง',
    ];

    // Employment type constants
    const TYPE_FULL_TIME = 'full_time';

    const TYPE_PART_TIME = 'part_time';

    const TYPE_CONTRACT = 'contract';

    const TYPE_TEMPORARY = 'temporary';

    const EMPLOYMENT_TYPES = [
        self::TYPE_FULL_TIME => 'พนักงานประจำ',
        self::TYPE_PART_TIME => 'พนักงานไม่เต็มเวลา',
        self::TYPE_CONTRACT => 'พนักงานสัญญาจ้าง',
        self::TYPE_TEMPORARY => 'พนักงานชั่วคราว',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(StaffTraining::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $prefix = $this->title_prefix ? $this->title_prefix.' ' : '';

        return $prefix.$this->first_name.' '.$this->last_name;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getEmploymentTypeNameAttribute(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? '';
    }

    public function getYearsOfServiceAttribute(): float
    {
        $endDate = $this->resignation_date ?? now();

        return round($this->hire_date->diffInDays($endDate) / 365, 1);
    }

    // Methods
    public static function generateEmployeeId(int $academyId): string
    {
        $prefix = 'EMP';
        $year = date('y');

        $lastEmployee = static::where('academy_id', $academyId)
            ->where('employee_id', 'like', "{$prefix}{$year}%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getLeaveBalance(int $leaveTypeId, ?int $year = null): array
    {
        $year = $year ?? date('Y');

        $leaveType = LeaveType::find($leaveTypeId);
        if (! $leaveType) {
            return ['total' => 0, 'used' => 0, 'remaining' => 0];
        }

        $usedDays = $this->leaveRequests()
            ->where('leave_type_id', $leaveTypeId)
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
            ->sum('total_days');

        $totalDays = $leaveType->max_days_per_year ?? 0;

        return [
            'total' => $totalDays,
            'used' => $usedDays,
            'remaining' => max(0, $totalDays - $usedDays),
        ];
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByPosition($query, $positionId)
    {
        return $query->where('position_id', $positionId);
    }

    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")
                ->orWhere('nickname', 'like', "%{$search}%");
        });
    }
}
