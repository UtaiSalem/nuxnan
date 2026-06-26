<?php

namespace App\Models;

use App\Traits\HasQrCheckIn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivitySession extends Model
{
    use HasFactory, HasQrCheckIn, SoftDeletes;

    protected $fillable = [
        'event_id',
        'title',
        'slot_label',
        'description',
        'start_datetime',
        'end_datetime',
        'status',
        'location',
        'is_makeup_class',
        'qr_token',
        'qr_token_expires_at',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'qr_token_expires_at' => 'datetime',
        'is_makeup_class' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SchoolEvent::class, 'event_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ActivityAttendance::class, 'session_id');
    }
}
