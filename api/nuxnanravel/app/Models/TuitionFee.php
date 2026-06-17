<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

/**
 * TuitionFee Model - ค่าเล่าเรียนของนักเรียน
 */
class TuitionFee extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'academy_id',
        'user_id',
        'student_id',
        'academic_year_id',
        'semester_id',
        'fee_structure_id',
        'classroom_id',
        'invoice_number',
        'total_amount',
        'discount_amount',
        'scholarship_amount',
        'net_amount',
        'paid_amount',
        'remaining_amount',
        'balance_amount',
        'issue_date',
        'due_date',
        'paid_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'scholarship_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    const STATUSES = [
        self::STATUS_PENDING => 'รอชำระ',
        self::STATUS_PARTIAL => 'ชำระบางส่วน',
        self::STATUS_PAID => 'ชำระครบแล้ว',
        self::STATUS_OVERDUE => 'เกินกำหนด',
        self::STATUS_CANCELLED => 'ยกเลิก',
        self::STATUS_REFUNDED => 'คืนเงินแล้ว',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TuitionFeeItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentPlanItems(): HasMany
    {
        return $this->hasMany(StudentPaymentPlan::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(FeeDiscount::class);
    }

    // Accessors
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_PAID 
            && $this->status !== self::STATUS_CANCELLED 
            && $this->due_date < now();
    }

    public function getPaymentPercentageAttribute(): float
    {
        if ($this->net_amount <= 0) {
            return 100;
        }
        return round(($this->paid_amount / $this->net_amount) * 100, 2);
    }

    // Methods
    public function calculateAmounts(): void
    {
        $this->total_amount = $this->items()->sum('amount');
        $this->net_amount = $this->total_amount - $this->discount_amount - $this->scholarship_amount;
        $this->balance_amount = $this->net_amount - $this->paid_amount;
    }

    public function recalculateAmounts(): void
    {
        $this->total_amount = $this->items()->sum('amount');
        $totalDiscount = $this->discounts()->sum('amount');
        $this->discount_amount = $totalDiscount;
        $this->net_amount = $this->total_amount - $totalDiscount;
        $this->remaining_amount = $this->net_amount - $this->paid_amount;
        $this->save();
    }

    public function updatePaymentStatus(): void
    {
        $this->paid_amount = $this->payments()
            ->where('status', Payment::STATUS_CONFIRMED)
            ->sum('amount');
        
        $this->balance_amount = $this->net_amount - $this->paid_amount;

        if ($this->balance_amount <= 0) {
            $this->status = self::STATUS_PAID;
            $this->paid_date = now();
        } elseif ($this->paid_amount > 0) {
            $this->status = self::STATUS_PARTIAL;
        } elseif ($this->due_date < now()) {
            $this->status = self::STATUS_OVERDUE;
        } else {
            $this->status = self::STATUS_PENDING;
        }

        $this->save();
    }

    public static function generateInvoiceNumber(int $academyId): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = static::where('academy_id', $academyId)
            ->where('invoice_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
            ->orWhere(function ($q) {
                $q->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                    ->where('due_date', '<', now());
            });
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }
}
