# Phase M — Gamification: School Level + Classroom Leaderboard

อ้างอิง: Phases C-L [✅]
Target: เพิ่ม Level/XP สำหรับโรงเรียน + Leaderboard ห้องเรียน ตามที่ design [School Homepage.html](./School Homepage.html) แสดง
วันที่: 2026-06-20

---

## 📌 Decisions ที่ผู้ใช้เลือก (จาก Q&A)

| Topic | คำตอบ | Implication |
|---|---|---|
| **XP sources** | All — posts+reactions, achievements, attendance, course completion | ต้อง hook event ทุกประเภท |
| **Classroom pts sources** | All — attendance, GPA, assignment+activity | aggregate หลาย source |
| **Refresh** | **Realtime on event** | Service::award() ทุก event → inc counter ทันที |
| **Reset** | ต่อเดือน/สัปดาห์ | Cycle-based aggregate (week/month key) |
| **Formula** | "คิดสูตรระหว่างทำ" | ใส่ default rates ใน config + tune ทีหลังได้ |

**สูตรเริ่มต้น (ปรับใน config):**

```
Level formula: level = floor(sqrt(total_xp / 1000))
  → lv 1 = 1k, lv 2 = 4k, lv 5 = 25k, lv 10 = 100k, lv 12 = 144k

School XP rates:
  post.created                = 10 XP
  post.like_received          = 1 XP
  post.comment_received       = 2 XP
  attendance.recorded         = 5 XP/student/day
  course.completed            = 50 XP
  achievement.awarded         = 100 XP
  event.attended              = 20 XP

Classroom point rates:
  attendance.checkin          = 1 pt
  assignment.submitted_on_time= 5 pt
  assignment.submitted_late   = 2 pt
  activity.participated       = 10 pt
  gpa.weekly_top_3            = 100 pt (top 3 GPA ห้อง)
```

---

# 🗺️ Phase M Sub-phases Overview

| # | งาน | Est. |
|---|---|---|
| **M.0** | Backend: migrations (`xp_events`, `school_xp_cycles`, `classroom_point_cycles`) | 1 hr |
| **M.1** | `XpService` + `ClassroomPointsService` (award + cycle resolve + level calc) | 1.5 hr |
| **M.2** | Config files: `xp_rates.php` + `gamification.php` | 30 min |
| **M.3** | Event hooks (Eloquent observers + service calls in controllers) | 2 hr |
| **M.4** | Endpoints: school XP summary + classroom leaderboard | 1 hr |
| **M.5** | Cover Level badge wiring (เชื่อม `academy.level`) | 30 min |
| **M.6** | `SchoolLevelCard.vue` (left sidebar — Phase A placeholder) | 1 hr |
| **M.7** | `SchoolClassroomLeaderboard.vue` (right sidebar — Phase A placeholder) | 1.5 hr |
| **M.8** | Scheduled cron: weekly/monthly reset + historical archive | 1 hr |
| **M.9** | Backfill seeder: คำนวณ XP จากข้อมูลที่มีอยู่ | 1 hr |
| **M.10** | Audit page (admin): ดู XP events log + leaderboard history | 1 hr |
| **รวม** | | **~12 ชม.** |

---

# M.0 — Migrations (3 ตัว)

## M.0.1 — `xp_events` (event log — immutable)

**File:** `2026_06_20_200001_create_xp_events_table.php`

```php
return new class extends Migration {
    public function up(): void {
        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who triggered
            $table->foreignId('classroom_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
            $table->string('source', 64); // 'post.created' / 'attendance.recorded' / ...
            $table->integer('xp');         // school XP awarded (0 if classroom-only)
            $table->integer('classroom_pts')->default(0); // classroom pts (0 if school-only)
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['academy_id', 'occurred_at']);
            $table->index(['classroom_group_id', 'occurred_at']);
            $table->index(['source']);
        });
    }
    public function down(): void { Schema::dropIfExists('xp_events'); }
};
```

## M.0.2 — `school_xp_cycles` (aggregate per academy per cycle)

**File:** `2026_06_20_200002_create_school_xp_cycles_table.php`

