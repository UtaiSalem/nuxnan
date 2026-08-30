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
        'archived_at' => 'datetime',
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

    /**
     * SET-S2 — โรงเรียนที่ยังไม่ถูกเก็บถาวร
     *
     * ใช้กับ "จุดที่แสดงรายการโรงเรียน" เท่านั้น (directory / ค้นหา / เป้าหมายแคมเปญ)
     * จงใจไม่ทำเป็น global scope — ดูเหตุผลในหัว migration add_archived_at_to_academies_table
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * ใครกดเก็บถาวร/กู้คืนโรงเรียนนี้ได้ — **นิยามเดียวของทั้งระบบ**
     *
     * เจ้าของโรงเรียน หรือ super admin เท่านั้น
     * admin/director ของโรงเรียน (แม้ถือ settings.manage) ทำไม่ได้ — เป็นการตัดสินใจของเจ้าของโปรเจค
     *
     * ห้ามเขียนเงื่อนไขนี้ซ้ำใน controller/middleware/route — ให้เรียกเมธอดนี้เสมอ
     */
    public function canManageArchive($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin() || $this->user_id === $user->id;
    }

    /**
     * สมาชิกที่ผ่านการอนุมัติแล้วเท่านั้น (status = 2)
     * 1=รออนุมัติ 3=ปฏิเสธ 4=ถูกเชิญ 5=ระงับ — ไม่นับเป็นสมาชิก
     */
    public function isApprovedMember($user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->academyMembers()
            ->where('user_id', $user->id)
            ->where('status', 2)
            ->exists();
    }

    /**
     * โหมดการเข้าร่วมที่บังคับใช้จริง — `join_mode` เป็นแหล่งความจริงเดียว
     * ค่าที่ไม่รู้จัก/ว่าง ให้ถือเป็น 'approval' (ปลอดภัยกว่าเปิดรับอัตโนมัติ)
     */
    public function joinMode(): string
    {
        $mode = $this->getSettings()?->join_mode;

        return in_array($mode, ['open', 'approval', 'invite_only'], true) ? $mode : 'approval';
    }

    public function isPrivate(): bool
    {
        return ($this->getSettings()?->privacy ?? 'public') === 'private';
    }

    /**
     * เนื้อหาของโรงเรียน (ฟีด/กลุ่ม/กิจกรรม/ห้องเรียน/ประกาศ/แต้ม) มองเห็นได้ไหม
     */
    public function canViewContent($user): bool
    {
        // SET-S2 — โรงเรียนที่ถูกเก็บถาวรมองเห็นได้เฉพาะคนที่กู้คืนมันได้
        if ($this->isArchived() && ! $this->canManageArchive($user)) {
            return false;
        }

        if (! $this->isPrivate()) {
            return true;
        }

        return $this->isAdmin($user) || $this->isApprovedMember($user);
    }

    public function canViewMemberList($user): bool
    {
        if ($this->isAdmin($user) || $this->isApprovedMember($user)) {
            return true;
        }

        return $this->canViewContent($user)
            && (bool) ($this->getSettings()?->show_member_list ?? true);
    }

    public function canViewCourseList($user): bool
    {
        if ($this->isAdmin($user) || $this->isApprovedMember($user)) {
            return true;
        }

        return $this->canViewContent($user)
            && (bool) ($this->getSettings()?->show_course_list ?? true);
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
