<?php

namespace App\Http\Resources\Learn\Course\info;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\Learn\Academy\AcademyResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceCourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUserId = auth()->guard('api')->id();

        // Optimized enrollment data from eager-loaded courseMembers
        $enrollment = $this->courseMembers->where('user_id', $currentUserId)->first();

        return [
            'id'                => $this->id,
            'user'              => $this->user ? new UserResource($this->user) : null,
            'academy'           => $this->academy ? new AcademyResource($this->academy) : null,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'code'              => $this->code,
            'semester'          => $this->semester,
            'academic_year'     => $this->academic_year,
            'description'       => $this->description,
            'logo'              => $this->logo_url,
            'cover'             => $this->cover_url,
            'price'             => $this->price,
            'discount'          => $this->discount,
            'category'          => $this->category,
            'level'             => $this->level,
            'education_level'   => $this->education_level,
            'education_year'    => $this->education_year,
            'rating'            => $this->rating,
            
            // Live count from withCount(['courseMembers as enrolled_students_count' => ...])
            'enrolled_students' => $this->enrolled_students_count ?? 0,
            
            // From withCount(['courseLessons', 'courseAssignments', 'courseQuizzes'])
            'lessons_count'     => $this->course_lessons_count ?? 0,
            'assignments_count' => $this->course_assignments_count ?? 0,
            'quizzes_count'     => $this->course_quizzes_count ?? 0,

            // Optimized checks (No N+1)
            'is_favorited'      => $this->favorites->isNotEmpty(),
            
            'enrollment_status' => [
                'is_member'   => (bool) ($enrollment && $enrollment->status === 1),
                'status'      => $enrollment ? match((int)$enrollment->course_member_status) {
                    1 => 'active',
                    0 => 'pending',
                    default => 'unknown',
                } : null,
                'enrolled_at' => $enrollment?->enrollment_date?->toDateString(),
            ],

            'saleable'           => (bool) $this->saleable,
            'is_for_marketplace' => (bool) $this->is_for_marketplace,
            'total_sales'        => $this->total_sales,
        ];
    }
}
