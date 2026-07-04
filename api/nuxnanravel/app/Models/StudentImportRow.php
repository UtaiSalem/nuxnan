<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentImportRow extends Model
{
    protected $fillable = [
        'batch_id', 'row_number', 'raw_data', 'normalized_data', 'status',
        'errors', 'warnings', 'student_id',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'normalized_data' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StudentImportBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
