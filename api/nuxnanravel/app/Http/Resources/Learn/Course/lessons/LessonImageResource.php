<?php

namespace App\Http\Resources\Learn\Course\lessons;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonImageResource extends JsonResource
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
            'lesson_id' => $this->lesson_id,
            'filename' => $this->filename,
            'url' => $this->full_url,
            'full_url' => $this->full_url,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
