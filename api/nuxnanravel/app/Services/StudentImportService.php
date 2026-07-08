<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\StudentImportRow;
use App\Services\Import\RosterReconciliationService;
use App\Services\Import\StudentRosterCommitService;
use App\Services\Import\StudentRosterUpdateService;
use App\Services\Import\StudentRosterXlsxParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudentImportService
{
    public const REQUIRED_HEADERS = [
        'student_code', 'first_name_th', 'last_name_th', 'grade_level', 'section',
    ];

    public function __construct(private readonly StudentIntakeService $intakeService) {}

    public function validateBatch(StudentImportBatch $batch): void
    {
        if (in_array($batch->status, ['cancelled', 'processing', 'completed'], true)) {
            return;
        }

        // Route to JSON Roster Reconciliation if applicable
        if ($batch->import_type === 'roster_reconciliation' || str_ends_with(strtolower($batch->filename), '.json')) {
            $batch->update(['import_type' => 'roster_reconciliation', 'source_format' => 'json']);
            $batch->update(['status' => 'validating']);
            $batch->rows()->delete();

            $reconciliationService = app(RosterReconciliationService::class);

            try {
                $filePath = Storage::disk('local')->path($batch->filename);
                $jsonData = json_decode(file_get_contents($filePath), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON import file.');
                }
                $reconciliationService->preview($batch->academy, $batch->academicYear, $batch, $jsonData);
            } catch (Throwable $exception) {
                if ($batch->fresh()->status !== 'cancelled') {
                    $batch->update(['status' => 'failed']);
                }
                throw $exception;
            }

            return;
        }

        // Route to XLSX Roster Update if applicable
        if ($batch->import_type === 'roster_update' || str_ends_with(strtolower($batch->filename), '.xlsx')) {
            $batch->update(['import_type' => 'roster_update', 'source_format' => 'xlsx']);
            $batch->update(['status' => 'validating']);
            $batch->rows()->delete();

            $parser = app(StudentRosterXlsxParser::class);
            $updateService = app(StudentRosterUpdateService::class);

            try {
                $filePath = Storage::disk('local')->path($batch->filename);
                $parsed = $parser->parse($filePath);
                $updateService->preview($batch->academy, $batch->academicYear, $batch, $parsed);
            } catch (Throwable $exception) {
                if ($batch->fresh()->status !== 'cancelled') {
                    $batch->update(['status' => 'failed']);
                }
                throw $exception;
            }

            return;
        }

        $batch->update(['status' => 'validating']);
        $batch->rows()->delete();

        try {
            $records = $this->readCsv($batch->filename);
            $headers = $records['headers'];
            $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, $headers));
            if ($missingHeaders !== []) {
                throw new \RuntimeException('Missing required columns: '.implode(', ', $missingHeaders));
            }

            $seenCodes = [];
            $seenCitizens = [];
            foreach ($records['rows'] as $offset => $raw) {
                $rowNumber = $offset + 2;
                [$normalized, $errors, $warnings] = $this->normalizeAndValidateRow(
                    $batch,
                    $raw,
                    $rowNumber,
                    $seenCodes,
                    $seenCitizens,
                );

                StudentImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'raw_data' => $raw,
                    'normalized_data' => $normalized,
                    'status' => $errors !== [] ? 'invalid' : ($warnings !== [] ? 'warning' : 'valid'),
                    'errors' => $errors ?: null,
                    'warnings' => $warnings ?: null,
                ]);
            }

            $this->refreshCounters($batch);
            if ($batch->fresh()->status !== 'cancelled') {
                $batch->update(['status' => 'validated']);
            }
        } catch (Throwable $exception) {
            if ($batch->fresh()->status !== 'cancelled') {
                $batch->update(['status' => 'failed']);
            }
            throw $exception;
        }
    }

    public function processBatch(StudentImportBatch $batch, bool $retryFailed = false): void
    {
        if ($batch->status === 'cancelled' || (! $retryFailed && ! in_array($batch->status, ['validated', 'processing'], true))) {
            return;
        }

        $operator = $batch->confirmedBy;
        if (! $operator) {
            $batch->update(['status' => 'failed']);

            return;
        }

        $batch->update(['status' => 'processing']);

        // Route to JSON Roster Reconciliation if applicable
        if ($batch->import_type === 'roster_reconciliation') {
            $reconciliationService = app(RosterReconciliationService::class);
            $reconciliationService->assignHomeroomTeachers($batch, $operator);
            $statuses = $retryFailed ? ['failed'] : ['valid', 'warning'];

            $batch->rows()
                ->whereIn('status', $statuses)
                ->orderBy('row_number')
                ->chunkById(50, function ($rows) use ($batch, $operator, $reconciliationService): void {
                    foreach ($rows as $row) {
                        try {
                            $reconciliationService->processRow($row, $operator);
                        } catch (Throwable $exception) {
                            Log::warning('Roster reconciliation row failed', [
                                'batch_id' => $batch->id,
                                'row_id' => $row->id,
                                'exception' => $exception,
                            ]);
                            $row->update([
                                'status' => 'failed',
                                'errors' => [['field' => '_system', 'message' => $exception->getMessage()]],
                            ]);
                        }
                    }
                });

            // Card sync
            if ($batch->rows()->where('status', 'imported')->exists()) {
                $commitService = app(StudentRosterCommitService::class);
                $commitService->syncCards($batch, $operator);
            }

            $this->refreshCounters($batch);
            $failed = $batch->rows()->where('status', 'failed')->exists();
            $batch->update([
                'status' => $failed ? 'partial' : 'completed',
                'completed_at' => now(),
            ]);

            return;
        }

        // Route to XLSX Roster Commit if applicable
        if ($batch->import_type === 'roster_update') {
            $commitService = app(StudentRosterCommitService::class);
            $statuses = $retryFailed ? ['failed'] : ['valid', 'warning'];

            $batch->rows()
                ->whereIn('status', $statuses)
                ->whereNotIn('action', ['conflict'])
                ->orderBy('row_number')
                ->chunkById(50, function ($rows) use ($batch, $operator, $commitService): void {
                    foreach ($rows as $row) {
                        try {
                            $commitService->processRow($row, $operator);
                        } catch (Throwable $exception) {
                            Log::warning('Student import roster row failed', [
                                'batch_id' => $batch->id,
                                'row_id' => $row->id,
                                'exception' => $exception,
                            ]);
                            $row->update([
                                'status' => 'failed',
                                'errors' => [['field' => '_system', 'message' => $exception->getMessage()]],
                            ]);
                        }
                    }
                });

            if ($batch->rows()->where('status', 'imported')->exists()) {
                $commitService->syncCards($batch, $operator);
            }

            $this->refreshCounters($batch);
            $failed = $batch->rows()->where('status', 'failed')->exists();
            $batch->update([
                'status' => $failed ? 'partial' : 'completed',
                'completed_at' => now(),
            ]);

            return;
        }

        $statuses = $retryFailed ? ['failed'] : ['valid', 'warning'];

        $batch->rows()->whereIn('status', $statuses)->orderBy('row_number')->chunkById(100, function ($rows) use ($batch, $operator): void {
            foreach ($rows as $row) {
                try {
                    $result = $this->intakeService->intake($batch->academy, $row->normalized_data, $operator);
                    $row->update([
                        'status' => 'imported',
                        'student_id' => $result['student']->id,
                        'errors' => null,
                    ]);
                } catch (Throwable $exception) {
                    Log::warning('Student import row failed', [
                        'batch_id' => $batch->id,
                        'row_id' => $row->id,
                        'exception' => $exception,
                    ]);
                    $messages = $exception instanceof ValidationException
                        ? collect($exception->errors())->flatten()->values()->all()
                        : ['Import failed for this row.'];
                    $row->update([
                        'status' => 'failed',
                        'errors' => collect($messages)->map(fn ($message) => ['field' => '_system', 'message' => $message])->all(),
                    ]);
                }
            }
        });

        $this->refreshCounters($batch);
        $failed = $batch->rows()->where('status', 'failed')->exists();
        $batch->update([
            'status' => $failed ? 'partial' : 'completed',
            'completed_at' => now(),
        ]);
    }

    public function refreshCounters(StudentImportBatch $batch): void
    {
        $counts = $batch->rows()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $batch->update([
            'total_rows' => $counts->sum(),
            'valid_rows' => ($counts['valid'] ?? 0) + ($counts['warning'] ?? 0) + ($counts['imported'] ?? 0),
            'invalid_rows' => $counts['invalid'] ?? 0,
            'imported_rows' => $counts['imported'] ?? 0,
            'skipped_rows' => $counts['skipped'] ?? 0,
        ]);
    }

    private function readCsv(string $path): array
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, Storage::disk('local')->get($path));
        rewind($stream);

        $headers = fgetcsv($stream);
        if ($headers === false) {
            fclose($stream);
            throw new \RuntimeException('CSV file is empty.');
        }

        $headers = array_map(fn ($value) => trim(strtolower(ltrim((string) $value, "\xEF\xBB\xBF"))), $headers);
        $rows = [];
        while (($values = fgetcsv($stream)) !== false) {
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $values = array_pad($values, count($headers), null);
            $rows[] = array_combine($headers, array_slice($values, 0, count($headers)));
        }
        fclose($stream);

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function normalizeAndValidateRow(StudentImportBatch $batch, array $raw, int $rowNumber, array &$seenCodes, array &$seenCitizens): array
    {
        $defaults = $batch->default_values ?? [];
        $code = trim((string) ($raw['student_code'] ?? ''));
        $citizenId = trim((string) ($raw['citizen_id'] ?? '')) ?: null;
        $grade = trim((string) ($raw['grade_level'] ?? ($defaults['grade_level'] ?? '')));
        $section = trim((string) ($raw['section'] ?? ($defaults['section'] ?? '')));
        $classroom = Classroom::query()
            ->where('academy_id', $batch->academy_id)
            ->where('academic_year_id', $batch->academic_year_id)
            ->where('grade_level', $grade)
            ->where('section', $section)
            ->first();

        $flat = [
            'student_code' => $code,
            'first_name_th' => trim((string) ($raw['first_name_th'] ?? '')),
            'last_name_th' => trim((string) ($raw['last_name_th'] ?? '')),
            'citizen_id' => $citizenId,
            'grade_level' => $grade,
            'section' => $section,
            'student_number' => ($raw['student_number'] ?? '') !== '' ? (int) $raw['student_number'] : null,
            'enrollment_date' => $this->normalizeDate($raw['enrollment_date'] ?? null) ?? ($defaults['enrollment_date'] ?? now()->toDateString()),
            'date_of_birth' => $this->normalizeDate($raw['date_of_birth'] ?? null),
            'gender' => $this->normalizeGender($raw['gender'] ?? null),
        ];

        $validator = Validator::make($flat, [
            'student_code' => ['required', 'string', 'max:20'],
            'first_name_th' => ['required', 'string', 'max:100'],
            'last_name_th' => ['required', 'string', 'max:100'],
            'citizen_id' => ['nullable', 'digits:13'],
            'grade_level' => ['required'],
            'section' => ['required'],
            'student_number' => ['nullable', 'integer', 'min:1'],
            'enrollment_date' => ['required', 'date'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'integer', 'in:0,1'],
        ]);
        $errors = collect($validator->errors()->messages())->flatMap(
            fn ($messages, $field) => collect($messages)->map(fn ($message) => ['field' => $field, 'message' => $message])
        )->values()->all();
        $warnings = [];

        if ($classroom === null) {
            $errors[] = ['field' => 'classroom', 'message' => "Classroom {$grade}/{$section} was not found."];
        }
        if (isset($seenCodes[$code])) {
            $errors[] = ['field' => 'student_code', 'message' => "Duplicate student code from row {$seenCodes[$code]}."];
        } else {
            $seenCodes[$code] = $rowNumber;
        }
        if ($citizenId && isset($seenCitizens[$citizenId])) {
            $errors[] = ['field' => 'citizen_id', 'message' => "Duplicate citizen ID from row {$seenCitizens[$citizenId]}."];
        } elseif ($citizenId) {
            $seenCitizens[$citizenId] = $rowNumber;
        }
        if (Student::where('academy_id', $batch->academy_id)->where('student_id', $code)->exists()) {
            $errors[] = ['field' => 'student_code', 'message' => 'Student code already exists in this academy.'];
        }
        if ($citizenId && Student::where('academy_id', $batch->academy_id)->where('citizen_id', $citizenId)->exists()) {
            $errors[] = ['field' => 'citizen_id', 'message' => 'Citizen ID already exists in this academy.'];
        }
        if ($citizenId && ! $this->validThaiCitizenChecksum($citizenId)) {
            $warnings[] = ['field' => 'citizen_id', 'message' => 'Citizen ID checksum may be invalid.'];
        }
        if ($classroom && $flat['student_number'] && ClassroomStudent::where('classroom_id', $classroom->id)->where('status', 'active')->where('student_number', $flat['student_number'])->exists()) {
            $errors[] = ['field' => 'student_number', 'message' => 'Student number is already used in this classroom.'];
        }

        $normalized = [
            'identity' => ['student_id' => $code, 'citizen_id' => $citizenId],
            'personal' => array_filter([
                'title_prefix_th' => trim((string) ($raw['title_th'] ?? '')) ?: null,
                'first_name_th' => $flat['first_name_th'],
                'last_name_th' => $flat['last_name_th'],
                'date_of_birth' => $flat['date_of_birth'],
                'gender' => $flat['gender'],
                'nationality' => trim((string) ($raw['nationality'] ?? '')) ?: 'ไทย',
            ], fn ($value) => $value !== null),
            'admission' => [
                'academic_year_id' => $batch->academic_year_id,
                'classroom_id' => $classroom?->id,
                'student_number' => $flat['student_number'],
                'enrollment_date' => $flat['enrollment_date'],
            ],
            'previous_school' => ['name' => trim((string) ($raw['previous_school'] ?? '')) ?: null],
            'guardians' => $this->normalizeGuardian($raw),
            'account' => ['mode' => $defaults['account_mode'] ?? 'pending_activation'],
        ];

        return [$normalized, $errors, $warnings];
    }

    private function normalizeGuardian(array $raw): array
    {
        $name = trim((string) ($raw['guardian_name'] ?? ''));
        if ($name === '') {
            return [];
        }
        $parts = preg_split('/\s+/u', $name, 2);

        return [[
            'guardian_type' => 'guardian',
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '-',
            'relationship' => trim((string) ($raw['guardian_relation'] ?? '')) ?: null,
            'is_primary_contact' => true,
            'contacts' => trim((string) ($raw['guardian_phone'] ?? '')) !== '' ? [[
                'contact_type' => 'mobile',
                'contact_value' => trim((string) $raw['guardian_phone']),
                'is_primary' => true,
            ]] : [],
        ]];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
            }
        }

        return $value;
    }

    private function normalizeGender(mixed $value): ?int
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            '1', 'male', 'm', 'ชาย' => 1,
            '0', 'female', 'f', 'หญิง' => 0,
            '', '-' => null,
            default => -1,
        };
    }

    private function validThaiCitizenChecksum(string $id): bool
    {
        if (! preg_match('/^\d{13}$/', $id)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $id[$i]) * (13 - $i);
        }

        return (11 - ($sum % 11)) % 10 === (int) $id[12];
    }
}
