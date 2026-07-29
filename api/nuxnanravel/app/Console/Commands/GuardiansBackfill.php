<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GuardiansBackfill extends Command
{
    protected $signature = 'guardians:backfill {--force : Apply the backfill; without this option the command is a dry-run} {--dry-run : Explicitly request a dry-run}';

    protected $description = 'Backfill guardians and links from legacy student_guardians';

    private array $fields = ['title_prefix', 'occupation', 'workplace', 'monthly_income', 'nationality', 'status'];

    public function handle(): int
    {
        $rows = DB::table('student_guardians')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $this->info('No legacy student_guardians rows found.');

            return self::SUCCESS;
        }

        $groups = $this->buildGroups($rows);
        $conflicts = $this->conflicts($groups);
        $mergeGroups = collect($groups)->filter(fn ($g) => count($g) > 1);
        $linkGroups = $this->buildLinkGroups($groups);
        $this->line('mode='.($this->option('force') && ! $this->option('dry-run') ? 'force' : 'dry-run'));
        $this->line('legacy_rows='.$rows->count());
        $this->line('guardians='.count($groups));
        $this->line('links='.count($linkGroups));
        $this->line('collapsed_link_pairs='.$linkGroups->filter(fn ($g) => count($g) > 1)->count());
        $this->line('students_with_guardians='.$rows->pluck('student_id')->unique()->count());
        $this->line('merged_groups='.$mergeGroups->count());
        $this->line('merged_rows='.$mergeGroups->flatten(1)->count());
        $this->line('contacts='.$this->contactCount($rows));
        $this->line('conflicts='.count($conflicts));

        if (! $this->option('force') || $this->option('dry-run')) {
            $this->info('Dry-run: no database writes performed.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($groups, $conflicts, $linkGroups): void {
            $map = [];
            foreach ($groups as $group) {
                $ids = collect($group)->pluck('id')->values()->all();
                $existing = DB::table('guardians')->whereJsonContains('legacy_row_ids', $ids[0])->first();
                if ($existing) {
                    $guardianId = $existing->id;
                } else {
                    $latest = collect($group)->sortByDesc(fn ($r) => [$r->updated_at ?? '', $r->id])->first();
                    $data = ['academy_id' => $latest->academy_id, 'user_id' => null, 'citizen_id' => $latest->citizen_id, 'first_name' => $latest->first_name, 'last_name' => $latest->last_name, 'legacy_row_ids' => json_encode($ids), 'created_at' => now(), 'updated_at' => now()];
                    foreach (array_merge(['title_prefix', 'occupation', 'workplace', 'monthly_income', 'nationality', 'status'], ['first_name', 'last_name']) as $field) {
                        $value = collect($group)->sortByDesc(fn ($r) => [$r->updated_at ?? '', $r->id])->pluck($field)->first(fn ($v) => trim((string) $v) !== '');
                        if ($value !== null) {
                            $data[$field] = $value;
                        }
                    }
                    $guardianId = DB::table('guardians')->insertGetId($data);
                }
                foreach ($group as $row) {
                    $map[$row->id] = $guardianId;
                }
            }
            $linkMerges = [];
            foreach ($linkGroups as $linkGroup) {
                $first = $linkGroup[0];
                $latest = collect($linkGroup)->sortByDesc(fn ($r) => [$r->updated_at ?? '', $r->id])->first();
                $type = $this->selectRelation($linkGroup, 'guardian_type');
                $relationship = $this->selectRelation($linkGroup, 'relationship');
                $data = ['student_id' => $first->student_id, 'guardian_id' => $map[$first->id], 'guardian_type' => $type, 'relationship' => $relationship, 'is_primary_contact' => collect($linkGroup)->contains(fn ($r) => (bool) $r->is_primary_contact), 'is_emergency_contact' => collect($linkGroup)->contains(fn ($r) => (bool) $r->is_emergency_contact), 'appointed_by_user_id' => null, 'appointed_by_role' => 'import', 'appointed_at' => collect($linkGroup)->min('created_at'), 'legacy_row_ids' => json_encode(collect($linkGroup)->pluck('id')->values()->all()), 'updated_at' => now(), 'created_at' => now()];
                DB::table('student_guardian_links')->updateOrInsert(['student_id' => $data['student_id'], 'guardian_id' => $data['guardian_id']], $data);
                if (count($linkGroup) > 1) {
                    $linkMerges[] = [$first->student_id, $data['guardian_id'], implode('|', $data['legacy_row_ids'] ? json_decode($data['legacy_row_ids'], true) : []), $type, $relationship, $data['is_primary_contact'] ? 1 : 0, $data['is_emergency_contact'] ? 1 : 0];
                }
            }
            foreach ($map as $legacyId => $guardianId) {
                DB::table('guardian_contacts')->where('guardian_id', $legacyId)->update(['guardian_person_id' => $guardianId]);
            }
            $this->writeConflicts($conflicts);
            $this->writeLinkMerges($linkMerges);
        });
        $this->info('Backfill applied successfully.');

        return self::SUCCESS;
    }

    private function buildLinkGroups(array $groups): Collection
    {
        $out = [];
        foreach ($groups as $groupIndex => $group) {
            foreach ($group as $row) {
                $out[$row->student_id.'|'.$groupIndex][] = $row;
            }
        }

        return collect($out)->values();
    }

    private function selectRelation($rows, string $field): ?string
    {
        $specific = collect($rows)->filter(fn ($row) => ! in_array(strtolower(trim((string) $row->{$field})), ['', 'guardian', 'other'], true));
        $pool = $specific->isNotEmpty() ? $specific : collect($rows);

        return $pool->sortByDesc(fn ($row) => [$row->updated_at ?? '', $row->id])->first()->{$field} ?? null;
    }

    private function buildGroups($rows): array
    {
        $namesByCitizen = [];
        foreach ($rows as $row) {
            $citizenId = (string) $row->citizen_id;
            if (preg_match('/^\d{13}$/', $citizenId)) {
                $namesByCitizen[$citizenId][] = [
                    $this->normalizeName($row->first_name),
                    $this->normalizeName($row->last_name),
                ];
            }
        }

        $cleanCitizens = [];
        foreach ($namesByCitizen as $citizenId => $names) {
            $cleanCitizens[$citizenId] = count(array_unique($names, SORT_REGULAR)) === 1;
        }

        $groups = [];
        foreach ($rows as $row) {
            $citizenId = (string) $row->citizen_id;
            $key = preg_match('/^\d{13}$/', $citizenId)
                && ($cleanCitizens[$citizenId] ?? false)
                ? implode('|', [
                    'merge',
                    $row->academy_id,
                    $citizenId,
                    $this->normalizeName($row->first_name),
                    $this->normalizeName($row->last_name),
                ])
                : 'row|'.$row->id;
            $groups[$key][] = $row;
        }

        return array_values($groups);
    }

    private function normalizeName($name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) $name));

        return preg_replace('/[\x{0E48}\x{0E49}\x{0E4A}\x{0E4B}]/u', '', $name);
    }

    private function conflicts(array $groups): array
    {
        $out = [];
        foreach ($groups as $group) {
            if (count($group) > 1) {
                foreach ($this->fields as $field) {
                    $values = collect($group)->pluck($field)->map(fn ($v) => trim((string) $v))->filter()->unique()->values();
                    if ($values->count() > 1 || ($values->count() === 1 && $values->count() < count($group))) {
                        $out[] = [$group[0]->citizen_id, $field, $values->implode('|'), $values->first(), 'newest updated_at, then highest id'];
                    }
                }
            }
        }

        return $out;
    }

    private function contactCount($rows): int
    {
        return DB::table('guardian_contacts')->whereIn('guardian_id', $rows->pluck('id'))->count();
    }

    private function writeConflicts(array $rows): void
    {
        Storage::makeDirectory('reports');
        $path = storage_path('app/reports/backfill_conflicts.csv');
        $h = fopen($path, 'wb');
        fputcsv($h, ['guardian_new', 'field', 'values', 'selected', 'reason']);
        foreach ($rows as $row) {
            fputcsv($h, $row);
        } fclose($h);
    }

    private function writeLinkMerges(array $rows): void
    {
        Storage::makeDirectory('reports');
        $h = fopen(storage_path('app/reports/backfill_link_merges.csv'), 'wb');
        fputcsv($h, ['student_id', 'guardian_id', 'legacy_row_ids', 'guardian_type', 'relationship', 'is_primary_contact', 'is_emergency_contact']);
        foreach ($rows as $row) {
            fputcsv($h, $row);
        }
        fclose($h);
    }
}
