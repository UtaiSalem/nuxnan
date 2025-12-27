<?php

namespace App\Models;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Academy;
use App\Models\Question;
use App\Models\Assignment;
use App\Models\CourseQuiz;
use App\Models\CourseSetting;
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
        'enrolled_students' => 'integer',
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
}
