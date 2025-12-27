<?php

namespace App\Models;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademySetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
}
