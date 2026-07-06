<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Budget Model - งบประมาณ
 */
class Budget extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'academy_id',
        'academic_year_id',
        'category_id',
        'name',
        'description',
        'allocated_amount',
        'spent_amount',
        'remaining_amount',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_CLOSED = 'closed';

    const STATUS_EXCEEDED = 'exceeded';

    const STATUSES = [
        self::STATUS_DRAFT => 'ร่าง',
        self::STATUS_ACTIVE => 'ใช้งาน',
        self::STATUS_CLOSED => 'ปิด',
        self::STATUS_EXCEEDED => 'เกินงบ',
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
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getUsagePercentageAttribute(): float
    {
        if ($this->allocated_amount <= 0) {
            return 0;
        }

        return round(($this->spent_amount / $this->allocated_amount) * 100, 2);
    }

    // Methods
    public function updateSpent(): void
    {
        $spent = $this->expenses()
            ->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_PAID])
            ->sum('amount');

        $this->spent_amount = $spent;
        $this->remaining_amount = $this->allocated_amount - $spent;

        // Update status if exceeded
        if ($this->remaining_amount < 0) {
            $this->status = self::STATUS_EXCEEDED;
        }

        $this->save();
    }

    public function canSpend(float $amount): bool
    {
        return $this->remaining_amount >= $amount;
    }

    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function close(): void
    {
        $this->update(['status' => self::STATUS_CLOSED]);
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
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeWithRemaining($query)
    {
        return $query->where('remaining_amount', '>', 0);
    }
}
