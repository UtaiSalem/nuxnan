<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentImportRow extends Model
{
    protected $fillable = [
        'batch_id', 'row_number', 'raw_data', 'normalized_data', 'status', 'action',
        'errors', 'warnings', 'student_id', 'matched_student_id', 'diff_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'normalized_data' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
        'diff_data' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StudentImportBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function matchedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'matched_student_id');
    }
}
