<?php

namespace App\Http\Resources\Learn\Academy;

use App\Models\Academy;
use Illuminate\Http\Request;
use App\Http\Resources\AcademyResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'user_id' => $this->user_id,
            'student_id' => $this->student_id,
            'member_code' => $this->member_code,
            'role' => $this->role,
            'status' => $this->status,
            'member_name' => $this->member_name,
            'member_avatar' => $this->member_avatar,
            'user' => $this->whenLoaded('user'),
            'student' => $this->whenLoaded('student'),
            'academy' => new AcademyResource($this->whenLoaded('academy')),
        ];
    }
}
