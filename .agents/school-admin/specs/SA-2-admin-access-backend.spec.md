# สเปค SA-2 — backend: คีย์ `admin.access` + ปิดช่องร้านค้าด้วย `store.manage`

> แผนแม่: `C:\wamp64\www\nuxnan\.agents\plans\school-admin-improvement.md` (ข้อ 4 เฟส 1 · ข้อ 5.1 เคาะแล้ว)
> แบ่ง 2 shard ที่ไฟล์ไม่ทับกัน — **SA-2a** (สิทธิ์เข้าแอดมิน) ต้องลงก่อน/พร้อม SA-1 · **SA-2b** (ร้านค้า) อิสระ
>
> วิธีรัน (agy — หมดโควตาถึงราว 2026-11-27 · ระหว่างนี้ใช้ Codex ผ่าน `codex:rescue` แทนได้):
> `agy --print "Read C:\wamp64\www\nuxnan\.agents\school-admin\specs\SA-2-admin-access-backend.spec.md and do ONLY shard SA-2a." --add-dir "C:\wamp64\www\nuxnan" --model claude-opus-5-5-high --mode accept-edits --print-timeout 20m`
> (เช็ค slug ด้วย `agy models` ก่อนทุกครั้ง)

## ข้อห้ามประจำ (ทุก shard)

- ห้าม `git commit` · ห้าม `git push` · **ห้าม `php artisan migrate`** (Claude รันเองด้วย `--path`) · ห้าม `npm run build` · ห้ามแตะ `.env`
- ห้ามแตะไฟล์นอกรายการ "ไฟล์ที่แก้ได้" ของ shard นั้น
- ห้ามเขียนเช็คสิทธิ์ซ้ำในคอนโทรลเลอร์ถ้า route มี `academy.permission:<key>` แล้ว (ต้นเหตุ G18) — การเช็ค **ความเป็นเจ้าของรายการ** (ออเดอร์ของตัวเอง ฯลฯ) และ **tenant** ในคอนโทรลเลอร์ทำได้และต้องทำ
- รายงานกลับ: `git diff --stat` + ผลรันเกณฑ์แบบดิบ (ห้ามสรุปเอง)

---

## SHARD SA-2a — คีย์ `admin.access` + super admin ใน `/my-role` + ธง `canEnterAdmin`

### บริบท (วิเคราะห์จบแล้ว — ห้ามออกแบบใหม่)

ประตูเปลือกแอดมินฝั่งหน้าเว็บ (`ui/pages/academies/[name]/admin.vue:313`) ปล่อยแค่ `isAdmin || role.name === 'teacher'`
⇒ ผู้อำนวยการ/ผู้ดูแลระบบ/นายทะเบียน/เจ้าหน้าที่ ฯลฯ เข้าแอดมินไม่ได้ทั้งที่ backend ให้สิทธิ์
เจ้าของเคาะ (D2): เพิ่มคีย์ใหม่ **`admin.access`** = "เข้าหน้าจัดการโรงเรียน" แล้วมอบให้บทบาทฝั่งบุคลากร
ประตูใหม่ (ทำใน SA-1) = `isAdmin || can('admin.access')` · นักเรียน/ผู้ปกครอง/guest ต้อง **ไม่ได้** คีย์นี้

**ทำไม migration ต้องแตะทั้งบทบาทระบบและบทบาทที่โรงเรียนก๊อปไป:** ประตูเดิมเช็คจาก `name === 'teacher'`
ซึ่งรวมแถวบทบาทของแต่ละโรงเรียน (`academy_id` ไม่ว่าง) ที่ชื่อ `teacher` ด้วย — ถ้ามอบคีย์ให้แค่แถวระบบ ครูในโรงเรียนที่มีบทบาทก๊อปจะเข้าแอดมินไม่ได้หลัง SA-1
(บน dev มีบทบาทโรงเรียน 0 แถว แต่ production อาจมี)

### ไฟล์ที่แก้ได้

1. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Models\AcademyPermission.php`
2. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Models\AcademyRole.php`
3. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Http\Controllers\Api\Learn\Academy\AcademyRoleController.php`
4. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Http\Controllers\Api\Learn\Academy\AcademyController.php`
5. สร้างใหม่: `C:\wamp64\www\nuxnan\api\nuxnanravel\database\migrations\2026_11_23_000001_grant_admin_access_permission.php`
6. สร้างใหม่: `C:\wamp64\www\nuxnan\api\nuxnanravel\tests\Feature\Academy\AdminAccessPermissionTest.php`

### การแก้

