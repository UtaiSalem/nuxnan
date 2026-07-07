<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentImportBatch extends Model
{
    protected $fillable = [
        'academy_id', 'academic_year_id', 'import_type', 'source_format', 'filename', 'original_filename', 'status',
        'total_rows', 'valid_rows', 'invalid_rows', 'imported_rows', 'skipped_rows',
        'column_mapping', 'default_values', 'idempotency_key', 'created_by',
        'confirmed_by', 'confirmed_at', 'completed_at',
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'default_values' => 'array',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(StudentImportRow::class, 'batch_id');
    }
}
