<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ElectionBallot extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function (self $ballot) {
            $ballot->uuid ??= (string) Str::uuid();
        });
    }

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function party()
    {
        return $this->belongsTo(ElectionParty::class, 'party_id');
    }
}
