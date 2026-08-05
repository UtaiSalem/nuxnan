<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademyDonateClaim extends Model
{
    protected $guarded = [];

    protected $casts = ['claimed_at' => 'datetime'];

    public function donation()
    {
        return $this->belongsTo(AcademyDonate::class, 'academy_donate_id');
    }

    public function claimer()
    {
        return $this->belongsTo(User::class, 'claimer_id');
    }
}
