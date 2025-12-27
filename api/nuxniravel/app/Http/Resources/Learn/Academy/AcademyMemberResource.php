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
           'academy' => Academy::find($this->academy_id)
        //    'academy' => $this->academies
        ];
    }
}
