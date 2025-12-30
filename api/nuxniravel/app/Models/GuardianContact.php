<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guardian Contact Model
 */
class GuardianContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'guardian_id',
        'contact_type',
        'contact_value',
        'is_primary',
        'is_verified'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean'
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(StudentGuardian::class, 'guardian_id');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('contact_type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