```php
return new class extends Migration {
    public function up(): void {
        Schema::create('school_xp_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('cycle_type', 16); // 'week' | 'month' | 'all_time'
            $table->string('cycle_key', 32);  // '2026-W26', '2026-06', 'all'
            $table->date('cycle_start');
            $table->date('cycle_end')->nullable();
            $table->unsignedBigInteger('total_xp')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->timestamps();
            $table->unique(['academy_id', 'cycle_type', 'cycle_key'], 'school_xp_cycle_unique');
            $table->index(['cycle_type', 'cycle_key']);
        });
    }
    public function down(): void { Schema::dropIfExists('school_xp_cycles'); }
};
```

## M.0.3 — `classroom_point_cycles` (aggregate per classroom per cycle)

**File:** `2026_06_20_200003_create_classroom_point_cycles_table.php`

```php
return new class extends Migration {
    public function up(): void {
        Schema::create('classroom_point_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('cycle_type', 16);
            $table->string('cycle_key', 32);
            $table->date('cycle_start');
            $table->date('cycle_end')->nullable();
            $table->unsignedBigInteger('total_points')->default(0);
            $table->timestamps();
            $table->unique(['classroom_group_id', 'cycle_type', 'cycle_key'], 'classroom_point_cycle_unique');
            $table->index(['academy_id', 'cycle_type', 'cycle_key']);
            $table->index(['total_points']); // for leaderboard ORDER BY DESC
        });
    }
    public function down(): void { Schema::dropIfExists('classroom_point_cycles'); }
};
```

> 💡 **ทำไม cycle-based aggregate?**
> - reset โดยไม่ลบประวัติ — แค่สร้าง cycle ใหม่
> - leaderboard query ง่าย: `WHERE cycle_type='week' AND cycle_key='2026-W26' ORDER BY total_points DESC LIMIT 3`
> - all-time view = cycle_type='all_time'

---

# M.1 — Services

## M.1.1 — XpService

**File:** `app/Services/Gamification/XpService.php` (NEW)

```php
<?php

namespace App\Services\Gamification;

use App\Models\Academy;
use App\Models\SchoolXpCycle;
use App\Models\XpEvent;
use Carbon\Carbon;

class XpService
{
    /**
     * Award XP for an action. Logs xp_events + increments active cycles.
     */
    public function award(
        Academy $academy,
        string $source,
        int $xp,
        ?int $userId = null,
        ?int $classroomGroupId = null,
        array $metadata = []
    ): XpEvent {
        // 1. Log immutable event
        $event = XpEvent::create([
            'academy_id'         => $academy->id,
            'user_id'            => $userId,
            'classroom_group_id' => $classroomGroupId,
            'source'             => $source,
            'xp'                 => $xp,
            'classroom_pts'      => 0,
            'metadata'           => $metadata,
            'occurred_at'        => now(),
        ]);

        // 2. Update aggregates for active cycles (week, month, all_time)
        foreach ($this->activeCycles() as $cycle) {
            $row = SchoolXpCycle::firstOrCreate(
                [
                    'academy_id' => $academy->id,
                    'cycle_type' => $cycle['type'],
                    'cycle_key'  => $cycle['key'],
                ],
                [
                    'cycle_start' => $cycle['start'],
                    'cycle_end'   => $cycle['end'],
                    'total_xp'    => 0,
                    'level'       => 1,
                ]
            );
            $row->increment('total_xp', $xp);
            $row->level = $this->levelFromXp($row->total_xp);
            $row->save();
        }

        return $event;
    }

    public function levelFromXp(int $xp): int
    {
        return max(1, (int) floor(sqrt($xp / 1000)));
    }

    public function xpToNextLevel(int $xp): int
    {
        $level = $this->levelFromXp($xp);
        return ($level + 1) ** 2 * 1000;
    }

    /**
     * Compute current cycles (week, month, all_time)
     */
    protected function activeCycles(): array
    {
        $now = Carbon::now();
        return [
            [
                'type'  => 'week',
                'key'   => $now->format('o-\WW'), // ISO year-week e.g. '2026-W26'
                'start' => $now->copy()->startOfWeek(),
                'end'   => $now->copy()->endOfWeek(),
            ],
            [
                'type'  => 'month',
                'key'   => $now->format('Y-m'),
                'start' => $now->copy()->startOfMonth(),
                'end'   => $now->copy()->endOfMonth(),
            ],
            [
                'type'  => 'all_time',
                'key'   => 'all',
                'start' => Carbon::create(2020, 1, 1),
                'end'   => null,
            ],
        ];
    }

    public function summary(Academy $academy, string $cycleType = 'all_time'): array
    {
        $key = match ($cycleType) {
            'week'  => Carbon::now()->format('o-\WW'),
            'month' => Carbon::now()->format('Y-m'),
            default => 'all',
        };
        $row = SchoolXpCycle::where('academy_id', $academy->id)
            ->where('cycle_type', $cycleType)
            ->where('cycle_key', $key)
            ->first();
        $total = $row?->total_xp ?? 0;
        $level = $this->levelFromXp($total);
        $next  = $this->xpToNextLevel($total);
        $prev  = $level ** 2 * 1000;
        $pct   = $next > $prev ? (int) (100 * ($total - $prev) / ($next - $prev)) : 0;
        return [
            'cycle_type' => $cycleType,
            'cycle_key'  => $key,
            'total_xp'   => $total,
            'level'      => $level,
            'next_level' => $level + 1,
            'xp_to_next' => max(0, $next - $total),
            'progress_pct' => max(0, min(100, $pct)),
        ];
    }
}
```

