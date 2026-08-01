<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseAssignmentRow extends Model
{
    protected $table = 'house_assignment_rows';

    protected $guarded = [];

    protected $casts = ['raw' => 'array'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HouseAssignmentBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
