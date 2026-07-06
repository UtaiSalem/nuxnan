<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payroll Model - เงินเดือน
 */
class Payroll extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'academy_id',
        'staff_profile_id',
        'salary_structure_id',
        'payroll_period',
        'period_start',
        'period_end',
        'payment_date',
        'base_salary',
        'total_allowances',
        'total_deductions',
        'overtime_pay',
        'bonus',
        'tax',
        'social_security',
        'net_salary',
        'status',
        'payment_method',
        'bank_name',
        'bank_account',
        'payslip_number',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'base_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonus' => 'decimal:2',
        'tax' => 'decimal:2',
        'social_security' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_PAID = 'paid';

    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_DRAFT => 'ร่าง',
        self::STATUS_PENDING => 'รออนุมัติ',
        self::STATUS_APPROVED => 'อนุมัติแล้ว',
        self::STATUS_PAID => 'จ่ายแล้ว',
        self::STATUS_CANCELLED => 'ยกเลิก',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function getGrossSalaryAttribute(): float
    {
        return $this->base_salary + $this->total_allowances + $this->overtime_pay + $this->bonus;
    }

    // Methods
    public function calculateNetSalary(): void
    {
        $gross = $this->base_salary + $this->total_allowances + $this->overtime_pay + $this->bonus;
        $deductions = $this->total_deductions + $this->tax + $this->social_security;
        $this->net_salary = $gross - $deductions;
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'payment_date' => now(),
        ]);
    }

    public static function generatePayslipNumber(int $academyId, string $period): string
    {
        $prefix = 'PAY';
        $periodCode = str_replace('-', '', $period);

        $lastPayslip = static::where('academy_id', $academyId)
            ->where('payslip_number', 'like', "{$prefix}{$periodCode}%")
            ->orderBy('payslip_number', 'desc')
            ->first();

        if ($lastPayslip) {
            $lastNumber = (int) substr($lastPayslip->payslip_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$periodCode.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByStaff($query, $staffProfileId)
    {
        return $query->where('staff_profile_id', $staffProfileId);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('payroll_period', $period);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }
}
