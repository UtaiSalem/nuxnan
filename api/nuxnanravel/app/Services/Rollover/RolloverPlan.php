<?php

namespace App\Services\Rollover;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class RolloverPlan implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly int $academyId,
        public readonly int $fromYearId,
        public readonly int $toYearId,
        /** @var array<int, array{
         *   from_classroom_id: int,
         *   to_classroom_id?: int,
         *   action: 'promote'|'graduate'|'drop'|'repeat'|'new_intake'|'skip',
         *   student_id: int,
         *   reason?: string,
         * }> */
        public readonly array $entries,
        public readonly array $summary,
        public readonly array $warnings,
    ) {}

    public function toArray(): array
    {
        return [
            'academy_id' => $this->academyId,
            'from_academic_year_id' => $this->fromYearId,
            'to_academic_year_id' => $this->toYearId,
            'entries' => $this->entries,
            'summary' => $this->summary,
            'warnings' => $this->warnings,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            academyId: (int) $data['academy_id'],
            fromYearId: (int) $data['from_academic_year_id'],
            toYearId: (int) $data['to_academic_year_id'],
            entries: $data['entries'] ?? [],
            summary: $data['summary'] ?? [],
            warnings: $data['warnings'] ?? [],
        );
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
