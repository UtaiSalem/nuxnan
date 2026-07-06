# แผน Photo Path Migration — ฉบับสมบูรณ์

**วันที่:** 2026-07-07
**สถานะ:** วางแผน (ยังไม่เริ่มพัฒนา)
**ปัญหา:** Rollover เปลี่ยน class_level/class_section แต่รูปยังอยู่ path เดิม → URL ใหม่ชี้ผิดที่
**เป้าหมาย:** เปลี่ยนเป็น canonical path ตาม student identity ที่ไม่เปลี่ยนเมื่อเลื่อนชั้น

---

## User Analysis Input

### แผนเดิมจากผู้ใช้ (สรุป)
1. เปลี่ยน path เป็น `images/students/profiles/{academy_id}/{student_db_id}.{ext}`
2. ให้ `students.profile_image_path` เป็น source of truth เดียว
3. Backend ส่ง URL สำเร็จรูป (`profile_image_url`)
4. สร้าง `StudentPhotoService` จัดการรูปส่วนกลาง
5. สร้าง Artisan command `students:migrate-profile-images` สำหรับ copy รูปเก่า
6. แก้ระบบอัพโหลด/ลบให้ใช้ path ใหม่

---

## การวิเคราะห์เทียบกับ Codebase จริง

### สิ่งที่ผู้ใช้วิเคราะห์ถูกต้อง

1. **ปัญหา root cause ถูกต้อง** — path ผูกกับชั้น/ห้อง rollover เปลี่ยนชั้นแต่ไม่ย้ายไฟล์ ทุก consumer ที่ประกอบ path จะพัง
2. **ทิศทาง canonical path ถูกต้อง** — ใช้ student ID แทนชั้น/ห้อง ไม่ต้องย้ายไฟล์ทุกปี
3. **ให้ backend ส่ง URL สำเร็จรูปถูกต้อง** — frontend ไม่ควรประกอบ path เอง
4. **ใช้ copy ไม่ใช่ move ในรอบแรกถูกต้อง** — rollback ได้

### สิ่งที่ต้องเพิ่มเติมจากการสำรวจ codebase

5. **ขนาดปัญหาใหญ่กว่าที่คาด — พบ 35+ ไฟล์ที่ประกอบ path:**
   - **Backend:** 4 จุดใน StudentCardController (upload/delete), 2 จุดใน ClassroomController, 1 จุดใน AcademyMember model
   - **Frontend:** 20+ Vue files ที่ใช้ template literal `` `/storage/images/students/${level}/${section}/${filename}` ``
   - **ครอบคลุม:** student card, student profile, home visit, admin, print, edit, modal, my-card

6. **AcademyMember.php มี path อีกรูปแบบอยู่แล้ว:**
   - Line 166: `/storage/images/students/profiles/{profile_image}` (ไม่มี level/section)
   - เป็น pattern ที่ใกล้เคียง canonical path แต่ยังไม่สมบูรณ์ (ไม่มี academy_id subfolder)

7. **Database เก็บแค่ filename ไม่ใช่ full path:**
   - `students.profile_image` → filename only (e.g., "12345.jpg")
   - `student_cards.profile_image` → filename only, varchar(28) (ถูกขยายเป็น 255 แล้วจาก fix ก่อนหน้า)
   - ทั้ง 2 fields ไม่มี path prefix — path ถูกประกอบใน code ทุกครั้ง

8. **students table ยังไม่มี `profile_image_path` field** — มีแค่ `profile_image` (filename only)
   - ต้องเพิ่ม field ใหม่หรือเปลี่ยน semantic ของ field เดิม

9. **Rollover batch เก็บ before-state ครบถ้วน:**
   - `plan_summary['before'][student_id]['class_level']` → ชั้นเก่า
   - `plan_summary['before'][student_id]['class_section']` → ห้องเก่า
   - ใช้สร้าง path เก่าเพื่อค้นรูปได้

10. **มี 3 วิธีหา path เก่า (เรียงตามความง่าย):**
    - **A: RolloverBatch plan_summary** — เก็บ class_level/class_section เก่าตรงๆ
    - **B: ClassroomStudent ที่ status='promoted'** — ยังมี classroom_id เก่าอยู่
    - **C: StudentAcademicInfo ที่ is_current=false** — มี current_grade/current_class เก่า