**1) `AcademyPermission::PERMISSIONS`** — เพิ่ม 2 กลุ่มใหม่ต่อท้าย (หลังกลุ่ม `'emergency'`) — **add-only ห้ามแก้กลุ่มเดิม**
```php
        'admin' => [
            ['name' => 'admin.access', 'display_name' => 'เข้าหน้าจัดการโรงเรียน (เห็นเมนูตามสิทธิ์อื่นที่มี)'],
        ],
        'store' => [
            ['name' => 'store.manage', 'display_name' => 'จัดการร้านค้าโรงเรียน: ตั้งค่าร้าน/สินค้า/หมวดหมู่/คำสั่งซื้อ/รายงานขาย'],
        ],
```
**ห้าม** เพิ่ม `admin` หรือ `store` ลงใน `DEPARTMENT_DELEGABLE_FAMILIES` (ฝ่ายห้ามมอบสองคีย์นี้ — ค่าเริ่มต้นปฏิเสธอยู่แล้ว)

**2) `AcademyRole::SYSTEM_ROLES`** — เติม `'admin.access'` ต่อท้ายอาเรย์ `permissions` ของ 7 บทบาท:
`director`, `admin`, `teacher`, `registrar`, `staff`, `card_admin`, `finance_staff`
**ห้าม** เติมให้ `owner` (มี `*` อยู่แล้ว) · `student` · `parent` · `guest` · ห้ามแตะคีย์อื่นในอาเรย์

**3) migration ใหม่** — ลอกโครงจาก `database/migrations/2026_10_11_000001_grant_student_registry_permissions.php` ทุกอย่าง ยกเว้น:
- ค่าคงที่: `private const ROLE_NAMES = ['director','admin','teacher','registrar','staff','card_admin','finance_staff'];` และ `private const KEY = 'admin.access';`
- query: `DB::table('academy_roles')->whereIn('name', self::ROLE_NAMES)->orderBy('id')->eachById(...)` — **ไม่มี `whereNull('academy_id')`** (เหตุผลอยู่ในบริบทด้านบน — เขียนเป็นคอมเมนต์หัวไฟล์ภาษาไทยด้วย)
- `up()`: ข้ามแถวที่มี `*` · ถ้ามีคีย์อยู่แล้วไม่ต้องทำอะไร · เพิ่ม `admin.access` แบบไม่ซ้ำ · ถ้าตาราง `academy_roles` ว่างให้ `return`
- `down()`: ลบเฉพาะ `admin.access` ออกจากแถวที่ชื่ออยู่ใน `ROLE_NAMES` (คีย์นี้เป็นคีย์ใหม่ จึงคืนค่าได้ตรง)
- ห่อด้วย `DB::transaction` · `json_encode(..., JSON_THROW_ON_ERROR)` · ห้าม hardcode id

**4) `AcademyRoleController::myRole()`** — เพิ่มสาขา super admin **ถัดจากสาขาเจ้าของ (ก่อนสาขา academy_admins)**:
```php
        // super admin = ผ่านทุกด่านฝั่ง backend อยู่แล้ว (Academy::isAdmin) — หน้าเว็บต้องรู้ด้วย ไม่งั้นประตูแอดมินเด้งออก
        if ($user->isSuperAdmin()) {
            $adminRole = AcademyRole::where('name', 'admin')
                ->whereNull('academy_id')
                ->first();

            return response()->json([
                'success' => true,
                'role' => $adminRole,
                'permissions' => ['*'],
                'is_owner' => false,
                'is_admin' => true,
                'is_super_admin' => true,
            ]);
        }
```
สาขาอื่นห้ามแก้

**5) `AcademyController::show()`** — เพิ่มคีย์ระดับบนสุดใน response (ข้าง `'isAcademyAdmin'`) **ห้ามใส่ใน `AcademyResource`** (resource ถูกใช้ในรายการ จะเกิด N+1):
```php
            // SA-2a — ปุ่ม "จัดการโรงเรียน" ใช้กฎเดียวกับประตูแอดมิน (isAdmin || admin.access)
            'canEnterAdmin' => auth()->check() && $academy->userCan(auth()->user(), 'admin.access'),
```
(`userCan` ปล่อย super admin/เจ้าของ/academy_admins ผ่านเองอยู่แล้ว)

