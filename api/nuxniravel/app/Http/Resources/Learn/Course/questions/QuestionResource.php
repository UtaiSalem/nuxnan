<?php

namespace App\Http\Resources\Learn\Course;

use Illuminate\Http\Request;
use App\Models\UserAnswerQuestion;
use App\Http\Resources\UserResource;
use App\Http\Resources\QuestionOptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authAnswerQuestion = UserAnswerQuestion::where('question_id', $this->id)->where('user_id', auth()->id())->first();

        return [
            'id'                    => $this->id,
            'creator'               => new UserResource($this->user),
            'questionable_id'       => $this->questionable_id,
            'questionable_type'     => $this->questionable_type,
            'text'                  => $this->text,
            'type'                  => $this->type,
            'options'               => QuestionOptionResource::collection($this->options), // Assuming it's a JSON field
            'correct_option_id'     => $this->correct_option_id,
            'explanation'           => $this->explanation,
            'difficulty_level'      => $this->difficulty_level,
            'time_limit'            => $this->time_limit,
            'points'                => $this->points,
            'pp_fine'               => $this->pp_fine,
            'position'              => $this->position,
            'tags'                  => $this->tags,
            'images'                => $this->images,
            'authAnswerQuestion'    => $authAnswerQuestion ? $authAnswerQuestion->id : null,
            'isAnsweredByAuth'      => $authAnswerQuestion ? $authAnswerQuestion->answer_id : null,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
