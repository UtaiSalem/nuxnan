<?php

namespace App\Http\Resources\Learn\Course\info;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\Learn\Course\members\CourseMemberResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'user'              => $this->user ? new UserResource($this->user) : null,
            'academy'           => $this->academy ? new AcademyResource($this->academy) : null,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'code'              => $this->code,
            'description'       => $this->description,
            'logo'              => $this->logo_url,
            'cover'             => $this->cover_url,
            'header'            => $this->cover_header,
            'subheader'         => $this->cover_subheader,
            'duration'          => $this->duration,
            'start_date'        => $this->start_date,
            'end_date'          => $this->end_date,
            'tuition_fees'      => $this->tuition_fees,
            'price'             => $this->price,
            'discount'          => $this->discount,
            'points'            => $this->points,
            'credit_units'      => $this->credit_units,
            'hours_per_week'    => $this->hours_per_week,
            'semester'          => $this->semester,
            'academic_year'     => $this->academic_year,
            'category'          => $this->category,
            'instructor'        => $this->instructor,
            'capacity'          => $this->capacity,
            'enrolled_students' => $this->course_members_count ?? $this->enrolled_students,
            'lessons'           => $this->course_lessons_count ?? $this->lessons,
            'assignments'       => $this->whenLoaded('courseAssignments', function() {
                return AssignmentResource::collection($this->courseAssignments);
            }),
            'assignments_count' => $this->course_assignments_count ?? $this->assignments,
            'quizzes'           => $this->quizzes,
            'quizzes_count'     => $this->course_quizzes_count ?? $this->quizzes,
            'groups'            => $this->groups,
            'class_schedule'    => $this->class_schedule,
            'prerequisites'     => $this->prerequisites,
            'course_materials'  => $this->course_materials,
            'status'            => $this->status,
            'location'          => $this->location,
            'is_favorited'      => $this->when(isset($this->favorites), function() {
                return $this->favorites->isNotEmpty();
            }, $this->is_favorited),
            'accreditation'     => $this->accreditation,
            'accreditation_body'=> $this->accreditation_body,
            'education_level'   => $this->education_level,
            'education_year'    => $this->education_year,
            'rating'            => $this->rating,
            'syllabus'          => $this->syllabus,
            'certificate'       => $this->certificate,
            'isMember'          => $this->when(isset($this->courseMembers), function() {
                return $this->courseMembers->where('user_id', auth()->guard('api')->id())->where('status', 1)->isNotEmpty();
            }, $this->isMember(auth()->guard('api')->user())),
            'member_status'     => $this->when(isset($this->courseMembers), function() {
                $member = $this->courseMembers->where('user_id', auth()->guard('api')->id())->first();
                return $member?->course_member_status;
            }, $this->member_status($this->id)), //Course member status
            'lessons_count'          => $this->course_lessons_count ?? $this->lessons,
            'course_lessons_count'   => $this->course_lessons_count,
            'isCourseAdmin'     => $this->isAdmin(auth()->guard('api')->user()),
            'total_score'       => $this->total_score,
            'setting'           => $this->courseSettings,
            'auth_role'         => $this->when(auth()->guard('api')->check(), function() {
                if ($this->user_id == auth()->guard('api')->id()) {
                    return 4;
                }
                $member = $this->courseMembers->where('user_id', auth()->guard('api')->id())->first();
                return $member?->role;
            }),
            'auth_progress'     => $this->when(auth()->guard('api')->check(), function() {
                $member = $this->courseMembers->where('user_id', auth()->guard('api')->id())->first();
                return $member?->getPercentageScore() ?? 0;
            }),
            'saleable'          => $this->saleable,
            'is_for_marketplace' => $this->is_for_marketplace,
            'price_points'      => $this->price_points,
            'price_type'        => $this->price_type,
            'total_sales'       => $this->total_sales,
            'is_owned'          => $this->when(auth()->guard('api')->check(), function() {
                $user = auth()->guard('api')->user();
                if ($this->user_id === $user->id) return false;
                return $user->courses()
                    ->where('source_course_id', $this->id)
                    ->exists();
            }),
            'enrollment_status' => $this->when(auth()->guard('api')->check(), function () {
                $member = $this->courseMembers
                    ->where('user_id', auth()->guard('api')->id())
                    ->first();
                return $member
                    ? [
                        'is_member'   => true,
                        'status'      => match((int) $member->course_member_status) {
                            1 => 'active',
                            0 => 'pending',
                            default => 'unknown',
                        },
                        'enrolled_at' => $member->enrollment_date,
                    ]
                    : ['is_member' => false, 'status' => null, 'enrolled_at' => null];
            }),
            'pending_invitation' => $this->when(auth()->guard('api')->check(), function() {
                return \App\Models\CourseInvitation::where('course_id', $this->id)
                    ->where('invitee_id', auth()->guard('api')->id())
                    ->where('status', 'pending')
                    ->first();
            }),
        ];
    }
}
