<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCard extends Model
{
    protected $fillable = [
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

    /**
     * Get the corresponding student from normalized database
     * Use manual query to avoid collation issues
     */
    public function getStudentAttribute()
    {
        return Student::where('student_id', $this->student_number)
            ->orWhere('citizen_id', $this->national_id)
            ->first();
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
