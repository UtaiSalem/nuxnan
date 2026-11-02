<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    // append is_published to the model
    protected $appends = ['is_published'];

    protected $casts = [
        'due_date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_group_assignment' => 'boolean',
        'target_groups' => 'array',
        'status' => 'integer',
        'increase_points' => 'integer',
        'decrease_points' => 'integer',
        'points' => 'integer',
        'passing_score' => 'integer',
        'graded_score' => 'integer',
    ];

    public function assignmentable()
    {
        return $this->morphTo();
    }

    public function images(): HasMany
    {
        return $this->hasMany(AssignmentImage::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssignmentAnswer::class);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 1;
    }

    /**
     * Resolve the lesson this assignment belongs to (directly or through topic)
     *
     * assignmentable เป็น morphTo — บทเรียน/หัวข้อปลายทางอาจถูกลบไปแล้ว
     * ก่อนหน้านี้สาขา Topic เรียก ->lesson บน null ตรง ๆ ทำให้ทุก endpoint
     * ที่เรียกเมธอดนี้ตอบ 500 (resolveCourse, ContentVisibilityService ฯลฯ)
     * ผู้เรียกทั้ง 9 จุดรับ null ได้อยู่แล้ว จึงคืน null แทนการระเบิด
     */
    public function getLesson(): ?Lesson
    {
        if ($this->assignmentable_type === Lesson::class) {
            return $this->assignmentable;
        }

        if ($this->assignmentable_type === Topic::class) {
            return $this->assignmentable?->lesson;
        }

        return null;
    }

    /**
     * คอร์สที่ assignment นี้สังกัด
     *
     * assignments ไม่มีคอลัมน์ course_id — ผูกกับคอร์สผ่าน assignmentable (morphTo)
     * ซึ่งปลายทางเป็น Course / Lesson / Topic อย่างใดอย่างหนึ่ง จึงทำเป็น belongsTo ไม่ได้
     * เมธอดนี้กับ scopeInAcademy()/scopeTaughtBy() ต้องเดินเส้นทางเดียวกันเสมอ
     */
    public function getCourse(): ?Course
    {
        if ($this->assignmentable_type === Course::class) {
            return $this->assignmentable;
        }

        return $this->getLesson()?->course;
    }

    /**
     * จำกัดเฉพาะงานที่อยู่ในคอร์สของโรงเรียนนี้
     *
     * ต้องครบทั้ง 3 morph type — ถ้าใส่แค่ Lesson::class ผลจะเป็น 0 เงียบ ๆ
     * เพราะงานผูกกับ Course โดยตรงเป็นหลัก (DB dev: 26/26 แถวเป็น Course)
     */
    public function scopeInAcademy(Builder $query, Academy $academy): Builder
    {
        return $this->applyCourseConstraint($query, function ($courseQuery) use ($academy) {
            $courseQuery->where('academy_id', $academy->id);
        });
    }

    /**
     * จำกัดเฉพาะงานในคอร์สที่ผู้ใช้คนนี้สอน
     *
     * "สอน" = เจ้าของคอร์ส / ผู้ดูแลคอร์ส role 4 (Course::scopeAdministeredBy)
     * หรือครูผู้สอนที่ระบุไว้ที่ courses.instructor_id
     * assignments ไม่มีคอลัมน์ teacher_id — ความเป็น "ครูของงานนี้" มาจากคอร์สเท่านั้น
     */
    public function scopeTaughtBy(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // super admin ดูแลทุกคอร์สตาม administeredBy() — ถ้าปล่อยลงไปถึง orWhere ข้างล่าง
        // กลุ่มเงื่อนไขจะเหลือแค่ instructor_id แล้วกลายเป็นการจำกัดแทน
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $this->applyCourseConstraint($query, function ($courseQuery) use ($user) {
            $courseQuery->where(function ($q) use ($user) {
                $q->administeredBy($user)->orWhere('instructor_id', $user->id);
            });
        });
    }

    /**
     * ใช้เงื่อนไขกับคอร์สปลายทางของ assignment ไม่ว่าจะ morph ไปที่ type ใด
     */
    protected function applyCourseConstraint(Builder $query, callable $constraint): Builder
    {
        return $query->whereHasMorph(
            'assignmentable',
            [Course::class, Lesson::class, Topic::class],
            function ($q, $type) use ($constraint) {
                if ($type === Course::class) {
                    $constraint($q);

                    return;
                }

                // Lesson มี course_id ตรง ๆ ส่วน Topic เดินผ่าน lesson ให้ตรงกับ getLesson()
                $path = $type === Lesson::class ? 'course' : 'lesson.course';

                $q->whereHas($path, $constraint);
            }
        );
    }
}
