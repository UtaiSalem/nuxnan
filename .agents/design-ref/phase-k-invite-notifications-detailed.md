# Phase K — Schema Repair + Invite Flow + Notifications

อ้างอิง: [decisions](./school-departments-decisions.md) + Phases G [✅] H [✅] I [✅] J [✅]
Target: **fix schema blocker → join request workflow → notification system → polish**
วันที่: 2026-06-20

---

## 🚨 Critical Blocker (เจอตอนตรวจ Phase J)

ตาราง `academy_group_members` และ `academy_group_admins` ใน DB **ขาดคอลัมน์สำคัญ** — มีเพียง `id, created_at, updated_at`

ผลกระทบ: Phase G/H/I/J controller methods ที่ใช้ `where('academy_group_id', ...)` / `where('user_id', ...)` **silently fail** — query คืน empty result เสมอ ทำให้:
- Invite member → SQLException ตอน INSERT
- Post-as-group permission check → fail → block ทุกการโพสต์ในนามกลุ่ม
- Manage tabs ดูเหมือนใช้งานได้ แต่ data ไม่ persist
- `postable-groups` คืน `[]` เสมอ

**Phase K.0 จะแก้ก่อนทุกอย่าง — feature ใหม่ของ Phase K จะมีค่าก็ต่อเมื่อ schema ใช้งานได้**

---

# 🗺️ Phase K Sub-phases Overview

| # | งาน | Priority | Est. |
|---|---|---|---|
| **K.0** | 🚨 Schema repair migration (force columns) + verification | 🔴 BLOCKER | 1 hr |
| **K.1** | Backfill seeder for permissions ของกลุ่มเก่า | 🔴 BLOCKER | 30 min |
| **K.2** | Join request workflow (member request to join group) | 🟡 Feature | 1.5 hr |
| **K.3** | Notification system — basic infrastructure | 🟡 Feature | 2 hr |
| **K.4** | Notification types: invited / joined / role_changed / removed / appointed | 🟡 Feature | 1.5 hr |
| **K.5** | Frontend: notification bell + dropdown integration | 🟡 Feature | 1.5 hr |
| **K.6** | Group invite modal (admin invite via search) | 🟡 Feature | 1 hr |
| **K.7** | Appointment workflow polish — academy admin appoints group leaders | 🟢 Polish | 1 hr |
| **K.8** | Activity log / audit trail | 🟢 Polish | 1 hr |
| **K.9** | E2E smoke test scenarios | 🟢 Test | 1 hr |
| **รวม** | | | **~12 ชม.** |

---

# K.0 — 🚨 Schema Repair (เริ่มก่อนสิ่งอื่น)

## K.0.1 — Investigation

ก่อนแก้ ตรวจสาเหตุก่อน — อาจมีข้อมูล relevant ที่ห้ามทำลาย:

```bash
cd api/nuxnanravel
# 1. ดู record count
php artisan tinker --execute="
echo 'members: ' . DB::table('academy_group_members')->count() . PHP_EOL;
echo 'admins: ' . DB::table('academy_group_admins')->count() . PHP_EOL;
"

# 2. ดู actual columns ผ่าน raw SQL
php artisan tinker --execute="
foreach (DB::select('SHOW COLUMNS FROM academy_group_members') as \$r) {
  echo \$r->Field . ' (' . \$r->Type . ')' . PHP_EOL;
}
"

# 3. ดู migration history ที่เกี่ยวข้อง
php artisan migrate:status | grep -E "academy_group_(member|admin)"
```

ถ้า count = 0 ทั้งคู่ — safe มาก สามารถ drop+recreate ได้ (ทาง A)
ถ้ามี records — ต้องใช้ทาง B (force add columns)

## K.0.2 — ทาง A: Drop + Recreate (ถ้า count = 0)

