<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class AcademyScopeTask extends Model
{
    use Auditable;

    protected $fillable = ['academy_id', 'scope_type', 'scope_id', 'created_by', 'title', 'description', 'status', 'priority', 'due_at'];

    protected $casts = ['due_at' => 'datetime'];
}
