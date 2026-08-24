<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class StudentGuardianLink extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'guardian_id', 'guardian_type', 'relationship', 'is_primary_contact', 'is_emergency_contact', 'appointed_by_user_id', 'appointed_by_role', 'appointed_at', 'verified_by_user_id', 'verified_at', 'legacy_row_ids'];

    protected $casts = ['is_primary_contact' => 'boolean', 'is_emergency_contact' => 'boolean', 'appointed_at' => 'datetime', 'verified_at' => 'datetime', 'legacy_row_ids' => 'array'];

    public function getFullNameAttribute(): ?string
    {
        return $this->guardian?->full_name;
    }

    public function getPrimaryPhoneAttribute(): ?string
    {
        return $this->guardian?->contacts->firstWhere('is_primary', true)?->contact_value ?? $this->guardian?->contacts->first()?->contact_value;
    }

    public function getTitlePrefixAttribute($value)
    {
        return $this->guardian?->title_prefix;
    }

    public function getFirstNameAttribute($value)
    {
        return $this->guardian?->first_name;
    }

    public function getLastNameAttribute($value)
    {
        return $this->guardian?->last_name;
    }

    public function getOccupationAttribute($value)
    {
        return $this->guardian?->occupation;
    }

    public function getWorkplaceAttribute($value)
    {
        return $this->guardian?->workplace;
    }

    public function getMonthlyIncomeAttribute($value)
    {
        return $this->guardian?->monthly_income;
    }

    public function getStatusAttribute($value)
    {
        return $this->guardian?->status;
    }

    public function getNationalityAttribute($value)
    {
        return $this->guardian?->nationality;
    }

    public function getCitizenIdAttribute($value)
    {
        return $this->guardian?->citizen_id;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function contacts(): HasManyThrough
    {
        return $this->hasManyThrough(GuardianContact::class, Guardian::class, 'id', 'guardian_person_id', 'guardian_id', 'id')->whereNull('guardian_contacts.superseded_by_contact_id');
    }

    public function primaryContact(): HasOneThrough
    {
        return $this->hasOneThrough(GuardianContact::class, Guardian::class, 'id', 'guardian_person_id', 'guardian_id', 'id')->where('guardian_contacts.is_primary', true)->whereNull('guardian_contacts.superseded_by_contact_id');
    }

    public function appointedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appointed_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
