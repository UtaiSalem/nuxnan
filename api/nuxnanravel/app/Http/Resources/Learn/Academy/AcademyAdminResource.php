<?php

namespace App\Http\Resources\Learn\Academy;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => $this->user ? new UserResource($this->user) : null,
            'academy' => $this->academy ? new AcademyResource($this->academy) : null,
        ];
    }
}