11. **Student model มี `profileImage()` relation ไป StudentDocument** — เป็น alternative system สำหรับเก็บรูป แต่ใช้กับ student card ไม่ได้โดยตรง

12. **Admin pages (3 ไฟล์) ใช้ `asset()` หรือ `config.public.apiBase`** — path construction pattern ต่างกัน 3 แบบ:
    - แบบ A: `` `/storage/images/students/${level}/${section}/${filename}` `` (relative)
    - แบบ B: `` `${apiBase}/storage/images/students/...` `` (absolute with apiBase)
    - แบบ C: `` `../../storage/images/students/...` `` (relative with parent dirs — HomeVisit)

### สิ่งที่ต้องปรับจากแผนเดิม

13. **path ใหม่ไม่ควรใช้ academy_id subfolder** — ระบบปัจจุบันมี academy เดียว (id=1) และ student.id ก็ unique ทั้ง system แล้ว subfolder เพิ่มความซับซ้อนโดยไม่จำเป็น
    - **เสนอ:** `images/students/profiles/{student_id}.{ext}` แทน `images/students/profiles/{academy_id}/{student_id}.{ext}`

14. **ไม่จำเป็นต้องเพิ่ม column `profile_image_path` ใหม่** — เปลี่ยน semantic ของ `students.profile_image` จาก "filename only" เป็น "relative path" ได้เลย เพราะโค้ดที่ประกอบ path จะถูกแทนที่ด้วย accessor/service ทั้งหมด
    - **แต่ระวัง:** ต้อง migrate ค่าเดิม (e.g., "12345.jpg" → "images/students/profiles/1394.jpg") ในขั้นตอนเดียวกับ copy ไฟล์

15. **`student_cards.profile_image` ควร sync จาก `students.profile_image`** ไม่ควรเก็บค่าอิสระ — StudentCardSyncService ต้อง copy ค่ามาจาก students เสมอ

---

## Work Plan — ฉบับปรับปรุงสมบูรณ์

### ระยะที่ 1: สร้าง StudentPhotoService

**เป้าหมาย:** รวมศูนย์ logic จัดการรูปนักเรียนไว้ที่เดียว

**ไฟล์:** `api/nuxnanravel/app/Services/StudentPhotoService.php`

**Methods:**

```
canonicalPath(Student $student): string
    → "images/students/profiles/{$student->id}.{ext}"
    → extension ดึงจาก profile_image เดิม หรือ default 'jpg'

url(Student $student): ?string
    → ถ้ามีรูป: asset("storage/" . $student->profile_image_path)
    → ถ้าไม่มี: null

store(Student $student, UploadedFile $photo): string
    → save ไปที่ canonical path
    → อัพเดท students.profile_image = canonical path
    → return canonical path

delete(Student $student): bool
    → ลบไฟล์จาก storage
    → clear students.profile_image
    → return success

exists(Student $student): bool
    → ตรวจว่าไฟล์อยู่ที่ canonical path จริงไหม

resolveOldPath(Student $student, ?string $oldLevel, ?string $oldSection): ?string
    → สร้าง legacy path จากข้อมูลเก่า
    → ใช้สำหรับ migration

findPhotoInLegacyPaths(Student $student): ?string
    → ค้นรูปจาก:
      1. canonical path (ถ้ามีแล้ว)
      2. path จาก student_cards (class_level/class_section ปัจจุบัน)
      3. path จาก rollover before-state (ชั้น/ห้องเก่า)
      4. path จาก promoted classroom_students (classroom เก่า)
      5. recursive search by filename
    → return path ที่พบ หรือ null
```

**Design decisions:**
- ใช้ `Storage::disk('public')` เหมือนเดิม (symlink `/storage/` → `storage/app/public/`)
- ไม่สร้าง subdirectory ตาม academy — student.id unique ทั้งระบบ
- Extension คงจากไฟล์เดิม ไม่แปลง format
- filename = `{student.id}.{ext}` (ไม่ใช่ student_id/student_number เพราะอาจเปลี่ยนได้)

---

### ระยะที่ 2: เพิ่ม accessor ใน Student model

**เป้าหมาย:** ให้ Student model เป็น single source of truth สำหรับ photo URL

**ไฟล์:** `api/nuxnanravel/app/Models/Student.php`

**เพิ่ม:**

