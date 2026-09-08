<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Expense Model - รายจ่าย
 */
class Expense extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'academy_id',
        'expense_category_id',
        'academic_year_id',
        'title',
        'description',
        'amount',
        'expense_date',
        'vendor',
        'reference_number',
        'receipt_image',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_PAID = 'paid';

    const STATUSES = [
        self::STATUS_PENDING => 'รออนุมัติ',
        self::STATUS_APPROVED => 'อนุมัติแล้ว',
        self::STATUS_REJECTED => 'ปฏิเสธ',
        self::STATUS_PAID => 'จ่ายแล้ว',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
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
    public function approve(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $userId, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_notes' => $reason,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('expense_category_id', $categoryId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_PAID]);
    }
}
