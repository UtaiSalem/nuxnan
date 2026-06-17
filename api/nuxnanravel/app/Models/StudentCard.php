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

    protected $appends = ['qr_content', 'qr_url'];

    /**
     * Get the corresponding student from normalized database
     */
    public function getStudentAttribute()
    {
        return $this->getRelationValue('student') ?: Student::where('student_id', $this->student_number)
            ->orWhere('citizen_id', $this->national_id)
            ->first();
    }

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
}
