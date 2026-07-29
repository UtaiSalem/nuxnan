<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = ['academy_id', 'user_id', 'citizen_id', 'title_prefix', 'first_name', 'last_name', 'occupation', 'workplace', 'monthly_income', 'nationality', 'status', 'legacy_row_ids'];

    protected $casts = ['monthly_income' => 'decimal:2', 'legacy_row_ids' => 'array'];

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->title_prefix, $this->first_name, $this->last_name]));
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(GuardianContact::class, 'guardian_person_id')->whereNull('superseded_by_contact_id');
    }

    public function allContacts(): HasMany
    {
        return $this->hasMany(GuardianContact::class, 'guardian_person_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardian_links', 'guardian_id', 'student_id')
            ->withPivot('guardian_type', 'relationship', 'is_primary_contact', 'is_emergency_contact', 'appointed_by_role', 'appointed_at')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
}
