<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payment Model - การชำระเงิน
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'tuition_fee_id',
        'payment_method_id',
        'user_id',
        'receipt_number',
        'amount',
        'payment_date',
        'payment_method_name',
        'bank_name',
        'bank_account',
        'transfer_reference',
        'slip_image',
        'status',
        'notes',
        'confirmed_by',
        'confirmed_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REFUNDED = 'refunded';

    const STATUSES = [
        self::STATUS_PENDING => 'รอตรวจสอบ',
        self::STATUS_CONFIRMED => 'ยืนยันแล้ว',
        self::STATUS_REJECTED => 'ปฏิเสธ',
        self::STATUS_REFUNDED => 'คืนเงินแล้ว',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function tuitionFee(): BelongsTo
    {
        return $this->belongsTo(TuitionFee::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
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

    // Methods
    public function confirm(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);

        // Update tuition fee payment status
        $this->tuitionFee->updatePaymentStatus();
    }

    public function reject(int $userId, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
            'notes' => $reason,
        ]);
    }

    public static function generateReceiptNumber(int $academyId): string
    {
        $prefix = 'RCP';
        $year = date('Y');
        $month = date('m');
        
        $lastReceipt = static::where('academy_id', $academyId)
            ->where('receipt_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_number, -5);
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

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
}
