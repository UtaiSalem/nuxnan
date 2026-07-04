<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAccountInvitation extends Model
{
    protected $fillable = [
        'student_id',
        'academy_id',
        'token_hash',
        'email',
        'status',
        'created_by',
        'sent_at',
        'activated_at',
        'expires_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
