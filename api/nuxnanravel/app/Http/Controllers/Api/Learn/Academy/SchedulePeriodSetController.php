<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\ClassSchedule;
use App\Models\SchedulePeriod;
use App\Models\SchedulePeriodSet;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * SC-S6 — CRUD ของชุดโครงคาบเรียน
 *
 * ชุดหนึ่งถือคาบเป็นลูก และแก้ไขแบบ "ส่งรายการคาบทั้งชุดมาทีเดียว"
 * โดยจับคู่ของเดิมด้วย `period_number` เพื่อให้ id ของคาบไม่เปลี่ยน
 * (`class_schedules.period_id` อ้างถึงอยู่)
 */
class SchedulePeriodSetController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function index(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        $query = SchedulePeriodSet::with(['periods' => fn ($q) => $q->ordered()])
            ->byAcademy($academy->id)
            ->ordered();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request, $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($error = $this->periodsProblem($request->input('periods', []))) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['periods' => [$error]],
            ], 422);
        }

        $set = DB::transaction(function () use ($request, $academy) {
            $set = SchedulePeriodSet::create([
                'academy_id' => $academy->id,
                'name' => $request->name,
                'days' => $this->cleanDays($request->input('days')),
                'grade_levels' => $this->cleanLevels($request->input('grade_levels')),
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
                'display_order' => $request->input('display_order', 0),
                'created_by' => $request->user()->id,
            ]);

            $this->syncPeriods($set, $request->input('periods', []));
            $this->keepSingleDefault($set);

            return $set;
        });

        $set->load(['periods' => fn ($q) => $q->ordered()]);

        $this->auditLogService->logCreate($set, 'schedule_period_sets');

        return response()->json([
            'success' => true,
            'message' => 'สร้างชุดโครงคาบสำเร็จ',
            'data' => $set,
        ], 201);
    }

    public function update(Request $request, $academyId, $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        $set = SchedulePeriodSet::where('academy_id', $academy->id)->findOrFail($id);

        $validator = Validator::make($request->all(), $this->rules(false));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('periods')) {
            if ($error = $this->periodsProblem($request->input('periods', []))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => ['periods' => [$error]],
                ], 422);
            }
        }

        $oldValues = $set->toArray();

        DB::transaction(function () use ($request, $set) {
            $payload = [];

            if ($request->has('name')) {
                $payload['name'] = $request->name;
            }
            if ($request->has('days')) {
                $payload['days'] = $this->cleanDays($request->input('days'));
            }
            if ($request->has('grade_levels')) {
                $payload['grade_levels'] = $this->cleanLevels($request->input('grade_levels'));
            }
            if ($request->has('is_default')) {
                $payload['is_default'] = $request->boolean('is_default');
            }
            if ($request->has('is_active')) {
                $payload['is_active'] = $request->boolean('is_active');
            }
            if ($request->has('display_order')) {
                $payload['display_order'] = (int) $request->input('display_order');
            }

            if (! empty($payload)) {
                $set->update($payload);
            }

            if ($request->has('periods')) {
                $this->syncPeriods($set, $request->input('periods', []));
            }

            $this->keepSingleDefault($set);
        });

        $set->refresh()->load(['periods' => fn ($q) => $q->ordered()]);

        $this->auditLogService->logUpdate($set, $oldValues, 'schedule_period_sets');

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทชุดโครงคาบสำเร็จ',
            'data' => $set,
        ]);
    }

    public function destroy(Request $request, $academyId, $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        $set = SchedulePeriodSet::where('academy_id', $academy->id)->findOrFail($id);

        $this->auditLogService->logDelete($set, 'schedule_period_sets');

        DB::transaction(function () use ($set) {
            $periodIds = $set->periods()->pluck('id');

            if ($periodIds->isNotEmpty()) {
                ClassSchedule::whereIn('period_id', $periodIds)->update(['period_id' => null]);
                SchedulePeriod::whereIn('id', $periodIds)->delete();
            }

            $set->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'ลบชุดโครงคาบสำเร็จ',
        ]);
    }

    private function rules(bool $creating = true): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'name' => $required.'|string|max:100',
            'days' => 'nullable|array',
            'days.*' => 'integer|between:1,7',
            'grade_levels' => 'nullable|array',
            'grade_levels.*' => 'string|max:50',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'display_order' => 'nullable|integer|min:0',
            'periods' => $required.'|array|min:1|max:30',
            'periods.*.period_number' => 'required|integer|min:1|max:60',
            'periods.*.name' => 'required|string|max:50',
            'periods.*.start_time' => 'required|date_format:H:i',
            'periods.*.end_time' => 'required|date_format:H:i|after:periods.*.start_time',
            'periods.*.period_type' => ['nullable', Rule::in(array_keys(SchedulePeriod::TYPES))],
            'periods.*.display_order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * คืนข้อความปัญหาถ้ารายการคาบในชุดขัดกันเอง (เลขคาบซ้ำ / เวลาซ้อนกันเอง) ไม่งั้นคืน null
     */
    private function periodsProblem(array $periods): ?string
    {
        $numbers = [];

        foreach ($periods as $period) {
            $number = (int) ($period['period_number'] ?? 0);
            if (isset($numbers[$number])) {
                return 'เลขคาบซ้ำกันภายในชุดเดียวกัน (คาบที่ '.$number.')';
            }
            $numbers[$number] = true;
        }

        $sorted = collect($periods)->sortBy(fn ($period) => $period['start_time'] ?? '')->values();

        for ($i = 1; $i < $sorted->count(); $i++) {
            $previous = $sorted[$i - 1];
            $current = $sorted[$i];

            if (strtotime($current['start_time']) < strtotime($previous['end_time'])) {
                return 'ช่วงเวลาของคาบซ้อนทับกันเอง ('.$previous['name'].' กับ '.$current['name'].')';
            }
        }

        return null;
    }

    private function cleanDays($days): ?array
    {
        if (! is_array($days)) {
            return null;
        }

        $days = array_values(array_unique(array_map('intval', $days)));
        sort($days);

        return empty($days) ? null : $days;
    }

    private function cleanLevels($levels): ?array
    {
        if (! is_array($levels)) {
            return null;
        }

        $levels = array_values(array_unique(array_filter(array_map('trim', $levels), fn ($level) => $level !== '')));

        return empty($levels) ? null : $levels;
    }

    /**
     * เขียนรายการคาบของชุดใหม่ทั้งชุด โดยจับคู่ของเดิมด้วย `period_number` เพื่อคง id เดิมไว้
     * คาบที่หายไปจากรายการ: เคลียร์ `class_schedules.period_id` ที่อ้างถึงก่อนแล้วค่อยลบ
     */
    private function syncPeriods(SchedulePeriodSet $set, array $periods): void
    {
        $existing = $set->periods()->get()->keyBy('period_number');
        $keptIds = [];

        foreach (array_values($periods) as $index => $period) {
            $attributes = [
                'academy_id' => $set->academy_id,
                'set_id' => $set->id,
                'period_number' => (int) $period['period_number'],
                'name' => $period['name'],
                'start_time' => $period['start_time'],
                'end_time' => $period['end_time'],
                'period_type' => $period['period_type'] ?? SchedulePeriod::TYPE_CLASS,
                'display_order' => isset($period['display_order']) ? (int) $period['display_order'] : $index + 1,
                'is_active' => true,
            ];

            $row = $existing->get((int) $period['period_number']);

            if ($row) {
                $row->update($attributes);
                $keptIds[] = $row->id;
            } else {
                $keptIds[] = SchedulePeriod::create($attributes)->id;
            }
        }

        $removed = $set->periods()->whereNotIn('id', $keptIds ?: [0])->pluck('id');

        if ($removed->isNotEmpty()) {
            ClassSchedule::whereIn('period_id', $removed)->update(['period_id' => null]);
            SchedulePeriod::whereIn('id', $removed)->delete();
        }
    }

    /**
     * โรงเรียนหนึ่งมีชุดเริ่มต้นได้ชุดเดียว
     */
    private function keepSingleDefault(SchedulePeriodSet $set): void
    {
        if (! $set->fresh()->is_default) {
            return;
        }

        SchedulePeriodSet::byAcademy($set->academy_id)
            ->where('id', '!=', $set->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
