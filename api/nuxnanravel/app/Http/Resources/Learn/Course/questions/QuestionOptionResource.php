<?php

namespace App\Http\Resources\Learn\Course\questions;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Answer-key fields (which option is right, and the per-option
        // explanation that usually says "correct because…") go to the
        // question's author and the course admins only — never learners.
        $optionable = $this->optionable;
        $canSeeKey = $optionable instanceof Question && QuestionResource::canSeeAnswerKey($optionable);

        return [
            'id' => $this->id,
            'optionable_id' => $this->optionable_id,
            'optionable_type' => $this->optionable_type,
            'text' => $this->text,
            'is_correct' => $this->when($canSeeKey, $this->is_correct),
            'explanation' => $this->when($canSeeKey, $this->explanation),
            'position' => $this->position,
            'status' => $this->status,
            'images' => $this->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => $img->url,
                    'full_url' => $img->full_url,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
