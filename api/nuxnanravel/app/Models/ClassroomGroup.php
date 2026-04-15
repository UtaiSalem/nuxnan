<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\GroupMember;

/**
 * ClassroomGroup Model - กลุ่มย่อยในห้องเรียน
 */
class ClassroomGroup extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'classroom_id',
        'group_name',
        'group_type',
        'description',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Type constants
    const TYPE_STATIC = 'static';
    const TYPE_DYNAMIC = 'dynamic';

    // ───── Relationships ─────

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    // ───── Accessors ─────

    public function getMemberCountAttribute(): int
    {
        return $this->groupMembers()->count();
    }
}
