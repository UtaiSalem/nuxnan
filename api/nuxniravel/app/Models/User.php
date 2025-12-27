<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Academy;
use App\Models\Support;
use App\Models\Activity;
use App\Models\CourseQuiz;
use App\Models\Friendship;
use App\Models\LessonLike;
use App\Models\CourseGroup;
use App\Models\PostComment;
use App\Models\UserProfile;
use App\Models\PlearndAdmin;
use App\Models\AcademyMember;
use App\Models\LessonComment;
use App\Models\LessonDislike;
use App\Models\AssignmentAnswer;
use App\Models\CourseQuizResult;
use App\Models\CourseGroupMember;
use App\Models\UserAnswerQuestion;

use Illuminate\Support\Str;
// use Laravel\Sanctum\HasApiTokens;

// use App\Models\UserActivity;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    // use HasApiTokens;
    use \Multicaret\Acquaintances\Traits\Friendable;

    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'google_id',
        'facebook_id',
        'twitter_id',
        'linkedin_id',
        'github_id',
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
            'verified'          => 'boolean',
            'email_verified_at' => 'datetime',
            'created_at'        => 'datetime:Y-m-d H:i:s',
            'updated_at'        => 'datetime:Y-m-d H:i:s',
            'phone_verified_at' => 'datetime',
            'password'          => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            // 'no_of_ref' => 'integer',
            // 'pp' => 'decimal:2',
            // 'wallet' => 'decimal:2',
        ]; 
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'connected_providers',
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

    //get referal link
    public function getReferalLinkAttribute()
    {
        return env('APP_URL') . '/register/' . $this->reference_code;
    }

    //get profile photo url
    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path;
    }

    /**
     * Get connected OAuth providers
     */
    public function getConnectedProvidersAttribute()
    {
        $providers = [];
        $oauthProviders = ['google', 'facebook', 'twitter', 'github', 'linkedin'];
        
        foreach ($oauthProviders as $provider) {
            $field = $provider . '_id';
            if (!empty($this->$field)) {
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
        $field = $provider . '_id';
        return !empty($this->$field);
    }

    /**
     * Get all of the activities for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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

    public function academyAdmins(): BelongsToMany
    {
        return $this->belongsToMany(Academy::class, 'academy_admins', 'user_id', 'academy_id');
    }

    public function academyPosts(): HasMany
    {
        return $this->hasMany(AcademyPost::class);
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

    public function supports(): HasMany
    {
        return $this->hasMany(Support::class);
    }

    public function supportViewers(): HasMany
    {
        return $this->hasMany(SupportViewer::class);
    }

    public function isPlearndAdmin(): bool
    {
        return PlearndAdmin::where('user_id', 1)->exists() && $this->hasVerifiedEmail();
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
        return date('dd-mm-Y H:i:s', strtotime($value));
    }

    public function memberAcademies(): HasMany
    {
        return $this->hasMany(AcademyMember::class, 'user_id');
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
        if(Auth::check()){
            $authUser = auth()->user();
            $friend = User::find($friendId);
            if ($friend) { 
                if ($authUser->isFriendWith($friend)) {
                    return 1; 
                    //accepted
                } elseif ($authUser->hasSentFriendRequestTo($friend)) {
                    return 0; 
                    //pending
                } else {
                    return null; 
                    //not friend
                }
            }else {
                return null;
            }
        }else {
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

}
