<?php

namespace App\Http\Resources\RevenueSharePolicy;

use App\Models\RevenueSharePolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevenueSharePolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'scope_type' => $this->scope_type, 'scope_id' => $this->scope_id, 'student_pct' => $this->student_pct, 'course_pct' => $this->course_pct, 'platform_pct' => $this->platform_pct, 'effective_from' => $this->effective_from, 'effective_to' => $this->effective_to, 'version' => $this->version, 'notes' => $this->notes, 'created_by' => $this->created_by, 'created_by_name' => $this->creator?->name, 'is_active' => RevenueSharePolicy::query()->active(now())->whereKey($this->id)->exists()];
    }
}
