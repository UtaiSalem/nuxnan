<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionPartyMember extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function party()
    {
        return $this->belongsTo(ElectionParty::class, 'party_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
