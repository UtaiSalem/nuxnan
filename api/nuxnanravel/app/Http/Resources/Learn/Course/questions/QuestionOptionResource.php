<?php

namespace App\Http\Resources\Learn\Course\questions;

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
        return [
            'id' => $this->id,
            'optionable_id' => $this->optionable_id,
            'optionable_type' => $this->optionable_type,
            'text' => $this->text,
            'is_correct' => $this->when(
                auth()->check() && $this->optionable && $this->optionable->user_id === auth()->id(),
                $this->is_correct
            ),
            'explanation' => $this->explanation,
            'position' => $this->position,
            'status' => $this->status,
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
