<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademySetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
}
