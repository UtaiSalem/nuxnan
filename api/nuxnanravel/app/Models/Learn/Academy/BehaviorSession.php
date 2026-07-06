<?php

namespace App\Models\Learn\Academy;

use App\Models\Academy;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BehaviorSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'classroom_id',
        'title',
        'date',
        'status',
        'created_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(BehaviorRecord::class, 'session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
