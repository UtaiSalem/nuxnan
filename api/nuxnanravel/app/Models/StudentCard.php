<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCard extends Model
{
    use Auditable;

    protected $fillable = [
        'student_id',
        'academy_id',
        'academic_year_id',
        'order_no',
        'full_name_thai',
        'class_level',
        'class_section',
        'national_id',
        'student_number',
        'level_and_room',
        'title_name',
        'first_name_thai',
        'last_name_thai',
        'first_name_english',
        'birth_date',
        'birth_date_string',
        'card_issue_date',
        'card_expiry_date',
        'student_status',
        'profile_image',
    ];

    protected $appends = ['qr_content', 'qr_url', 'profile_image_url'];

    /**
     * Get the universal QR content for this student card
     */
    public function getQrContentAttribute(): string
    {
        return "STUDENT:{$this->academy_id}:{$this->student_number}";
    }

    /**
     * Get the student profile URL (for legacy scanner fallback)
     */
    public function getQrUrlAttribute(): string
    {
        return url("/academies/{$this->academy_id}/members/{$this->student_number}");
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get student info (legacy table) - DEPRECATED
     * Use getStudentAttribute() instead
     */
    public function studentInfo()
    {
        // DEPRECATED: This method is no longer used
        // Use the getStudentAttribute() method instead for normalized data
        return null;
    }

    /**
     * Get the profile image URL for student card.
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (! $this->profile_image) {
            return null;
        }

        if (str_starts_with($this->profile_image, 'images/')) {
            return asset('storage/'.$this->profile_image);
        }

        if ($this->student_id && $rel = $this->student) {
            return $rel->profile_image_url;
        }

        if ($this->class_level && $this->class_section) {
            $level = (int) str_replace('ม.', '', $this->class_level);
            $section = (int) $this->class_section;

            return asset("storage/images/students/{$level}/{$section}/{$this->profile_image}");
        }

        return null;
    }
}
