<?php

namespace App\Models;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Academy;
use App\Models\Question;
use App\Models\Assignment;
use App\Models\CourseQuiz;
use App\Models\CourseSetting;
use App\Models\CourseReview;
use App\Models\CourseAttendance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

    protected $guarded = [];
    // protected $fillable = ['title', 'description', 'instructor_id', 'duration', 'price', 'category', 'level', 'language', 'start_date', 'end_date', 'enrolled_students', 'rating', 'image', 'syllabus', 'prerequisites', 'certificate'];
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
    ];

    public function courseSettings(): HasOne
    {
        return $this->hasOne(CourseSetting::class, 'course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function course_lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get all of the courseLessons for the Course
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courseLessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
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
        if (!auth('api')->check()) {
            return false;
        }
        return $this->favorites()->where('user_id', auth('api')->id())->exists();
    }

    public function courseMembers(): HasMany
    {
        return $this->hasMany(CourseMember::class);
    }

    public function isMember(User $user)
    {
        return $this->members->contains($user);
    }

    public function member_status($id)
    {
        // return CourseMember::where('user_id', auth()->id())->where('course_id', $id)->pluck('member_status')->first();
        return CourseMember::where('user_id', auth()->id())->where('course_id', $id)->pluck('course_member_status')->first();
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
        return $this->hasMany(CourseGroup::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get all of the courseQuizzez for the Course
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courseQuizzes(): HasMany
    {
        return $this->hasMany(CourseQuiz::class);
    }
    
    /**
     * Get all of the courseQuizResult for the CourseQuiz
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courseQuizResults(): HasMany
    {
        return $this->hasMany(CourseQuizResult::class);
    }

    public function courseAttendances(): HasMany
    {
        return $this->hasMany(CourseAttendance::class);
    }

    public function getCoverUrlAttribute()
    {
        if (!$this->cover) {
            return null;
        }
        if (filter_var($this->cover, FILTER_VALIDATE_URL)) {
            return $this->cover;
        }
        return asset('storage/images/courses/covers/' . $this->cover);
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return null;
        }
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }
        return asset('storage/images/courses/logos/' . $this->logo);
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
        if (!$user) {
            return false;
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
}
