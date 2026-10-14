# agy spec — #23 RV-S1: align revenue authz กับ finance.view / finance.manage

> วิธีรัน (บนเครื่อง WAMP):
> `agy --print "Read C:\wamp64\www\nuxnan\.agents\school-admin\specs\23-RV-S1-authz.spec.md and follow it exactly." --add-dir "C:\wamp64\www\nuxnan" --model gemini-3.1-pro-high --mode accept-edits --print-timeout 20m`

## 1. บริบท + root cause (วิเคราะห์จบแล้ว — agy ไม่ต้องคิดสถาปัตยกรรมเอง)

เมนู #23 รายได้ มี **authz inconsistency**: route gate ด้วย `finance.view`/`finance.manage` และ FE โชว์ tab/ปุ่มตาม `finance.view`/`finance.manage`
แต่ **controller กลับบังคับ `$academy->isAdmin($user)` ซ้ำ** ⇒ ผู้ถือ `finance.view`/`finance.manage` ที่ไม่ใช่ owner/admin โดน 403
→ มอบงานการเงินให้ฝ่ายไม่ได้ และแท็บแคมเปญพังสำหรับ finance.view

**เจ้าของเคาะแล้ว (Q1):** ให้ `finance.view` ดูแคมเปญได้ + `finance.manage` อนุมัติบริจาค/จัดการแคมเปญได้

**วิธีแก้:** แทนที่การเช็ก `$academy->isAdmin($user)` ด้วย `$academy->userCan($user, '<key>')`
- `userCan()` คืน true ให้ owner/admin อยู่แล้ว (เมธอดเริ่มด้วย `if ($this->isAdmin($user)) return true;`) → **ไม่ regress owner/admin**
- อ่านแคมเปญ = `finance.view` · เขียน (approve/reject/campaign store/update) = `finance.manage`
- **คง tenant check `academy_id` ทุกจุดเดิมไว้เป๊ะ ๆ** (ห้ามลบ)

## 2. ไฟล์ที่แก้ได้ (ชื่อเต็ม — ห้ามแตะไฟล์อื่น)

- `C:\wamp64\www\nuxnan\api\nuxnanravel\app\Http\Controllers\Api\Academies\AcademyRevenueController.php`
- สร้างใหม่: `C:\wamp64\www\nuxnan\api\nuxnanravel\tests\Feature\Academy\AcademyRevenueAuthzTest.php`

**ห้ามแตะ:** routes, FE (`ui/`), models, service, resource, migration, `.env`, ไฟล์อื่นทุกไฟล์

## 3. การแก้แบบเป๊ะ ๆ (before → after) ใน AcademyRevenueController.php

### 3.1 `donations()` — เปลี่ยนตัวแปร isAdmin เป็น canManage
**before:**
```php
        $user = $request->user();
        $isAdmin = $academy->isAdmin($user);

        $query = AcademyDonate::with(['academy', 'donor', 'transaction', 'reviewer'])
            ->where('academy_id', $academy->id)
            ->whereNull('deleted_at')
            ->latest();

        if (! $isAdmin) {
            $query->whereIn('status', ['approved', 'completed']);
        }
```
**after:**
```php
        $user = $request->user();
        $canManage = $academy->userCan($user, 'finance.manage');

        $query = AcademyDonate::with(['academy', 'donor', 'transaction', 'reviewer'])
            ->where('academy_id', $academy->id)
            ->whereNull('deleted_at')
            ->latest();

        if (! $canManage) {
            $query->whereIn('status', ['approved', 'completed']);
        }
```
และถัดลงไปในเมธอดเดิมมี:
```php
        $resource = $isAdmin
            ? AcademyAdminDonationResource::class
            : AcademyPublicDonationResource::class;
```
**เปลี่ยนเป็น:**
```php
        $resource = $canManage
            ? AcademyAdminDonationResource::class
            : AcademyPublicDonationResource::class;
```
(ในเมธอด `donations()` ต้องไม่เหลือคำว่า `$isAdmin` อีก)

### 3.2 `approveDonation()` — isAdmin → finance.manage (คง tenant check)
**before:**
```php
        abort_if($donation->academy_id !== $academy->id, 403);
        abort_if($academy->isAdmin($request->user()) === false, 403);
```
**after:**
```php
        abort_if($donation->academy_id !== $academy->id, 403);
        abort_unless($academy->userCan($request->user(), 'finance.manage'), 403);
```

### 3.3 `rejectDonation()` — เหมือน 3.2 เป๊ะ
**before:**
```php
        abort_if($donation->academy_id !== $academy->id, 403);
        abort_if($academy->isAdmin($request->user()) === false, 403);
```
**after:**
```php
        abort_if($donation->academy_id !== $academy->id, 403);
        abort_unless($academy->userCan($request->user(), 'finance.manage'), 403);
```

