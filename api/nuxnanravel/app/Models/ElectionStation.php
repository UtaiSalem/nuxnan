<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionStation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['is_open' => 'boolean', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function receipts()
    {
        return $this->hasMany(ElectionVoterReceipt::class, 'station_id');
    }
}