## M.1.2 — ClassroomPointsService

**File:** `app/Services/Gamification/ClassroomPointsService.php` (NEW)

```php
<?php

namespace App\Services\Gamification;

use App\Models\AcademyGroup;
use App\Models\ClassroomPointCycle;
use App\Models\XpEvent;
use Carbon\Carbon;

class ClassroomPointsService
{
    public function award(
        AcademyGroup $classroom,
        string $source,
        int $points,
        ?int $userId = null,
        array $metadata = []
    ): XpEvent {
        $event = XpEvent::create([
            'academy_id'         => $classroom->academy_id,
            'user_id'            => $userId,
            'classroom_group_id' => $classroom->id,
            'source'             => $source,
            'xp'                 => 0,
            'classroom_pts'      => $points,
            'metadata'           => $metadata,
            'occurred_at'        => now(),
        ]);

        foreach ($this->activeCycles() as $cycle) {
            $row = ClassroomPointCycle::firstOrCreate(
                [
                    'classroom_group_id' => $classroom->id,
                    'cycle_type'         => $cycle['type'],
                    'cycle_key'          => $cycle['key'],
                ],
                [
                    'academy_id'   => $classroom->academy_id,
                    'cycle_start'  => $cycle['start'],
                    'cycle_end'    => $cycle['end'],
                    'total_points' => 0,
                ]
            );
            $row->increment('total_points', $points);
        }

        return $event;
    }

    protected function activeCycles(): array
    {
        // เหมือน XpService (consider extracting to trait)
        $now = Carbon::now();
        return [
            ['type' => 'week',     'key' => $now->format('o-\WW'), 'start' => $now->copy()->startOfWeek(),  'end' => $now->copy()->endOfWeek()],
            ['type' => 'month',    'key' => $now->format('Y-m'),    'start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth()],
            ['type' => 'all_time', 'key' => 'all',                  'start' => Carbon::create(2020,1,1),     'end' => null],
        ];
    }

    /**
     * Top N classrooms in academy by points for given cycle
     */
    public function leaderboard(int $academyId, string $cycleType = 'month', int $limit = 3): array
    {
        $key = match ($cycleType) {
            'week'  => Carbon::now()->format('o-\WW'),
            'month' => Carbon::now()->format('Y-m'),
            default => 'all',
        };
        return ClassroomPointCycle::with('classroomGroup:id,name,type')
            ->where('academy_id', $academyId)
            ->where('cycle_type', $cycleType)
            ->where('cycle_key', $key)
            ->whereHas('classroomGroup', fn ($q) => $q->where('type', 'classroom'))
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get()
            ->map(fn ($r, $i) => [
                'rank'      => $i + 1,
                'group_id'  => $r->classroom_group_id,
                'name'      => $r->classroomGroup->name,
                'points'    => $r->total_points,
            ])
            ->all();
    }
}
```

