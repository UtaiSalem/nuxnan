<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

class Academy extends Model
{
    use Auditable, HasFactory;
    // use HasUlids;

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($academy) {
            Cache::forget("academy_settings_{$academy->id}");
        });
    }

    /**
     * Get cached settings
     */
    public function getSettings()
    {
        return Cache::remember("academy_settings_{$this->id}", now()->addHours(24), function () {
            return $this->academySetting()->first();
        });
    }

    /**
     * Mass-assignable attributes.
     *
     * ใช้ whitelist แบบ $fillable เพื่อป้องกัน mass assignment attack.
     * ถ้าเพิ่มคอลัมน์ใหม่ใน migration ต้องเพิ่มในลิสต์นี้ด้วย.
     */
    protected $fillable = [
        'user_id',
        'owner_id',
        'name',
        'slogan',
        'description',
        'address',
        'email',
        'phone',
        'director',
        'established_year',
        'type',
        'accreditation',
        'accreditation_body',
        'total_students',
        'total_teachers',
        'membership_fees_points',
        'courses_offered',
        'facilities',
        'academy_timings',
        'holidays',
        'social_media_links',
        'logo',
        'cover',
        'name_en',
        'description_en',
        'website',
        'province',
        'country',
        'name_slug',
        'donation_enabled',
    ];

    protected $casts = [
        'donation_enabled' => 'boolean',
        'student_editable_fields' => 'array',
    ];

    public function donationEnabled(): bool
    {
        return $this->donation_enabled ?? (bool) config('platform.course_donation.enabled');
    }

    public function pointAccount(): HasOne
    {
        return $this->hasOne(AcademyPointAccount::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(AcademyDonate::class);
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class);
    }

    /**
     * Get the academySetting associated with the Academy
     */
    public function academySetting(): HasOne
    {
        return $this->hasOne(AcademySetting::class, 'academy_id');
    }

    public function isAdmin($user)
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->user_id === $user->id ||
               $this->academyAdmins()->where('user_id', $user->id)->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function academyGroups(): HasMany
    {
        return $this->hasMany(AcademyGroup::class)->orderBy('sort_order')->orderBy('id');
    }

    public function academyAdmins(): HasMany
    {
        return $this->hasMany(AcademyAdmin::class);
    }

    public function academyMembers(): HasMany
    {
        return $this->hasMany(AcademyMember::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * นักเรียนที่ active ในโรงเรียน
     */
    public function activeStudents(): HasMany
    {
        return $this->hasMany(Student::class)->where('status', 'active');
    }

    /**
     * สมาชิกที่เป็นนักเรียน (academy_members ที่มี student_id)
     */
    public function studentMembers(): HasMany
    {
        return $this->hasMany(AcademyMember::class)->whereNotNull('student_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_members', 'academy_id', 'user_id')->withPivot('status');
    }

    public function member_status($id)
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            return null;
        }

        return auth()->user()->memberAcademies()->where('academy_id', $id)->pluck('status')->first();
    }

    /**
     * Get all of the academyPost for the Academy
     */
    public function posts(): HasMany
    {
        return $this->hasMany(AcademyPost::class);
    }

    public function getCoverUrlAttribute()
    {
        $path = $this->cover;

        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Clean up path
        $cleanPath = preg_replace('#^/?(storage/)?#', '', $path);

        // If it was just a filename (old behavior), prepend the old directory structure?
        // Old structure was 'storage/images/academies/covers/'.
        // If it's a new upload, where does it go?
        // We aren't standardizing Academy uploads yet (only User), so we must preserve the old pathing if it assumes explicit path.
        // But wait, the old accessor did: asset('storage/images/academies/covers/' . $this->cover);
        // This implies $this->cover is JUST THE FILENAME.
        // So we should construct the full path correctly.

        // Check if $cleanPath already looks like a full path (e.g. contains '/')
        if (strpos($cleanPath, '/') !== false) {
            return url('storage/'.$cleanPath);
        }

        // Otherwise assume it's just a filename in the legacy folder
        return url('storage/images/academies/covers/'.$cleanPath);
    }

    public function getLogoUrlAttribute()
    {
        $path = $this->logo;

        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $cleanPath = preg_replace('#^/?(storage/)?#', '', $path);

        if (strpos($cleanPath, '/') !== false) {
            return url('storage/'.$cleanPath);
        }

        return url('storage/images/academies/logos/'.$cleanPath);
    }

    public function rolloverBatches(): HasMany
    {
        return $this->hasMany(RolloverBatch::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(RolloverBatch::class);
    }
}
