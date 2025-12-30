<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Student Model - Core Entity for New Normalized Structure
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'citizen_id',
        'title_prefix_th',
        'first_name_th',
        'last_name_th',
        'middle_name_th',
        'title_prefix_en',
        'first_name_en',
        'last_name_en',
        'middle_name_en',
        'nickname',
        'date_of_birth',
        'gender',
        'nationality',
        'religion',
        'profile_image',
        'status',
        'enrollment_date',
        'class_level',
        'class_section'
    ];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'enrollment_date' => 'date:Y-m-d',
        'gender' => 'integer',
    ];
    
    // Gender constants
    const GENDER_FEMALE = 0;
    const GENDER_MALE = 1;
    
    // Gender accessor - แปลงเป็นข้อความสำหรับแสดงผล
    public function getGenderTextAttribute(): string
    {
        return match($this->gender) {
            self::GENDER_FEMALE => 'หญิง',
            self::GENDER_MALE => 'ชาย',
            default => 'ไม่ระบุ'
        };
    }
    
    // Gender scope สำหรับ query
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }
    
    public function scopeFemale($query)
    {
        return $query->where('gender', self::GENDER_FEMALE);
    }
    
    public function scopeMale($query)
    {
        return $query->where('gender', self::GENDER_MALE);
    }

    // Relationships
    public function academicInfos(): HasMany
    {
        return $this->hasMany(StudentAcademicInfo::class);
    }

    // Current academic info (latest or active)
    public function currentAcademicInfo(): HasOne
    {
        return $this->hasOne(StudentAcademicInfo::class)
                    ->where('is_current', true)
                    ->orWhere(function($query) {
                        $query->orderBy('academic_year', 'desc')
                              ->orderBy('created_at', 'desc')
                              ->limit(1);
                    });
    }

    // Legacy support - for backward compatibility
    public function academicInfo(): HasOne
    {
        return $this->currentAcademicInfo();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(StudentAddress::class);
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(StudentAddress::class)->where('is_primary', true);
    }

    public function currentAddress(): HasOne
    {
        return $this->hasOne(StudentAddress::class)->where('address_type', 'current');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(StudentContact::class);
    }

    // Academic Records removed - using StudentAcademicInfo instead

    public function primaryContact(): HasOne
    {
        return $this->hasOne(StudentContact::class)->where('is_primary', true);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    public function guardiansByCode(): HasMany
    {
        return $this->hasMany(StudentGuardian::class, 'student_code', 'student_id');
    }

    public function father(): HasOne
    {
        return $this->hasOne(StudentGuardian::class)->where('guardian_type', 'father');
    }

    public function mother(): HasOne
    {
        return $this->hasOne(StudentGuardian::class)->where('guardian_type', 'mother');
    }

    public function primaryGuardian(): HasOne
    {
        return $this->hasOne(StudentGuardian::class)->where('is_primary_contact', true);
    }

    public function healthInfo(): HasOne
    {
        return $this->hasOne(StudentHealthInfo::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function homeVisits(): HasMany
    {
        return $this->hasMany(StudentHomeVisit::class);
    }

    /**
     * Get the corresponding student card
     * Use manual query to avoid collation issues
     */
    public function getStudentCardAttribute()
    {
        return StudentCard::where('student_number', $this->student_id)
            ->orWhere('national_id', $this->citizen_id)
            ->first();
    }

    public function profileImage(): HasOne
    {
        return $this->hasOne(StudentDocument::class)
                    ->where('document_type', 'profile_image')
                    ->where('is_verified', true);
    }

    // Accessors
    public function getFullNameThAttribute(): string
    {
        $parts = array_filter([
            $this->title_prefix_th,
            $this->first_name_th,
            $this->middle_name_th,
            $this->last_name_th
        ]);
        
        return implode(' ', $parts);
    }

    public function getFullNameEnAttribute(): string
    {
        $parts = array_filter([
            $this->title_prefix_en,
            $this->first_name_en,
            $this->middle_name_en,
            $this->last_name_en
        ]);
        
        return implode(' ', $parts);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getCurrentClassroomAttribute(): ?string
    {
        return $this->currentAcademicInfo?->classroom_full;
    }

    public function getAcademicHistoryAttribute(): array
    {
        return $this->academicInfos()
                    ->orderBy('academic_year', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->toArray();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByGrade($query, $grade)
    {
        return $query->whereHas('currentAcademicInfo', function($q) use ($grade) {
            $q->where('current_grade', $grade);
        });
    }

    public function scopeByClass($query, $grade, $class)
    {
        return $query->whereHas('currentAcademicInfo', function($q) use ($grade, $class) {
            $q->where('current_grade', $grade)
              ->where('current_class', $class);
        });
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->whereHas('currentAcademicInfo', function($q) use ($year) {
            $q->where('academic_year', $year);
        });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('student_id', 'LIKE', "%{$search}%")
              ->orWhere('citizen_id', 'LIKE', "%{$search}%")
              ->orWhere('first_name_th', 'LIKE', "%{$search}%")
              ->orWhere('last_name_th', 'LIKE', "%{$search}%")
              ->orWhere('first_name_en', 'LIKE', "%{$search}%")
              ->orWhere('last_name_en', 'LIKE', "%{$search}%");
        });
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasProfileImage(): bool
    {
        return $this->documents()
                    ->where('document_type', 'profile_image')
                    ->where('is_verified', true)
                    ->exists();
    }

    public function getMainContactNumber(): ?string
    {
        $contact = $this->contacts()
                       ->whereIn('contact_type', ['mobile', 'phone'])
                       ->where('is_primary', true)
                       ->first();
        
        return $contact?->contact_value;
    }

    public function getEmergencyContact(): ?StudentGuardian
    {
        return $this->guardians()
                   ->where('is_emergency_contact', true)
                   ->first();
    }
}
