<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PaymentMethod Model - วิธีการชำระเงิน
 */
class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'code',
        'description',
        'config',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    // Common payment method codes
    const CODE_CASH = 'cash';
    const CODE_TRANSFER = 'transfer';
    const CODE_CREDIT_CARD = 'credit_card';
    const CODE_PROMPTPAY = 'promptpay';
    const CODE_WALLET = 'wallet';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
