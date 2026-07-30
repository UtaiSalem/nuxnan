<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionVoter extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function academyMember()
    {
        return $this->belongsTo(AcademyMember::class);
    }

    public function receipt()
    {
        return $this->hasOne(ElectionVoterReceipt::class);
    }
}
