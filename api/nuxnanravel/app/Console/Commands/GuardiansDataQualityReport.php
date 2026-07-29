<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GuardiansDataQualityReport extends Command
{
    protected $signature = 'guardians:data-quality-report {--csv : Write detail CSV files} {--academy= : Limit to academy id}';

    protected $description = 'Read-only data quality report for legacy student guardians';

    public function handle(): int
    {
        $academy = $this->option('academy');
        $sg = DB::table('student_guardians as g')->leftJoin('students as s', 's.id', '=', 'g.student_id')
            ->select('g.*', 's.academy_id as student_academy_id', 's.first_name_th as student_first_name', 's.last_name_th as student_last_name')
            ->when($academy !== null, fn ($q) => $q->where(function ($q) use ($academy) {
                $q->where('g.academy_id', $academy)->orWhere('s.academy_id', $academy);
            }))
            ->get();
        $students = DB::table('students')->when($academy !== null, fn ($q) => $q->where('academy_id', $academy))->get();
        $contacts = DB::table('guardian_contacts as c')->join('student_guardians as g', 'g.id', '=', 'c.guardian_id')->leftJoin('students as s', 's.id', '=', 'g.student_id')
            ->select('c.*')->when($academy !== null, fn ($q) => $q->where(function ($q) use ($academy) {
                $q->where('g.academy_id', $academy)->orWhere('s.academy_id', $academy);
            }))->get();

        $byStudent = $sg->groupBy('student_id');
        $byCitizen = $sg->filter(fn ($r) => $this->validCitizen($r->citizen_id))->groupBy('citizen_id');
        $csv = [];
        if ($this->option('csv')) {
            Storage::makeDirectory('reports');
        }
        $bad = $sg->filter(fn ($r) => ! $this->validCitizen($r->citizen_id));
        $this->writeCsv($csv['invalid_guardians'] ?? null, 'invalid_guardians.csv', $bad, ['id', 'student_id', 'student_name', 'citizen_id', 'length']);
        $merge = $byCitizen->filter(fn ($g) => $g->count() > 1);
        $mergeRows = $merge->map(fn ($g, $id) => [$id, $g->count(), $g->pluck('student_id')->unique()->implode('|'), $g->map(fn ($r) => trim(($r->student_first_name ?? '').' '.($r->student_last_name ?? '')))->implode('|')]);
        $this->writeCsv($csv['merge_groups'] ?? null, 'merge_groups.csv', $mergeRows, ['citizen_id', 'row_count', 'student_ids', 'student_names']);

        $conflicts = [];
        $nameMismatch = [];
        foreach ($merge as $citizen => $rows) {
            foreach (['title_prefix', 'first_name', 'last_name', 'occupation', 'workplace', 'monthly_income', 'nationality', 'status'] as $field) {
                $values = $rows->pluck($field)->map(fn ($v) => trim((string) $v))->filter()->unique()->values();
                $nonEmpty = $rows->filter(fn ($r) => trim((string) $r->{$field}) !== '')->count();
                if ($values->count() > 1 || ($values->count() === 1 && $nonEmpty < $rows->count())) {
                    $conflicts[] = [$citizen, $field, $values->implode('|'), $values->count() > 1 ? 'ขัดกันจริง' : 'แค่บางแถวว่าง'];
                }
            }
            if ($rows->pluck('first_name')->map('trim')->unique()->count() > 1 || $rows->pluck('last_name')->map('trim')->unique()->count() > 1) {
                foreach ($rows as $r) {
                    $nameMismatch[] = [$citizen, $r->id, $r->student_id, $r->first_name, $r->last_name];
                }
            }
        }
        $this->writeCsv(null, 'merge_conflicts.csv', collect($conflicts), ['citizen_id', 'field', 'values', 'kind']);
        $this->writeCsv(null, 'merge_name_mismatches.csv', collect($nameMismatch), ['citizen_id', 'id', 'student_id', 'first_name', 'last_name']);

        $sameName = $sg->groupBy(fn ($r) => mb_strtolower(trim($r->first_name.' '.$r->last_name)))->filter(fn ($g) => $g->count() > 1);
        $sameNameRows = $sameName->flatMap(fn ($g) => $g->map(fn ($r) => [$r->first_name.' '.$r->last_name, $r->id, $r->student_id, $r->citizen_id]));
        $this->writeCsv(null, 'same_names_different_citizen.csv', $sameNameRows, ['name', 'id', 'student_id', 'citizen_id']);

        $contactByGuardian = $contacts->groupBy('guardian_id');
        $duplicateContacts = $contacts->groupBy(fn ($c) => $c->guardian_id.'|'.$c->contact_type.'|'.trim((string) $c->contact_value))->filter(fn ($g) => $g->count() > 1);
        $phoneBad = $contacts->filter(fn ($c) => in_array($c->contact_type, ['phone', 'mobile'], true) && ! preg_match('/^\d{9,10}$/', preg_replace('/[^0-9]/', '', (string) $c->contact_value)));
        $orphan = $sg->filter(fn ($r) => $r->student_academy_id === null);
        $out = [
            'A' => ['student_guardians' => $sg->count(), 'students_with_guardians' => $byStudent->count(), 'guardian_contacts' => $contacts->count(), 'students_total' => $students->count(), 'students_without_guardians' => $students->filter(fn ($s) => ! isset($byStudent[$s->id]))->count(), 'distribution' => $byStudent->countBy(fn ($g) => $g->count() >= 4 ? 'มากกว่า' : $g->count())],
            'B' => ['valid_13' => $sg->filter(fn ($r) => preg_match('/^\d{13}$/', (string) $r->citizen_id) && $this->validCitizen($r->citizen_id))->count(), 'eight' => $sg->filter(fn ($r) => strlen((string) $r->citizen_id) === 8)->count(), 'other' => $sg->filter(fn ($r) => $r->citizen_id !== null && strlen((string) $r->citizen_id) !== 0 && strlen((string) $r->citizen_id) !== 8 && strlen((string) $r->citizen_id) !== 13)->count(), 'blank' => $sg->filter(fn ($r) => trim((string) $r->citizen_id) === '')->count(), 'non_numeric' => $sg->filter(fn ($r) => $r->citizen_id !== null && $r->citizen_id !== '' && ! ctype_digit((string) $r->citizen_id))->count(), 'checksum_fail' => $sg->filter(fn ($r) => preg_match('/^\d{13}$/', (string) $r->citizen_id) && ! $this->validCitizen($r->citizen_id))->count()],
            'C' => ['groups' => $merge->count(), 'rows' => $merge->flatten(1)->count(), 'rows_removed' => $merge->sum(fn ($g) => $g->count() - 1)],
            'D' => ['conflict_rows' => count($conflicts), 'name_mismatch_rows' => count($nameMismatch)],
            'E' => ['same_name_groups' => $sameName->count(), 'rows' => $sameNameRows->count()],
            'F' => ['contact_distribution' => $contactByGuardian->countBy(fn ($g) => $g->count()), 'guardians_without_contact' => $sg->pluck('id')->unique()->filter(fn ($id) => ! isset($contactByGuardian[$id]))->count(), 'duplicate_values' => $duplicateContacts->count(), 'bad_phones' => $phoneBad->count(), 'verified' => $contacts->where('is_verified', 1)->count()],
            'G' => ['students_2plus_no_primary' => $byStudent->filter(fn ($g) => $g->count() > 1 && $g->where('is_primary_contact', 1)->isEmpty())->count(), 'students_multiple_primary' => $byStudent->filter(fn ($g) => $g->where('is_primary_contact', 1)->count() > 1)->count(), 'academy_mismatch_or_blank' => $sg->filter(fn ($r) => $r->student_academy_id === null || $r->academy_id === null || $r->academy_id != $r->student_academy_id)->count(), 'orphan_student_id' => $orphan->count()],
        ];
        foreach ($out as $section => $data) {
            $this->line($section.' '.json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        if ($this->option('csv')) {
            $this->info('CSV: storage/app/reports');
        }

        return self::SUCCESS;
    }

    private function validCitizen($value): bool
    {
        $v = (string) $value;
        if (! preg_match('/^\d{13}$/', $v)) {
            return false;
        } $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $v[$i] * (13 - $i);
        }

        return (11 - ($sum % 11)) % 10 === (int) $v[12];
    }

    private function writeCsv($unused, string $name, $rows, array $header): void
    {
        if (! $this->option('csv')) {
            return;
        }
        $directory = storage_path('app/reports');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $path = $directory.'/'.$name;
        $h = fopen($path, 'wb');
        fputcsv($h, $header);
        foreach ($rows as $r) {
            if (is_object($r)) {
                $r = (array) $r;
            } fputcsv($h, $r);
        } fclose($h);
    }
}