**File:** `api/nuxnanravel/database/migrations/2026_06_20_180000_recreate_academy_group_members_admins.php` (NEW)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === members ===
        Schema::dropIfExists('academy_group_members');
        Schema::create('academy_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('student'); // student | teacher | admin (per Phase H)
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['academy_group_id', 'user_id']);
            $table->index('user_id');
        });

        // === admins ===
        Schema::dropIfExists('academy_group_admins');
        Schema::create('academy_group_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('leader'); // leader | co_leader | advisor
            $table->foreignId('appointed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['academy_group_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_group_members');
        Schema::dropIfExists('academy_group_admins');
        // recreate skeleton (so prior migrations don't blow up)
        Schema::create('academy_group_members', function (Blueprint $t) { $t->id(); $t->timestamps(); });
        Schema::create('academy_group_admins',  function (Blueprint $t) { $t->id(); $t->timestamps(); });
    }
};
```

> 🆕 Bonus: เพิ่ม `invited_by` / `appointed_by` — ใช้สำหรับ K.4 notification + K.8 audit

## K.0.3 — ทาง B: Force add columns (ถ้ามี records)

ถ้าไม่อยากสูญข้อมูล — ใช้ raw ALTER:

```php
public function up(): void
{
    if (!Schema::hasColumn('academy_group_members', 'academy_group_id')) {
        DB::statement('ALTER TABLE academy_group_members
            ADD COLUMN academy_group_id BIGINT UNSIGNED NOT NULL AFTER id,
            ADD COLUMN user_id BIGINT UNSIGNED NOT NULL AFTER academy_group_id,
            ADD COLUMN role VARCHAR(50) DEFAULT "student" AFTER user_id,
            ADD COLUMN invited_by BIGINT UNSIGNED NULL AFTER role,
            ADD INDEX (academy_group_id),
            ADD INDEX (user_id),
            ADD UNIQUE (academy_group_id, user_id)
        ');
    }
    // ทำคล้ายๆ กันกับ academy_group_admins
}
```

## K.0.4 — Run migration + verify

```bash
php artisan migrate

php artisan tinker --execute="
foreach (DB::select('SHOW COLUMNS FROM academy_group_members') as \$r) echo \$r->Field . PHP_EOL;
echo '---' . PHP_EOL;
foreach (DB::select('SHOW COLUMNS FROM academy_group_admins') as \$r) echo \$r->Field . PHP_EOL;
"
# ควรเห็น: id, academy_group_id, user_id, role, invited_by/appointed_by, created_at, updated_at
```

## K.0.5 — End-to-end re-verification

ก่อนไปต่อ — ทดสอบว่า Phase G/H/J flow ใช้ได้แล้ว:

```bash
# 1. สร้างกลุ่ม + เพิ่มตัวเองเป็น member (UI หรือ tinker)
# 2. toggle can_post = true ใน DB (หรือ ManageTabPermissions)
# 3. โหลด /api/academies/{academy}/postable-groups → ต้องเห็นกลุ่ม
# 4. ลอง POST academy_post พร้อม posted_as_group_id → success 200
# 5. ลอง add admin ใน ManageTabAdmins → DB ต้อง insert record
```

> ⚠️ **ห้ามข้าม K.0** — ทุก feature ของ Phase K ต่อจากนี้ depends on schema นี้

---

# K.1 — Backfill permissions seeder

**Goal:** กลุ่มที่สร้างก่อน Phase G.6 (auto-seed) ไม่มี permission records → ManageTabPermissions ทำงานผิด

## K.1.1 — Seeder

**File:** `api/nuxnanravel/database/seeders/BackfillGroupPermissionsSeeder.php` (NEW)

```php
<?php

namespace Database\Seeders;

use App\Constants\AcademyGroupPermissions;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupPermission;
use Illuminate\Database\Seeder;

class BackfillGroupPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $count = 0;
        AcademyGroup::doesntHave('permissions')->chunk(50, function ($groups) use (&$count) {
            foreach ($groups as $group) {
                foreach (AcademyGroupPermissions::PERMISSIONS as $key => $meta) {
                    AcademyGroupPermission::firstOrCreate(
                        ['academy_group_id' => $group->id, 'permission_key' => $key],
                        ['enabled' => $meta['default']],
                    );
                    $count++;
                }
            }
        });
        $this->command->info("Backfilled {$count} permission records");
    }
}
```

> ⚠️ ต้องเช็คว่า `AcademyGroup` มี relation `permissions()` ไหม — ถ้าไม่มี ให้เพิ่ม:
> ```php
> public function permissions(): HasMany
> { return $this->hasMany(AcademyGroupPermission::class); }
> ```

## K.1.2 — Run

```bash
php artisan db:seed --class=BackfillGroupPermissionsSeeder
```

---

# K.2 — Join Request Workflow

**Goal:** สมาชิกโรงเรียนขอเข้าร่วมส่วนงาน (ไม่ใช่แค่ admin เชิญ — แต่ user request เอง)

## K.2.1 — Migration: status column

**File:** `2026_06_20_180001_add_status_to_academy_group_members.php`

```php
return new class extends Migration {
    public function up(): void {
        Schema::table('academy_group_members', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_group_members', 'status')) {
                $table->tinyInteger('status')->default(2)->after('role');
                // 1 = pending, 2 = approved (default), 3 = rejected
                $table->index('status');
            }
        });
    }
    public function down(): void {
        Schema::table('academy_group_members', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
```

## K.2.2 — Controller methods

**File:** `AcademyGroupController.php` เพิ่ม:

```php
/**
 * POST /api/academies/groups/{academyGroup}/join-request
 * User self-requests to join group
 */
public function requestJoin(Request $request, AcademyGroup $academyGroup)
{
    $user = $request->user();

    // ต้องเป็น academy member ก่อน
    $isAcademyMember = AcademyMember::where('academy_id', $academyGroup->academy_id)
        ->where('user_id', $user->id)
        ->where('status', 2)
        ->exists();
    if (!$isAcademyMember) {
        return response()->json(['success' => false, 'message' => 'ต้องเป็นสมาชิกของโรงเรียนก่อน'], 422);
    }

    // ป้องกันซ้ำ
    $existing = AcademyGroupMember::where('academy_group_id', $academyGroup->id)
        ->where('user_id', $user->id)
        ->first();
    if ($existing) {
        return response()->json([
            'success' => false,
            'message' => $existing->status === 1 ? 'ส่งคำขอแล้ว รอการอนุมัติ' : 'คุณเป็นสมาชิกอยู่แล้ว',
        ], 422);
    }

    $member = AcademyGroupMember::create([
        'academy_group_id' => $academyGroup->id,
        'user_id'          => $user->id,
        'role'             => 'student',
        'status'           => 1, // pending
    ]);

    // K.4 จะเพิ่ม notification ตรงนี้ ส่งให้ group admins
    return response()->json(['success' => true, 'request' => $member], 201);
}

/**
 * GET /api/academies/groups/{academyGroup}/pending-members
 */
public function pendingMembers(AcademyGroup $academyGroup)
{
    $pending = AcademyGroupMember::with('user:id,name,profile_photo_path')
        ->where('academy_group_id', $academyGroup->id)
        ->where('status', 1)
        ->get();
    return response()->json(['success' => true, 'data' => $pending]);
}

/**
 * POST /api/academies/groups/{academyGroup}/approve/{member}
 */
public function approveMember(Request $request, AcademyGroup $academyGroup, AcademyGroupMember $member)
{
    abort_unless($member->academy_group_id === $academyGroup->id, 404);
    $member->update(['status' => 2]);
    // K.4 notification: notify requester
    return response()->json(['success' => true, 'member' => $member]);
}

/**
 * POST /api/academies/groups/{academyGroup}/reject/{member}
 */
public function rejectMember(Request $request, AcademyGroup $academyGroup, AcademyGroupMember $member)
{
    abort_unless($member->academy_group_id === $academyGroup->id, 404);
    $member->delete(); // hard delete = user can request again later
    return response()->json(['success' => true]);
}
```

## K.2.3 — Routes

```php
Route::post('/groups/{academyGroup}/join-request',         [AcademyGroupController::class, 'requestJoin'])->name('api.academy.groups.requestJoin');
Route::get('/groups/{academyGroup}/pending-members',       [AcademyGroupController::class, 'pendingMembers'])->name('api.academy.groups.pendingMembers');
Route::post('/groups/{academyGroup}/approve/{member}',     [AcademyGroupController::class, 'approveMember'])->name('api.academy.groups.approveMember');
Route::post('/groups/{academyGroup}/reject/{member}',      [AcademyGroupController::class, 'rejectMember'])->name('api.academy.groups.rejectMember');
```

## K.2.4 — Update existing queries to exclude pending

ใน `AcademyGroupController::getMembers` + `postableForUser` + `listMembers` ทุกที่ที่ค้นหา members ต้องเพิ่ม `->where('status', 2)` มิเช่นนั้น pending จะปนกับ approved

## K.2.5 — UI: Join button on group profile page

**File:** `ui/components/academy/groups/GroupProfileCover.vue` (Phase I)
เพิ่ม prop `joinStatus` + ปุ่ม:

```vue
<button
  v-if="joinStatus === 'none'"
  class="px-3 py-2 rounded-lg bg-vikinger-purple text-white"
  @click="emit('requestJoin')"
>
  ขอเข้าร่วม
</button>
<button
  v-else-if="joinStatus === 'pending'"
  class="px-3 py-2 rounded-lg bg-amber-100 text-amber-700 cursor-not-allowed"
  disabled
>
  รอการอนุมัติ
</button>
```

ใน `[groupId].vue` page เพิ่ม:
```ts
const joinStatus = computed<'none' | 'pending' | 'member'>(() => {
  const me = members.value.find((m) => m.user_id === user.value?.id)
  if (!me) return 'none'
  return me.status === 1 ? 'pending' : 'member'
})

const onRequestJoin = async () => {
  await api.call(`/api/academies/groups/${groupId.value}/join-request`, { method: 'POST' })
  await loadGroup() // refresh
}
```

---

# K.3 — Notification System: Basic Infrastructure

**Goal:** ใช้ `notifications` table ที่มีอยู่แล้ว (`user_id, content, read_status, action_url, type, sender_id, related_id, metadata`) สร้าง infrastructure ส่งและรับ

## K.3.1 — Notification model

**File:** `api/nuxnanravel/app/Models/Notification.php` (check exists)

ถ้ายังไม่มี / ไม่ครบ:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'sender_id', 'type', 'content',
        'action_url', 'related_id', 'metadata', 'read_status',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'read_status' => 'boolean',
    ];

    public function user(): BelongsTo
    { return $this->belongsTo(User::class); }

    public function sender(): BelongsTo
    { return $this->belongsTo(User::class, 'sender_id'); }
}
```

## K.3.2 — Service สำหรับ create notification

**File:** `api/nuxnanravel/app/Services/NotificationService.php` (NEW)

```php
<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function send(int $userId, string $type, string $content, array $opts = []): Notification
    {
        return Notification::create([
            'user_id'     => $userId,
            'sender_id'   => $opts['sender_id'] ?? null,
            'type'        => $type,
            'content'     => $content,
            'action_url'  => $opts['action_url'] ?? null,
            'related_id'  => $opts['related_id'] ?? null,
            'metadata'    => $opts['metadata'] ?? null,
            'read_status' => false,
        ]);
    }

    public function sendBulk(array $userIds, string $type, string $content, array $opts = []): int
    {
        $rows = collect($userIds)->map(fn ($uid) => [
            'user_id'     => $uid,
            'sender_id'   => $opts['sender_id'] ?? null,
            'type'        => $type,
            'content'     => $content,
            'action_url'  => $opts['action_url'] ?? null,
            'related_id'  => $opts['related_id'] ?? null,
            'metadata'    => isset($opts['metadata']) ? json_encode($opts['metadata']) : null,
            'read_status' => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ])->all();

        Notification::insert($rows);
        return count($rows);
    }
}
```

## K.3.3 — REST endpoints

**File:** `api/nuxnanravel/app/Http/Controllers/Api/NotificationController.php` (NEW or extend)

```php
public function index(Request $request)
{
    $perPage = (int) $request->input('per_page', 20);
    $notifications = Notification::where('user_id', $request->user()->id)
        ->with('sender:id,name,profile_photo_path')
        ->latest()
        ->paginate($perPage);
    return response()->json(['success' => true, 'data' => $notifications]);
}

public function unreadCount(Request $request)
{
    $count = Notification::where('user_id', $request->user()->id)
        ->where('read_status', false)
        ->count();
    return response()->json(['success' => true, 'count' => $count]);
}

public function markRead(Request $request, Notification $notification)
{
    abort_unless($notification->user_id === $request->user()->id, 403);
    $notification->update(['read_status' => true]);
    return response()->json(['success' => true]);
}

public function markAllRead(Request $request)
{
    Notification::where('user_id', $request->user()->id)
        ->where('read_status', false)
        ->update(['read_status' => true]);
    return response()->json(['success' => true]);
}
```

## K.3.4 — Routes

**File:** `routes/api.php` หรือ `routes/learn/notifications.php`

```php
Route::middleware('auth:api')->group(function () {
    Route::get('/notifications',                  [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count',     [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/mark-all-read',   [NotificationController::class, 'markAllRead']);
});
```

---

# K.4 — Notification Types: Group events

**Goal:** ส่ง notification เมื่อมี event ที่เกี่ยวข้องกับ user

## K.4.1 — Type constants

**File:** `api/nuxnanravel/app/Constants/NotificationTypes.php` (NEW)

```php
class NotificationTypes
{
    public const GROUP_INVITED        = 'group.invited';        // user ถูกเชิญเข้ากลุ่ม
    public const GROUP_JOIN_REQUEST   = 'group.join_request';   // admin ได้รับคำขอ join
    public const GROUP_JOIN_APPROVED  = 'group.join_approved';  // requester ได้รับการอนุมัติ
    public const GROUP_JOIN_REJECTED  = 'group.join_rejected';  // คำขอถูก reject
    public const GROUP_ROLE_CHANGED   = 'group.role_changed';   // role ของฉันใน group เปลี่ยน
    public const GROUP_REMOVED        = 'group.removed';        // ถูกถอนออกจากกลุ่ม
    public const GROUP_APPOINTED      = 'group.appointed';      // ถูกแต่งตั้งเป็นหัวหน้า
    public const GROUP_POST           = 'group.post';           // กลุ่มที่เป็นสมาชิกมีโพสต์ใหม่ (optional)
}
```

## K.4.2 — Inject NotificationService ลง controllers

ใน `AcademyGroupController` + `AcademyGroupAdminController`:

```php
public function __construct(private NotificationService $notify) {}

// ตัวอย่าง: addMember → notify
public function addMember(Request $request, AcademyGroup $academyGroup) {
    // ... existing validation + insert ...

    $this->notify->send($userId, NotificationTypes::GROUP_INVITED,
        "{$inviter->name} เชิญคุณเข้าร่วมส่วนงาน {$academyGroup->name}",
        [
            'sender_id'   => $inviter->id,
            'action_url'  => "/academies/{$academy->name}/groups/{$academyGroup->id}",
            'related_id'  => $academyGroup->id,
            'metadata'    => ['group_name' => $academyGroup->name, 'group_type' => $academyGroup->type],
        ]
    );
}

// ตัวอย่าง: requestJoin → notify admins
public function requestJoin(Request $request, AcademyGroup $academyGroup) {
    // ... existing logic ...

    $adminIds = AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)
        ->pluck('user_id')
        ->toArray();
    $this->notify->sendBulk($adminIds, NotificationTypes::GROUP_JOIN_REQUEST,
        "{$user->name} ขอเข้าร่วม {$academyGroup->name}",
        [
            'sender_id'   => $user->id,
            'action_url'  => "/academies/{$academy->name}/groups/{$academyGroup->id}#members",
            'related_id'  => $academyGroup->id,
        ]
    );
}
```

Apply pattern เดียวกันกับ:
- `approveMember` / `rejectMember` → notify requester
- `removeMember` → notify removed user
- `updateMemberRole` → notify member
- `addAdmin` (appointment) → notify appointee

---

# K.5 — Frontend: Notification Bell + Dropdown

## K.5.1 — Composable

**File:** `ui/composables/useNotifications.ts` (NEW)

```ts
export const useNotifications = () => {
  const api = useApi()

  const list = ref<any[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)

  const load = async () => {
    isLoading.value = true
    try {
      const [listRes, cntRes]: any = await Promise.all([
        api.call('/api/notifications', { params: { per_page: 20 } }),
        api.call('/api/notifications/unread-count'),
      ])
      list.value = listRes?.data?.data ?? []
      unreadCount.value = cntRes?.count ?? 0
    } finally {
      isLoading.value = false
    }
  }

  const markRead = async (id: number) => {
    await api.call(`/api/notifications/${id}/read`, { method: 'POST' })
    const n = list.value.find((x) => x.id === id)
    if (n) n.read_status = true
    if (unreadCount.value > 0) unreadCount.value--
  }

  const markAllRead = async () => {
    await api.call('/api/notifications/mark-all-read', { method: 'POST' })
    list.value.forEach((n) => (n.read_status = true))
    unreadCount.value = 0
  }

  // Polling (light) — every 60s when active tab
  let pollTimer: any = null
  const startPolling = () => {
    stopPolling()
    pollTimer = setInterval(() => {
      if (!document.hidden) load()
    }, 60000)
  }
  const stopPolling = () => {
    if (pollTimer) clearInterval(pollTimer)
    pollTimer = null
  }

  return { list, unreadCount, isLoading, load, markRead, markAllRead, startPolling, stopPolling }
}
```

## K.5.2 — NotificationBell component

**File:** `ui/components/notifications/NotificationBell.vue` (NEW)

```vue
<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { Icon } from '@iconify/vue'

const { list, unreadCount, load, markRead, markAllRead, startPolling, stopPolling } = useNotifications()

const isOpen = ref(false)

const toggle = async () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) await load()
}

const onClick = async (n: any) => {
  if (!n.read_status) await markRead(n.id)
  if (n.action_url) await navigateTo(n.action_url)
  isOpen.value = false
}

onMounted(() => {
  load()
  startPolling()
})
onBeforeUnmount(stopPolling)

const relativeTime = (iso?: string) => {
  if (!iso) return ''
  const diff = (Date.now() - new Date(iso).getTime()) / 1000
  if (diff < 60) return 'เมื่อสักครู่'
  if (diff < 3600) return `${Math.floor(diff / 60)} นาที`
  if (diff < 86400) return `${Math.floor(diff / 3600)} ชม.`
  return `${Math.floor(diff / 86400)} วัน`
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
      @click="toggle"
    >
      <Icon icon="heroicons:bell" class="w-5 h-5 text-gray-700 dark:text-gray-300" />
      <span
        v-if="unreadCount > 0"
        class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-80 bg-white dark:bg-vikinger-dark-100 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-[480px] flex flex-col"
    >
      <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <span class="font-bold text-gray-900 dark:text-white">การแจ้งเตือน</span>
        <button
          v-if="unreadCount > 0"
          class="text-xs text-vikinger-purple"
          @click="markAllRead"
        >
          อ่านทั้งหมด
        </button>
      </div>
      <div class="overflow-y-auto flex-1">
        <div v-if="list.length === 0" class="py-8 text-center text-sm text-gray-500">
          ยังไม่มีการแจ้งเตือน
        </div>
        <button
          v-for="n in list"
          :key="n.id"
          type="button"
          :class="[
            'w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 border-b border-gray-50 dark:border-gray-700 flex gap-3',
            !n.read_status && 'bg-vikinger-purple/5'
          ]"
          @click="onClick(n)"
        >
          <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
            <img v-if="n.sender?.profile_photo_path" :src="n.sender.profile_photo_path" class="w-full h-full object-cover" />
            <Icon v-else icon="heroicons:bell" class="w-full h-full p-2 text-gray-400" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm text-gray-900 dark:text-white">{{ n.content }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ relativeTime(n.created_at) }}</div>
          </div>
          <span v-if="!n.read_status" class="w-2 h-2 rounded-full bg-vikinger-purple flex-shrink-0 mt-1.5"></span>
        </button>
      </div>
    </div>
  </div>
</template>
```

## K.5.3 — Mount in main layout

**File:** `ui/layouts/main.vue`
หา navbar / header area — ใส่:
```vue
<NotificationsNotificationBell v-if="user" />
```

---

# K.6 — Group Invite Modal (admin invite)

**Goal:** Phase H มี autocomplete แล้ว — เพิ่ม dedicated modal มี multi-select + invite หลายคนพร้อมกัน

> 💡 ถ้า ManageTabMembers/Admins ใช้ได้พอแล้ว — skip K.6 หรือทำเป็น polish later

**File:** `ui/components/academy/groups/GroupBulkInviteModal.vue` (optional NEW)

- Search + multi-select
- ปุ่ม "เชิญ N คน"
- POST `/groups/{id}/members/bulk` (ต้องเพิ่ม backend endpoint)

(Skip รายละเอียดถ้าไม่เร่ง — Phase H ทำงานได้แล้ว)

---

# K.7 — Appointment Workflow Polish

**Goal:** Academy admin "แต่งตั้ง" group leader อย่างเป็นทางการ + log

## K.7.1 — เพิ่ม `appointed_by` column (รวมใน K.0.2 แล้ว)

## K.7.2 — UI: Appointment record ใน Manage tab admins

ใน `ManageTabAdmins.vue` (Phase H) เพิ่ม render `appointed_by` info:

```vue
<div v-if="admin.appointed_by" class="text-[10px] text-gray-400">
  แต่งตั้งโดย {{ admin.appointer?.name }} เมื่อ {{ relativeDate(admin.created_at) }}
</div>
```

Backend `listAdmins` ต้อง eager load `appointer` relation:
```php
->with('user:id,name,profile_photo_path', 'appointer:id,name')
```

## K.7.3 — Auto-set `appointed_by`

ใน `AcademyGroupAdminController::store` ใส่:
```php
$admin = AcademyGroupAdmin::create([
    ...,
    'appointed_by' => $request->user()->id,
]);
```

---

# K.8 — Audit log (optional)

**File:** `app/Models/AcademyGroupAuditLog.php` + migration (NEW)

Tracking events:
- group.created / updated / deleted
- member.added / removed / role_changed
- admin.appointed / removed
- permission.changed

Schema:
```php
$table->foreignId('academy_group_id')->constrained()->cascadeOnDelete();
$table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
$table->string('action');         // 'member.added', etc.
$table->json('payload')->nullable();
$table->timestamps();
```

Service hook into critical actions (similar to NotificationService)

---

# K.9 — E2E smoke test scenarios

ทดสอบ flow ครบสาย:

## Scenario 1 — เปิดส่วนงานใหม่และเชิญสมาชิก
1. Login เป็น academy admin
2. เปิดส่วนงาน "ฝ่ายวิชาการ" ใน Manage tab → toggle `can_post = true`
3. Tab Admins → เพิ่ม user X เป็นหัวหน้า → ดู notification X ได้รับ
4. Login เป็น X → กดเข้าหน้า group profile → เห็นชื่อตัวเองใน admins list

## Scenario 2 — User request join + admin approve
1. Login เป็น user Y (academy member แต่ไม่ใช่ group member)
2. เข้าหน้า group profile → กด "ขอเข้าร่วม" → ปุ่มเปลี่ยน "รอการอนุมัติ"
3. Login เป็น admin → ดู notification → คลิก → เข้าหน้ากลุ่ม
4. เปิด ManageTabMembers → ดู pending → approve
5. Login Y → notification "ได้รับการอนุมัติ" → ลองโพสต์ในนามกลุ่ม

## Scenario 3 — Post-as-group + appear in main feed
1. Login เป็น group member (with can_post)
2. ไปหน้า academy main → composer chip "โพสต์ในนาม: ฉัน ▾"
3. คลิก → เลือก "ฝ่ายวิชาการ" → โพสต์
4. ดู feed → render avatar ของกลุ่ม + ชื่อกลุ่ม + "โดย {ชื่อ user}"
5. คลิกชื่อกลุ่ม → ไปหน้า group profile

## Scenario 4 — Mute + unmute
1. Mute "ฝ่ายวิชาการ" → main feed ไม่เห็นโพสต์ของฝ่ายนี้
2. ใน group profile หน้ากลุ่มยังเห็นโพสต์ (ไม่ filter ที่นี่)
3. Unmute → กลับมาเห็น

---

# 📋 Phase K — Files Summary

## ✨ New files (~10)

### Backend (7)
```
api/nuxnanravel/database/migrations/{ts}_recreate_academy_group_members_admins.php  (K.0)
api/nuxnanravel/database/migrations/{ts}_add_status_to_academy_group_members.php    (K.2)
api/nuxnanravel/database/seeders/BackfillGroupPermissionsSeeder.php                 (K.1)
api/nuxnanravel/app/Constants/NotificationTypes.php                                 (K.4)
api/nuxnanravel/app/Services/NotificationService.php                                (K.3)
api/nuxnanravel/app/Http/Controllers/Api/NotificationController.php (or extend)     (K.3)
api/nuxnanravel/app/Models/Notification.php  (verify/create)                         (K.3)
```

### Frontend (3)
```
ui/composables/useNotifications.ts                                       (K.5)
ui/components/notifications/NotificationBell.vue                         (K.5)
ui/components/academy/groups/GroupBulkInviteModal.vue (optional)         (K.6)
```

## 🔧 Modified files (~6)
```
api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php
   - requestJoin/pendingMembers/approveMember/rejectMember
   - inject NotificationService → emit notifications

api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php
   - set appointed_by, emit notification

api/nuxnanravel/routes/learn/academy.php           (K.2 routes)
api/nuxnanravel/routes/api.php (or new)            (K.3 notification routes)
ui/components/academy/groups/GroupProfileCover.vue (K.2.5 join button)
ui/pages/academies/[name]/groups/[groupId].vue     (K.2.5 onRequestJoin)
ui/components/academy/groups/ManageTabAdmins.vue   (K.7.2 appointed_by display)
ui/layouts/main.vue                                 (K.5.3 mount NotificationBell)
```

---

# 🛣️ Commit plan (6 commits)

```
1. 🚨 fix(db): repair academy_group_members + admins schema
   - K.0 migration (drop+recreate or force ALTER)
   - K.1 backfill seeder

2. feat(academy): join request workflow
   - K.2: requestJoin/approve/reject + status column + UI button

3. feat(notifications): service + REST API + types
   - K.3: NotificationService, controller, routes
   - K.4: NotificationTypes constants, hooks into group controllers

4. feat(ui): notification bell + dropdown
   - K.5: composable + bell component + mount

5. feat(academy): appointment workflow polish
   - K.7: appointed_by, list admins with appointer info

6. feat(academy): audit log (optional)
   - K.8 (skip if low priority)
```

---

# ✅ Phase K — Test Checklist

## K.0 Schema repair
- [ ] `SHOW COLUMNS academy_group_members` มี `academy_group_id`, `user_id`, `role`, `invited_by`
- [ ] `SHOW COLUMNS academy_group_admins` มี `academy_group_id`, `user_id`, `role`, `appointed_by`
- [ ] เพิ่ม member ผ่าน UI → DB INSERT สำเร็จ (ไม่มี SQL error)
- [ ] `/postable-groups` คืนกลุ่มจริง (ไม่ใช่ array ว่างเสมอ)

## K.1 backfill
- [ ] กลุ่มเก่าทุกตัวมี `academy_group_permissions` 6 records
- [ ] เปิด ManageTabPermissions ของกลุ่มเก่า → toggle ได้ปกติ

## K.2 join request
- [ ] User non-member เข้าหน้ากลุ่ม → เห็นปุ่ม "ขอเข้าร่วม"
- [ ] กดแล้ว ปุ่มเปลี่ยน "รอการอนุมัติ"
- [ ] Admin เห็น pending ใน ManageTabMembers (filter status = 1)
- [ ] Approve → user status = 2 → user post-as-group ได้

## K.3+K.4 notifications
- [ ] `GET /api/notifications` คืน list
- [ ] addMember → notification record สร้าง type `group.invited`
- [ ] requestJoin → notification ส่งไปทุก admin ของกลุ่ม
- [ ] approveMember → notification ไป requester

## K.5 bell UI
- [ ] เห็น bell + count บน navbar
- [ ] กดเปิด dropdown → list แสดง
- [ ] กด notification → mark read + navigate ไป action_url
- [ ] กด "อ่านทั้งหมด" → count = 0
- [ ] Polling 60 วินาที refresh count (test ด้วยการสร้าง notification จาก tinker)

## K.7 appointment
- [ ] เพิ่ม admin → DB `appointed_by` = academy admin id
- [ ] ManageTabAdmins แสดง "แต่งตั้งโดย {ชื่อ}"

## E2E (K.9)
- ผ่าน 4 scenarios

---

# ⚠️ Pitfalls & Notes

## 1. K.0 ตัดสินใจ drop vs alter ระวัง
- ถ้ามี data — ห้าม drop! ใช้ ALTER แทน
- ก่อนรัน migration จริงทำ backup: `mysqldump nuxnan > backup.sql`

## 2. status field interpretation
- `member.status = 1 (pending) / 2 (approved)` ตรงกับ AcademyMember convention
- เปลี่ยน existing queries ใน `getMembers`, `listMembers`, `postableForUser` ให้กรอง `status = 2`
- ถ้าลืม → user pending จะถูกนับเป็นสมาชิกเต็มตัว

## 3. Notification spam
- ระวัง notification.bulk_insert จำนวนเยอะมาก (เช่น 500 admin)
- ทาง K.4 ใช้ `Notification::insert([...])` ไม่ดี (ไม่ trigger model events เช่น broadcasting)
- ถ้าต้องการ realtime → ใช้ Laravel Notification + Reverb broadcast

## 4. NotificationBell on SSR
- `document.hidden` ใน polling check → ต้อง guard ด้วย `if (import.meta.client)`
- ใช้ใน `setInterval` callback ที่อยู่ใน onMounted → safe

## 5. action_url ที่ภาษาไทยใน URL
- URL ภาษาไทย (academy.name) ต้อง encode
- backend ส่ง action_url แบบ encoded: `urlencode($academy->name)`
- frontend ใช้ NuxtLink รับ encoded fine

## 6. read_status migration
- ถ้า notification table มีอยู่แล้ว และมี records → backfill `read_status = false` for nulls

## 7. ManageTabMembers แสดง pending ยังไง?
- เพิ่ม section "รออนุมัติ" ขึ้นบน + ปุ่ม approve/reject
- หรือใช้ tab ย่อยใน Members

## 8. Inject NotificationService ใน controller
- ต้องใช้ `Constructor injection`: `public function __construct(private NotificationService $notify) {}`
- Laravel auto-resolve

---

# 🎯 ลำดับงานแนะนำ (เริ่มจาก K.0 เสมอ)

```
🚨 K.0 schema repair → verify Phase G/H/J ทำงานจริง
   ↓
K.1 backfill seeder → ManageTabPermissions ใช้ได้
   ↓
K.2 join request → end-user flow ครบ
   ↓
K.3 notification service → infrastructure
   ↓
K.4 hook into controllers → events ที่เกี่ยวข้องส่ง notif
   ↓
K.5 notification bell UI → ผู้ใช้เห็น notif
   ↓
K.7 appointment polish (optional)
K.8 audit log (optional)
K.9 E2E smoke test ทั้งระบบ
```

หลัง K เสร็จ → **ระบบส่วนงานครบ workflow** จากการสร้าง → เชิญ → โพสต์ → แจ้งเตือน → audit ตรงตาม design ทั้งสิ้น 🎯

ติดตรงไหนตอนทำมาถามได้เลยครับ 🙌
