<?php

namespace App\Models;

use App\Models\User;
use App\Models\Course;
use App\Models\AcademyPost;
use App\Models\AcademyAdmin;
use App\Models\AcademyMember;
// use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Models\AcademySetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Academy extends Model
{
    use HasFactory;
    // use HasUlids;
    
    // protected $fillable = [];
    protected $guarded = [];

    /**
     * Get the academySetting associated with the Academy
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function academySetting(): HasOne
    {
        return $this->hasOne(AcademySetting::class, 'academy_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function academyAdmins(): HasMany
    {
        return $this->hasMany(AcademyAdmin::class);
    }

    public function academyMembers(): HasMany
    {
        return $this->hasMany(AcademyMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_members', 'academy_id', 'user_id')->withPivot('status');
    }

    public function member_status($id)
    {
        // $academy = AcademyMember::where('academy_id', $id)->where('user_id', auth()->id())->get();
        
        // return $academy->pluck('status')->first();

        return auth()->user()->memberAcademies()->where('academy_id', $id)->pluck('status')->first();
    }

  
    /**
     * Get all of the academyPost for the Academy
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(AcademyPost::class);
    }

}
