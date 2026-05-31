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

    public function generateQrToken(int $ttlSeconds = 60): string
    {
        $token = Str::random(32);
        $this->update([
            'qr_token' => $token,
            'qr_token_expires_at' => now()->addSeconds($ttlSeconds),
        ]);

        return $token;
    }

    public function isQrTokenValid(string $token): bool
    {
        return $this->qr_token === $token
            && $this->qr_token_expires_at !== null
            && now()->lt($this->qr_token_expires_at);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
