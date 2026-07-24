<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Donate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'donor_id',
        'donor_name',
        'amounts',
        'slip',
        'transfer_date',
        'transfer_time',
        'donation_date',
        'donor_email',
        'donation_purpose',
        'payment_method',
        'transaction_id',
        'remaining_points',
        'status',
        'approved_by',
        'privacy_settings',
        'notes',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'donation_date' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = [
        'total_points',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function activity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'activityable');
    }

    // public function getStatusAttribute($value)
    // {
    //     return $value == 0 ? 'pending' : 'received';
    // }

    public function getSlipAttribute($value)
    {
        return null;
    }

    public function getTransferDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    // user who recieve the donation, belons to many users
    public function recipients()
    {
        return $this->belongsToMany(User::class, 'donate_recipients', 'donate_id', 'user_id')->withTimestamps();
    }

    public function donateRecipients(): HasMany
    {
        return $this->hasMany(DonateRecipient::class, 'donate_id');
    }

    public function getTotalPointsAttribute()
    {
        return $this->amounts * 1080;
    }
}