> ⚠️ **Models ที่ต้องสร้าง**: `XpEvent`, `SchoolXpCycle`, `ClassroomPointCycle` (basic Eloquent models — fillable + casts + relations)

---

# M.2 — Config files

## M.2.1 — XP rates

**File:** `config/xp_rates.php` (NEW)

```php
<?php

return [
    'school' => [
        'post.created'          => 10,
        'post.like_received'    => 1,
        'post.comment_received' => 2,
        'attendance.recorded'   => 5,
        'course.completed'      => 50,
        'achievement.awarded'   => 100,
        'event.attended'        => 20,
    ],
    'classroom' => [
        'attendance.checkin'           => 1,
        'assignment.submitted_on_time' => 5,
        'assignment.submitted_late'    => 2,
        'activity.participated'        => 10,
        'gpa.weekly_top_3'             => 100,
    ],
];
```

ใช้: `config('xp_rates.school.post.created')` → 10

## M.2.2 — Gamification settings

**File:** `config/gamification.php` (NEW)

```php
<?php

return [
    'level_formula' => [
        'base'      => 1000,       // XP for level 1
        'curve'     => 'sqrt',     // 'sqrt' | 'linear' | 'exponential'
    ],
    'leaderboard_cycle' => env('LEADERBOARD_CYCLE', 'month'), // week | month
    'cache_ttl_seconds' => 60,
];
```

---

# M.3 — Event hooks

## M.3.1 — Hook into AcademyPost::created

**File:** `app/Observers/AcademyPostObserver.php` (NEW)

```php
<?php

namespace App\Observers;

use App\Models\AcademyPost;
use App\Services\Gamification\XpService;

class AcademyPostObserver
{
    public function __construct(private XpService $xp) {}

    public function created(AcademyPost $post): void
    {
        if (!$post->academy_id || !$post->user_id) return;
        $this->xp->award(
            $post->academy,
            'post.created',
            config('xp_rates.school.post.created', 10),
            $post->user_id,
            null,
            ['post_id' => $post->id, 'post_type' => $post->post_type ?? 'regular']
        );
    }
}
```

**Register:** `app/Providers/AppServiceProvider.php`
```php
public function boot(): void
{
    \App\Models\AcademyPost::observe(\App\Observers\AcademyPostObserver::class);
    \App\Models\AcademyPostLike::observe(\App\Observers\AcademyPostLikeObserver::class);
    \App\Models\AcademyPostComment::observe(\App\Observers\AcademyPostCommentObserver::class);
}
```

## M.3.2 — Like / comment observers (same pattern)

**File:** `app/Observers/AcademyPostLikeObserver.php`

```php
public function created(AcademyPostLike $like): void
{
    $post = $like->post; // belongsTo
    if (!$post || !$post->user_id) return;
    $this->xp->award(
        $post->academy,
        'post.like_received',
        config('xp_rates.school.post.like_received', 1),
        $post->user_id, // ผู้ที่ได้รับ like = ผู้ถูก award
        null,
        ['post_id' => $post->id, 'liker_id' => $like->user_id]
    );
}
```

## M.3.3 — Hooks in attendance/course/event controllers

**File:** `AcademyActivityController` หรือ `AttendanceController`:
```php
// ตอน mark attendance
app(XpService::class)->award($academy, 'attendance.recorded',
    config('xp_rates.school.attendance.recorded', 5),
    $studentId, $classroomGroupId, ['date' => today()]
);
app(ClassroomPointsService::class)->award($classroom, 'attendance.checkin',
    config('xp_rates.classroom.attendance.checkin', 1),
    $studentId
);
```

แบบเดียวกันสำหรับ:
- Course completed (`LessonCompletedListener` หรือ `EnrollmentController`)
- Achievement awarded (`AchievementService`)
- Assignment submitted (`AssignmentController`)
- Event registered (`SchoolEventController::enroll`)

---

# M.4 — Endpoints

## M.4.1 — Routes

```php
Route::middleware('auth:api')->group(function () {
    Route::get('/academies/{academy}/gamification/summary',     [GamificationController::class, 'summary']);
    Route::get('/academies/{academy}/gamification/leaderboard', [GamificationController::class, 'leaderboard']);
    Route::get('/academies/{academy}/gamification/recent',      [GamificationController::class, 'recentEvents']); // admin audit
});
```