### 3.4 `campaignsIndex()` — isAdmin → finance.view (อ่าน)
**before:**
```php
        abort_if($academy->isAdmin($request->user()) === false, 403);
```
**after:**
```php
        abort_unless($academy->userCan($request->user(), 'finance.view'), 403);
```

### 3.5 `campaignsStore()` — isAdmin → finance.manage
**before:**
```php
        abort_if($academy->isAdmin($request->user()) === false, 403);
```
**after:**
```php
        abort_unless($academy->userCan($request->user(), 'finance.manage'), 403);
```

### 3.6 `campaignsUpdate()` — isAdmin → finance.manage (คง tenant check)
**before:**
```php
        abort_if($campaign->academy_id !== $academy->id, 403);
        abort_if($academy->isAdmin($request->user()) === false, 403);
```
**after:**
```php
        abort_if($campaign->academy_id !== $academy->id, 403);
        abort_unless($academy->userCan($request->user(), 'finance.manage'), 403);
```

> หมายเหตุ: ทั้งไฟล์ หลังแก้แล้ว **ต้องไม่เหลือ `isAdmin(` ใน AcademyRevenueController.php อีกเลย** (ตรวจด้วย grep ใน §5)

## 4. เทสต์ที่ต้องสร้าง: AcademyRevenueAuthzTest.php

สร้าง `tests/Feature/Academy/AcademyRevenueAuthzTest.php` (namespace `Tests\Feature\Academy`, `use RefreshDatabase`) ครอบคลุม:

1. **finance.manage (ไม่ใช่ admin) อนุมัติบริจาคได้** — สร้าง Academy, สร้าง User เป็นสมาชิก (AcademyMember status=2) ผูก AcademyRole ที่มี permission `finance.manage`,
   สร้าง AcademyDonate (academy_id ตรง, status pending), ยิง `POST /api/academies/{id}/revenue/donations/{donation}/approve` → คาดหวัง **ไม่ใช่ 403** (200 หรือ 422 ตาม service — ขอแค่ผ่านด่านสิทธิ์ ไม่ใช่ 403)
2. **finance.view (ไม่ใช่ admin) ดูแคมเปญได้** — สมาชิก role finance.view ยิง `GET /api/academies/{id}/revenue/campaigns` → **200**
3. **finance.view ห้ามสร้างแคมเปญ** — สมาชิก role finance.view ยิง `POST /api/academies/{id}/revenue/campaigns` → **403**
4. **tenant isolation** — สมาชิก admin ของโรงเรียน A อนุมัติ donation ของโรงเรียน B → **403**

> หา pattern การสร้าง AcademyMember + AcademyRole ที่มี permission เฉพาะ ได้จากเทสต์ที่มีอยู่:
> `tests/Feature/StudentRegistryTest.php` หรือ `tests/Feature/Academy/*` (ดูวิธี seed role+permission + actingAs(...,'api'))
> ถ้า factory ของ AcademyDonate ไม่มี ให้ `AcademyDonate::create([...])` ด้วยฟิลด์ที่คอลัมน์จริงต้องการ (ดู migration/$fillable)

## 5. เกณฑ์ผ่าน (รันเองทุกบรรทัด — ห้าม paste ตัวเลขจากรายงาน agy)

```bash
cd C:\wamp64\www\nuxnan\api\nuxnanravel
php -l app/Http/Controllers/Api/Academies/AcademyRevenueController.php
# ต้องไม่เหลือ isAdmin( ใน controller นี้ (คาดหวัง: ไม่มีผลลัพธ์)
grep -n "isAdmin(" app/Http/Controllers/Api/Academies/AcademyRevenueController.php
./vendor/bin/pint --test app/Http/Controllers/Api/Academies/AcademyRevenueController.php tests/Feature/Academy/AcademyRevenueAuthzTest.php
php artisan test -c phpunit.mysql.xml --filter=AcademyRevenueAuthzTest
```
- `git diff --stat` ต้องมีแค่ 2 ไฟล์ (controller + test ใหม่) · deletion ใน controller ควรน้อย (แก้บรรทัด abort/ตัวแปร เท่านั้น)
- ถ้ามีไฟล์นอก §2 ถูกแก้ = ไม่ผ่าน

## 6. ข้อห้ามประจำ
ห้าม `git commit` · ห้าม `git push` · ห้าม `php artisan migrate` · ห้าม `npm run build` · ห้ามแตะ `.env`
ห้ามลบ tenant check (`academy_id !== $academy->id`) · ห้ามแตะ routes/FE/models/service/resource