```php
// Accessor ส่ง URL สำเร็จรูป
protected $appends = [..., 'profile_image_url'];

public function getProfileImageUrlAttribute(): ?string
{
    if (!$this->profile_image) return null;

    // ถ้าเป็น relative path แล้ว (canonical format)
    if (str_starts_with($this->profile_image, 'images/')) {
        return asset('storage/' . $this->profile_image);
    }

    // Legacy fallback: filename only → ประกอบ path จากชั้น/ห้อง
    // (ใช้ชั่วคราวระหว่าง migration)
    if ($this->class_level && $this->class_section) {
        $level = (int) str_replace('ม.', '', $this->class_level);
        $section = (int) $this->class_section;
        return asset("storage/images/students/{$level}/{$section}/{$this->profile_image}");
    }

    return null;
}
```

**ข้อดี:**
- Frontend ทุกจุดที่เรียก student data จะได้ `profile_image_url` พร้อมใช้
- รองรับทั้ง format เก่า (filename only) และ format ใหม่ (relative path)
- ไม่ต้องแก้ทุกจุดพร้อมกัน — ทยอยเปลี่ยน frontend ไปใช้ accessor ได้

---

### ระยะที่ 3: เพิ่ม `profile_image_url` ใน StudentCard API response

**เป้าหมาย:** ให้ API ส่ง URL สำเร็จรูปมากับ student card data

**ไฟล์:** `api/nuxnanravel/app/Models/StudentCard.php`

**เพิ่ม:**

```php
protected $appends = [..., 'profile_image_url'];

public function getProfileImageUrlAttribute(): ?string
{
    if (!$this->profile_image) return null;

    // ถ้ามี student relation → ใช้ student URL (source of truth)
    if ($this->student_id && $rel = $this->student) {
        return $rel->profile_image_url;
    }

    // Fallback: ประกอบ path จาก card data เอง (legacy)
    if ($this->class_level && $this->class_section) {
        return asset("storage/images/students/{$this->class_level}/{$this->class_section}/{$this->profile_image}");
    }

    return null;
}
```

**ข้อสำคัญ:**
- ⚠️ ระวัง N+1 — ถ้า eager load `student` ไม่ครบ จะ query ทีละใบ
- Controller ที่ return collection ของ cards ต้อง `->load('student')` ก่อน
- ทางเลือก: ให้ StudentCardSyncService copy `profile_image` แบบ full path จาก student มาเลย ไม่ต้อง join

---

### ระยะที่ 4: สร้าง Migration Command

**เป้าหมาย:** Copy รูปจาก legacy path ไป canonical path + อัพเดท DB

**ไฟล์:** `api/nuxnanravel/app/Console/Commands/MigrateStudentPhotos.php`
**Signature:** `students:migrate-photos {--academy=1} {--dry-run} {--commit} {--report=}`

**Logic ของ --dry-run (preview):**

```
สำหรับทุก student ที่มี profile_image (1,531 คน):

1. ตรวจว่ารูปอยู่ที่ canonical path แล้วหรือยัง
   → ถ้าอยู่แล้ว → count as "already_migrated"

2. ถ้ายังไม่มี → ค้นรูปจาก legacy paths:
   a. path จาก student_cards ปัจจุบัน:
      images/students/{card.class_level}/{card.class_section}/{filename}
   
   b. path จาก before-state ของ rollover batch 3c9ca6f7...:
      → ดึง plan_summary['before'][student.id]['class_level'] / ['class_section']
      → images/students/{old_level}/{old_section}/{filename}
   
   c. path จาก promoted classroom_students:
      → หา classroom_students.status = 'promoted', ดึง classroom.grade_level/section
      → images/students/{old_grade_level_num}/{old_section}/{filename}
   
   d. recursive search by filename:
      → Storage::disk('public')->allFiles('images/students/')
      → filter by basename match

3. จำแนกผลลัพธ์:
   - found_single: พบ 1 ไฟล์ → พร้อม copy
   - found_multiple: พบหลายไฟล์ → ต้อง manual review (compare checksum)
   - not_found: ไม่พบไฟล์ → แจ้งเตือน
   - already_migrated: อยู่ canonical path แล้ว → skip

4. แสดงสรุป + export report ถ้า --report= ระบุ
```

**Logic ของ --commit:**

