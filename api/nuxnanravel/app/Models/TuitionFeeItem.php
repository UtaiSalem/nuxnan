<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TuitionFeeItem Model - รายการในใบแจ้งหนี้
 */
class TuitionFeeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tuition_fee_id',
        'fee_item_id',
        'name',
        'fee_type',
        'amount',
        'discount',
        'net_amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    // Relationships
    public function tuitionFee(): BelongsTo
    {
        return $this->belongsTo(TuitionFee::class);
    }

    public function feeItem(): BelongsTo
    {
        return $this->belongsTo(FeeItem::class);
    }

    // Calculate net amount
    public function calculateNetAmount(): float
    {
        return $this->amount - $this->discount;
    }

    // Boot method to auto-calculate
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->net_amount = $model->calculateNetAmount();
        });
    }
}
