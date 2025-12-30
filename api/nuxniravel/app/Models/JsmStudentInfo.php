<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JsmStudentInfo extends Model
{
    use HasFactory;

    protected $table = 'jsm_student_info';

    protected $fillable = [
        'student_id',
        'student_number',
        'citizen_id',
        'title_prefix_th',
        'first_name_th',
        'middle_name_th',
        'last_name_th',
        'title_prefix_en',
        'first_name_en',
        'middle_name_en',
        'last_name_en',
        'nickname',
        'gender',
        'birthdate',
        'blood_type',
        'religion',
        'nationality',
        'ethnicity',
        'marital_status',
        'address_number',
        'alley',
        'road',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'email',
        'phone_number',
        'enrollment_date',
        
        // Father information
        'father_citizen_id',
        'father_title_prefix',
        'father_first_name',
        'father_last_name',
        'father_status',
        'father_nationality',
        
        // Mother information
        'mother_citizen_id',
        'mother_title_prefix',
        'mother_first_name',
        'mother_last_name',
        'mother_status',
        'mother_nationality',
        
        // Guardian information
        'guardian_citizen_id',
        'guardian_title_prefix',
        'guardian_full_name',
        'guardian_occupation',
        'guardian_phone_number',
        'relationship',
        
        // Health information
        'height_cm',
        'weight_kg',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'enrollment_date' => 'date',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
    ];

    // Relationship to Student
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
