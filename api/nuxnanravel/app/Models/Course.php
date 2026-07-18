<?php

namespace App\Models;

use App\Enums\CourseLifecycleState;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Course extends Model
{
    use Auditable, HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * ใช้ whitelist แบบ $fillable (ไม่ใช่ $guarded = []) เพื่อป้องกัน
     * mass assignment attack — ฟิลด์ใดที่ไม่อยู่ในลิสต์นี้จะตั้งค่าผ่าน
     * $model->update($request->all()) ไม่ได้
     */
    protected $fillable = [
        // Relations / ownership
        'user_id',
        'instructor_id',
        'academy_id',
        'creator_id',
        'category_id',

        // Core info
        'name',
        'slug',
        'code',
        'description',
        'category',
        'level',
        'education_level',
        'education_year',
        'language',

        // Schedule
        'duration',
        'start_date',
        'end_date',
        'class_schedule',
        'semester',
        'academic_year',
        'hours_per_week',

        // Commerce
        'tuition_fees',
        'price',
        'discount',
        'discount_type',
        'points',
        'saleable',

        // Capacity / counts
        'credit_units',
        'capacity',
        'enrolled_students',
        'lessons',
        'assignments',
        'quizzes',
        'groups',

        // Content
        'prerequisites',
        'course_materials',
        'syllabus',

        // Media
        'cover',
        'logo',
        'cover_header',
        'cover_subheader',

        // Accreditation / certificate
        'accreditation',
        'accreditation_body',
        'certificate',
        'certificate_download_cost',
        'certificate_free_download',

        // Status
        'status',
        'rating',
        'total_score',

        // Attendance & exam eligibility
        'max_absence_percent',
        'allow_unlock_by_points',
        'unlock_points_cost',
        'allow_unlock_by_reading',
        'unlock_reading_minutes',
        'unlock_reading_mode',
        'unlock_reading_lesson_count',
        'unlock_reading_lesson_ids',
        'min_sessions_for_eligibility_check',
        'allow_unlock_by_appeal',
        'allow_self_unlock',

        // Finalization
        'finalization_status',
        'finalized_at',
        'finalized_by',

        // Remediation / appeal
        'allow_remediation',
        'max_remediation_attempts',
        'remediation_max_grade',
        'allow_grade_appeal',
        'appeal_deadline_days',

        // Marketplace
        'is_for_marketplace',
        'price_points',
        'price_type',
        'total_sales',
        'source_course_id',
        'donation_enabled',
    ];

    protected $casts = [
        'price' => 'integer',
        'credit_units' => 'integer',
        'duration' => 'integer',
        'tuition_fees' => 'integer',
        'hours_per_week' => 'integer',
        'capacity' => 'integer',
        'enrolled_students' => 'integer',
        'rating' => 'integer',
        'discount' => 'integer',
        'certificate_download_cost' => 'decimal:2',
        'certificate_free_download' => 'boolean',
        'is_for_marketplace' => 'boolean',
        'price_points' => 'integer',
        'education_year' => 'integer',
        'total_sales' => 'integer',
        'source_course_id' => 'integer',
        'max_absence_percent' => 'integer',
        'min_sessions_for_eligibility_check' => 'integer',
        'allow_unlock_by_appeal' => 'boolean',
        'allow_unlock_by_points' => 'boolean',
        'unlock_points_cost' => 'integer',
        'allow_unlock_by_reading' => 'boolean',
        'unlock_reading_minutes' => 'integer',
        'unlock_reading_mode' => 'string',
        'unlock_reading_lesson_count' => 'integer',
        'unlock_reading_lesson_ids' => 'array',
        'allow_self_unlock' => 'boolean',
        'end_date' => 'datetime',
        'donation_enabled' => 'boolean',
    ];

    public function donationEnabled(): bool
    {
        if ($this->donation_enabled !== null) {
            return (bool) $this->donation_enabled;
        }

        return (bool) config('platform.course_donation.enabled', true);
    }

    /**
     * Derived lifecycle state for this course.
     *
     * Precedence:
     *   1. finalization_status = archived  -> Archived
     *   2. finalization_status = finalized -> Finalized
     *   3. finalization_status = published -> Published
     *   4. finalization_status = grading   -> Grading
     *   5. status = 2 (Draft)              -> Draft
     *   6. status = 4 (Closed) OR end_date in past -> EnrollmentClosed
     *   7. otherwise                       -> Active
     *
     * Note: finalization_status is source-of-truth; courses.status is a manual
     * instructor override that only takes effect when finalization_status is
     * not_started.
     */
    public function lifecycleState(): CourseLifecycleState
    {
        $fin = $this->finalization_status;

        if ($fin === 'archived') {
            return CourseLifecycleState::Archived;
        }
        if ($fin === 'finalized') {
            return CourseLifecycleState::Finalized;
        }
        if ($fin === 'published') {
            return CourseLifecycleState::Published;
        }
        if ($fin === 'grading') {
            return CourseLifecycleState::Grading;
        }

        if ((int) $this->status === 2) {
            return CourseLifecycleState::Draft;
        }

        if ((int) $this->status === 4 || ($this->end_date && $this->end_date->isPast())) {
            return CourseLifecycleState::EnrollmentClosed;
        }

        return CourseLifecycleState::Active;
    }

    protected function isEnrollmentOpen(): Attribute
    {
        return Attribute::get(fn () => $this->lifecycleState()->isEnrollmentOpen());
    }

    protected function isLearningActive(): Attribute
    {
        return Attribute::get(fn () => $this->lifecycleState()->isLearningActive());
    }

    protected function isPostEnd(): Attribute
    {
        return Attribute::get(fn () => $this->lifecycleState()->isPostEnd());
    }

    public function courseSettings(): HasOne
    {
        return $this->hasOne(CourseSetting::class, 'course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'source_course_id');
    }

    public function clonedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'source_course_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(CoursePurchase::class, 'source_course_id');
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function courseDonates(): HasMany
    {
        return $this->hasMany(CourseDonate::class);
    }

    public function pointCampaigns(): HasMany
    {
        return $this->hasMany(CoursePointCampaign::class);
    }

    public function scopeForMarketplace($query)
    {
        return $query->where('is_for_marketplace', true);
    }

    public function scopeCloned($query)
    {
        return $query->whereNotNull('source_course_id');
    }

    public function scopeOriginal($query)
    {
        return $query->whereNull('source_course_id');
    }

    public function course_lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order')->orderBy('id');
    }

    /**
     * Get all of the courseLessons for the Course
     */
    public function courseLessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order')->orderBy('id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_members', 'course_id', 'user_id');
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'course_favorites', 'course_id', 'user_id')->withTimestamps();
    }

    public function getIsFavoritedAttribute()
    {
        if (! auth('api')->check()) {
            return false;
        }

        return $this->favorites()->where('user_id', auth('api')->id())->exists();
    }

    public function courseMembers(): HasMany
    {
        return $this->hasMany(CourseMember::class);
    }

    public function courseExternalScores(): HasMany
    {
        return $this->hasMany(CourseExternalScore::class);
    }

    public function isMember(?User $user)
    {
        if (! $user) {
            return false;
        }

        return $this->members->contains($user);
    }

    public function member_status($id)
    {
        $userId = auth()->guard('api')->id() ?? auth()->id();

        return CourseMember::where('user_id', $userId)->where('course_id', $id)->pluck('course_member_status')->first();
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignmentable');
    }

    public function courseAssignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignmentable');
    }

    public function questions(): MorphMany
    {
        return $this->morphMany(Question::class, 'questionable');
    }

    public function courseGroups(): HasMany
    {
        return $this->hasMany(CourseGroup::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get all of the courseQuizzez for the Course
     */
    public function courseQuizzes(): HasMany
    {
        return $this->hasMany(CourseQuiz::class);
    }

    /**
     * Get all of the courseQuizResult for the CourseQuiz
     */
    public function courseQuizResults(): HasMany
    {
        return $this->hasMany(CourseQuizResult::class);
    }

    public function gradeAppeals(): HasMany
    {
        return $this->hasMany(GradeAppeal::class);
    }

    public function remediationSessions(): HasMany
    {
        return $this->hasMany(CourseRemediationSession::class);
    }

    public function courseAttendances(): HasMany
    {
        return $this->hasMany(CourseAttendance::class);
    }

    public function getCoverUrlAttribute()
    {
        if (! $this->cover) {
            return null;
        }
        if (filter_var($this->cover, FILTER_VALIDATE_URL)) {
            return $this->cover;
        }

        return asset('storage/images/courses/covers/'.$this->cover);
    }

    public function getLogoUrlAttribute()
    {
        if (! $this->logo) {
            return null;
        }
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        return asset('storage/images/courses/logos/'.$this->logo);
    }

    /**
     * Get all reviews for the course.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    /**
     * Get the count of approved reviews.
     */
    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->where('is_approved', true)->count();
    }

    /**
     * Get the average rating from approved reviews.
     */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->where('is_approved', true)->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * Check if the given user is an admin of the course.
     * Returns true if user is the owner OR is a member with role 4 (Admin).
     */
    public function isAdmin($user)
    {
        if (! $user) {
            return false;
        }

        // Super Admin has full control
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        // Check if member exists, has role 4 (Admin) and is active (status 1)
        // using existence check for efficiency
        return $this->courseMembers()
            ->where('user_id', $user->id)
            ->where('role', 4)
            ->where('status', 1)
            ->exists();
    }

    /**
     * Check if the given user has a specific permission for this course.
     */
    public function hasPermission($user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        // Course owner has all permissions
        if ($this->user_id === $user->id) {
            return true;
        }

        // Find the member's course membership
        $member = $this->courseMembers()
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (! $member) {
            return false;
        }

        return $member->hasPermission($permission);
    }

    /**
     * Get the course member record for a user.
     */
    public function getMember($user)
    {
        if (! $user) {
            return null;
        }

        return $this->courseMembers()
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();
    }

    /**
     * Check if the given user already has a clone of this course.
     */
    public function isOwnedBy(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        return $user->courses()
            ->where('source_course_id', $this->id)
            ->exists();
    }
}
