<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PayrollItem Model - รายการเงินเดือน
 */
class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'type',
        'code',
        'name',
        'description',
        'amount',
        'is_taxable',
        'display_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_taxable' => 'boolean',
    ];

    // Type constants
    const TYPE_EARNING = 'earning';
    const TYPE_DEDUCTION = 'deduction';

    // Relationships
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    // Scopes
    public function scopeEarnings($query)
    {
        return $query->where('type', self::TYPE_EARNING);
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
