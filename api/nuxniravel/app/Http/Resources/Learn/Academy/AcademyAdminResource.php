<?php

namespace App\Http\Resources\Learn\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\Learn\Academy\AcademyResource;

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
            'user'      => new UserResource($this->user),
            'academy'   => new AcademyResource($this->academy),
        ];
    }
}
