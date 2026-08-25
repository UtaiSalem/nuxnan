<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Guardian Contact Model
 */
class GuardianContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'guardian_person_id',
        'contact_type',
        'contact_value',
        'is_primary',
        'is_verified',
        'superseded_by_contact_id',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'superseded_by_contact_id' => 'integer',
    ];

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
