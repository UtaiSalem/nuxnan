<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionVoterReceipt extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_CAST = 'cast';

    public const STATUS_VOID = 'void';

    public const STATUS_EXPIRED = 'expired';

    protected $guarded = [];

    protected $casts = ['token_expires_at' => 'datetime', 'issued_at' => 'datetime', 'cast_at' => 'datetime'];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function voter()
    {
        return $this->belongsTo(ElectionVoter::class, 'election_voter_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function station()
    {
        return $this->belongsTo(ElectionStation::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
