<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SelectsGuardianRelation;
use App\Models\GuardianMergeCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GuardiansMerge extends Command
{
    use SelectsGuardianRelation;

    protected $signature = 'guardians:merge {--candidate=} {--keep=}';

    protected $description = 'Merge a reviewed guardian candidate into the selected guardian';

    public function handle(): int
    {
        $candidateId = (int) $this->option('candidate');
        $keepId = (int) $this->option('keep');
        if (! $candidateId || ! $keepId) {
            $this->error('Both --candidate and --keep are required.');

            return self::INVALID;
        }
        DB::transaction(function () use ($candidateId, $keepId) {
            $candidate = GuardianMergeCandidate::lockForUpdate()->findOrFail($candidateId);
            if ($candidate->status !== 'pending') {
                throw new \RuntimeException('Candidate is not pending.');
            }
            $ids = array_map('intval', $candidate->guardian_ids);
            if (! in_array($keepId, $ids, true)) {
                throw new \RuntimeException('Keep guardian is not in candidate.');
            }
            $keep = DB::table('guardians')->lockForUpdate()->find($keepId);
            $absorbed = array_values(array_filter($ids, fn ($id) => $id !== $keepId));
            $snapshot = DB::table('guardians')->whereIn('id', $absorbed)->get()->map(fn ($r) => (array) $r)->all();
            $conflicts = [];
            foreach (['academy_id', 'user_id', 'citizen_id', 'title_prefix', 'first_name', 'last_name', 'occupation', 'workplace', 'monthly_income', 'nationality', 'status'] as $field) {
                if (($keep->{$field} === null || $keep->{$field} === '') && collect($snapshot)->pluck($field)->first(fn ($v) => $v !== null && $v !== '') !== null) {
                    DB::table('guardians')->where('id', $keepId)->update([$field => collect($snapshot)->pluck($field)->first(fn ($v) => $v !== null && $v !== '')]);
                }
                if (collect($snapshot)->pluck($field)->filter(fn ($v) => $v !== null && $v !== '')->unique()->count() > 1 || (isset($keep->{$field}) && collect($snapshot)->pluck($field)->filter()->contains(fn ($v) => (string) $v !== (string) $keep->{$field}))) {
                    $conflicts[] = [$candidateId, $field, $keep->{$field}, json_encode(collect($snapshot)->pluck($field)->all(), JSON_UNESCAPED_UNICODE)];
                }
            }
            $legacy = collect($keep->legacy_row_ids ?: [])->merge(collect($snapshot)->flatMap(fn ($r) => json_decode($r['legacy_row_ids'] ?: '[]', true) ?: []))->unique()->values()->all();
            DB::table('guardians')->where('id', $keepId)->update(['legacy_row_ids' => json_encode($legacy), 'updated_at' => now()]);
            foreach (DB::table('student_guardian_links')->whereIn('guardian_id', $absorbed)->get()->groupBy('student_id') as $studentId => $links) {
                $existing = DB::table('student_guardian_links')->where(['student_id' => $studentId, 'guardian_id' => $keepId])->first();
                $source = $this->selectRelationRow($links, 'guardian_type');
                if ($existing) {
                    $legacyIds = collect(json_decode($existing->legacy_row_ids ?: '[]', true) ?: [])->merge($links->flatMap(fn ($l) => json_decode($l->legacy_row_ids ?: '[]', true) ?: []))->unique()->values()->all();
                    DB::table('student_guardian_links')->where('id', $existing->id)->update(['is_primary_contact' => (int) ($existing->is_primary_contact || $links->contains('is_primary_contact', 1)), 'is_emergency_contact' => (int) ($existing->is_emergency_contact || $links->contains('is_emergency_contact', 1)), 'guardian_type' => $this->selectRelation([$existing, ...$links], 'guardian_type'), 'relationship' => $this->selectRelation([$existing, ...$links], 'relationship'), 'legacy_row_ids' => json_encode($legacyIds), 'updated_at' => now()]);
                } else {
                    $legacyIds = $links->flatMap(fn ($l) => json_decode($l->legacy_row_ids ?: '[]', true) ?: [])->unique()->values()->all();
                    $row = (array) $source;
                    unset($row['id']);
                    $row['guardian_id'] = $keepId;
                    $row['is_primary_contact'] = (int) $links->contains('is_primary_contact', 1);
                    $row['is_emergency_contact'] = (int) $links->contains('is_emergency_contact', 1);
                    $row['guardian_type'] = $this->selectRelation($links, 'guardian_type');
                    $row['relationship'] = $this->selectRelation($links, 'relationship');
                    $row['legacy_row_ids'] = json_encode($legacyIds);
                    $row['appointed_at'] = $links->pluck('appointed_at')->filter()->sort()->first();
                    $row['created_at'] = $row['updated_at'] = now();
                    DB::table('student_guardian_links')->insert($row);
                }
                DB::table('student_guardian_links')->whereIn('id', $links->pluck('id'))->delete();
            }
            DB::table('guardian_contacts')->whereIn('guardian_person_id', $absorbed)->update(['guardian_person_id' => $keepId]);
            $this->dedupeContacts($keepId);
            DB::table('guardians')->whereIn('id', $absorbed)->delete();
            $candidate->update(['status' => 'merged', 'reviewed_at' => now(), 'absorbed_snapshot' => $snapshot]);
            if ($conflicts) {
                Storage::makeDirectory('reports');
                $h = fopen(storage_path('app/reports/guardian_merge_conflicts.csv'), 'ab');
                foreach ($conflicts as $row) {
                    fputcsv($h, $row);
                } fclose($h);
            }
        });

        return self::SUCCESS;
    }

    private function dedupeContacts(int $guardianId): void
    {
        $rows = DB::table('guardian_contacts')->where('guardian_person_id', $guardianId)->get()->groupBy(fn ($c) => $c->contact_type.'|'.trim((string) $c->contact_value));
        foreach ($rows as $group) {
            $keep = $group->sortByDesc('is_primary')->sortByDesc('is_verified')->sortBy('id')->first();
            DB::table('guardian_contacts')->whereIn('id', $group->pluck('id')->reject(fn ($id) => $id === $keep->id))->update(['superseded_by_contact_id' => $keep->id]);
        }
    }
}
