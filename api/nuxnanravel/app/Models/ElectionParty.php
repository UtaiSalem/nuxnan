<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElectionParty extends Model
{
    use HasFactory,SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $guarded = [];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function members()
    {
        return $this->hasMany(ElectionPartyMember::class, 'party_id');
    }

    public function ballots()
    {
        return $this->hasMany(ElectionBallot::class, 'party_id');
    }

    public function results()
    {
        return $this->hasMany(ElectionResult::class, 'party_id');
    }
}
