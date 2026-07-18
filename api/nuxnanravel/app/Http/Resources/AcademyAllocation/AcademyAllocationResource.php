<?php

namespace App\Http\Resources\AcademyAllocation;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyAllocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'amount' => $this->amount, 'balance_before' => $this->balance_before, 'balance_after' => $this->balance_after, 'course_id' => ($courseId = data_get($this->metadata, 'course_id')), 'course' => ['name' => optional(Course::find($courseId))->name], 'purpose' => data_get($this->metadata, 'purpose'), 'performer' => ['name' => optional(User::find($this->user_id))->name], 'created_at' => $this->created_at, 'related_course_point_transaction_id' => $this->related_course_point_transaction_id];
    }
}
