<?php

namespace App\Http\Resources\Learn\Academy\Enrollment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentImportRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_number' => $this->row_number,
            'status' => $this->status,
            'raw_data' => $this->raw_data,
            'normalized_data' => $this->normalized_data,
            'errors' => $this->errors ?? [],
            'warnings' => $this->warnings ?? [],
            'student_id' => $this->student_id,
        ];
    }
}
