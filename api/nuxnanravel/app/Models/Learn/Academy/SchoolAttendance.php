<?php

namespace App\Models\Learn\Academy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SchoolAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id', 'date', 'title', 'start_time',
        'late_minutes', 'qr_token', 'status', 'created_by',
        'closed_at', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
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

    public function generateQrToken(): string
    {
        $token = Str::random(32);
        $this->update(['qr_token' => $token]);

        return $token;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