## M.4.2 — Controller

**File:** `app/Http/Controllers/Api/Learn/Academy/GamificationController.php` (NEW)

```php
public function summary(Academy $academy, Request $request)
{
    $cycleType = $request->input('cycle', 'all_time');
    $sum = app(XpService::class)->summary($academy, $cycleType);
    return response()->json(['success' => true, 'data' => $sum]);
}

public function leaderboard(Academy $academy, Request $request)
{
    $cycleType = $request->input('cycle', config('gamification.leaderboard_cycle', 'month'));
    $limit = (int) $request->input('limit', 3);
    $rows = app(ClassroomPointsService::class)->leaderboard($academy->id, $cycleType, $limit);
    return response()->json(['success' => true, 'data' => $rows]);
}

public function recentEvents(Academy $academy, Request $request)
{
    $events = XpEvent::where('academy_id', $academy->id)
        ->with('user:id,name', 'classroomGroup:id,name')
        ->latest('occurred_at')
        ->paginate(20);
    return response()->json(['success' => true, 'data' => $events]);
}
```

---

# M.5 — Cover Level badge wiring

**File:** `ui/pages/academies/[name].vue`
**Location:** ราว line 1043-1053 (verified badge + level badge ของ Phase C.3 — มี `v-if="academy.level"` placeholder อยู่)

## M.5.1 — Inject summary ตอน load academy

ใน `fetchAcademy()` หลัง academy load:
```ts
const sumRes: any = await api.call(`/api/academies/${academy.value.id}/gamification/summary`)
if (sumRes?.success) {
  academy.value.level = sumRes.data.level
  academy.value.xp_to_next = sumRes.data.xp_to_next
  academy.value.progress_pct = sumRes.data.progress_pct
}
```

ตอนนี้ badge "เลเวล X" ที่ Phase C.3 เขียนไว้ → ใช้งานได้ทันที

---

# M.6 — SchoolLevelCard.vue (left sidebar)

**File:** `ui/components/school/SchoolLevelCard.vue` (NEW — placeholder ใน Phase A.1)

```vue
<script setup lang="ts">
interface Props {
  level: number
  totalXp: number
  xpToNext: number
  progressPct: number
}
defineProps<Props>()
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-4">
    <div class="flex items-center gap-3 mb-3">
      <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center text-white font-bold text-sm">
        {{ level }}
      </div>
      <div>
        <div class="font-bold text-sm text-gray-900 dark:text-white">ระดับโรงเรียน</div>
        <div class="text-xs text-gray-500">อีก {{ xpToNext.toLocaleString() }} XP ถึงเลเวล {{ level + 1 }}</div>
      </div>
    </div>
    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
      <div
        class="h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan transition-all"
        :style="{ width: `${progressPct}%` }"
      ></div>
    </div>
    <div class="text-right text-[10px] text-gray-400 mt-1">{{ progressPct }}%</div>
  </div>
</template>
```

Mount ใน LEFT aside ของ `[name].vue`:
```html
<SchoolQuickMenu :academy="academy" @navigate="switchTab" />
<SchoolLevelCard
  v-if="academy.level"
  :level="academy.level"
  :total-xp="academy.total_xp ?? 0"
  :xp-to-next="academy.xp_to_next ?? 1000"
  :progress-pct="academy.progress_pct ?? 0"
/>
```

---

# M.7 — SchoolClassroomLeaderboard.vue (right sidebar)

