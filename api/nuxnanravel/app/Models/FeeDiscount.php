<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeeDiscount Model - ส่วนลดค่าธรรมเนียม
 */
class FeeDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tuition_fee_id',
        'scholarship_id',
        'discount_type',
        'description',
        'amount',
        'applied_by',
        'applied_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    // Discount types
    const TYPE_SCHOLARSHIP = 'scholarship';
    const TYPE_SIBLING = 'sibling';
    const TYPE_EARLY_PAYMENT = 'early_payment';
    const TYPE_PROMOTION = 'promotion';
    const TYPE_OTHER = 'other';

    const TYPES = [
        self::TYPE_SCHOLARSHIP => 'ทุนการศึกษา',
        self::TYPE_SIBLING => 'ส่วนลดพี่น้อง',
        self::TYPE_EARLY_PAYMENT => 'ส่วนลดชำระก่อนกำหนด',
        self::TYPE_PROMOTION => 'โปรโมชั่น',
        self::TYPE_OTHER => 'อื่นๆ',
    ];

    // Relationships
    public function tuitionFee(): BelongsTo
    {
        return $this->belongsTo(TuitionFee::class);
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->discount_type] ?? '';
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::created(function ($discount) {
            $discount->tuitionFee->recalculateAmounts();
        });

        static::deleted(function ($discount) {
            $discount->tuitionFee->recalculateAmounts();
        });
    }
}
