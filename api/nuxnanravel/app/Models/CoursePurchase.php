<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoursePurchase extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount_wallet' => 'decimal:2',
        'amount_points' => 'integer',
        'paid_at' => 'datetime',
        'cloned_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function sourceCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'source_course_id');
    }

    public function clonedCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'cloned_course_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }

    public function pointsTransaction(): BelongsTo
    {
        return $this->belongsTo(PointsTransaction::class, 'points_transaction_id');
    }

    public function sellerIncomeTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'seller_income_transaction_id');
    }
}
