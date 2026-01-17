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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function members():HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academies(): HasMany
    {
        return $this->hasMany(Academy::class, 'id');
    }

    public function getMemberNameAttribute()
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        if ($this->student_id && $this->student) {
            return $this->student->first_name_th . ' ' . $this->student->last_name_th;
        }
        return 'Unknown Member';
    }

    public function getMemberAvatarAttribute()
    {
        if ($this->user_id && $this->user) {
            return $this->user->profile_photo_url;
        }
        if ($this->student_id && $this->student && $this->student->profile_image) {
            return '/storage/images/students/profiles/' . $this->student->profile_image;
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->member_name) . '&color=7F9CF5&background=EBF4FF';
    }
}
