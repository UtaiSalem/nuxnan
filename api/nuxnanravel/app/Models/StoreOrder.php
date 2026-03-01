<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StoreOrder extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_READY = 'ready';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Payment status constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'academy_store_id',
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'discount_amount',
        'total',
        'payment_method',
        'points_spent',
        'payment_status',
        'notes',
        'cancel_reason',
        'processed_by',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'points_spent' => 'integer',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ============================================================
    // Boot
    // ============================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(8)) . '-' . time();
            }
        });
    }

    // ============================================================
    // Relationships
    // ============================================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(AcademyStore::class, 'academy_store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StoreOrderItem::class, 'store_order_id');
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StoreProductReview::class, 'store_order_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_REFUNDED]);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ============================================================
    // Helpers
    // ============================================================

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_PROCESSING, self::STATUS_READY]);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_CONFIRMED => 'ยืนยันแล้ว',
            self::STATUS_PROCESSING => 'กำลังดำเนินการ',
            self::STATUS_READY => 'พร้อมรับ',
            self::STATUS_COMPLETED => 'เสร็จสิ้น',
            self::STATUS_CANCELLED => 'ยกเลิก',
            self::STATUS_REFUNDED => 'คืนเงิน',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_PROCESSING => 'indigo',
            self::STATUS_READY => 'cyan',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_REFUNDED => 'gray',
            default => 'gray',
        };
    }

    public function calculateTotal(): void
    {
        $subtotal = $this->items->sum('total_price');
        $this->update([
            'subtotal' => $subtotal,
            'total' => $subtotal - $this->discount_amount,
        ]);
    }
}
