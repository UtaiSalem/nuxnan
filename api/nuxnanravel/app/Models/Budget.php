<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Budget Model - งบประมาณ
 *
 * คอลัมน์ตรงกับ migration `2026_02_04_100002_create_finance_tables` (= สคีมาจริง):
 * หมวด = `expense_category_id` · สถานะ = `is_active` (เปิด/ปิด) · ไม่มี `status`/`created_by`
 * "เกินงบ" ไม่ได้เก็บเป็นสถานะ แต่คำนวณจาก `remaining_amount < 0` ({@see isExceeded()})
 *
 * ตาราง `expenses` ไม่มี `budget_id` ⇒ รายจ่ายของงบคือรายจ่ายของโรงเรียนเดียวกัน
 * ที่ `expense_date` อยู่ในช่วง start_date..end_date และอยู่ในหมวดของงบ
 * (งบที่ไม่ระบุหมวด = ทุกหมวด) — ดู {@see expensesQuery()}
 */
class Budget extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'academy_id',
        'academic_year_id',
        'expense_category_id',
        'name',
        'description',
        'allocated_amount',
        'spent_amount',
        'remaining_amount',
        'period',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Period constants (enum ในตาราง)
    const PERIOD_YEARLY = 'yearly';

    const PERIOD_SEMESTER = 'semester';

    const PERIOD_MONTHLY = 'monthly';

    const PERIODS = [
        self::PERIOD_YEARLY => 'รายปี',
        self::PERIOD_SEMESTER => 'รายภาคเรียน',
        self::PERIOD_MONTHLY => 'รายเดือน',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * รายจ่ายที่นับเข้างบนี้ (ไม่ใช่ relation — `expenses` ไม่มี `budget_id`)
     *
     * ใช้ `< วันถัดจาก end_date` แทน BETWEEN: sqlite เก็บ date cast เป็น "Y-m-d 00:00:00"
     * ซึ่งมากกว่าสตริง "Y-m-d" ของวันสุดท้าย (วันสุดท้ายจะหลุด) — แบบนี้ถูกทั้งสองฐานและยังใช้ index ได้
     */
    public function expensesQuery(): Builder
    {
        return Expense::query()
            ->where('academy_id', $this->academy_id)
            ->where('expense_date', '>=', $this->start_date->toDateString())
            ->where('expense_date', '<', $this->end_date->copy()->addDay()->toDateString())
            ->when($this->expense_category_id, fn (Builder $q) => $q->where('expense_category_id', $this->expense_category_id));
    }

    // Accessors
    public function getPeriodNameAttribute(): string
    {
        return self::PERIODS[$this->period] ?? '';
    }

    public function getUsagePercentageAttribute(): float
    {
        if ($this->allocated_amount <= 0) {
            return 0;
        }

        return round(($this->spent_amount / $this->allocated_amount) * 100, 2);
    }

    // Methods
    public function isExceeded(): bool
    {
        return (float) $this->remaining_amount < 0;
    }

    /**
     * คำนวณยอดใช้ไปใหม่จากรายจ่ายที่อนุมัติ/จ่ายแล้วในช่วงและหมวดของงบ
     */
    public function updateSpent(): void
    {
        $spent = $this->expensesQuery()
            ->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_PAID])
            ->sum('amount');

        $this->spent_amount = $spent;
        $this->remaining_amount = $this->allocated_amount - $spent;

        $this->save();
    }

    public function canSpend(float $amount): bool
    {
        return $this->remaining_amount >= $amount;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function close(): void
    {
        $this->update(['is_active' => false]);
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExceeded($query)
    {
        return $query->where('remaining_amount', '<', 0);
    }

    public function scopeWithRemaining($query)
    {
        return $query->where('remaining_amount', '>', 0);
    }
}