```
สำหรับทุก student ที่ found_single:

1. Copy ไฟล์ไป canonical path (ไม่ move):
   Storage::disk('public')->copy($oldPath, $canonicalPath)

2. ตรวจ checksum ไฟล์ต้นทาง vs ปลายทาง

3. อัพเดท DB:
   students.profile_image = "images/students/profiles/{student.id}.{ext}"

4. Idempotent: ถ้า canonical path มีอยู่แล้ว + checksum ตรง → skip

5. ไม่ลบไฟล์ต้นทาง (เก็บไว้จน reconciliation เสร็จ)

6. Log ทุก action สำหรับ audit trail
```

**Report format (CSV):**

```csv
student_id,student_number,name,status,old_path,new_path,file_exists,action,notes
1394,30001,สมชาย ใจดี,found_single,images/students/2/3/12345.jpg,images/students/profiles/1394.jpg,true,copied,
1500,30102,สมหญิง ดีใจ,found_multiple,images/students/2/5/30102.jpg|images/students/3/5/30102.jpg,,true,needs_review,2 files found
1600,30200,ใหม่ ม.1,not_found,,,false,skipped,new student no photo
```

---

### ระยะที่ 5: แก้ Backend ให้ใช้ StudentPhotoService

**เป้าหมาย:** เปลี่ยนทุก controller/model ที่ประกอบ path ให้ใช้ service

**ไฟล์ที่ต้องแก้ (Backend — 4 ไฟล์):**

| # | ไฟล์ | จุดที่แก้ | เปลี่ยนจาก | เปลี่ยนเป็น |
|---|------|----------|-----------|------------|
| 1 | `StudentCardController.php:428-437` | Upload photo | `Storage::putFileAs('images/students/'.$level.'/'.$section, ...)` | `$photoService->store($student, $photo)` |
| 2 | `StudentCardController.php:428-429` | Delete old on upload | `Storage::delete('images/students/'.$level.'/'.$section.'/'.$filename)` | `$photoService->delete($student)` |
| 3 | `StudentCardController.php:688-689` | destroyPhoto | `Storage::delete('images/students/...')` | `$photoService->delete($student)` |
| 4 | `ClassroomController.php:83,102` | Construct URL | `asset("storage/images/students/{$level}/{$section}/{$image}")` | `$student->profile_image_url` (accessor) |

**เพิ่มเติม:**
| 5 | `AcademyMember.php:166` | Avatar path | `'/storage/images/students/profiles/'.$this->student->profile_image` | `$this->student->profile_image_url` |
| 6 | `StudentCardSyncService.php` | Sync profile_image | copies filename from student | copies full path from student |

**ลำดับ:**
- แก้ upload/delete ก่อน (ของใหม่จะเข้า canonical path ทันที)
- แก้ URL construction ทีหลัง (รอ migration เสร็จ accessor จะ fallback ให้)

---

### ระยะที่ 6: แก้ Frontend ให้ใช้ `profile_image_url` จาก API

**เป้าหมาย:** ลบ path construction ออกจาก Vue ทั้งหมด — ใช้ URL ที่ backend ส่งมา

**ไฟล์ที่ต้องแก้ (Frontend — 20 ไฟล์):**

**กลุ่ม A: Student Card components (5 ไฟล์)**
| # | ไฟล์ | จุด | เปลี่ยนจาก | เปลี่ยนเป็น |
|---|------|-----|-----------|------------|
| 1 | `components/student-card/StudentCardItem.vue:52` | photoUrl computed | `` `/storage/images/students/${level}/${section}/${filename}` `` | `props.studentInfo.profile_image_url` |
| 2 | `components/learn/student-card/StudentCardFront.vue:99` | photoUrl computed | `` `/storage/images/students/${level}/${section}/${filename}` `` | `props.student.profile_image_url` |
| 3 | `pages/Learn/Student/Card/StudentCard.vue:72` | photoUrl computed | `` `/storage/images/students/${level}/${section}/${filename}` `` | `props.studentInfo.profile_image_url` |
| 4 | `pages/Learn/Student/Card/Admin/StudentCard.vue:75` | photoUrl computed | `` `/storage/images/students/${level}/${section}/${filename}` `` | `props.studentInfo.profile_image_url` |
| 5 | `pages/Learn/Student/Card/Admin/StudentCardByRoom.vue:342` | img src | inline template literal | `:src="student.profile_image_url"` |

