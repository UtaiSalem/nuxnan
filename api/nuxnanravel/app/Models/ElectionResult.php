<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionResult extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['published_at' => 'datetime', 'is_winner' => 'boolean'];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function party()
    {
        return $this->belongsTo(ElectionParty::class, 'party_id');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