**File:** `ui/components/school/SchoolClassroomLeaderboard.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  academyId: number
  cycle?: 'week' | 'month' | 'all_time'
}
const props = withDefaults(defineProps<Props>(), { cycle: 'month' })

const api = useApi()
const rows = ref<any[]>([])
const isLoading = ref(true)

const medalBg = ['#ffd700', '#c0c0c0', '#cd7f32']

const load = async () => {
  isLoading.value = true
  try {
    const res: any = await api.call(`/api/academies/${props.academyId}/gamification/leaderboard`, {
      params: { cycle: props.cycle, limit: 3 },
    })
    rows.value = res?.data ?? []
  } finally {
    isLoading.value = false
  }
}
onMounted(load)
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <span class="font-bold text-gray-900 dark:text-white text-sm">อันดับห้องเรียน</span>
      <Icon icon="heroicons:trophy-solid" class="w-4 h-4 text-amber-500" />
    </div>
    <div class="p-4">
      <div v-if="isLoading" class="py-3 text-center">
        <Icon icon="svg-spinners:ring-resize" class="w-5 h-5 text-vikinger-purple mx-auto" />
      </div>
      <div v-else-if="rows.length === 0" class="py-3 text-center text-xs text-gray-500">
        ยังไม่มีข้อมูลในรอบนี้
      </div>
      <div v-else class="flex flex-col gap-3">
        <div
          v-for="(row, i) in rows"
          :key="row.group_id"
          class="flex items-center gap-3"
        >
          <span
            class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-gray-800 flex-shrink-0"
            :style="{ background: medalBg[i] || '#e5e7eb' }"
          >
            {{ row.rank }}
          </span>
          <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white truncate">
            {{ row.name }}
          </span>
          <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600">
            <Icon icon="heroicons:sparkles-solid" class="w-3.5 h-3.5" />
            {{ row.points.toLocaleString() }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
```

Mount ใน RIGHT aside ของ `[name].vue` (เหนือ existing widgets):
```html
<SchoolClassroomLeaderboard :academy-id="academy.id" cycle="month" />
<SchoolStatGrid ... />
<SchoolUpcomingEvents ... />
```

---

# M.8 — Cron: weekly/monthly reset

**Goal:** ไม่ต้อง "reset" จริงๆ — แค่สร้าง cycle ใหม่ตอนสัปดาห์/เดือนใหม่ (cycle_key เปลี่ยนเอง)

**ปัญหา**: ถ้าไม่มี event ใดๆ ใน cycle ใหม่ → row ยังไม่ถูกสร้าง → leaderboard ว่าง
**ทางแก้ option A**: ใช้ on-demand creation (รอ event แรกค่อยสร้าง row) — แต่ leaderboard query หา rows ที่ไม่มี

**ทางแก้ option B** (แนะนำ): Scheduled task pre-create rows ตอนเริ่ม cycle ใหม่

## M.8.1 — Scheduled job

**File:** `app/Console/Commands/InitializeCycleRowsCommand.php` (NEW)

```php
public function handle(): void
{
    foreach (Academy::cursor() as $academy) {
        app(XpService::class)->ensureCurrentCycles($academy);

        $classrooms = AcademyGroup::where('academy_id', $academy->id)
            ->where('type', 'classroom')->get();
        foreach ($classrooms as $cl) {
            app(ClassroomPointsService::class)->ensureCurrentCycles($cl);
        }
    }
    $this->info('Cycle rows initialized');
}
```

(เพิ่ม method `ensureCurrentCycles` ใน services — เหมือน `award` แต่ไม่ inc)

## M.8.2 — Schedule

**File:** `app/Console/Kernel.php` หรือ `routes/console.php`

```php
Schedule::command('gamification:init-cycles')->weeklyOn(0, '00:01'); // Sunday midnight
Schedule::command('gamification:init-cycles')->monthlyOn(1, '00:01'); // 1st of month
```

---

# M.9 — Backfill seeder (compute historical XP)

**File:** `database/seeders/BackfillGamificationSeeder.php` (NEW)

```php
public function run(XpService $xp): void
{
    // Posts
    AcademyPost::cursor()->each(fn ($p) =>
        $xp->award($p->academy, 'post.created',
            config('xp_rates.school.post.created'), $p->user_id, null,
            ['backfill' => true, 'post_id' => $p->id])
    );
    // Likes
    AcademyPostLike::cursor()->each(fn ($l) =>
        $xp->award($l->post->academy, 'post.like_received',
            config('xp_rates.school.post.like_received'), $l->post->user_id, null,
            ['backfill' => true, 'like_id' => $l->id])
    );
    // ... continue for comments, attendance, courses, etc.
}
```

```bash
php artisan db:seed --class=BackfillGamificationSeeder
```

> ⚠️ **ถ้ามีข้อมูลเก่าเยอะ** — รัน chunk + queue เพื่อไม่ block

