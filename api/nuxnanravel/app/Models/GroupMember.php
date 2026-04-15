<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GroupMember Model - สมาชิกในกลุ่มย่อย
 */
class GroupMember extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'member_id',
        'assigned_at',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    // ───── Relationships ─────

    public function group(): BelongsTo
    {
        return $this->belongsTo(ClassroomGroup::class, 'group_id');
    }

    public function classroomMember(): BelongsTo
    {
        return $this->belongsTo(ClassroomMember::class, 'member_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
