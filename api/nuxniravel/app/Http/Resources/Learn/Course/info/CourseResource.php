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
            'user'              => new UserResource($this->user),
            'academy'           => new AcademyResource($this->academy),
            'name'              => $this->name,
            'slug'              => $this->slug,
            'code'              => $this->code,
            'description'       => $this->description,
            'logo'              => $this->logo,
            'cover'             => $this->cover,
            'duration'          => $this->duration,
            'start_date'        => $this->start_date,
            'end_date'          => $this->end_date,
            // 'start_date'        => Carbon::parse($this->start_date)->toDateTimeString(),
            // 'end_date'          => Carbon::parse($this->end_date)->toDateTimeString(),
            'tuition_fees'      => $this->tuition_fees,
            'price'             => $this->price,
            'discount'          => $this->discount,
            'points'            => $this->points,
            'credit_units'      => $this->credit_units,
            'hours_per_week'    => $this->hours_per_week,
            'category'          => $this->category,
            'instructor'        => $this->instructor,
            'capacity'          => $this->capacity,
            'enrolled_students' => $this->enrolled_students,
            'lessons'           => $this->lessons,
            'assignments'       => $this->assignments,
            'quizzes'           => $this->quizzes,
            'groups'            => $this->groups,
            'class_schedule'    => $this->class_schedule,
            'prerequisites'     => $this->prerequisites,
            'course_materials'  => $this->course_materials,
            'status'            => $this->status,
            'location'          => $this->location,
            'accreditation'     => $this->accreditation,
            'accreditation_body'=> $this->accreditation_body,
            'level'             => $this->level,
            'rating'            => $this->rating,
            'syllabus'          => $this->syllabus,
            'certificate'       => $this->certificate,
            'isMember'          => $this->isMember(auth()->user()),
            'member_status'     => $this->member_status($this->id), //Course member status
            // 'member_details'    => $this->member_details($this->id),
            'lessons_count'     => $this->lessons,
            'total_score'       => $this->total_score,
            'setting'           => $this->courseSettings,
            'saleable'          => $this->saleable,
            // 'member_detail'     => new CourseMemberResource(),
            // 'assignments'       => AssignmentResource::collection($this->assignments),
        ];
    }
}