**กลุ่ม B: Student profile/admin (5 ไฟล์)**
| 6 | `components/learn/student/ProfileViewCards.vue:48-56` | photoUrl | level/section path | `props.student.profile_image_url` |
| 7 | `components/learn/student/profile-cards/ProfileHeader.vue:49-56` | photoUrl | level/section path | `props.student.profile_image_url` |
| 8 | `components/academy/member/StudentCardModal.vue:91-96` | photoUrl | level/section path | `student.value.profile_image_url` |
| 9 | `pages/Admin/students/[id].vue:70` | img src | apiBase + /storage/ + filename | `student.profile_image_url` |
| 10 | `pages/Admin/students/index.vue:145` | img src | apiBase + /storage/ + filename | `student.profile_image_url` |

**กลุ่ม C: Academy admin student cards (3 ไฟล์)**
| 11 | `pages/academies/[name]/admin/student-cards/index.vue:202` | photoUrl | level/section path | `student.profile_image_url` |
| 12 | `pages/academies/[name]/admin/student-cards/print.vue:119` | photoUrl | level/section path | `student.profile_image_url` |
| 13 | `pages/academies/[name]/admin/student-cards/[id]/edit.vue:136` | photoUrl | level/section path | `student.profile_image_url` |

**กลุ่ม D: Home visit (3 ไฟล์)**
| 14 | `pages/Learn/Student/HomeVisit/Teacher/ManageStudent.vue:333` | photoUrl | `../../storage/images/students/...` | `student.profile_image_url` |
| 15 | `pages/Learn/Student/HomeVisit/Teacher/Components/StudentCard.vue:291` | photoUrl | `../../storage/images/students/...` | `student.profile_image_url` |
| 16 | `pages/Learn/Student/HomeVisit/Components/StudentsCard.vue:93-104` | photoUrl (multiple) | level/section path | `student.profile_image_url \|\| card.profile_image_url` |

**กลุ่ม E: อื่นๆ (4 ไฟล์)**
| 17 | `pages/student-card/admin/students/[level]/[room].vue:200` | img src | `${apiBase}/storage/images/students/...` | `student.profile_image_url` |
| 18 | `pages/student/profile.vue:134` | img src | apiBase + /storage/ + filename | `student.profile_image_url` |
| 19 | `pages/academies/[name]/my-card.vue:63` | reference | raw filename | `profile_image_url` |
| 20 | `components/student/profile/StudentCardTab.vue:92` | reference | raw filename | `profile_image_url` |

**Pattern การแก้ไขจะเหมือนกันทุกไฟล์:**
```vue
<!-- เดิม -->
<img :src="`/storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`" />

<!-- ใหม่ -->
<img :src="student.profile_image_url" />
```

**Fallback สำหรับ missing image:**
```vue
<img :src="student.profile_image_url || '/images/default-avatar.png'" />
```

---

### ระยะที่ 7: อัพเดท StudentCardSyncService

**เป้าหมาย:** Sync ต้อง copy full path จาก student ไม่ใช่แค่ filename

**ไฟล์:** `api/nuxnanravel/app/Services/StudentCardSyncService.php`

**แก้ในส่วน create:**
```php
// เดิม
'profile_image' => $enrollment->profile_image,

// ใหม่
'profile_image' => $enrollment->profile_image,  // full relative path
'profile_image_url' => ... // ไม่ต้องเก็บใน DB — ใช้ accessor
```

**แก้ในส่วน update:**
- เช็คว่า students.profile_image เปลี่ยน → อัพเดท student_cards.profile_image ด้วย

---

### ระยะที่ 8: Cleanup ไฟล์เก่า (หลังยืนยัน)

**เป้าหมาย:** ลบ legacy files หลังยืนยันว่า canonical path ทำงานครบ

**สร้าง command:** `students:cleanup-legacy-photos {--academy=1} {--dry-run} {--commit}`

**Logic:**
1. สำหรับทุก student ที่ profile_image เป็น canonical path แล้ว:
   - ตรวจว่า canonical file มีจริง + checksum ตรง
   - หา legacy file(s) ที่ basename ตรง
   - ถ้า legacy file ยังอยู่ → mark สำหรับลบ
2. `--dry-run`: แสดงรายการ files จะลบ + size ที่จะ reclaim
3. `--commit`: ลบไฟล์เก่า + ลบ empty directories

**⚠️ ห้ามรันจนกว่า:**
- Reconciliation ผ่าน 100% (canonical file ครบทุกคน)
- Frontend ทุกจุดใช้ `profile_image_url` จาก API แล้ว
- ทดสอบบน production แล้ว 1 สัปดาห์ขึ้นไป

