<?php

namespace App\Services\Sports;

use App\Models\Academy;
use Illuminate\Support\Facades\DB;

/**
 * Resolves one imported spreadsheet row to a student and a house.
 *
 * The lookup tables are built once per (academy, year) and reused for every row.
 * A real roster is ~2,200 rows against ~2,900 students, so resolving each row with
 * its own queries would mean millions of hydrated rows for a single preview.
 */
class HouseImportMatcher
{
    private ?string $preparedFor = null;

    /** @var array<string, int> school student code => students.id */
    private array $byCode = [];

    /** @var array<string, int> 13-digit citizen_id => students.id */
    private array $byCitizenId = [];

    /** @var array<string, array<int, int>> "first|last" normalised => students.id[] */
    private array $byName = [];

    /** @var array<string, int> lower-cased house name => academy_groups.id */
    private array $houses = [];

    /** @var array<int, int> students.id => house_group_id already held this year */
    private array $existing = [];

    public function match(Academy $academy, int $year, array $raw, array $mapping, string $conflict = 'skip'): array
    {
        $this->prepare($academy, $year);

        $identifier = trim((string) ($raw[$mapping['student_identifier'] ?? ''] ?? ''));
        $houseName = trim((string) ($raw[$mapping['house_name'] ?? ''] ?? ''));

        if (count(array_filter($raw, fn ($value) => trim((string) $value) !== '')) === 0) {
            return ['status' => 'skipped', 'message' => 'Blank row.'];
        }

        $studentId = null;
        if ($identifier !== '') {
            $studentId = $this->byCode[$identifier] ?? null;

            // Only an exact 13-digit value may be read as a citizen id. 215 rows on the
            // production data were mangled by Excel into forms like 1.90E+12, and one of
            // those values repeats across 72 different students — matching loosely here
            // would sweep all 72 into whichever house the first such row named.
            if ($studentId === null && preg_match('/^\d{13}$/', $identifier)) {
                $studentId = $this->byCitizenId[$identifier] ?? null;
            }
        }

        if ($studentId === null) {
            $first = $this->normalize((string) ($raw[$mapping['first_name_th'] ?? ''] ?? ''));
            $last = $this->normalize((string) ($raw[$mapping['last_name_th'] ?? ''] ?? ''));

            if ($first !== '' || $last !== '') {
                $candidates = $this->byName[$first.'|'.$last] ?? [];
                if (count($candidates) > 1) {
                    return ['status' => 'ambiguous', 'message' => 'More than one student matched this name.'];
                }
                $studentId = $candidates[0] ?? null;
            }
        }

        if ($studentId === null) {
            return ['status' => 'unmatched', 'message' => 'Student not found.'];
        }

        // A house is never created from a file — an unrecognised name is reported, not invented.
        $houseId = $this->houses[mb_strtolower($houseName)] ?? null;
        if ($houseId === null) {
            return ['student_id' => $studentId, 'status' => 'unknown_house', 'message' => 'House not found.'];
        }

        $previous = $this->existing[$studentId] ?? null;
        if ($previous !== null && $conflict !== 'overwrite') {
            return ['student_id' => $studentId, 'house_group_id' => $houseId, 'status' => 'already_assigned', 'message' => 'Student is already assigned.', 'previous_house_group_id' => $previous];
        }

        return ['student_id' => $studentId, 'house_group_id' => $houseId, 'status' => 'ok', 'message' => null, 'previous_house_group_id' => $previous];
    }

    private function prepare(Academy $academy, int $year): void
    {
        $key = $academy->id.':'.$year;
        if ($this->preparedFor === $key) {
            return;
        }

        $this->byCode = [];
        $this->byCitizenId = [];
        $this->byName = [];

        DB::table('students')
            ->where('academy_id', $academy->id)
            ->select('id', 'student_id', 'citizen_id', 'first_name_th', 'last_name_th')
            ->orderBy('id')
            ->chunk(1000, function ($students) {
                foreach ($students as $student) {
                    $code = trim((string) $student->student_id);
                    if ($code !== '' && ! isset($this->byCode[$code])) {
                        $this->byCode[$code] = (int) $student->id;
                    }

                    $citizenId = trim((string) $student->citizen_id);
                    if (preg_match('/^\d{13}$/', $citizenId) && ! isset($this->byCitizenId[$citizenId])) {
                        $this->byCitizenId[$citizenId] = (int) $student->id;
                    }

                    $name = $this->normalize((string) $student->first_name_th).'|'.$this->normalize((string) $student->last_name_th);
                    $this->byName[$name][] = (int) $student->id;
                }
            });

        $this->houses = DB::table('academy_groups')
            ->where('academy_id', $academy->id)
            ->where('type', 'house')
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => (int) $id])
            ->all();

        $this->existing = DB::table('house_memberships')
            ->where('academic_year_id', $year)
            ->pluck('house_group_id', 'student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->preparedFor = $key;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim(str_replace(['่', '้', '๊', '๋'], '', $value)));
    }
}
