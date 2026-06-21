<?php

namespace App\Http\Resources\Learn\Academy\Enrollment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolloverBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $academy = $this->relationLoaded('academy') ? $this->academy : null;
        $canSeeSensitive = $academy !== null
            ? ($request->user()?->can('enrollment.commit', $academy) ?? false)
            : false;

        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'from_year' => $this->whenLoaded('fromAcademicYear', fn () => [
                'id' => $this->fromAcademicYear->id,
                'name' => $this->fromAcademicYear->name,
            ]),
            'to_year' => $this->whenLoaded('toAcademicYear', fn () => [
                'id' => $this->toAcademicYear->id,
                'name' => $this->toAcademicYear->name,
            ]),
            'status' => $this->status,
            'committed_at' => $this->committed_at?->toIso8601String(),
            'committed_by' => $this->whenLoaded('committedBy', fn () => [
                'id' => $this->committedBy?->id,
                'name' => $this->committedBy?->name,
            ]),
            'undo_closed_at' => $this->undo_closed_at?->toIso8601String(),
            'undone_at' => $this->undone_at?->toIso8601String(),
            'undone_by' => $this->whenLoaded('undoneBy', fn () => [
                'id' => $this->undoneBy?->id,
                'name' => $this->undoneBy?->name,
            ]),
            'is_undoable' => $this->isUndoable(),
            'undo_expires_at' => $this->committed_at?->copy()->addDay()->toIso8601String(),
            'totals' => $this->totals,
            'plan_summary' => $canSeeSensitive ? $this->plan_summary : null,
            'notes' => $this->notes,
        ];
    }
}
