<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class AcademyScopeFile extends Model
{
    use Auditable;

    protected $fillable = ['academy_id', 'scope_type', 'scope_id', 'uploaded_by', 'name', 'path', 'mime_type', 'size'];
}
