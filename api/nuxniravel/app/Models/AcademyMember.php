<?php

namespace App\Models;


use App\Models\User;
use App\Models\Academy;
use App\Models\AcademyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademyMember extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function members():HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academies(): HasMany
    {
        return $this->hasMany(Academy::class, 'id');
    }
}
