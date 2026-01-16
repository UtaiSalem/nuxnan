<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademyGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function groupMembers(): HasMany
    {
        return $this->hasMany(AcademyGroupMember::class, 'academy_group_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_group_members', 'academy_group_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function groupAdmins(): HasMany
    {
        return $this->hasMany(AcademyGroupAdmin::class, 'academy_group_id');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_group_admins', 'academy_group_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }
}
