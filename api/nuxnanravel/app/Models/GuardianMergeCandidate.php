<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuardianMergeCandidate extends Model
{
    protected $fillable = ['academy_id', 'reason', 'group_key', 'guardian_ids', 'record_count', 'status', 'reviewed_by_user_id', 'reviewed_at', 'note', 'absorbed_snapshot'];

    protected $casts = ['guardian_ids' => 'array', 'absorbed_snapshot' => 'array', 'reviewed_at' => 'datetime'];
}
