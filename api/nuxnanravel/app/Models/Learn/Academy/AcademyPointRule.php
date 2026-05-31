<?php

namespace App\Models\Learn\Academy;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademyPointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'rule_type',
        'rule_key',
        'name',
        'description',
        'points',
        'daily_cap',
        'requires_approval',
        'is_active',
    ];

    protected $casts = [
        'points' => 'float',
        'daily_cap' => 'integer',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }
}
