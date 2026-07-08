<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Enrollment\UploadStudentImportRequest;
use App\Http\Resources\Learn\Academy\Enrollment\StudentImportBatchResource;
use App\Http\Resources\Learn\Academy\Enrollment\StudentImportRowResource;
use App\Jobs\ProcessStudentImportBatchJob;
use App\Jobs\ValidateStudentImportBatchJob;
use App\Models\Academy;
use App\Models\StudentImportBatch;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportController extends Controller
{
    public function index(Request $request, Academy $academy)
    {
        $this->authorizeImport($academy);
        $batches = StudentImportBatch::where('academy_id', $academy->id)
            ->latest()->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return StudentImportBatchResource::collection($batches);
    }

    public function upload(UploadStudentImportRequest $request, Academy $academy): JsonResponse
    {
        $key = $request->input('idempotency_key') ?: $request->header('Idempotency-Key') ?: Str::uuid()->toString();
        $existing = StudentImportBatch::where('idempotency_key', hash('sha256', $academy->id.':'.$key))->first();
        if ($existing) {
            abort_unless($existing->academy_id === $academy->id, 404);

            return response()->json(['success' => true, 'data' => (new StudentImportBatchResource($existing))->resolve($request)], 200);
        }

        $file = $request->file('file');
        $path = $file->store("student-imports/{$academy->id}", 'local');
        try {
            $batch = StudentImportBatch::create([
                'academy_id' => $academy->id,
                'academic_year_id' => $request->integer('academic_year_id'),
                'import_type' => $request->input('import_type', 'new_intake'),
                'filename' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'status' => 'uploaded',
                'default_values' => $request->input('defaults', []),
                'idempotency_key' => hash('sha256', $academy->id.':'.$key),
                'created_by' => $request->user()->id,
            ]);
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($path);
            $batch = StudentImportBatch::where('idempotency_key', hash('sha256', $academy->id.':'.$key))->first();
            if (! $batch) {
                throw $exception;
            }

            return response()->json(['success' => true, 'data' => (new StudentImportBatchResource($batch))->resolve($request)]);
        }

        ValidateStudentImportBatchJob::dispatch($batch->id);

        return response()->json(['success' => true, 'data' => (new StudentImportBatchResource($batch))->resolve($request)], 202);
    }

    public function template(Academy $academy): StreamedResponse
    {
        $this->authorizeImport($academy);
        $headers = ['student_code', 'title_th', 'first_name_th', 'last_name_th', 'citizen_id', 'date_of_birth', 'gender', 'nationality', 'grade_level', 'section', 'student_number', 'enrollment_date', 'previous_school', 'guardian_name', 'guardian_phone', 'guardian_relation'];

        return response()->streamDownload(function () use ($headers): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            fclose($output);
        }, 'student-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(Request $request, Academy $academy, StudentImportBatch $batch): JsonResponse
    {
        $this->authorizeBatch($academy, $batch);

        return response()->json(['success' => true, 'data' => (new StudentImportBatchResource($batch))->resolve($request)]);
    }

    public function rows(Request $request, Academy $academy, StudentImportBatch $batch)
    {
        $this->authorizeBatch($academy, $batch);
        $query = $batch->rows()->orderBy('row_number');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return StudentImportRowResource::collection($query->paginate(max(1, min($request->integer('per_page', 50), 200))));
    }

    public function confirm(Request $request, Academy $academy, StudentImportBatch $batch): JsonResponse
    {
        $this->authorizeBatch($academy, $batch);
        $updated = StudentImportBatch::whereKey($batch->id)->where('status', 'validated')->update([
            'status' => 'processing', 'confirmed_by' => $request->user()->id, 'confirmed_at' => now(),
        ]);
        abort_unless($updated === 1, 409, 'Only validated batches can be confirmed.');
        ProcessStudentImportBatchJob::dispatch($batch->id);

        return response()->json(['success' => true, 'data' => (new StudentImportBatchResource($batch->fresh()))->resolve($request)], 202);
    }

    public function retry(Request $request, Academy $academy, StudentImportBatch $batch): JsonResponse
    {
        $this->authorizeBatch($academy, $batch);
        $updated = StudentImportBatch::whereKey($batch->id)->whereIn('status', ['partial', 'failed'])->update([
            'status' => 'processing', 'confirmed_by' => $request->user()->id, 'confirmed_at' => now(), 'completed_at' => null,
        ]);
        abort_unless($updated === 1, 409, 'Only failed or partial batches can be retried.');
        ProcessStudentImportBatchJob::dispatch($batch->id, true);

        return response()->json(['success' => true, 'data' => (new StudentImportBatchResource($batch->fresh()))->resolve($request)], 202);
    }

    public function errors(Academy $academy, StudentImportBatch $batch): StreamedResponse
    {
        $this->authorizeBatch($academy, $batch);
        $rows = $batch->rows()->whereIn('status', ['invalid', 'failed'])->orderBy('row_number')->get();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['row_number', 'student_code', 'status', 'errors']);
            foreach ($rows as $row) {
                $code = (string) ($row->raw_data['student_code'] ?? '');
                if (preg_match('/^[=+\-@]/', $code)) {
                    $code = "'".$code;
                }
                fputcsv($output, [$row->row_number, $code, $row->status, collect($row->errors)->pluck('message')->implode(' | ')]);
            }
            fclose($output);
        }, "student-import-errors-{$batch->id}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function cancel(Academy $academy, StudentImportBatch $batch): JsonResponse
    {
        $this->authorizeBatch($academy, $batch);
        abort_if(in_array($batch->status, ['processing', 'completed', 'partial'], true), 409, 'This batch can no longer be cancelled.');
        $batch->update(['status' => 'cancelled']);
        Storage::disk('local')->delete($batch->filename);

        return response()->json(['success' => true]);
    }

    private function authorizeImport(Academy $academy): void
    {
        abort_unless(Gate::allows('student.import', $academy), 403);
    }

    private function authorizeBatch(Academy $academy, StudentImportBatch $batch): void
    {
        $this->authorizeImport($academy);
        abort_unless($batch->academy_id === $academy->id, 404);
    }
}
