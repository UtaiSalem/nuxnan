<?php

namespace App\Http\Resources\Learn\Course\members;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class CourseMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    =>  $this->id,
            'user_id'               =>  $this->user_id,
            'course_id'             =>  $this->course_id,
            'user'                  =>  new UserResource($this->user),
            'member_name'           =>  $this->member_name,
            'member_email'          =>  $this->member_email,
            'group'                 =>  $this->group,
            'access_expiry_date'    =>  $this->access_expiry_date,
            'completion_date'       =>  $this->completion_date,
            'enrollment_date'       =>  $this->enrollment_date,
            'grade_progress'        =>  $this->grade_progress,
            'group_id'              =>  $this->group_id,
            'achieved_score'        =>  $this->achieved_score,
            'bonus_points'          =>  $this->bonus_points,
            'notes_comments'        =>  $this->notes_comments,
            'order_number'          =>  $this->order_number,
            'member_code'           =>  $this->member_code,
            'role'                  =>  $this->role,
            'status'                =>  $this->course_member_status,
            'course_member_status'  =>  $this->course_member_status,
            'group_member_status'   =>  $this->group_member_status,
			'last_accessed_tab'     =>  $this->last_accessed_tab ?? 0,
            'last_accessed_group_tab' =>  $this->last_accessed_group_tab ?? 0,
            'last_accessed_at'      =>  $this->last_accessed_at,
            'updated_at'            =>  $this->updated_at,
            'created_at'            =>  $this->created_at,
            'grade_name'            =>  $this->getGradeName(),
        ];
    }
}
