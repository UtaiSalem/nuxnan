<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\StudentImportBatch;
use App\Services\Import\StudentRosterUpdateService;
use App\Services\Import\StudentRosterXlsxParser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RosterPreviewCommand extends Command
{
    protected $signature = 'roster:preview {file} {--academy=1} {--year=2}';

    protected $description = 'Preview student roster import from XLSX and show stats of matching categories';

    public function handle(
        StudentRosterXlsxParser $parser,
        StudentRosterUpdateService $updateService
    ): int {
        $filePath = $this->argument('file');
        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $academyId = (int) $this->option('academy');
        $academy = Academy::find($academyId);
        if (! $academy) {
            $this->error("Academy ID {$academyId} not found.");

            return 1;
        }

        $yearId = (int) $this->option('year');
        $year = AcademicYear::where('academy_id', $academyId)->find($yearId);
        if (! $year) {
            $this->error("Academic Year ID {$yearId} not found for Academy ID {$academyId}.");

            return 1;
        }

        $this->info("Parsing XLSX file: {$filePath}...");
        try {
            $parsedRows = $parser->parse($filePath);
        } catch (\Exception $e) {
            $this->error('Failed to parse XLSX: '.$e->getMessage());

            return 1;
        }

        $this->info('Found '.$parsedRows->count().' rows (excluding kindergarten & primary levels).');

        // Create temporary or preview batch in DB
        $idempotencyKey = hash('sha256', $academyId.':'.Str::uuid()->toString());
        $batch = StudentImportBatch::create([
            'academy_id' => $academyId,
            'academic_year_id' => $yearId,
            'import_type' => 'roster_update',
            'source_format' => 'xlsx',
            'filename' => $filePath,
            'original_filename' => basename($filePath),
            'status' => 'uploaded',
            'idempotency_key' => $idempotencyKey,
            'created_by' => 1, // Assume first system user or CLI operator
        ]);

        $this->info('Analyzing and matching identity data...');
        $updateService->preview($academy, $year, $batch, $parsedRows);

        $batch->refresh();
        $this->info("Matching analysis completed. Batch ID: {$batch->id}");

        // Gather statistics
        $rows = $batch->rows;
        $stats = [
            'unchanged' => $rows->where('action', 'unchanged')->count(),
            'update_identity' => $rows->where('action', 'update_identity')->count(),
            'update_personal' => $rows->where('action', 'update_personal')->count(),
            'move_classroom' => $rows->where('action', 'move_classroom')->count(),
            'create_enrollment' => $rows->where('action', 'create_enrollment')->count(),
            'new_student' => $rows->where('action', 'new_student')->count(),
            'conflict' => $rows->where('action', 'conflict')->count(),
            'invalid' => $rows->where('status', 'invalid')->count(),
        ];

        $this->table(
            ['Action Category', 'Count'],
            [
                ['Unchanged (MATCHED)', $stats['unchanged']],
                ['Update Identity (CODE_ONLY_MATCH)', $stats['update_identity']],
                ['Update Personal (Personal Info Differs)', $stats['update_personal']],
                ['Move Classroom (Section Differs)', $stats['move_classroom']],
                ['Create Enrollment (New intake/promoted)', $stats['create_enrollment']],
                ['New Student (Completely New)', $stats['new_student']],
                ['Conflict (Code vs Citizen mismatch)', $stats['conflict']],
                ['Invalid (Validation Failed)', $stats['invalid']],
            ]
        );

        if ($stats['conflict'] > 0) {
            $this->warn("WARNING: There are {$stats['conflict']} conflict(s) that require manual resolution before commit.");
        }

        if ($stats['invalid'] > 0) {
            $this->error("ERROR: There are {$stats['invalid']} invalid row(s) that failed validation.");
        }

        return 0;
    }
}
