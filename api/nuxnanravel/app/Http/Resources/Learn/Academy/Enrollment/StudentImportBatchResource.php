<?php

namespace App\Http\Resources\Learn\Academy\Enrollment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentImportBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'academic_year_id' => $this->academic_year_id,
            'original_filename' => $this->original_filename,
            'status' => $this->status,
            'total_rows' => $this->total_rows,
            'valid_rows' => $this->valid_rows,
            'invalid_rows' => $this->invalid_rows,
            'imported_rows' => $this->imported_rows,
            'skipped_rows' => $this->skipped_rows,
            'defaults' => $this->default_values,
            'created_by' => $this->created_by,
            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
