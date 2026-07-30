<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Election extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_NOMINATION = 'nomination';

    public const STATUS_CAMPAIGN = 'campaign';

    public const STATUS_VOTING = 'voting';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = ['nomination_opens_at' => 'datetime', 'nomination_closes_at' => 'datetime', 'voting_opens_at' => 'datetime', 'voting_closes_at' => 'datetime', 'voter_roll_locked_at' => 'datetime', 'published_at' => 'datetime', 'settings' => 'array', 'allow_abstain' => 'boolean'];

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parties()
    {
        return $this->hasMany(ElectionParty::class);
    }

    public function voters()
    {
        return $this->hasMany(ElectionVoter::class);
    }

    public function receipts()
    {
        return $this->hasMany(ElectionVoterReceipt::class);
    }

    public function ballots()
    {
        return $this->hasMany(ElectionBallot::class);
    }

    public function stations()
    {
        return $this->hasMany(ElectionStation::class);
    }

    public function results()
    {
        return $this->hasMany(ElectionResult::class);
    }
}
