<?php

namespace App\Models\Learn\Academy;

use App\Models\User;
use App\Traits\HasQrCheckIn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolAttendance extends Model
{
    use HasFactory, HasQrCheckIn;

    protected $fillable = [
        'academy_id', 'date', 'title', 'start_time',
        'late_minutes', 'qr_token', 'qr_token_expires_at', 'status', 'created_by',
        'closed_at', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'qr_token_expires_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function academy()
    {
        return $this->belongsTo(\App\Models\Academy::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function records()
    {
        return $this->hasMany(SchoolAttendanceRecord::class, 'attendance_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
