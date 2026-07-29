<?php

namespace App\Console\Commands\Concerns;

trait SelectsGuardianRelation
{
    private function selectRelation(iterable $rows, string $field): ?string
    {
        return $this->selectRelationRow($rows, $field)?->{$field};
    }

    private function selectRelationRow(iterable $rows, string $field): mixed
    {
        $rows = collect($rows);
        $specific = $rows->filter(fn ($row) => ! in_array(strtolower(trim((string) $row->{$field})), ['', 'guardian', 'other'], true));
        $pool = $specific->isNotEmpty() ? $specific : $rows;

        return $pool->sortByDesc(fn ($row) => [$row->updated_at ?? '', $row->id])->first();
    }
}
