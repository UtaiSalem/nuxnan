<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'student_id',
        'model_type',
        'model_id',
        'field',
        'old_value',
        'new_value',
        'status',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
        'approved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }
}
