<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'group_id',
        'user_id',
        'status',
        'role',
        'request_status',
        'last_accessed_tab',
    ];

    /**
     * `course_group_members.status` is an enum('0','1'). Writing a PHP integer to
     * a MySQL enum is interpreted as a 1-based index (int 0 = invalid → truncated,
     * int 1 = the FIRST label '0'), which corrupts the value or throws
     * `1265 Data truncated`. SQLite treats the column as text and stores 0/1 as-is,
     * so the mismatch is invisible there. Coerce to the enum's string label on
     * write so numeric callers (0 = inactive/pending, 1 = active) round-trip
     * correctly on both engines. Same fix family as the cluster-1 status mutators.
     */
    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = $value === null ? null : (string) (int) $value;
    }

    public function course_group(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class, 'group_id');
    }
}