---

# M.10 — Admin audit page (optional)

**File:** `ui/pages/academies/[name]/admin/gamification.vue` (NEW)

- Recent xp_events (paginated)
- Filter by user / source / date range
- Chart total XP over time
- Manual adjust (เพิ่ม/ลบ XP เป็นพิเศษ)

---

# 📋 Phase M — Files Summary

## ✨ New files (~15)

### Backend (11)
```
api/nuxnanravel/database/migrations/{ts}_create_xp_events_table.php
api/nuxnanravel/database/migrations/{ts}_create_school_xp_cycles_table.php
api/nuxnanravel/database/migrations/{ts}_create_classroom_point_cycles_table.php
api/nuxnanravel/database/seeders/BackfillGamificationSeeder.php

api/nuxnanravel/app/Models/XpEvent.php
api/nuxnanravel/app/Models/SchoolXpCycle.php
api/nuxnanravel/app/Models/ClassroomPointCycle.php

api/nuxnanravel/app/Services/Gamification/XpService.php
api/nuxnanravel/app/Services/Gamification/ClassroomPointsService.php

api/nuxnanravel/app/Observers/AcademyPostObserver.php
api/nuxnanravel/app/Observers/AcademyPostLikeObserver.php
api/nuxnanravel/app/Observers/AcademyPostCommentObserver.php

api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/GamificationController.php
api/nuxnanravel/app/Console/Commands/InitializeCycleRowsCommand.php

api/nuxnanravel/config/xp_rates.php
api/nuxnanravel/config/gamification.php
```

### Frontend (3)
```
ui/components/school/SchoolLevelCard.vue
ui/components/school/SchoolClassroomLeaderboard.vue
ui/pages/academies/[name]/admin/gamification.vue (optional)
```

## 🔧 Modified files (~6)
```
api/nuxnanravel/app/Providers/AppServiceProvider.php           (register observers)
api/nuxnanravel/app/Console/Kernel.php                          (schedule)
api/nuxnanravel/app/Http/Controllers/.../AttendanceController.php (award call)
api/nuxnanravel/app/Http/Controllers/.../EnrollmentController.php (award call)
api/nuxnanravel/routes/learn/academy.php                        (gamification routes)
ui/pages/academies/[name].vue                                   (mount widgets + fetch summary)
```

---

# 🛣️ Commit plan (7 commits)

```
1. feat(db): create gamification tables (xp_events, cycles)
2. feat(api): XpService + ClassroomPointsService + config files
3. feat(api): event observers + service hooks in controllers
4. feat(api): gamification endpoints (summary, leaderboard, recent)
5. feat(ui): SchoolLevelCard + Cover level badge wiring
6. feat(ui): SchoolClassroomLeaderboard widget
7. feat(scheduler): weekly/monthly cycle initialization + backfill seeder
```

---

# ✅ Phase M — Test Checklist

## Backend
- [ ] 3 migrations run สำเร็จ
- [ ] โพสต์ใหม่ → `xp_events` มี 1 row + `school_xp_cycles` (3 rows: week/month/all_time) total_xp += 10
- [ ] กด like → award XP เจ้าของโพสต์ (+1)
- [ ] Award บ่อย → level เพิ่มตาม sqrt formula

## Endpoints
- [ ] `GET /gamification/summary?cycle=all_time` คืน `{ level, total_xp, xp_to_next, progress_pct }`
- [ ] `GET /gamification/leaderboard?cycle=month&limit=3` คืน top 3 ห้องเรียน

## UI
- [ ] Cover badge แสดง "เลเวล X" — Phase C.3 placeholder ใช้งานได้
- [ ] SchoolLevelCard ใน left sidebar — progress bar เคลื่อน
- [ ] SchoolClassroomLeaderboard right sidebar — แสดง medal + ชื่อห้อง + แต้ม
- [ ] หลังเหตุการณ์ → reload → ค่าอัปเดต

## Cron
- [ ] รัน `php artisan gamification:init-cycles` manual → cycle rows ถูก pre-create
- [ ] Sunday 00:01 → cron trigger (ใน production)

## Backfill
- [ ] รัน seeder → xp_events มี records จาก historical posts/likes
- [ ] aggregate cycles ถูก update ตาม

