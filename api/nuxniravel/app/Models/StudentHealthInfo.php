<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Student Health Information Model
 */
class StudentHealthInfo extends Model
{
    use HasFactory;

    protected $table = 'student_health_info';

    protected $fillable = [
        'student_id',
        'height_cm',
        'weight_kg',
        'blood_type',
        'rh_factor',
        'allergies',
        'chronic_diseases',
        'medications',
        'last_checkup_date'
    ];

    protected $casts = [
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'last_checkup_date' => 'date'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getBmiAttribute(): ?float
    {
        if (!$this->height_cm || !$this->weight_kg) {
            return null;
        }
        
        $heightInMeters = $this->height_cm / 100;
        return round($this->weight_kg / ($heightInMeters * $heightInMeters), 2);
    }

    public function getBmiCategoryAttribute(): ?string
    {
        $bmi = $this->bmi;
        if (!$bmi) return null;
        
        if ($bmi < 18.5) return 'น้ำหนักน้อย';
        if ($bmi < 25) return 'น้ำหนักปกติ';
        if ($bmi < 30) return 'น้ำหนักเกิน';
        return 'อ้วน';
    }
}
