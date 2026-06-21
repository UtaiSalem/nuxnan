# Phase G — Backend Foundation (Detailed DIY Plan)

อ้างอิง decisions: [school-departments-decisions.md](file:///C:/wamp64/www/nuxnan/.agents/design-ref/school-departments-decisions.md)
Target: รองรับ post-as-group + type metadata + mute groups + (เผื่อ) hierarchy
วันที่: 2026-06-20

---

## 📌 Pre-check ที่ยืนยันความถูกต้องแล้ว

| ข้อเท็จจริง | ใช้สำหรับ |
|---|---|
| Academy feed ใช้ `AcademyPost` (ไม่ใช่ `Post`/`Activity` ทั่วไป) | **`posted_as_group_id` ต้องไปบน `academy_posts` table** |
| `Activity` เป็น polymorphic wrapper (`activityable_type/id` → AcademyPost) | Feed query ผ่าน `whereHasMorph` |
| `AcademyGroup` มี type, `academy_id` → cascade; `parent_id` ยังไม่มี | ต้อง migration เพิ่ม |
| `AcademyGroupController::addMember` **ยังไม่มี** validation ว่าผู้ที่จะเพิ่มเป็น academy member | ต้องเพิ่มใน Controller |
| `AcademyMember` มี `status` (int) | `status = 2` คือ Approved (สมาชิกที่ผ่านการอนุมัติแล้ว) |

### 🔎 Verification commands (รันเพื่อตรวจสอบ)

```bash
# 1. ตรวจสอบสถานะการเชื่อมต่อ Database และข้อมูลเบื้องต้น
php artisan tinker --execute="echo 'academy_members count: ' . \App\Models\AcademyMember::count();"

# 2. ตรวจสอบโครงสร้างตาราง
php artisan tinker --execute="echo json_encode(Schema::getColumnListing('academy_posts'));"
```

---

# G.1 — Type metadata config + public endpoint

**เป้าหมาย:** sync ข้อมูลประเภทกลุ่ม (Group Types) ให้ Backend เป็น Source of Truth โดยเพิ่ม endpoint สำหรับดึง metadata ไปแสดงผลที่ Frontend

## G.1.1 — สร้าง Constants ของกลุ่ม

**File:** [AcademyGroupTypes.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Constants/AcademyGroupTypes.php) (NEW)

```php
<?php

namespace App\Constants;

class AcademyGroupTypes
{
    /**
     * Source of truth for academy_group.type metadata.
     * Mirror this when changing ui/constants/academyGroupTypes.ts.
     */
    public const TYPES = [
        'office' => [
            'label'    => 'สำนัก',
            'label_en' => 'Office',
            'icon'     => 'heroicons:building-office',
            'color'    => 'purple',
            'order'    => 1,
        ],
        'department' => [
            'label'    => 'ฝ่าย',
            'label_en' => 'Department',
            'icon'     => 'heroicons:briefcase',
            'color'    => 'cyan',
            'order'    => 2,
        ],
        'section' => [
            'label'    => 'งาน',
            'label_en' => 'Section',
            'icon'     => 'heroicons:clipboard-document-list',
            'color'    => 'green',
            'order'    => 3,
        ],
        'academic_group' => [
            'label'    => 'กลุ่มสาระ',
            'label_en' => 'Academic Group',
            'icon'     => 'heroicons:book-open',
            'color'    => 'orange',
            'order'    => 4,
        ],
        'classroom' => [
            'label'    => 'ห้องเรียน',
            'label_en' => 'Classroom',
            'icon'     => 'heroicons:academic-cap',
            'color'    => 'cyan',
            'order'    => 5,
        ],
        'club' => [
            'label'    => 'ชมรม',
            'label_en' => 'Club',
            'icon'     => 'heroicons:trophy',
            'color'    => 'pink',
            'order'    => 6,
        ],
        'committee' => [
            'label'    => 'คณะกรรมการ',
            'label_en' => 'Committee',
            'icon'     => 'heroicons:user-group',
            'color'    => 'amber',
            'order'    => 7,
        ],
    ];

    public static function all(): array
    {
        return collect(self::TYPES)
            ->map(fn ($meta, $key) => array_merge(['key' => $key], $meta))
            ->sortBy('order')
            ->values()
            ->all();
    }

    public static function get(string $key): ?array
    {
        return self::TYPES[$key] ?? null;
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::TYPES);
    }

    public static function keys(): array
    {
        return array_keys(self::TYPES);
    }
}
```

## G.1.2 — สร้าง Controller

**File:** [AcademyGroupTypeController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupTypeController.php) (NEW)

```php
<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Constants\AcademyGroupTypes;
use App\Http\Controllers\Controller;

class AcademyGroupTypeController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => AcademyGroupTypes::all(),
        ]);
    }
}
```

## G.1.3 — เพิ่ม Route สำหรับดึงประเภทกลุ่ม

**File:** [academy.php (Routes)](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy.php)

**Location:** เพิ่ม import ที่ด้านบนไฟล์
```php
use App\Http\Controllers\Api\Learn\Academy\AcademyGroupTypeController;
```

**Location:** เพิ่มก่อน `Route::group(['middleware' => 'auth:api'], ...)` หรือวางในระดับ Public ของไฟล์ routes
```php
Route::get('/academy-group-types', [AcademyGroupTypeController::class, 'index'])
    ->name('api.academy.groupTypes');
```

**การทดสอบ:**
```bash
curl http://localhost:8000/api/academy-group-types
# คาดหวัง: {"success":true,"data":[{"key":"office","label":"\u0e2a\u0e33\u0e19\u0e31\u0e02",...}]}
```

---

# G.2 — Migrations (3 ตัว)

ใช้คำสั่ง `php artisan make:migration` หรือสร้างไฟล์ที่มีโครงสร้างตามรายละเอียดด้านล่าง:

## G.2.1 — เพิ่ม `parent_id` ใน `academy_groups`

**File:** [timestamp_add_parent_id_to_academy_groups_table.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2026_06_20_000001_add_parent_id_to_academy_groups_table.php) (NEW)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_groups', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('academy_id')
                    ->constrained('academy_groups')
                    ->nullOnDelete();
                $table->index(['academy_id', 'parent_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('academy_groups', function (Blueprint $table) {
            if (Schema::hasColumn('academy_groups', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropIndex(['academy_id', 'parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};
```

## G.2.2 — เพิ่ม `posted_as_group_id` ใน `academy_posts`

**File:** [timestamp_add_posted_as_group_id_to_academy_posts_table.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2026_06_20_000002_add_posted_as_group_id_to_academy_posts_table.php) (NEW)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_posts', 'posted_as_group_id')) {
                $table->foreignId('posted_as_group_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('academy_groups')
                    ->nullOnDelete();
                $table->index('posted_as_group_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academy_posts', function (Blueprint $table) {
            if (Schema::hasColumn('academy_posts', 'posted_as_group_id')) {
                $table->dropForeign(['posted_as_group_id']);
                $table->dropIndex(['posted_as_group_id']);
                $table->dropColumn('posted_as_group_id');
            }
        });
    }
};
```

## G.2.3 — สร้างตารางเก็บการ Mute ส่วนงาน (`user_muted_groups`)

**File:** [timestamp_create_user_muted_groups_table.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2026_06_20_000003_create_user_muted_groups_table.php) (NEW)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_muted_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('academy_group_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'academy_group_id']);
            $table->index('academy_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_muted_groups');
    }
};
```

**คำสั่งรัน Migration:**
```bash
php artisan migrate
```

---

# G.3 — Models + Relationships

## G.3.1 — ปรับแต่ง `AcademyGroup`

**File:** [AcademyGroup.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Models/AcademyGroup.php)

เพิ่มความสัมพันธ์และการเข้าถึงข้อมูล Meta-type ในคลาส:
```php
    // เพิ่ม parent_id ใน $fillable ของ Model
    protected $fillable = [
        'academy_id',
        'parent_id',
        'name',
        'description',
        'type',
        'settings',
        'sort_order'
    ];

    protected $appends = ['type_meta'];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * ดึง Type metadata (เช่น label, icon, color)
     */
    public function getTypeMetaAttribute(): ?array
    {
        return \App\Constants\AcademyGroupTypes::get($this->type);
    }
```

## G.3.2 — ปรับแต่ง `AcademyPost`

**File:** [AcademyPost.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Models/AcademyPost.php)

เพิ่มความสัมพันธ์โพสต์ในนามส่วนงาน:
```php
    public function postedAsGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'posted_as_group_id');
    }
```

## G.3.3 — สร้างโมเดล `UserMutedGroup`

**File:** [UserMutedGroup.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Models/UserMutedGroup.php) (NEW)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMutedGroup extends Model
{
    protected $fillable = ['user_id', 'academy_group_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'academy_group_id');
    }
}
```

## G.3.4 — ปรับแต่ง `User`

**File:** [User.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Models/User.php)

เพิ่มความสัมพันธ์การ Mute กลุ่ม:
```php
    public function mutedGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            AcademyGroup::class,
            'user_muted_groups',
            'user_id',
            'academy_group_id'
        )->withTimestamps();
    }
```

---

# G.4 — API Payload & Resource Updates

## G.4.1 — เพิ่ม `posted_as_group` ลงใน `AcademyPostResource`

**File:** [AcademyPostResource.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Resources/Learn/Academy/AcademyPostResource.php)

เพิ่มฟิลด์ลงในเมธอด `toArray()`:
```php
            'posted_as_group_id' => $this->posted_as_group_id,
            'posted_as_group'    => $this->whenLoaded('postedAsGroup', function () {
                return [
                    'id'        => $this->postedAsGroup->id,
                    'name'      => $this->postedAsGroup->name,
                    'type'      => $this->postedAsGroup->type,
                    'type_meta' => \App\Constants\AcademyGroupTypes::get($this->postedAsGroup->type),
                ];
            }),
```

---

# G.5 — Validation Rules

## G.5.1 — เพิ่มการตรวจสอบโพสต์ในนามกลุ่มใน `AcademyPostController`

เนื่องจาก `AcademyPostController` จัดการการเก็บข้อมูลด้วย `$request->validate()` ในตัวฟังก์ชันโดยตรง จึงต้องเพิ่มการตรวจสอบสิทธิ์การโพสต์และการตั้งค่าเปิด/ปิดสิทธิ์ของกลุ่มนั้นลงในส่วนนี้:

**File:** [AcademyPostController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyPostController.php)

เพิ่ม validation และ custom check ในเมธอด `store()`:
```php
    public function store(Academy $academy, Request $request)
    {
        // Check points - require 180 PP to create academy post
        $pointsRequired = 180;
        if (auth()->user()->pp < $pointsRequired) {
            return response()->json([
                'success' => false,
                'message' => "คุณมีแต้มสะสมไม่พอสำหรับการสร้างโพสต์ (ต้องการ {$pointsRequired} แต้ม)",
            ], 403);
        }

        $validatedData = $request->validate([
            'content'   => 'nullable|string|max:1000',
            'images'    => 'array|max:4',
            'images.*'  => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'posted_as_group_id' => 'nullable|integer|exists:academy_groups,id',
        ]);

        if (empty($validatedData['content']) && !$request->hasFile('images')) {
            return response()->json(['message' => 'Post cannot be empty.'], 422);
        }

        // Custom validation check สำหรับ Posted as Group
        if ($request->filled('posted_as_group_id')) {
            $groupId = $validatedData['posted_as_group_id'];
            $userId = auth()->id();
            
            // 1. ตรวจสอบว่าเป็นสมาชิก หรือ Admin ของกลุ่มนั้นหรือไม่
            $isMember = \App\Models\AcademyGroupMember::where('academy_group_id', $groupId)
                ->where('user_id', $userId)
                ->exists();
            $isAdmin = \App\Models\AcademyGroupAdmin::where('academy_group_id', $groupId)
                ->where('user_id', $userId)
                ->exists();

            if (!$isMember && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่ใช่สมาชิกของส่วนงานนี้'
                ], 403);
            }

            // 2. ตรวจสอบสิทธิ์ can_post ของกลุ่ม (STRICT permission check)
            $canPost = \App\Models\AcademyGroupPermission::where('academy_group_id', $groupId)
                ->where('permission_key', 'can_post')
                ->where('enabled', true)
                ->exists();

            if (!$canPost) {
                return response()->json([
                    'success' => false,
                    'message' => 'ส่วนงานนี้ยังไม่ได้เปิดสิทธิ์โพสต์'
                ], 403);
            }
        }

        $content = $validatedData['content'] ?? '';
        $hashtags = $this->extractHashtags($content);

        $post = new AcademyPost();
        $post->user_id = auth()->user()->id;
        $post->academy_id = $academy->id;
        $post->content = $content;
        $post->hashtags = json_encode($hashtags);
        if ($request->filled('posted_as_group_id')) {
            $post->posted_as_group_id = $validatedData['posted_as_group_id'];
        }
        $post->save();
        
        // ... (คงโครงสร้างการบันทึกรูปภาพและกิจกรรมตามเดิม) ...
```

## G.5.2 — ตรวจสอบสมาชิกโรงเรียนเมื่อเพิ่มเข้ากลุ่มย่อย

**File:** [AcademyGroupController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php)

*จุดระวัง:* Signature ของเมธอดในระบบจริงคือ `addMember(AcademyGroup $academyGroup, Request $request)` 

ปรับปรุงการ validate และเพิ่มเงื่อนไข:
```php
    public function addMember(AcademyGroup $academyGroup, Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|in:student,teacher,admin'
        ]);

        $userId = $validated['user_id'];

        // ⭐ ตรวจสอบว่าเป็นสมาชิกของสถาบันที่ได้รับอนุมัติแล้วหรือไม่ (status = 2)
        $isAcademyMember = \App\Models\AcademyMember::where('academy_id', $academyGroup->academy_id)
            ->where('user_id', $userId)
            ->where('status', 2) // 2 = Approved
            ->exists();

        if (!$isAcademyMember) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้รายนี้ยังไม่ได้เป็นสมาชิกที่ได้รับการอนุมัติของสถาบันการศึกษา'
            ], 422);
        }

        // ตรวจสอบสมาชิกซ้ำ
        if ($academyGroup->members()->where('user_id', $userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้นี้เป็นสมาชิกของกลุ่มอยู่แล้ว'
            ], 400);
        }

        $academyGroup->members()->attach($userId, [
            'role' => $validated['role'] ?? 'student'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มสมาชิกเรียบร้อยแล้ว'
        ]);
    }
```

และปรับปรุง validation ให้รองรับ Type อื่นๆ และ `parent_id` ในเมธอด `store` และ `update` ด้วย:
```php
    public function store(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:office,department,section,academic_group,classroom,club,committee',
            'parent_id' => 'nullable|integer|exists:academy_groups,id',
            'settings' => 'nullable|array'
        ]);

        $group = $academy->academyGroups()->create($validated);

        // Auto-seed Default Permissions
        foreach (\App\Constants\AcademyGroupPermissions::PERMISSIONS as $key => $meta) {
            \App\Models\AcademyGroupPermission::create([
                'academy_group_id' => $group->id,
                'permission_key'   => $key,
                'enabled'          => $meta['default'],
            ]);
        }

        return response()->json([
            'success' => true,
            'group' => $group
        ], 201);
    }
```

และปรับปรุง `getByType` เพื่อให้การตรวจความถูกต้องของ Type มีความยืดหยุ่น:
```php
    public function getByType(Academy $academy, string $type)
    {
        if (!\App\Constants\AcademyGroupTypes::exists($type)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid group type'
            ], 400);
        }

        $groups = $academy->academyGroups()
            ->where('type', $type)
            ->withCount('members')
            ->get();

        return response()->json([
            'success' => true,
            'groups' => $groups
        ]);
    }
```

---

# G.6 — Permission Constants & Seeding

## G.6.1 — สร้าง Constants ของสิทธิ์ (Permissions)

**File:** [AcademyGroupPermissions.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Constants/AcademyGroupPermissions.php) (NEW)

```php
<?php

namespace App\Constants;

class AcademyGroupPermissions
{
    public const PERMISSIONS = [
        'can_post'             => ['label' => 'โพสต์ในนามส่วนงาน',  'default' => false],
        'can_invite_member'    => ['label' => 'เชิญสมาชิกใหม่',       'default' => true],
        'can_remove_member'    => ['label' => 'นำสมาชิกออก',          'default' => false],
        'can_pin_post'         => ['label' => 'ปักหมุดโพสต์',          'default' => false],
        'can_create_event'     => ['label' => 'สร้างกิจกรรม',          'default' => false],
        'can_send_announcement' => ['label' => 'ออกประกาศ',            'default' => false],
    ];

    public static function all(): array
    {
        return collect(self::PERMISSIONS)
            ->map(fn ($meta, $key) => array_merge(['key' => $key], $meta))
            ->values()
            ->all();
    }

    public static function keys(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function defaultFor(string $key): bool
    {
        return self::PERMISSIONS[$key]['default'] ?? false;
    }
}
```

## G.6.2 — เพิ่ม Route สำหรับดึงรายการสิทธิ์ทั้งหมด

**File:** [academy.php (Routes)](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy.php)

เพิ่ม Endpoint:
```php
Route::get('/academy-group-permissions', [AcademyGroupTypeController::class, 'permissions'])
    ->name('api.academy.groupPermissions');
```

และเพิ่มเมธอด `permissions()` ใน [AcademyGroupTypeController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupTypeController.php):
```php
    public function permissions()
    {
        return response()->json([
            'success' => true,
            'data'    => \App\Constants\AcademyGroupPermissions::all(),
        ]);
    }
```

## G.6.3 — สร้าง Seeder สำหรับ backfill กลุ่มเก่าในระบบ

**File:** [BackfillGroupPermissionsSeeder.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/seeders/BackfillGroupPermissionsSeeder.php) (NEW)

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupPermission;
use App\Constants\AcademyGroupPermissions;

class BackfillGroupPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        AcademyGroup::doesntHave('permissions')->chunk(100, function ($groups) {
            foreach ($groups as $g) {
                foreach (AcademyGroupPermissions::PERMISSIONS as $key => $meta) {
                    AcademyGroupPermission::firstOrCreate(
                        ['academy_group_id' => $g->id, 'permission_key' => $key],
                        ['enabled' => $meta['default']],
                    );
                }
            }
        });
    }
}
```

---

# G.7 — Mute / Unmute endpoints

## G.7.1 — สร้าง Controller

**File:** [AcademyGroupMuteController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupMuteController.php) (NEW)

```php
<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\AcademyGroup;
use App\Models\UserMutedGroup;
use Illuminate\Http\Request;

class AcademyGroupMuteController extends Controller
{
    public function mute(Request $request, AcademyGroup $academyGroup)
    {
        UserMutedGroup::firstOrCreate([
            'user_id'          => $request->user()->id,
            'academy_group_id' => $academyGroup->id,
        ]);

        return response()->json(['success' => true, 'muted' => true]);
    }

    public function unmute(Request $request, AcademyGroup $academyGroup)
    {
        UserMutedGroup::where('user_id', $request->user()->id)
            ->where('academy_group_id', $academyGroup->id)
            ->delete();

        return response()->json(['success' => true, 'muted' => false]);
    }
}
```

## G.7.2 — กำหนด Routes ใน [academy.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy.php)

วางภายในกลุ่ม Middleware `auth:api`:
```php
use App\Http\Controllers\Api\Learn\Academy\AcademyGroupMuteController;

// ...
Route::post('/groups/{academyGroup}/mute', [AcademyGroupMuteController::class, 'mute'])
    ->name('api.academy.groups.mute');
Route::delete('/groups/{academyGroup}/mute', [AcademyGroupMuteController::class, 'unmute'])
    ->name('api.academy.groups.unmute');
```

---

# G.8 — Feed Filter (Auto-exclude muted)

ปรับปรุงระบบ Feed ของโรงเรียนใน [AcademyActivityController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyActivityController.php) เพื่อให้กรองตาม Group Type, Post Type และทำการเอาโพสต์ของกลุ่มที่ผู้ใช้กด Mute ออกจาก Feed อัตโนมัติ

**File:** [AcademyActivityController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyActivityController.php)

```php
<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Activity;
use App\Models\AcademyPost;
use Illuminate\Http\Request;
use App\Http\Resources\AcademyResource;
use App\Http\Resources\Play\ActivityResource;

class AcademyActivityController extends Controller
{
    public function index(Academy $academy, Request $request)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();
        $userId = auth()->id();
        $filterType = $request->input('filter_type');
        $groupType = $request->input('group_type');

        $activities = Activity::with([
            'user',
            'activityable.user',
            'activityable.academy',
            'activityable.images',
            'activityable.postedAsGroup',
            'activityable.comments' => function ($cq) {
                $cq->with('user')->latest()->limit(3);
            },
        ])
        ->whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy, $userId, $filterType, $groupType) {
            $query->where('academy_id', $academy->id);

            // กรองตามประเภทของโพสต์ (เช่น announcement, event)
            if ($filterType && $filterType !== 'all') {
                $query->where('post_type', $filterType);
            }

            // กรองตามประเภทกลุ่มย่อย
            if ($groupType) {
                $query->whereHas('postedAsGroup', function ($g) use ($groupType) {
                    $g->where('type', $groupType);
                });
            }

            // ซ่อนโพสต์จากกลุ่มที่กด Mute ไว้
            if ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->whereNull('posted_as_group_id')
                      ->orWhereNotIn('posted_as_group_id', function ($sub) use ($userId) {
                          $sub->select('academy_group_id')
                              ->from('user_muted_groups')
                              ->where('user_id', $userId);
                      });
                });
            }
        })
        ->latest()
        ->paginate();

        return response()->json([
            'academy'               => new AcademyResource($academy),
            'isAcademyAdmin'        => $isAcademyAdmin,
            'activities'            => ActivityResource::collection($activities),
        ]);
    }

    public function getActivities(Academy $academy, Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $userId = auth()->id();
        $filterType = $request->input('filter_type');
        $groupType = $request->input('group_type');
        
        $activities = Activity::with([
            'user',
            'activityable.user',
            'activityable.academy',
            'activityable.images',
            'activityable.postedAsGroup',
            'activityable.comments' => function ($cq) {
                $cq->with('user')->latest()->limit(3);
            },
        ])
        ->whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy, $userId, $filterType, $groupType) {
            $query->where('academy_id', $academy->id);

            // กรองตามประเภทของโพสต์
            if ($filterType && $filterType !== 'all') {
                $query->where('post_type', $filterType);
            }

            // กรองตามประเภทกลุ่มย่อย
            if ($groupType) {
                $query->whereHas('postedAsGroup', function ($g) use ($groupType) {
                    $g->where('type', $groupType);
                });
            }

            // ซ่อนโพสต์จากกลุ่มที่กด Mute ไว้
            if ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->whereNull('posted_as_group_id')
                      ->orWhereNotIn('posted_as_group_id', function ($sub) use ($userId) {
                          $sub->select('academy_group_id')
                              ->from('user_muted_groups')
                              ->where('user_id', $userId);
                      });
                });
            }
        })
        ->latest()
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'activities' => [
                'data' => ActivityResource::collection($activities->items()),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'next_page_url' => $activities->nextPageUrl(),
                'prev_page_url' => $activities->previousPageUrl(),
            ],
        ]);
    }
}
```

---

# 📋 Summary of Files Affected

## ✏️ New Files (9)
*   `api/nuxnanravel/app/Constants/AcademyGroupTypes.php`
*   `api/nuxnanravel/app/Constants/AcademyGroupPermissions.php`
*   `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupTypeController.php`
*   `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupMuteController.php`
*   `api/nuxnanravel/app/Models/UserMutedGroup.php`
*   `api/nuxnanravel/database/migrations/2026_06_20_000001_add_parent_id_to_academy_groups_table.php`
*   `api/nuxnanravel/database/migrations/2026_06_20_000002_add_posted_as_group_id_to_academy_posts_table.php`
*   `api/nuxnanravel/database/migrations/2026_06_20_000003_create_user_muted_groups_table.php`
*   `api/nuxnanravel/database/seeders/BackfillGroupPermissionsSeeder.php`

## 🔧 Modified Files (7)
*   `api/nuxnanravel/routes/learn/academy.php`
*   `api/nuxnanravel/app/Models/AcademyGroup.php`
*   `api/nuxnanravel/app/Models/AcademyPost.php`
*   `api/nuxnanravel/app/Models/User.php`
*   `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php`
*   `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyPostController.php`
*   `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyActivityController.php`
*   `api/nuxnanravel/app/Http/Resources/Learn/Academy/AcademyPostResource.php`

---

# 🛣️ Commit Plan (7 Commits)

```
1. feat(academy): add group type & permission constants + endpoints
2. feat(db): add parent_id column and hierarchical relation to academy_groups
3. feat(db): add posted_as_group_id column and relation to academy_posts
4. feat(db): create user_muted_groups table and user relation
5. feat(academy): implement posted_as_group validation and academy member verification
6. feat(academy): auto-seed group permissions on creation and backfill seeder
7. feat(academy): implement group mute/unmute and update activity feeds with filters
```

---

# ✅ Phase G — Test Checklist

- [ ] `php artisan migrate` รันสำเร็จ
- [ ] `php artisan route:list | grep group` แสดง endpoints ครบถ้วน
- [ ] Tinker สามารถเรียกและดึงความสัมพันธ์ของ `parent`, `children`, `postedAsGroup`, `mutedGroups` ได้อย่างไม่มีข้อผิดพลาด
- [ ] `GET /api/academy-group-types` คืนข้อมูลกลุ่มประเภทใหม่ทั้งหมด 7 ประเภทถูกต้องตามลำดับ
- [ ] `POST /api/academies/{academy}/posts` ปฏิเสธการสร้างโพสต์แบบโพสต์ในนามกลุ่ม หากผู้ใช้ไม่ใช่สมาชิกกลุ่มนั้น (คืน status 403)
- [ ] `POST /api/academies/{academy}/posts` ปฏิเสธการสร้างโพสต์แบบโพสต์ในนามกลุ่ม หากกลุ่มไม่มีสิทธิ์ `can_post` (enabled=false)
- [ ] การเพิ่มสมาชิกเข้ากลุ่มผ่าน `addMember` ปฏิเสธการเพิ่มผู้ใช้ที่ไม่ได้เป็นสมาชิกสถาบันการศึกษา (Approved status=2)
- [ ] สร้างกลุ่มใหม่แล้ว ตรวจสอบว่าระบบบันทึกรายการ Default Permissions ลงตาราง `academy_group_permissions` ครบ 6 ค่า
- [ ] การ Mute กลุ่มแล้วเรียก Feed จะไม่พบโพสต์ของกลุ่มดังกล่าว
- [ ] การกรอง Feed ผ่าน `?filter_type=announcement` และ `?group_type=club` คืนเฉพาะผลลัพธ์ที่ถูกต้อง
