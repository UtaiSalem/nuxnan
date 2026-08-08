<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\HouseAssignmentBatch;
use App\Models\SportsEdition;
use App\Services\Sports\HouseAssignmentService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HouseAssignmentController extends Controller
{
    public function __construct(private readonly HouseAssignmentService $service) {}

    private function scoped(Academy $academy, HouseAssignmentBatch $batch): HouseAssignmentBatch
    {
        abort_unless((int) $batch->academy_id === (int) $academy->id, 404);

        return $batch;
    }

    private function edition(Academy $academy, int $id): SportsEdition
    {
        $edition = SportsEdition::find($id);
        abort_unless($edition && (int) $edition->academy_id === (int) $academy->id, 422);

        return $edition;
    }

    public function index(Request $request, Academy $academy)
    {
        $query = HouseAssignmentBatch::where('academy_id', $academy->id)->latest();
        if ($request->filled('edition_id')) {
            $query->where('edition_id', $request->integer('edition_id'));
        }

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function current(Request $request, Academy $academy)
    {
        $request->validate(['edition_id' => ['required', 'integer']]);
        $editionId = $request->integer('edition_id');
        $edition = $this->edition($academy, $editionId);

        $members = DB::table('house_memberships')
            ->where('edition_id', $editionId)
            ->selectRaw('house_group_id, COUNT(*) as count')
            ->groupBy('house_group_id')
            ->pluck('count', 'house_group_id');

        $counts = [];
        foreach ($edition->houseGroupIds() as $houseId) {
            $counts[$houseId] = $members->get($houseId, 0);
        }

        return response()->json(['edition_id' => $editionId, 'counts' => $counts]);
    }

    public function previewRandom(Request $request, Academy $academy)
    {
        $request->validate(['edition_id' => ['required', 'integer'], 'house_group_ids' => ['required', 'array', 'min:2'], 'house_group_ids.*' => ['integer'], 'strategy' => ['nullable', 'in:stratified,pure_random'], 'scope' => ['nullable', 'in:unassigned_only,all'], 'seed' => ['nullable', 'integer'], 'balance_gender' => ['nullable', 'boolean']]);
        try {
            $batch = $this->service->previewRandom($academy, $this->edition($academy, $request->integer('edition_id')), $request->all(), $request->user());

            return response()->json(['batch' => $batch], 201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function previewImport(Request $request, Academy $academy)
    {
        $request->validate(['edition_id' => ['required', 'integer'], 'file' => ['required', 'file', 'mimes:csv,txt,xlsx'], 'column_mapping' => ['required'], 'on_conflict' => ['nullable', 'in:skip,overwrite']]);
        try {
            return response()->json(['batch' => $this->service->previewImport($academy, $this->edition($academy, $request->integer('edition_id')), $request->file('file'), $request->all(), $request->user())], 201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function template(Academy $academy): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            // หัวคอลัมน์ต้องตรงกับค่าเริ่มต้นในหน้าจอนำเข้า ไม่งั้นครูที่โหลดไฟล์นี้ไปกรอก
            // แล้วอัปกลับมาจะเจอ "ไม่พบนักเรียน" ทั้งไฟล์เพราะแม็พคอลัมน์ไม่ตรง
            fputcsv($out, ['เลขประจำตัว', 'คณะสี', 'ชื่อ', 'นามสกุล']);
            fputcsv($out, ['12345', 'แดง', '', '']);
            fclose($out);
        }, 'house-assignment-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(Academy $academy, HouseAssignmentBatch $batch)
    {
        return response()->json(['batch' => $this->scoped($academy, $batch)]);
    }

    public function rows(Request $request, Academy $academy, HouseAssignmentBatch $batch)
    {
        $batch = $this->scoped($academy, $batch);
        $q = $batch->rows()->with('student');
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        return response()->json($q->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function commit(Request $request, Academy $academy, HouseAssignmentBatch $batch)
    {
        try {
            $this->service->commit($this->scoped($academy, $batch), $request->user());

            return response()->json(['batch' => $batch->fresh()]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function undo(Request $request, Academy $academy, HouseAssignmentBatch $batch)
    {
        try {
            $this->service->undo($this->scoped($academy, $batch), $request->user());

            return response()->json(['batch' => $batch->fresh()]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function discard(Academy $academy, HouseAssignmentBatch $batch)
    {
        $batch = $this->scoped($academy, $batch);
        abort_unless($batch->status === 'draft', 422);
        $batch->delete();

        return response()->noContent();
    }
}
