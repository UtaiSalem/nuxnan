<?php

namespace App\Services\Sports;

use App\Models\Academy;
use App\Models\HouseAssignmentBatch;
use App\Models\HouseAssignmentRow;
use App\Models\SportsEdition;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

class HouseAssignmentService
{
    public function __construct(private readonly HouseMembershipProjector $projector) {}

    public function previewRandom(Academy $academy, SportsEdition $edition, array $input, User $actor): HouseAssignmentBatch
    {
        if ((int) $edition->academy_id !== (int) $academy->id) {
            throw new DomainException('Edition does not belong to this academy.');
        }
        if ($edition->status === 'closed') {
            throw new DomainException('Cannot modify a closed edition.');
        }
        $ids = collect($input['house_group_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
        if (count($ids) < 2 || count($ids) !== count($input['house_group_ids'] ?? [])) {
            throw new DomainException('At least two distinct houses are required.');
        }
        $allowed = $edition->houseGroupIds();
        if (array_diff($ids, $allowed) !== []) {
            throw new DomainException('All houses must belong to this edition.');
        }

        $options = [
            'house_group_ids' => $ids,
            'strategy' => $input['strategy'] ?? 'stratified',
            'balance_gender' => (bool) ($input['balance_gender'] ?? true),
            'scope' => $input['scope'] ?? 'unassigned_only',
            'seed' => array_key_exists('seed', $input) ? (int) $input['seed'] : random_int(1, PHP_INT_MAX),
        ];
        if (! in_array($options['strategy'], ['stratified', 'pure_random'], true) || ! in_array($options['scope'], ['unassigned_only', 'all'], true)) {
            throw new DomainException('Invalid random options.');
        }

        $students = DB::table('classroom_students')->join('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')->join('students', 'students.id', '=', 'classroom_students.student_id')->where('classroom_students.academy_id', $edition->academy_id)->where('classroom_students.academic_year_id', $edition->academic_year_id)->where('classroom_students.status', 'active')->select('students.id', 'students.gender', 'students.user_id', 'classrooms.id as classroom_id', 'classrooms.grade_level')->orderBy('classroom_students.classroom_id')->orderBy('students.id')->get()->unique('id')->values();
        $existing = DB::table('house_memberships')->where('edition_id', $edition->id)->whereIn('student_id', $students->pluck('id'))->pluck('house_group_id', 'student_id');
        $population = $students->reject(fn ($student) => $options['scope'] === 'unassigned_only' && $existing->has($student->id))->values();
        $rng = new Randomizer(new Mt19937($options['seed']));
        $counts = array_fill_keys($ids, 0);
        $grade = [];
        $assigned = [];
        $buckets = $options['strategy'] === 'pure_random' ? [$population] : collect($population)->groupBy('classroom_id')->sortKeys()->flatMap(function ($group) use ($options) {
            if (! $options['balance_gender']) {
                return [$group->values()];
            }

            // Strict comparison is required: Collection::where() compares loosely and
            // null == 0 in PHP, so a student with no recorded gender would land in the
            // female bucket AND the unknown bucket and be assigned a house twice.
            return [
                $group->filter(fn ($student) => $student->gender === 1)->values(),
                $group->filter(fn ($student) => $student->gender === 0)->values(),
                $group->filter(fn ($student) => $student->gender === null)->values(),
            ];
        });
        foreach ($buckets as $bucket) {
            foreach ($rng->shuffleArray($bucket->all()) as $student) {
                $minimum = min($counts);
                $house = (int) array_key_first(array_filter($counts, fn ($count) => $count === $minimum));
                $counts[$house]++;
                $level = $student->grade_level ?? 'unknown';
                $grade[$house][$level] = ($grade[$house][$level] ?? 0) + 1;
                $assigned[] = ['student_id' => $student->id, 'house_group_id' => $house, 'previous_house_group_id' => $existing[$student->id] ?? null];
            }
        }

        $batch = HouseAssignmentBatch::create(['id' => (string) Str::uuid(), 'academy_id' => $edition->academy_id, 'edition_id' => $edition->id, 'mode' => 'random', 'status' => 'draft', 'options' => $options, 'summary' => ['total' => count($assigned), 'per_house' => $counts, 'per_house_by_grade' => $grade, 'skipped_count' => $students->count() - $population->count(), 'moved_count' => collect($assigned)->filter(fn ($row) => $row['previous_house_group_id'] !== null && (int) $row['previous_house_group_id'] !== $row['house_group_id'])->count()], 'created_by_user_id' => $actor->id]);
        foreach (array_chunk(array_map(fn ($row, $index) => ['batch_id' => $batch->id, 'row_number' => $index + 1, 'student_id' => $row['student_id'], 'house_group_id' => $row['house_group_id'], 'previous_house_group_id' => $row['previous_house_group_id'], 'status' => 'ok', 'created_at' => now(), 'updated_at' => now()], $assigned, array_keys($assigned)), 500) as $chunk) {
            HouseAssignmentRow::insert($chunk);
        }

        return $batch->fresh();
    }

    public function previewImport(Academy $academy, SportsEdition $edition, UploadedFile $file, array $options, User $actor): HouseAssignmentBatch
    {
        if ((int) $edition->academy_id !== (int) $academy->id) {
            throw new DomainException('Edition does not belong to this academy.');
        }
        if ($edition->status === 'closed') {
            throw new DomainException('Cannot modify a closed edition.');
        }
        $mapping = $options['column_mapping'] ?? [];
        if (is_string($mapping)) {
            $mapping = json_decode($mapping, true) ?: [];
        }
        if (empty($mapping['student_identifier']) || empty($mapping['house_name'])) {
            throw new DomainException('Student and house columns are required.');
        }
        $conflict = $options['on_conflict'] ?? 'skip';
        if (! in_array($conflict, ['skip', 'overwrite'], true)) {
            throw new DomainException('Invalid conflict option.');
        }
        $stored = $file->store('house-assignments', 'local');
        $batch = HouseAssignmentBatch::create(['id' => (string) Str::uuid(), 'academy_id' => $edition->academy_id, 'edition_id' => $edition->id, 'mode' => 'import', 'status' => 'draft', 'options' => ['column_mapping' => $mapping, 'on_conflict' => $conflict, 'stored_path' => $stored], 'summary' => [], 'source_filename' => $file->getClientOriginalName(), 'created_by_user_id' => $actor->id]);
        $matcher = app(HouseImportMatcher::class);
        $summary = ['total' => 0, 'per_house' => [], 'per_house_by_grade' => [], 'by_status' => array_fill_keys(['ok', 'unmatched', 'ambiguous', 'unknown_house', 'already_assigned', 'skipped'], 0), 'moved_count' => 0];
        $rows = [];

        // Prefetched once: a roster file has ~2,200 rows, and looking each student's
        // grade up individually would issue a query per row.
        $grades = DB::table('classroom_students')
            ->join('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')
            ->where('classroom_students.academic_year_id', $edition->academic_year_id)
            ->where('classroom_students.status', 'active')
            ->pluck('classrooms.grade_level', 'classroom_students.student_id');

        $seen = [];
        foreach (app(HouseImportParser::class)->parse($file) as $number => $raw) {
            $result = $matcher->match($edition, $raw, $mapping, $conflict);
            $status = $result['status'];

            // The same student listed twice would otherwise produce two 'ok' rows: the
            // preview would count them twice while commit's upsert keeps only the last,
            // so the screen the operator approves would not match what gets written.
            if ($status === 'ok' && isset($seen[$result['student_id']])) {
                $status = 'ambiguous';
                $result['message'] = 'This student appears more than once in the file (first at row '.$seen[$result['student_id']].').';
                $result['house_group_id'] = null;
            } elseif ($status === 'ok') {
                $seen[$result['student_id']] = $number + 1;
            }

            $summary['total']++;
            $summary['by_status'][$status]++;
            if ($status === 'ok') {
                $house = (string) $result['house_group_id'];
                $summary['per_house'][$house] = ($summary['per_house'][$house] ?? 0) + 1;
                if (($result['previous_house_group_id'] ?? null) !== null && (int) $result['previous_house_group_id'] !== (int) $result['house_group_id']) {
                    $summary['moved_count']++;
                }
                $grade = $grades[$result['student_id']] ?? 'unknown';
                $summary['per_house_by_grade'][$house][$grade] = ($summary['per_house_by_grade'][$house][$grade] ?? 0) + 1;
            }
            $rows[] = ['batch_id' => $batch->id, 'row_number' => $number + 1, 'raw' => json_encode($raw, JSON_UNESCAPED_UNICODE), 'student_id' => $result['student_id'] ?? null, 'house_group_id' => $result['house_group_id'] ?? null, 'previous_house_group_id' => $result['previous_house_group_id'] ?? null, 'status' => $status, 'message' => $result['message'] ?? null, 'created_at' => now(), 'updated_at' => now()];
            if (count($rows) === 500) {
                HouseAssignmentRow::insert($rows);
                $rows = [];
            }
        }
        if ($rows) {
            HouseAssignmentRow::insert($rows);
        }
        $batch->update(['summary' => $summary]);

        return $batch->fresh();
    }

    public function commit(HouseAssignmentBatch $batch, User $actor): void
    {
        if ($batch->status !== 'draft') {
            throw new DomainException('Batch is not draft.');
        }
        DB::transaction(function () use ($batch, $actor) {
            foreach ($batch->rows()->where('status', 'ok')->with('student')->get()->chunk(500) as $chunk) {
                DB::table('house_memberships')->upsert($chunk->map(fn ($row) => ['academy_id' => $batch->academy_id, 'edition_id' => $batch->edition_id, 'student_id' => $row->student_id, 'house_group_id' => $row->house_group_id, 'user_id' => $row->student->user_id, 'source' => $batch->mode, 'batch_id' => $batch->id, 'assigned_by_user_id' => $actor->id, 'created_at' => now(), 'updated_at' => now()])->all(), ['edition_id', 'student_id'], ['academy_id', 'house_group_id', 'user_id', 'source', 'batch_id', 'assigned_by_user_id', 'updated_at']);
            }
            $batch->update(['status' => 'committed', 'committed_at' => now(), 'committed_by_user_id' => $actor->id]);
            if ($batch->edition->status === 'active') {
                $this->projector->rebuild($batch->edition);
            }
        });
    }

    public function undo(HouseAssignmentBatch $batch, User $actor): void
    {
        if (! $batch->isUndoable()) {
            throw new DomainException('Batch cannot be undone.');
        }
        DB::transaction(function () use ($batch, $actor) {
            foreach ($batch->rows()->where('status', 'ok')->get() as $row) {
                // Only reverse rows this batch still owns. If a later batch has already
                // moved the student on, undoing this one must not drag them back and
                // silently discard the newer division.
                $query = DB::table('house_memberships')
                    ->where('edition_id', $batch->edition_id)
                    ->where('student_id', $row->student_id)
                    ->where('batch_id', $batch->id);

                if ($row->previous_house_group_id === null) {
                    $query->delete();
                } else {
                    $query->update(['house_group_id' => $row->previous_house_group_id, 'batch_id' => null, 'source' => 'manual', 'updated_at' => now()]);
                }
            }
            if ($batch->edition->status === 'active') {
                $this->projector->rebuild($batch->edition);
            }
            $batch->update(['status' => 'undone', 'undone_at' => now(), 'undone_by_user_id' => $actor->id]);
        });
    }
}