## Regression
- [ ] โพสต์ปกติยังโพสต์ได้ (observer ไม่ throw)
- [ ] Attendance ปกติยังบันทึกได้

---

# ⚠️ Pitfalls & Notes

## 1. Observer performance
- Observer ทำงาน sync ใน request → ทุก post create จะ block 1 INSERT + 3 UPDATE
- ถ้า high traffic → ย้ายเป็น Queue Job:
  ```php
  dispatch(new AwardXpJob($academy, 'post.created', 10, $user, ...));
  ```

## 2. Aggregate race condition
- ถ้า 2 events มาพร้อมกัน → `increment()` ใช้ atomic DB operation → ปลอดภัย
- แต่ `level` calc บน application side → อาจเขียนทับ → ใช้ DB raw:
  ```sql
  UPDATE school_xp_cycles SET total_xp = total_xp + ?, level = FLOOR(SQRT((total_xp+?)/1000)) WHERE ...
  ```

## 3. Backfill ตอนระบบมี data เยอะ
- chunk + delay หรือ queue:
  ```bash
  php artisan db:seed --class=BackfillGamificationSeeder &
  ```
- หรือใช้ `cursor()` แทน `get()` เพื่อ memory-safe

## 4. Cycle key timezone
- ใช้ Carbon `Y-m` / `o-\WW` ตาม PHP timezone — ตั้ง `'timezone' => 'Asia/Bangkok'` ใน `config/app.php`

## 5. Decimal points / fractional XP
- ใช้ integer ทั้งหมด → ไม่มี rounding error
- ถ้าอยาก weighted (e.g. premium member × 1.5) → multiply ก่อนส่ง award

## 6. Leaderboard cache
- บน high-traffic page → cache `summary()` + `leaderboard()` 60 วินาที
- ใช้ Laravel `Cache::remember('school.xp.summary.' . $academyId, 60, fn () => ...)`

## 7. XP rate tuning
- เริ่มจากค่า default ใน config — observe behavior 1-2 สัปดาห์
- ผู้ใช้ rank up ช้าเกิน → เพิ่ม rate
- ผู้ใช้ rank up เร็วเกิน → ลด rate (existing aggregates ไม่เปลี่ยน — แค่ future events ได้น้อยลง)

## 8. Reset cycle ที่ "ไม่จริง"
- คำว่า reset = สร้าง cycle ใหม่ ไม่ลบของเก่า
- All-time cycle ไม่เคย reset
- UI default แสดง month — ผู้ใช้สลับ cycle ผ่าน dropdown ถ้าต้องการ

## 9. Negative XP?
- ถ้าผู้ใช้กระทำผิด → award XP ลบได้ไหม?
- backend รองรับ (`xp` column = signed int) — ใช้ award amount = -5 เป็นต้น
- แต่ aggregate ต้อง check ไม่ให้ total < 0

---

# 🎯 ลำดับงานแนะนำ

```
1. M.0 (migrations) + M.2 (config) → foundation
2. M.1 services + models (XpEvent, cycles) → backend logic
3. M.4 endpoints + manual tinker test (award แล้วเรียก summary)
4. M.5 + M.6 + M.7 frontend → เห็นผลใน UI
5. M.3 observers + service hooks → ระบบ award อัตโนมัติ
6. M.8 scheduler → cycle init
7. M.9 backfill → ข้อมูลเก่ามี XP
8. M.10 audit page → admin tool
```

หลัง M เสร็จ → ระบบ **ครบทุก feature** ที่ design [School Homepage.html](./School Homepage.html) แสดง

- ✅ Cover Level badge
- ✅ Left sidebar: Quick menu + School level card
- ✅ Right sidebar: Stats + Upcoming events + **Classroom leaderboard 🥇🥈🥉**
- ✅ Post variants: Director / Event / Attendance progress / Pinned announcement
- ✅ Target audience + reward chip
- ✅ Group page (mini homepage) + post-as-group composer
- ✅ Notification bell + join workflow + appointment

🎉 ระบบส่วนงานเป็น **production-ready social school hub**

ติดตรงไหนตอนทำมาถามได้เลยครับ 🙌
