<?php

namespace App\Models;

use App\Models\User;
use App\Models\Donate;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DonateRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'donate_id',
        'user_id',
    ];

    public function donation()
    {
        return $this->belongsTo(Donate::class, 'donate_id');
    }

    public function reciever()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function activity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'activityable');
    }
}
