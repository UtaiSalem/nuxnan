# แผนปรับปรุงและแก้ไข: ระบบจัดการบัตรนักเรียน (Student Cards Management System)

แผนการทำงานฉบับสมบูรณ์นี้ได้รับการปรับปรุงเพื่อมุ่งเน้นการปฏิบัติตามหลักความปลอดภัยเป็นอันดับแรก (Backend First Security) การแบ่งแยกสิทธิ์อ่าน/จัดการข้อมูลที่ชัดเจน และการเพิ่มระบบการจัดการสถานะข้อมูล (Loading, Empty, Error) ในหน้า [index.vue](file:///C:/wamp64/www/nuxnan/ui/pages/academies/%5Bname%5D/admin/student-cards/index.vue)

---

## 1. การแบ่งแยกสิทธิ์และเส้นทางข้อมูล (Permission & Endpoint Matrix)

เพื่อไม่ให้เกิดสิทธิ์รั่วไหลหรือพึ่งพาเฉพาะ Client-side Validation จะทำการจัดกลุ่มสิทธิ์ที่ Backend เป็นหลัก ดังนี้:

### 1.1 ตารางสิทธิ์การใช้งาน (Authorization Matrix)

| Endpoint / ความสามารถ | สิทธิ์ที่ใช้ (Backend) | คำอธิบาย |
|---|:---:|---|
| **GET** `/statistics` | `students.view` | ดูสถิติหน้าแรก |
| **GET** `/levels` | `students.view` | ดึงข้อมูลระดับชั้นที่ปรากฏบนแท็บ |
| **GET** `/sections` | `students.view` | ดึงห้องเรียนทั้งหมดของระดับชั้น |
| **GET** `/search` | `students.view` | ค้นหารายชื่อนักเรียนสำหรับผู้ใช้ระดับ "ดูข้อมูล" |
| **GET** `/{level}/{room}` | `students.view` | ดึงข้อมูลนักเรียนแยกตามระดับ/ห้อง |
| **GET** `/profile/{student_card}` | `students.view` | ดูรายละเอียดบัตรนักเรียน |
| **GET** `/by-student/{student}` | `students.view` | ดูบัตรนักเรียนผ่านโมเดล Student |
| **GET** `/admin/students` | `students.manage` | ดึงข้อมูลนักเรียนสำหรับแอดมิน/ผู้จัดการระบบ |
| **POST/PUT/DELETE** ทั้งหมด | `students.manage` | นำเข้า, แก้ไข, อัปโหลด, ลบรูป, Sync และ Bulk update |

### 1.2 การป้องกันการเข้าถึงข้ามสถาบัน (Cross-Academy / Tenancy Protection)
* ทุก Endpoint ที่อยู่ในไฟล์ [academy-student-card.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy-student-card.php) จะต้องทำงานภายใต้ Middleware `academy.permission` เสมอ เพื่อยืนยันว่าผู้ใช้ปัจจุบันเป็นสมาชิกของ Academy นั้น และเป็นผู้ที่มีสิทธิ์ `students.view` หรือ `students.manage` ตามที่กำหนด

---

## 2. ขั้นตอนการทำงานโดยละเอียด (Step-by-Step Execution Plan)

### Phase 0: สังเกตอาการและตรวจสอบ (Diagnostics)
ทำการตรวจสอบผ่าน browser Network/Console เพื่อเก็บสถานะการทำงานเดิม:
1. `GET /api/academies/{name}` -> ตรวจความถูกต้องของ Slug ภาษาไทยและการแปลงค่าโรงเรียน
2. `GET /api/academies/{academyId}/my-role` -> ตรวจสอบ Role ของผู้ใช้ปัจจุบัน
3. `GET /api/academies/{academyId}/student-cards/statistics`
4. `GET /api/academies/{academyId}/student-cards/search`
5. `GET /api/academies/{academyId}/student-cards/admin/students`
6. `GET /api/academies/{academyId}/student-cards/{level}/{room}`

**บันทึกผลลัพธ์:** ตรวจสอบหากมีรหัสสถานะ `403` เกิดขึ้นแบบเงียบๆ หรือมีโครงสร้างข้อมูล (Response Shape) ที่ไม่ตรงกัน

---

### Phase 1: ปรับแต่ง Backend Authorization Contract
แก้ไขไฟล์เส้นทางระบบฝั่ง Laravel:

1. **ปรับปรุง [academy-student-card.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy-student-card.php):**
   * แยก Route สำหรับผู้ใช้ทั่วไป (`students.view`) และกลุ่ม Admin/Manager (`students.manage`)
   ```php
   // ตัวอย่างการจัดกลุ่ม Route ใหม่
   Route::middleware(['auth:api'])->prefix('/academies/{academy}/student-cards')->group(function () {
       
       // กลุ่มสิทธิ์อ่าน (Read-only endpoints)
       Route::middleware('academy.permission:students.view')->group(function () {
           Route::get('/statistics', [StudentCardController::class, 'statistics']);
           Route::get('/search', [StudentCardController::class, 'search']);
           Route::get('/levels', [StudentCardController::class, 'getLevels']);
           Route::get('/sections', [StudentCardController::class, 'getSections']);
           Route::get('/profile/{student_card}', [StudentCardController::class, 'profile']);
           Route::get('/by-student/{student}', [StudentCardController::class, 'byStudent']);
           Route::get('/{level}/{room}', [StudentCardController::class, 'getStudentByRoom']);
       });

       // กลุ่มสิทธิ์เขียน/จัดการ (Write/Manage endpoints)
       Route::middleware('academy.permission:students.manage')->group(function () {
           Route::put('/{student_card}', [StudentCardController::class, 'update']);
           Route::delete('/{student_card}/photo', [StudentCardController::class, 'destroyPhoto']);
           
           Route::prefix('admin')->name('admin.')->group(function () {
               Route::get('/students', [StudentCardController::class, 'adminStudents']);
               Route::post('/', [StudentCardController::class, 'store']);
               Route::post('/import', [StudentCardController::class, 'import']);
               Route::get('/export', [StudentCardController::class, 'export']);
               Route::post('/upload-photo/{student_card}', [StudentCardController::class, 'updateImage']);
               Route::post('/bulk-update', [StudentCardController::class, 'bulkUpdate']);
               Route::post('/bulk-upload-photos', [StudentCardController::class, 'bulkUploadPhotos']);
               Route::get('/audit', [StudentCardController::class, 'audit']);
               Route::get('/sync/preview', [StudentCardController::class, 'syncPreview']);
               Route::post('/sync/commit', [StudentCardController::class, 'syncCommit']);
           });
       });
   });
   ```

2. **ปรับปรุงความถูกต้องของโครงสร้างฐานข้อมูลและการดึงข้อมูล:**
   * ตรวจสอบว่าใน `StudentCardController.php` มีการทำ eager loading เพื่อดึงข้อมูล `student` และ `classroomEnrollments` เพื่อช่วยป้องกันการดึงข้อมูลข้ามโรงเรียนและปัญหา N+1 Query

---

### Phase 2: ปรับปรุง Frontend index.vue ให้แยกแยะสถานะการโหลดและสิทธิ์
แก้ไขไฟล์ [index.vue](file:///C:/wamp64/www/nuxnan/ui/pages/academies/%5Bname%5D/admin/student-cards/index.vue):

1. **เพิ่มและแยกประเภท Ref State:**
   ```typescript
   const pageError = ref<string | null>(null)
   const statsError = ref<string | null>(null)
   const listError = ref<string | null>(null)
   
   const isLoadingStats = ref(false)
   const isLoadingList = ref(false)
   const hasLoaded = ref(false)
   ```

2. **ปรับเปลี่ยนขั้นตอนการโหลดหน้าเว็บตอน Mount:**
   * เมื่อเมาท์หน้าเว็บ:
     1. โหลดข้อมูลสถาบันการศึกษา
     2. ตรวจสอบ Role ของผู้ใช้ปัจจุบันเพื่อคำนวณ `can('students.view')` และ `can('students.manage')`
     3. หากผู้ใช้ไม่มีสิทธิ์ `students.view` ให้โยนไปหน้า 403 หรือ redirect กลับ
     4. หากมีสิทธิ์ ให้เรียกโหลดสถิติ (`fetchStatistics`) และตั้งค่า `hasLoaded.value = true`
     5. ในกรณีมี `catch` ให้ทำการเก็บข้อมูลข้อผิดพลาดเข้าสู่ `pageError` หรือ `statsError` เพื่อนำไปแสดงใน UI ไม่ปล่อยให้พังเงียบ

---

### Phase 3: การดึงรายชื่อและจัดการสิทธิ์ (List Mode & Endpoint Fallback)
1. **การตรวจสอบ Endpoint ใน `fetchStudents()`:**
   * ตรรกะการสลับ URL ตามความสามารถ:
     ```typescript
     const endpoint = can('students.manage')
       ? `/api/academies/${academyId.value}/student-cards/admin/students`
       : `/api/academies/${academyId.value}/student-cards/search`
     ```
2. **การจัดการตารางข้อมูลรายชื่อ:**
   * ตั้งค่า `isLoadingList.value = true` และล้างค่าใน `listError` ตอนเริ่มทำงาน
   * ดักจับข้อผิดพลาดใน `catch` หากยิง API แล้วเจอรหัส `403` หรือข้อผิดพลาดอื่นๆ ให้เก็บข้อความไว้แสดงผลที่ `listError`
   * การป้องกันการยิง API ถี่เกินไป (Race Condition): พิจารณาใช้ Debounce หรือจำกัดคำขอซ้ำก่อนดึงข้อมูลเสร็จสิ้น

---

### Phase 4: เพิ่มหน้าจอ Empty State ให้สมบูรณ์ (4 กรณี)
ปรับปรุง template ของหน้ารายการหลักโดยมีกล่องเงื่อนไขแสดงสถานะอย่างชัดเจน:
1. **กรณีหน้ากำลังโหลด:** แสดง Spinner ขนาดใหญ่ในหน้าหลัก
2. **กรณีโหลดข้อมูลหลักล้มเหลว (Error State):** แสดงกล่องข้อความและมีปุ่ม **"ลองใหม่อีกครั้ง"**
3. **กรณีโหลดผ่านสำเร็จแต่ไม่มีข้อมูลบัตรเลย (`stats.totalStudents === 0`):**
   * แสดงข้อความ: "โรงเรียนยังไม่มีข้อมูลบัตรนักเรียนในระบบ"
   * หากมีสิทธิ์ `students.manage` แสดงปุ่ม "นำเข้าข้อมูล" หรือ "ซิงก์ข้อมูล"
   * หากไม่มีสิทธิ์ แสดงคำแนะนำให้แจ้งแอดมินโรงเรียนดำเนินการ
4. **กรณีมีนักเรียนแต่ `byLevel` เป็นค่าว่าง:**
   * แสดงข้อความ: "พบข้อมูลนักเรียน แต่ยังไม่มีข้อมูลระดับชั้น/ห้องสำหรับการจัดกลุ่มแท็บระดับชั้น"

---

### Phase 5: ซ่อนและจำกัดความสามารถปุ่มกระทำ (Button Constraints)
ทำการควบคุมปุ่มด้วย `v-if` หรือ `disabled` เพิ่มเติม:
* **พิมพ์บัตร:** (ตามสิทธิ์ทางธุรกิจที่ตกลง หากอนุญาตให้ครูพิมพ์ได้ ให้ใช้สิทธิ์ `students.view` หรือหากต้องการป้องกันให้ใช้ `students.manage`)
* **นำเข้าข้อมูล / ส่งออก / อัปโหลด / แก้ไข / Sync / Audit:** แสดงและอนุญาตเฉพาะคนที่มีสิทธิ์ `students.manage` เท่านั้น

---

## 3. แผนการทดสอบเพื่อความถูกต้องปลอดภัย (Verification & Backend Test)

### 3.1 การรันการทดสอบ Unit และ Feature ของ Laravel
เนื่องจากเราจะนำสิทธิ์ `students.view` มารัดกุมใน Endpoint การดึงสถิติและดึงข้อมูลตามห้อง เราจำเป็นต้องปรับการจำลองข้อมูลการทดสอบ (Database Seeding) ใน Unit & Feature Tests ของ Laravel จากเดิมที่เขียนสถานะเป็นสตริง `'status' => 'active'` ให้เปลี่ยนเป็นตัวเลข `'status' => 2` ตามโครงสร้างฟิลด์ `tinyInteger` ของตารางจริงเพื่อให้การคัดกรองใน `CheckAcademyPermission` ผ่านการประมวลผลอย่างถูกต้อง

**ไฟล์ทดสอบที่ต้องแก้ไข:**
1. [StudentCardSSOTTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/StudentCardSSOTTest.php#L48-L50)
2. [StudentCardByStudentTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/StudentCardByStudentTest.php#L43)

**ตัวอย่างจุดที่แก้ไขในการ Seeding:**
```php
// เปลี่ยนจาก:
AcademyMember::create([
    'user_id' => $admin->id,
    'academy_id' => $academy->id,
    'role' => 'admin',
    'status' => 'active', // <--- ตัวนี้จะถูกมองเป็น 0 ในระบบฐานข้อมูลเนื่องจากชนิดเป็น tinyInteger
]);

// แก้เป็น:
AcademyMember::create([
    'user_id' => $admin->id,
    'academy_id' => $academy->id,
    'role' => 'admin',
    'status' => 2, // 2 = approved member ตามที่ middleware และตรรกะระบบเช็ค
]);
```

### 3.2 การจำลองการทดสอบ 5 กรณีหลัก (Smoke Test Scenarios)
1. **ครูทั่วไปมีเพียง `students.view`:** ต้องเรียกข้อมูลแท็บ ค้นหารายชื่อ ดูรายละเอียดการ์ด และเปิดแท็บพิมพ์ได้ (หากเลือกไว้) แต่ต้องถูกปฏิเสธเมื่อกดบันทึกหรือทำ API เปลี่ยนแปลงข้อมูล
2. **แอดมินหรือผู้จัดการมีสิทธิ์ `students.manage`:** ต้องมองเห็นและสามารถเข้าถึง API การจัดการข้อมูลได้ครบทั้งหมด
3. **คนนอกโรงเรียนหรือล็อกอินทั่วไป:** ระบบต้องปฏิเสธตั้งแต่ชั้น Route (รหัส 403 / 404)
4. **โรงเรียนที่ยังไม่มีข้อมูลบัตร:** หน้าจอต้องเปลี่ยนเป็น Empty State ชัดเจน ไม่ค้างว่างเปล่า
5. **กรณี API ขัดข้องหรือขัดขืนสิทธิ์:** หน้าจอต้องแจ้งเตือนข้อผิดพลาดพร้อมปุ่มทดลองส่งคำขออีกครั้ง (Retry)
