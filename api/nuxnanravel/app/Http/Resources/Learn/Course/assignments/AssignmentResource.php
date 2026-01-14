<?php

namespace App\Http\Resources\Learn\Course\assignments;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\assignments\AssignmentAnswerResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $courseUserId = $this->assignmentable->user_id;
        $isCreator = false;
        
        if ($this->assignmentable_type === 'App\Models\Lesson') {
             // Check if user is lesson creator OR course creator
             if ($this->assignmentable->user_id === auth()->id() || $this->assignmentable->course->user_id === auth()->id()) {
                 $isCreator = true;
             }
        } elseif ($this->assignmentable_type === 'App\Models\Course') {
             if ($this->assignmentable->user_id === auth()->id()) {
                 $isCreator = true;
             }
        }

        if ($isCreator) {
            $answers = []; // Optimization: Instructors fetch answers via separate endpoint
        }else{
            $answers = $this->answers->where('user_id', auth()->id())->values();
        }

        return [
            'id'                    => $this->id,
            'assignmentable'        => $courseUserId,
            'assignmentable_id'     => $this->assignmentable_id,
            'assignmentable_type'   => $this->assignmentable_type,
            'title'                 => $this->title,
            'description'           => $this->description,
            'images'                => $this->images,
            'answers'               => AssignmentAnswerResource::collection($answers),
            'due_date'              => $this->due_date ? Carbon::parse($this->due_date)->toIso8601String() : null,
            'start_date'            => $this->start_date ? Carbon::parse($this->start_date)->toIso8601String() : null,
            'end_date'              => $this->end_date ? Carbon::parse($this->end_date)->toIso8601String() : null,
            'points'                => $this->points,
            'passing_score'         => $this->passing_score,
            'increase_points'       => $this->increase_points,
            'decrease_points'       => $this->decrease_points,
            'assignment_type'       => $this->assignment_type,
            'submission_method'     => $this->submission_method,
            'max_file_size'         => $this->max_file_size,
            'is_group_assignment'   => $this->is_group_assignment,
            // 'target_groups'         => collect(explode(",", $this->target_groups))->map(function($item){
            //                                 return (int) $item;
            //                             }),
            // 'target_groups'         => explode(",", $this->target_groups),
            // 'target_groups'         => $this->target_groups,
            'target_groups'         => $this->target_groups ? array_map('intval', $this->target_groups): [],
            'is_published'          => $this->is_published,
            'status'                => $this->status,
            'grading_rubric'        => $this->grading_rubric,
            'graded_score'          => $this->graded_score,
            'feedback'              => $this->feedback,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