**6) เทสต์ `AdminAccessPermissionTest`** — ดูวิธีสร้างโรงเรียน/สมาชิก/บทบาทจากเทสต์ที่มีอยู่ เช่น
`tests/Feature/SchoolManagementRouteGuardTest.php` และวิธีรัน migration ในเทสต์จาก `tests/Feature/BackfillCompletedCardRequestsMigrationTest.php` (ค้นด้วย `grep -rl` ถ้า path ไม่ตรง)
ต้องมีอย่างน้อย 7 เคส:
1. `up()` เติม `admin.access` ให้บทบาทระบบทั้ง 7 ชื่อ และ **ไม่** เติมให้ `student`/`parent`/`guest`/`owner`
2. `up()` เติมให้แถวบทบาทของโรงเรียน (`academy_id` = โรงเรียนในเทสต์) ที่ชื่อ `teacher` ด้วย
3. รัน `up()` ซ้ำไม่เกิดคีย์ซ้ำ · `down()` ลบออกหมดและคีย์อื่นคงเดิมทุกตัว
4. `GET /api/academies/{id}/my-role` ของ super admin ที่ไม่ใช่เจ้าของ/สมาชิก → `is_admin = true` และ `permissions` มี `*`
5. `GET /api/academies/{name}` ของสมาชิกบทบาท `teacher` ที่มี `admin.access` → `canEnterAdmin = true`
6. ของสมาชิกบทบาท `student` → `canEnterAdmin = false` · ของคนนอก → `false`
7. ของเจ้าของ → `true`

### เกณฑ์ผ่าน (Claude รันเอง)
```bash
cd C:/wamp64/www/nuxnan/api/nuxnanravel
./vendor/bin/pint --test
php artisan test --filter=AdminAccessPermissionTest
php artisan test:db:rebuild --force --no-interaction        # รัน background · อ่านไฟล์ output ตรง ๆ
php artisan test -c phpunit.mysql.xml --filter=AdminAccessPermissionTest
php artisan test -c phpunit.mysql.xml --filter='SchoolManagementRouteGuardTest|ReportsSummaryTest|DashboardSummaryTest'   # กันถอยหลัง
php artisan academy:roles-doctor   # ต้องไม่ฟ้องคีย์ใหม่ว่าไม่รู้จัก
git diff --stat   # AcademyPermission/AcademyRole ต้องเป็น + ล้วน
```
หลังผ่านแล้ว Claude รันบน dev เอง: `php artisan migrate --path=database/migrations/2026_11_23_000001_grant_admin_access_permission.php` แล้วตรวจ `migrate:status`

---

## SHARD SA-2b — ร้านค้าโรงเรียน: ปิดช่องสิทธิ์ด้วย `store.manage` + tenant

### บริบท (ยืนยันจากโค้ด 2026-11-23)

`routes/learn/academy-store.php` มีแค่ `auth:api` และคอนโทรลเลอร์ร้านค้า 4 ตัวเช็คแค่ "ล็อกอินหรือยัง" ⇒ **ใครล็อกอินก็:**
สร้าง/แก้ร้านของโรงเรียนไหนก็ได้ · เพิ่ม/แก้/ลบสินค้า (รวมราคาและราคาแต้ม) · ยืนยัน/เสร็จสิ้น/ยกเลิกคำสั่งซื้อของคนอื่น (การยกเลิกคืนแต้ม/เงินให้ผู้ซื้อ) ·
ดูคำสั่งซื้อทั้งหมดและรายงานยอดขาย · ลบรีวิวของคนอื่น · และ `{product}`/`{category}`/`{order}`/`{review}` ไม่ผูกกับโรงเรียนใน URL (IDOR ข้ามโรงเรียน)
ตารางร้านค้าบน dev ว่างทั้งหมด (0 แถว) — ยังไม่มีข้อมูลเสียหาย แต่ต้องปิดก่อนมีโรงเรียนใช้จริง
เจ้าของเคาะ (D9): ร้านค้าใช้คีย์ใหม่ `store.manage` (สร้างใน SA-2a) · **ไม่มอบให้บทบาทไหนโดยอัตโนมัติ** — เจ้าของ/super admin ผ่านเองผ่าน `userCan`

### ไฟล์ที่แก้ได้

1. `C:\wamp64\www\nuxnan\api\nuxnanravel\routes\learn\academy-store.php`
2. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Http\Controllers\Api\Learn\Academy\AcademyStoreController.php`
3. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Http\Controllers\Api\Learn\Academy\StoreCategoryController.php`
4. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Http\Controllers\Api\Learn\Academy\StoreProductController.php`
5. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Http\Controllers\Api\Learn\Academy\StoreOrderController.php`
6. `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Http\Controllers\Api\Learn\Academy\StoreReviewController.php`
7. สร้างใหม่: `C:\wamp64\www\nuxnan\api\nuxnanravel\tests\Feature\Academy\StoreAuthzTest.php`

**ห้ามแตะ:** `app/Services/SchoolStoreService.php` (การคืนเงินเข้า `wallet` ตรง ๆ ไม่ผ่าน ledger เป็นหนี้แยก — **ไม่อยู่ใน shard นี้**) · model · migration · ui

### การแก้

