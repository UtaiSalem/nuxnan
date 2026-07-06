<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SalaryStructure Model - โครงสร้างเงินเดือน
 */
class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'description',
        'pay_frequency',
        'allowances',
        'deductions',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'allowances' => 'array',
        'deductions' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Pay frequency constants
    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_BI_WEEKLY = 'bi_weekly';

    const FREQUENCY_WEEKLY = 'weekly';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    // Methods
    public function getTotalAllowances(): float
    {
        if (! $this->allowances) {
            return 0;
        }

        return collect($this->allowances)->sum('amount');
    }

    public function getTotalDeductions(): float
    {
        if (! $this->deductions) {
            return 0;
        }

        return collect($this->deductions)->sum('amount');
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
