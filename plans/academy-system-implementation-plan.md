# แผนการพัฒนาโมดูลโรงเรียน (สถาปัตยกรรมแบบกลุ่มรวม - Universal Group)

จากแนวคิดที่คุณเสนอว่า **"โรงเรียน (Academy) ก็คือกลุ่มหนึ่งกลุ่ม"** แผนงานนี้จึงถูกปรับปรุงเพื่อใช้สถาปัตยกรรมที่รวมฟังก์ชันของ Academy และ AcademyGroup เข้าด้วยกัน เพื่อให้มีความยืดหยุ่นและใช้งานคุณสมบัติพื้นฐานร่วมกันได้

## แนวคิดทางสถาปัตยกรรม (Architecture Concept)

เราจะมองว่าทั้ง **Academy** และ **AcademyGroup** คือ "ชุมชน" (Community) ที่มีพฤติกรรมคล้ายกัน:

1. **Academy (Root Group)**: กลุ่มระดับสูงสุดที่แทนโรงเรียนทั้งหมด
2. **AcademyGroup (Sub-Group)**: กลุ่มย่อยภายในโรงเรียน (เช่น ห้องเรียน, ภาควิชา, ชมรม)

## การเปลี่ยนแปลงที่นำเสนอ

### 1. สถาปัตยกรรมกลุ่มแบบลำดับชั้น (Hierarchical Group Structure)

ทำให้ Academy สามารถมีกลุ่มย่อยได้ โดยใช้ตรรกะเดียวกันในการจัดการ

- **โครงสร้าง**: Academy -> AcademyGroup (เปรียบเหมือน Parent -> Children)
- **ฟังก์ชันที่ใช้ร่วมกัน**: ทั้งสองระดับจะมีระบบสมาชิก (Members), ระบบบทบาท (Roles), และฟีดโซเชียล (Feed) ของตัวเอง

### 2. ระบบฟีดและโพสต์รวม (Unified Feed & Posting)

ในเมื่อทุกอย่างคือกลุ่ม การโพสต์ประกาศหรือข่าวสารจะใช้ระบบเดียวกัน

- **Academy Post**: โพสต์ในระดับโรงเรียน (ทุกคนในโรงเรียนเห็น)
- **Group Post**: โพสต์ในระดับกลุ่มย่อย (เห็นเฉพาะสมาชิกในกลุ่มนั้น เช่น เฉพาะนักเรียนในห้องเรียน)
- **ความสามารถ**: รองรับการระบุ `target_type` (Academy หรือ AcademyGroup) เพื่อให้ระบบโพสต์ขยายขอบเขตได้ในอนาคต

### 3. ระบบสมาชิกและบทบาทที่ยืดหยุ่น (Universal Member & Role System)

ใช้หลักการเดียวกันในการจัดการสมาชิก ไม่ว่าจะอยู่ในระดับโรงเรียนหรือกลุ่มย่อย

- **ระดับโรงเรียน**: กำหนดบทบาทเป็น ผู้อำนวยการ, ครูใหญ่, นักเรียน
- **ระดับกลุ่มย่อย (ห้องเรียน)**: กำหนดบทบาทเป็น ครูประจำชั้น, หัวหน้าห้อง, สมาชิกกลุ่ม
- **การเชื่อมโยง**: สมาชิกในกลุ่มย่อยจะต้องเป็นสมาชิกของโรงเรียนแม่ (Parent Academy) โดยอัตโนมัติหรือโดยเงื่อนไข

### 4. การจัดการคอร์สเรียนและหลักสูตร (Course & Curriculum Integration)

คอร์สเรียนสามารถผูกติดได้ทั้งกับระดับโรงเรียน หรือระบุเฉพาะเจาะจงลงไปในหลักสูตร (Curriculum)

- **ระบบหลักสูตร**: เพิ่มโครงสร้างสำหรับการจัดการหลักสูตร (เช่น หลักสูตรประถมศึกษา, หลักสูตรวิชาชีพ)
- **คอร์สระบุกลุ่ม**: อนุญาตให้เข้าถึงคอร์สเรียนได้เฉพาะสมาชิกในกลุ่มย่อยหรือผู้ที่ลงทะเบียนในหลักสูตรที่กำหนด

### 5. ระบบกิจกรรมและการเช็คชื่อ (Activity & Attendance System)

- **Activity Model**: สร้างระบบประกาศและจัดการกิจกรรมของโรงเรียน
- **Attendance Logic**: ระบบเช็คชื่อผ่าน QR Code หรือการยืนยันตัวตนโดยครูผู้สอน

### 6. การเตรียมการสำหรับระบบบริการ (Services Preparation)

- **Flexible Modules**: ออกแบบให้แต่ละโรงเรียนสามารถ เลือกเปิด/ปิด ฟีเจอร์ได้ (เช่น เปิดระบบหอพักเฉพาะโรงเรียนประจำ)
- **Integrated Wallet**: ใช้ระบบ Wallet เดิมรองรับการซื้อสินค้าในสหกรณ์หรือร้านค้า

### 7. การตั้งค่าและความเป็นส่วนตัว (Shared Settings Logic)

ใช้ตรรกะการตั้งค่าที่คล้ายกัน เช่น:

- การอนุมัติสมาชิก (Manual/Auto Approval)
- ใครสามารถโพสต์ได้ (Admins Only หรือ สมาชิกทุกคน)

## แผนการดำเนินการทางเทคนิค (Technical Implementation)

1. **Database**:
   - อัปเดต migration ของ [academy_groups](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2025_10_26_070433_create_academy_groups_table.php) ให้รองรับ `academy_id`, `name`, `description`, `type` (Department, Classroom, Club) และ `settings`
   - สร้าง migration สำหรับ `curriculums` (id, academy_id, name, description)
   - สร้าง migration สำหรับ `academy_activities` และ `activity_attendance`
2. **Models**: สร้างความสัมพันธ์ (Relationships) ใน [AcademyGroup.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Models/AcademyGroup.php) ให้เชื่อมกับ Academy และ Members รวมถึงสร้าง Model ใหม่สำหรับ `Curriculum` และ `AcademyActivity`
3. **Controllers**:
   - ปรับปรุง [AcademyGroupController.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php) ให้มีฟังก์ชันพื้นฐานเหมือน AcademyController
   - สร้าง `AcademyCurriculumController.php` และ `AcademyActivityController.php`

---

> [!TIP]
> การมองโรงเรียนเป็นกลุ่ม "Universal Group" จะช่วยให้เราสามารถเพิ่มฟีเจอร์ใหม่ๆ (เช่น ระบบคะแนนกลุ่ม, การแข่งขันระหว่างห้อง) ได้ง่ายขึ้นในอนาคต เพราะทุกระดับใช้ตรรกะพื้นฐานเดียวกัน
