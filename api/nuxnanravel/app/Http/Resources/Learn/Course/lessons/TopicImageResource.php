<?php

namespace App\Http\Resources\Learn\Course\lessons;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'filename' => $this->filename,
            'url'      => $this->image_url,
            'full_url' => $this->image_url,
        ];
    }
}
