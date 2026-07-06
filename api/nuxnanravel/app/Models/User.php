<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
// use App\Models\User; // Recursive import removed

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Multicaret\Acquaintances\Traits\Friendable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

// use Laravel\Sanctum\HasApiTokens;

// use App\Models\UserActivity;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    public const ADMIN_SUGGESTER_CODE = '11111111';

    public const MAX_REFERRALS_PER_SUGGESTER = 5;

    // use HasApiTokens;
    use Friendable;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public $incrementing = true;

    protected static ?bool $permissionsSchemaReady = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'phone_number',
        'google_id',
        'facebook_id',
        'twitter_id',
        'linkedin_id',
        'github_id',
        'suggester_code',
        'reference_code',
        'personal_code',
        'no_of_ref',
        'pp',
        'wallet',
        'profile_photo_path',
        'verified',
        'email_verified_at',
        'phone_verified_at',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'remember_token',
        'current_team_id',
        'total_points_earned',
        'total_points_spent',
        'level',
        'xp',
        'xp_level',
        'xp_for_next_level',
        'current_xp',
        'deleted_by',
        'deletion_reason',
        'anonymized_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'total_points_earned' => 'integer',
            'total_points_spent' => 'integer',
            'level' => 'integer',
            'xp' => 'integer',
            'xp_level' => 'integer',
            'xp_for_next_level' => 'integer',
            'current_xp' => 'integer',
            'no_of_ref' => 'integer',
            'pp' => 'decimal:2',
            'wallet' => 'decimal:2',
            'anonymized_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'connected_providers',
        'avatar',
        'profile_photo_url',
        'referal_link',
        'is_plearnd_admin',
        'is_super_admin',
    ];

    /**
     * Get the roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function coursePurchases(): HasMany
    {
        return $this->hasMany(CoursePurchase::class, 'buyer_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function courseSales(): HasMany
    {
        return $this->hasMany(CoursePurchase::class, 'seller_id');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('SUPER_ADMIN');
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Sync roles for the user.
     */
    public function syncRoles(array $roleNames): void
    {
        $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
        $this->roles()->sync($roleIds);
    }

    /**
     * Get direct permissions assigned to the user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /**
     * Get badges earned by the user.
     */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('is_earned', 'progress', 'earned_at')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific permission (via roles or direct).
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super Admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->permissionsSchemaReady()) {
            return false;
        }

        // Check direct permissions
        if ($this->permissions()->where('name', $permissionName)->exists()) {
            return true;
        }

        // Check permissions via roles
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permissions (direct + via roles).
     */
    public function getAllPermissions(): array
    {
        if (! $this->permissionsSchemaReady()) {
            return [];
        }

        $directPermissions = $this->permissions()->pluck('name')->toArray();

        $rolePermissions = [];
        foreach ($this->roles as $role) {
            $rolePermissions = array_merge($rolePermissions, $role->permissions()->pluck('name')->toArray());
        }

        return array_unique(array_merge($directPermissions, $rolePermissions));
    }

    private function permissionsSchemaReady(): bool
    {
        if (self::$permissionsSchemaReady !== null) {
            return self::$permissionsSchemaReady;
        }

        self::$permissionsSchemaReady = Schema::hasTable('permissions')
            && Schema::hasTable('role_permissions')
            && Schema::hasTable('user_permissions');

        return self::$permissionsSchemaReady;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    // Generate Personal Code (User's own code)
    public static function generateReferralCode()
    {
        $personal_code = mt_rand(10000000, 99999900);
        if (User::where('personal_code', $personal_code)->exists()) {
            return self::generateReferralCode();
        }

        return (string) $personal_code;
    }

    // Generate unique profile/reference code
    public static function generateReferenceCode(): string
    {
        do {
            $referenceCode = Str::random(10);
        } while (User::where('reference_code', $referenceCode)->exists());

        return $referenceCode;
    }

    // ===== Username (ชื่อ-สกุล) rules: ไทย/อังกฤษ/ตัวเลข + เว้นวรรคได้ ห้ามอักขระพิเศษ =====
    public const USERNAME_REGEX = '/^[\p{Thai}a-zA-Z0-9]+(?: [\p{Thai}a-zA-Z0-9]+)*$/u';

    // trim หัวท้าย + ยุบช่องว่างซ้อนให้เหลือช่องเดียว (กันชื่อ "เกือบซ้ำ" หลุด unique)
    public static function normalizeUsername(?string $raw): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $raw));
    }

    // ชุดกฎ validation กลาง — ทุก controller เรียกใช้ตัวนี้
    public static function usernameRules($ignoreId = null): array
    {
        $unique = Rule::unique('users', 'username');
        if ($ignoreId !== null) {
            $unique = $unique->ignore($ignoreId);
        }

        return ['required', 'string', 'min:3', 'max:191', 'regex:'.self::USERNAME_REGEX, $unique];
    }

    // สร้าง username ที่ unique จากข้อความตั้งต้น (ใช้ตอนสมัครผ่าน OAuth ที่ผู้ใช้ไม่ได้พิมพ์ username เอง)
    public static function generateUniqueUsername(?string $base): string
    {
        // เก็บเฉพาะ ไทย/อังกฤษ/ตัวเลข + เว้นวรรค (ตัดอักขระพิเศษจากชื่อ Google เช่น . ' -) แล้ว normalize
        $clean = preg_replace('/[^\p{Thai}a-zA-Z0-9 ]/u', '', (string) $base);
        $slug = self::normalizeUsername($clean) ?: 'user';

        $username = $slug;
        $i = 1;
        while (self::where('username', $username)->exists()) {
            $username = $slug.' '.(++$i);
        }

        return $username;
    }

    public function canAcceptReferral(): bool
    {
        return $this->no_of_ref < self::MAX_REFERRALS_PER_SUGGESTER;
    }

    // get referal link
    public function getReferalLinkAttribute()
    {
        return env('APP_URL').'/register/'.$this->reference_code;
    }

    // get profile photo url
    // get profile photo url
    public function getProfilePhotoUrlAttribute()
    {
        return $this->avatar;
    }

    // Get Avatar Attribute (New Standard)
    public function getAvatarAttribute()
    {
        $path = $this->profile_photo_path;

        if ($path) {
            // Check if it's already a full valid URL (e.g. Google/Facebook social login)
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }

            // Clean up the path: remove any leading slash, 'storage/', or '/storage/' prefix
            $cleanPath = preg_replace('#^/?(storage/)?#', '', $path);

            // Build the URL manually for better cross-environment support
            $appUrl = rtrim(config('app.url'), '/');

            return $appUrl.'/storage/'.ltrim($cleanPath, '/');
        }

        // Fallback: UI Avatars
        $name = urlencode($this->name ?? 'User');

        return "https://ui-avatars.com/api/?name={$name}&color=7F9CF5&background=EBF4FF";
    }

    /**
     * Get connected OAuth providers
     */
    public function getConnectedProvidersAttribute()
    {
        $providers = [];
        $oauthProviders = ['google', 'facebook', 'twitter', 'github', 'linkedin'];

        foreach ($oauthProviders as $provider) {
            $field = $provider.'_id';
            if (! empty($this->$field)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /**
     * Check if user has connected a specific OAuth provider
     */
    public function hasProvider($provider)
    {
        $field = $provider.'_id';

        return ! empty($this->$field);
    }

    /**
     * Get all of the activities for the User
     *
     * @return HasMany
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function academies(): HasMany
    {
        return $this->hasMany(Academy::class);
    }

    public function academyMembers(): BelongsToMany
    {
        return $this->belongsToMany(AcademyMember::class, 'academy_members', 'user_id', 'academy_id')
            ->withPivot(
                'status',
            )->withTimestamps();
    }

    public function academyGroupsMembers(): BelongsToMany
    {
        return $this->belongsToMany(AcademyGroup::class, 'academy_group_members', 'user_id', 'academy_group_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function academyGroupsAdmins(): BelongsToMany
    {
        return $this->belongsToMany(AcademyGroup::class, 'academy_group_admins', 'user_id', 'academy_group_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function academyAdmins(): BelongsToMany
    {
        return $this->belongsToMany(Academy::class, 'academy_admins', 'user_id', 'academy_id');
    }

    public function academyPosts(): HasMany
    {
        return $this->hasMany(AcademyPost::class);
    }

    public function mutedGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademyGroup::class,
            'user_muted_groups',
            'user_id',
            'academy_group_id'
        )->withTimestamps();
    }

    public function courseMembers(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_members', 'user_id', 'course_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function postComments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * Get posts where this user has been tagged
     */
    public function taggedInPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tagged_users', 'user_id', 'post_id')
            ->withPivot('is_approved', 'is_notified')
            ->withTimestamps();
    }

    /**
     * Get posts where this user has been mentioned
     */
    public function mentionedInPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_mentions', 'user_id', 'post_id')
            ->withPivot('position', 'length', 'is_notified')
            ->withTimestamps();
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function coursesGroups(): HasMany
    {
        return $this->hasMany(CourseGroup::class);
    }

    public function coursesGroupMembers(): HasMany
    {
        return $this->hasMany(CourseGroupMember::class);
    }

    public function courseQuizzes(): HasMany
    {
        return $this->hasMany(CourseQuiz::class);
    }

    public function courseQuizResults(): HasMany
    {
        return $this->hasMany(CourseQuizResult::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'user_id');
    }

    public function assignmentAnswers(): HasMany
    {
        return $this->hasMany(AssignmentAnswer::class);
    }

    public function answerQuestions(): HasMany
    {
        return $this->hasMany(UserAnswerQuestion::class);
    }

    public function adverts(): HasMany
    {
        return $this->hasMany(Advert::class);
    }

    public function advertViewers(): HasMany
    {
        return $this->hasMany(AdvertViewer::class);
    }

    public function isPlearndAdmin(): bool
    {
        return PlearndAdmin::where('user_id', $this->id)->exists() && $this->hasVerifiedEmail();
    }

    public function getIsPlearndAdminAttribute()
    {
        return $this->isPlearndAdmin();
    }

    public function getIsSuperAdminAttribute()
    {
        return $this->isSuperAdmin();
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? date('d-m-Y H:i:s', strtotime($value)) : null;
    }

    public function memberAcademies(): HasMany
    {
        return $this->hasMany(AcademyMember::class, 'user_id');
    }

    /**
     * Get academy member record for a specific academy
     */
    public function academyMember($academyId)
    {
        return $this->memberAcademies()->where('academy_id', $academyId)->first();
    }

    public function donateRecipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'donate_recipients', 'user_id', 'donate_id');
    }

    public function donateReciever(): HasMany
    {
        return $this->hasMany(Donate::class, 'user_id');
    }

    public function isFriendWithAuth($userId): bool
    {
        return $this->isFriendWith(User::find($userId));
    }

    public function friend_senders(): MorphToMany
    {
        return $this->morphToMany(Friendship::class, 'senderable');
    }

    public function friend_recipients(): MorphToMany
    {
        return $this->morphToMany(Friendship::class, 'recipientable');
    }

    public function friendships_status($friendId)
    {
        if (Auth::check()) {
            $authUser = auth()->user();
            $friend = User::find($friendId);
            if ($friend) {
                if ($authUser->isFriendWith($friend)) {
                    return 1;
                    // accepted
                } elseif ($authUser->hasSentFriendRequestTo($friend)) {
                    return 0;
                    // pending
                } else {
                    return null;
                    // not friend
                }
            } else {
                return null;
            }
        } else {
            return null;
        }

    }

    // has many likedLessons
    public function likeLessons(): HasMany
    {
        return $this->hasMany(LessonLike::class);
    }

    public function dislikeLessons(): HasMany
    {
        return $this->hasMany(LessonDislike::class);
    }

    public function lessonComments()
    {
        return $this->hasMany(LessonComment::class);
    }

    /**
     * Get points transactions for the user.
     */
    public function pointsTransactions()
    {
        return $this->hasMany(PointsTransaction::class);
    }

    /**
     * Get wallet transactions for the user.
     */
    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get user rewards for the user.
     */
    public function userRewards()
    {
        return $this->hasMany(UserReward::class);
    }

    /**
     * Get user achievements for the user.
     */
    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Get point streak for the user.
     */
    public function pointStreak()
    {
        return $this->hasOne(PointStreak::class);
    }

    /**
     * Get daily point limits for the user.
     */
    public function dailyPointLimits()
    {
        return $this->hasMany(DailyPointLimit::class);
    }

    /**
     * Get coupons created by the user.
     */
    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Get coupons redeemed by the user.
     */
    public function redeemedCoupons()
    {
        return $this->hasMany(Coupon::class, 'redeemed_by');
    }

    /**
     * Get coupon redemptions by the user.
     */
    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Get users that this user is following.
     */
    public function following()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    /**
     * Get users that are following this user.
     */
    public function followers()
    {
        return $this->hasMany(Follow::class, 'followed_id');
    }

    /**
     * Check if this user is following another user.
     */
    public function isFollowing(User $user): bool
    {
        return $this->following()->where('followed_id', $user->id)->exists();
    }

    /**
     * Check if this user is followed by another user.
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    /**
     * Follow a user.
     */
    public function follow(User $user): bool
    {
        if ($this->id === $user->id) {
            return false; // Cannot follow yourself
        }

        if ($this->isFollowing($user)) {
            return false; // Already following
        }

        return $this->following()->create([
            'followed_id' => $user->id,
        ]);
    }

    /**
     * Unfollow a user.
     */
    public function unfollow(User $user): bool
    {
        return $this->following()->where('followed_id', $user->id)->delete() > 0;
    }

    /**
     * Toggle follow status for a user.
     */
    public function toggleFollow(User $user): array
    {
        if ($this->isFollowing($user)) {
            $this->unfollow($user);

            return ['following' => false, 'followers_count' => $user->followers()->count()];
        } else {
            $this->follow($user);

            return ['following' => true, 'followers_count' => $user->followers()->count()];
        }
    }
}