**1) routes** — ใส่ `->middleware('academy.permission:store.manage')` ที่ **รายเส้น** (ห้ามแขวนที่กลุ่ม เพราะกลุ่มเดียวกันมีเส้นของผู้ซื้อ — บทเรียน SM-S1):
- `POST /` · `PATCH /` · `GET /stats` · `POST /logo` · `POST /banner`
- `POST /categories` · `PATCH /categories/{category}` · `DELETE /categories/{category}`
- `POST /products` · `PATCH /products/{product}` · `DELETE /products/{product}` · `POST /products/{product}/images` · `DELETE /products/{product}/images`
- `GET /orders` · `POST /orders/{order}/confirm` · `/process` · `/ready` · `/complete`
- `GET /reports/sales`

เส้นที่ **คง `auth:api` อย่างเดียว** (ผู้ซื้อ): `GET /` · `GET /categories` · `GET /products` · `GET /products/{product}` ·
reviews ทั้ง 3 เส้น · `POST /orders` · `GET /orders/my` · `GET /orders/{order}` · `POST /orders/{order}/cancel` (ทั้งสองเส้นหลังเช็คเจ้าของในคอนโทรลเลอร์ ข้อ 3)

**2) tenant ทุกเมธอดที่รับ `{product}` / `{category}` / `{order}` / `{review}`** — เพิ่ม private helper ในแต่ละคอนโทรลเลอร์ แล้วเรียกเป็นบรรทัดแรกหลังเช็คล็อกอิน:
- หา `$store = AcademyStore::where('academy_id', $academy->id)->firstOrFail();`
- product/category/order ต้องมี `academy_store_id === $store->id` ไม่งั้น `abort(404)`
- review ต้องเป็นของ `$product` ใน URL และ product ต้องอยู่ในร้านนี้ ไม่งั้น `abort(404)`
- validation `category_id` ใน product store/update: เปลี่ยน `exists:store_categories,id` เป็น
  `Rule::exists('store_categories', 'id')->where('academy_store_id', $store->id)`

**3) เจ้าของรายการ** (เช็คในคอนโทรลเลอร์ ใช้ `userCan` ตามกฎ G18):
- `StoreOrderController::show` และ `::cancel` — อนุญาตเมื่อ `$order->user_id === $user->id` **หรือ** `$academy->userCan($user, 'store.manage')` ไม่งั้น 403
- `StoreReviewController::destroy` — อนุญาตเมื่อ `$review->user_id === $user->id` **หรือ** `userCan($user, 'store.manage')` ไม่งั้น 403
  (ถ้าคอลัมน์เจ้าของรีวิวไม่ได้ชื่อ `user_id` ให้เปิด model ดูแล้วใช้ชื่อจริง — ห้ามเดา)

**4) เทสต์ `StoreAuthzTest`** อย่างน้อย 8 เคส:
1. สมาชิกธรรมดา: `POST /products` · `PATCH /products/{id}` · `POST /orders/{id}/complete` · `GET /orders` · `GET /reports/sales` → 403
2. สมาชิกบทบาทที่มี `store.manage` → ผ่าน (2xx) อย่างน้อย 3 เส้นข้างบน · เจ้าของ → ผ่าน
3. product ของโรงเรียน B ยิงผ่าน URL โรงเรียน A (คนยิงมี `store.manage` ของ A) → 404
4. ผู้ซื้อดู/ยกเลิกออเดอร์ของตัวเองได้ · ดูออเดอร์คนอื่น → 403
5. ลบรีวิวของคนอื่น → 403 · ลบของตัวเอง → ผ่าน
6. `category_id` ของร้านอื่นตอนสร้างสินค้า → 422
7. เส้นผู้ซื้อ (`GET /products`, `GET /orders/my`) ยังใช้ได้สำหรับสมาชิกธรรมดา
8. `php artisan route:list --path=store -v` ต้องเห็น `store.manage` ครบ 19 เส้นในข้อ 1 (เขียนเป็นเทสต์หรือรายงานผลดิบ)

### เกณฑ์ผ่าน (Claude รันเอง)
```bash
cd C:/wamp64/www/nuxnan/api/nuxnanravel
./vendor/bin/pint --test
php artisan route:list --path=academies/{academy}/store -v
php artisan test -c phpunit.mysql.xml --filter=StoreAuthzTest
git diff --stat
```

---

## หลังทั้งสอง shard

- Claude ตรวจ `git diff` ทุกบรรทัด + รันเกณฑ์เอง (ห้ามเชื่อตัวเลขในรายงาน)
- อัปเดต Review Log ในแผนแม่ + `.agents/worklog.md`
- **dev อีกเครื่องหลัง pull:** `php artisan migrate --path=database/migrations/2026_11_23_000001_grant_admin_access_permission.php` (ห้าม `migrate` เปล่า — มี Pending อื่น)
- **production:** SA-2a ต้อง deploy + migrate **ก่อนหรือพร้อม** SA-1 ไม่งั้นครูเข้าแอดมินไม่ได้