---

## ลำดับ Deployment

```
 1. ☐ Deploy StudentPhotoService (ระยะ 1) — ไม่กระทบ existing code
 2. ☐ Deploy accessor ใน Student + StudentCard models (ระยะ 2-3) — backward compatible
 3. ☐ แก้ Backend upload/delete ให้ใช้ canonical path (ระยะ 5 ส่วน upload) — รูปใหม่เข้า path ใหม่ทันที
 4. ☐ รัน migration command --dry-run (ระยะ 4) → ตรวจ report
 5. ☐ ให้นายทะเบียนตรวจ report: found_multiple + not_found
 6. ☐ รัน migration command --commit → copy รูปไป canonical path
 7. ☐ ตรวจ reconciliation: ทุก student ที่มีรูป ต้องมีไฟล์ที่ canonical path
 8. ☐ Deploy Frontend changes ทั้งหมด (ระยะ 6) — เปลี่ยนเป็น profile_image_url
 9. ☐ Deploy Backend URL construction changes (ระยะ 5 ส่วน URL)
10. ☐ Smoke test ทุกหน้าที่แสดงรูปนักเรียน (ดูรายการใน ระยะ 6)
11. ☐ Deploy sync update (ระยะ 7)
12. ☐ รอ 1 สัปดาห์ → ไม่มี bug report
13. ☐ Cleanup legacy files (ระยะ 8) — optional, reclaim disk space
```

---

## สรุปไฟล์ทั้งหมด

### ไฟล์ใหม่ (4 ไฟล์)
| # | ไฟล์ | ระยะ |
|---|------|------|
| 1 | `app/Services/StudentPhotoService.php` | 1 |
| 2 | `app/Console/Commands/MigrateStudentPhotos.php` | 4 |
| 3 | `app/Console/Commands/CleanupLegacyStudentPhotos.php` | 8 |
| 4 | (report CSV — generated by command) | 4 |

### ไฟล์แก้ไข (26 ไฟล์)
| กลุ่ม | จำนวน | ไฟล์ |
|-------|--------|------|
| Backend Models | 2 | Student.php, StudentCard.php |
| Backend Controllers | 2 | StudentCardController.php, ClassroomController.php |
| Backend Models (other) | 1 | AcademyMember.php |
| Backend Services | 1 | StudentCardSyncService.php |
| Frontend Components | 5 | StudentCardItem, StudentCardFront, ProfileViewCards, ProfileHeader, StudentCardModal |
| Frontend Pages (card) | 5 | StudentCard.vue ×2, StudentCardByRoom, admin students, my-card |
| Frontend Pages (admin) | 3 | student-cards index, print, edit |
| Frontend Pages (homevisit) | 3 | ManageStudent, StudentCard(HV), StudentsCard |
| Frontend Pages (other) | 3 | Admin students/[id], Admin students/index, student/profile |
| Frontend Pages (misc) | 1 | StudentCardTab |

---

## เกณฑ์ตรวจรับ (ตามที่ผู้ใช้กำหนด + เพิ่มเติม)

```
✓ รูปทุกไฟล์มี canonical path (images/students/profiles/{id}.{ext})
✓ เลื่อนชั้นแล้ว URL รูปไม่เปลี่ยน — ทดสอบด้วย rollover preview
✓ ไม่มี frontend ประกอบ images/students/{level}/{section} — grep ต้องได้ 0
✓ active card ที่มีรูปต้องเปิดไฟล์ได้จริง 100%
✓ รายการไฟล์หาย/ชื่อซ้ำมีรายงาน CSV ตรวจสอบ
✓ รัน migration ซ้ำได้โดยไม่สร้างไฟล์ซ้ำ (idempotent)
✓ ไฟล์เก่ายังอยู่จนผ่าน reconciliation + 1 สัปดาห์
✓ อัพโหลดรูปใหม่เข้า canonical path ทันที (ไม่ผ่าน legacy path)
✓ ลบรูปใช้ StudentPhotoService → ลบที่ canonical path
✓ API response มี profile_image_url ทุก endpoint ที่ส่ง student/card data
```

---

## หัวใจของแผน

> **"เปลี่ยน path เป็น student-identity based, ให้ backend เป็นเจ้าของ URL, frontend ใช้ URL สำเร็จรูป, migrate ด้วย copy + reconciliation, ไม่ลบของเก่าจนกว่าจะมั่นใจ"**
