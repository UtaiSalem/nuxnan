<?php

namespace App\Models;

use App\Constants\AcademyGroupTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
    ];

    protected $appends = ['type_meta'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get Type metadata (label, icon, color)
     */
    public function getTypeMetaAttribute(): ?array
    {
        return $this->type ? AcademyGroupTypes::get($this->type) : null;
    }

    protected static function booted()
    {
        static::creating(function ($group) {
            if (! $group->sort_order) {
                $group->sort_order = static::where('academy_id', $group->academy_id)->max('sort_order') + 1;
            }
        });
    }

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

    public function permissions(): HasMany
    {
        return $this->hasMany(AcademyGroupPermission::class, 'academy_group_id');
    }
}
