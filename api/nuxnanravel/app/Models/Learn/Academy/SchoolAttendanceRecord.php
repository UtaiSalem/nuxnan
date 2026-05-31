<?php

namespace App\Models\Learn\Academy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolAttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id', 'academy_id', 'student_id',
        'status', 'check_in_method', 'checked_in_at',
        'remark', 'recorded_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(SchoolAttendance::class, 'attendance_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
