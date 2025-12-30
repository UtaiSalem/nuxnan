<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Student Guardian Model
 */
class StudentGuardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'student_code',
        'guardian_type',
        'citizen_id',
        'title_prefix',
        'first_name',
        'last_name',
        'occupation',
        'workplace',
        'monthly_income',
        'relationship',
        'status',
        'nationality',
        'is_primary_contact',
        'is_emergency_contact'
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'is_primary_contact' => 'boolean',
        'is_emergency_contact' => 'boolean'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(GuardianContact::class, 'guardian_id');
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(GuardianContact::class, 'guardian_id')
                    ->where('is_primary', true);
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->title_prefix,
            $this->first_name,
            $this->last_name
        ]);
        
        return implode(' ', $parts);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('guardian_type', $type);
    }

    public function scopePrimaryContact($query)
    {
        return $query->where('is_primary_contact', true);
    }

    public function scopeEmergencyContact($query)
    {
        return $query->where('is_emergency_contact', true);
    }
}
