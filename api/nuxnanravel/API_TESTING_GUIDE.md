# 📚 คู่มือการทดสอบ API - School Management System

## 🚀 เริ่มต้นอย่างรวดเร็ว

### 1. ข้อกำหนดเบื้องต้น
```bash
# ตรวจสอบว่า server ทำงาน
cd c:\wamp64\www\nuxnan\api\nuxnanravel
php artisan serve --port=8000

# หรือใช้ WAMP: http://localhost/nuxnan/api/nuxnanravel/public
```

### 2. รับ Token สำหรับทดสอบ
```bash
# Login เพื่อรับ token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "your@email.com", "password": "yourpassword"}'
```

---

## 📋 รายการ API ที่ต้องทดสอบ

### Base URL
```
http://localhost:8000/api/academies/{academy_id}
```

แทนที่ `{academy_id}` ด้วย ID ของ Academy ที่มีในระบบ

---

## 🏫 Phase 1: Academic System

### Departments (แผนก)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/departments` | รายการแผนก |
| POST | `/departments` | สร้างแผนกใหม่ |
| GET | `/departments/{id}` | ดูแผนก |
| PATCH | `/departments/{id}` | แก้ไขแผนก |
| DELETE | `/departments/{id}` | ลบแผนก |

**ตัวอย่างทดสอบ:**
```bash
# รายการแผนก
curl -X GET "http://localhost:8000/api/academies/1/departments" \
  -H "Authorization: Bearer {token}"

# สร้างแผนก
curl -X POST "http://localhost:8000/api/academies/1/departments" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "แผนกวิทยาศาสตร์",
    "code": "SCI",
    "description": "แผนกวิทยาศาสตร์และเทคโนโลยี"
  }'
```

### Classrooms (ห้องเรียน)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/classrooms` | รายการห้องเรียน |
| POST | `/classrooms` | สร้างห้องเรียน |
| GET | `/classrooms/{id}` | ดูห้องเรียน |
| GET | `/classrooms/{id}/availability` | ตรวจสอบห้องว่าง |

**ตัวอย่างทดสอบ:**
```bash
# รายการห้องเรียน
curl -X GET "http://localhost:8000/api/academies/1/classrooms" \
  -H "Authorization: Bearer {token}"

# สร้างห้องเรียน
curl -X POST "http://localhost:8000/api/academies/1/classrooms" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "ห้อง 101",
    "building": "อาคาร A",
    "floor": 1,
    "capacity": 40,
    "room_type": "classroom"
  }'
```

### Class Schedules (ตารางเรียน)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/schedules` | รายการตารางเรียน |
| POST | `/schedules` | สร้างตารางเรียน |
| GET | `/schedules/timetable` | ตารางเรียนแบบปฏิทิน |
| GET | `/schedules/teacher/{id}` | ตารางสอนครู |
| GET | `/schedules/conflicts` | ตรวจสอบตารางซ้ำซ้อน |

---

## 💰 Phase 2: Finance System

### Fee Structures (โครงสร้างค่าธรรมเนียม)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/fees/structures` | รายการโครงสร้างค่าธรรมเนียม |
| POST | `/fees/structures` | สร้างโครงสร้างใหม่ |
| GET | `/fees/structures/{id}/items` | รายการค่าธรรมเนียมในโครงสร้าง |

**ตัวอย่างทดสอบ:**
```bash
# รายการโครงสร้างค่าธรรมเนียม
curl -X GET "http://localhost:8000/api/academies/1/fees/structures" \
  -H "Authorization: Bearer {token}"

# สร้างโครงสร้างค่าธรรมเนียม
curl -X POST "http://localhost:8000/api/academies/1/fees/structures" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "ค่าเทอม ป.1",
    "academic_year": "2568",
    "semester": 1,
    "grade_level": "ป.1",
    "total_amount": 15000
  }'
```

### Payments (การชำระเงิน)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/payments` | รายการการชำระเงิน |
| POST | `/payments` | บันทึกการชำระเงิน |
| GET | `/payments/{id}` | ดูรายละเอียด |
| GET | `/payments/{id}/receipt` | ดาวน์โหลดใบเสร็จ |
| GET | `/payments/summary` | สรุปยอดรับ |

### Expenses (ค่าใช้จ่าย)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/expenses` | รายการค่าใช้จ่าย |
| POST | `/expenses` | เพิ่มค่าใช้จ่าย |
| POST | `/expenses/{id}/approve` | อนุมัติ |
| POST | `/expenses/{id}/reject` | ปฏิเสธ |

### Budgets (งบประมาณ)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/budgets` | รายการงบประมาณ |
| POST | `/budgets` | สร้างงบประมาณ |
| GET | `/budgets/{id}/utilization` | รายงานการใช้จ่าย |

---

## 👨‍💼 Phase 3: Staff System

### Staff (บุคลากร)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/staff` | รายชื่อบุคลากร |
| POST | `/staff` | เพิ่มบุคลากร |
| GET | `/staff/{id}` | ดูข้อมูล |
| GET | `/staff/directory` | สมุดรายชื่อ |

**ตัวอย่างทดสอบ:**
```bash
# รายชื่อบุคลากร
curl -X GET "http://localhost:8000/api/academies/1/staff" \
  -H "Authorization: Bearer {token}"

# เพิ่มบุคลากร
curl -X POST "http://localhost:8000/api/academies/1/staff" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "employee_id": "EMP001",
    "department_id": 1,
    "position": "ครู",
    "employment_type": "full_time",
    "hire_date": "2026-01-01"
  }'
```

### Staff Attendance (การเข้างาน)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/staff-attendance` | บันทึกเข้างาน |
| POST | `/staff-attendance/check-in` | ลงเวลาเข้า |
| POST | `/staff-attendance/check-out` | ลงเวลาออก |
| GET | `/staff-attendance/report` | รายงาน |

### Leave Requests (การลา)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/leave-requests` | รายการใบลา |
| POST | `/leave-requests` | ยื่นใบลา |
| POST | `/leave-requests/{id}/approve` | อนุมัติ |
| POST | `/leave-requests/{id}/reject` | ปฏิเสธ |

### Payroll (เงินเดือน)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/payroll` | รายการเงินเดือน |
| POST | `/payroll/generate` | สร้างเงินเดือน |
| GET | `/payroll/{id}/payslip` | ดูสลิป |
| POST | `/payroll/{id}/pay` | จ่ายเงินเดือน |

---

## 📢 Phase 4: Communication System

### Announcements (ประกาศ)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/announcements` | รายการประกาศ |
| POST | `/announcements` | สร้างประกาศ |
| POST | `/announcements/{id}/publish` | เผยแพร่ |
| GET | `/announcements/{id}/stats` | สถิติการอ่าน |

**ตัวอย่างทดสอบ:**
```bash
# รายการประกาศ
curl -X GET "http://localhost:8000/api/academies/1/announcements" \
  -H "Authorization: Bearer {token}"

# สร้างประกาศ
curl -X POST "http://localhost:8000/api/academies/1/announcements" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "ประกาศหยุดเรียน",
    "content": "โรงเรียนหยุดวันที่ 10 ก.พ.",
    "priority": "high",
    "target_audience": "all"
  }'
```

### Events (กิจกรรม)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/events` | รายการกิจกรรม |
| POST | `/events` | สร้างกิจกรรม |
| GET | `/events/calendar` | ปฏิทินกิจกรรม |
| POST | `/events/{id}/register` | ลงทะเบียน |

### Emergency Alerts (แจ้งเตือนฉุกเฉิน)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/emergency-alerts` | รายการแจ้งเตือน |
| POST | `/emergency-alerts` | สร้างแจ้งเตือน |
| POST | `/emergency-alerts/{id}/acknowledge` | รับทราบ |

### Messages (ข้อความ)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/messages/threads` | รายการสนทนา |
| POST | `/messages/threads` | สร้างสนทนาใหม่ |
| GET | `/messages/threads/{id}/messages` | ข้อความในสนทนา |
| POST | `/messages/threads/{id}/messages` | ส่งข้อความ |

### Meetings (การประชุม)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/meetings/slots` | ช่วงเวลาประชุม |
| POST | `/meetings/slots` | สร้างช่วงเวลา |
| POST | `/meetings/slots/{id}/book` | จองประชุม |
| GET | `/meetings/my-bookings` | การจองของฉัน |

---

## 📊 Phase 5: Reports & Analytics

### Report Definitions (นิยามรายงาน)
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/reports/definitions` | รายการนิยามรายงาน |
| POST | `/reports/definitions` | สร้างนิยาม |
| POST | `/reports/generate` | สร้างรายงาน |
| GET | `/reports/saved` | รายงานที่บันทึก |

**ตัวอย่างทดสอบ:**
```bash
# รายการนิยามรายงาน
curl -X GET "http://localhost:8000/api/academies/1/reports/definitions" \
  -H "Authorization: Bearer {token}"
```

### Dashboard Widgets
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/dashboard/widgets` | รายการ widgets |
| GET | `/dashboard/layout` | layout ของผู้ใช้ |
| POST | `/dashboard/layout` | บันทึก layout |

### Analytics
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/analytics/overview` | ภาพรวม |
| GET | `/analytics/kpis` | รายการ KPIs |
| POST | `/analytics/kpis` | สร้าง KPI |
| GET | `/analytics/trends` | การวิเคราะห์แนวโน้ม |
| GET | `/analytics/comparisons` | เปรียบเทียบช่วงเวลา |

### Alert Rules
| Method | Endpoint | คำอธิบาย |
|--------|----------|---------|
| GET | `/analytics/alert-rules` | กฎเตือน |
| POST | `/analytics/alert-rules` | สร้างกฎ |
| GET | `/analytics/alerts/history` | ประวัติการเตือน |
| POST | `/analytics/alerts/{id}/acknowledge` | รับทราบ |

---

## 🧪 วิธีทดสอบด้วย PHPUnit

```bash
# รัน test ทั้งหมด
cd c:\wamp64\www\nuxnan\api\nuxnanravel
php artisan test tests/Api/SchoolManagementApiTest.php

# รันเฉพาะ test
php artisan test --filter=test_can_list_departments

# รันพร้อม verbose
php artisan test tests/Api/SchoolManagementApiTest.php -v
```

---

## 🔧 วิธีทดสอบด้วย Postman

### 1. Import Collection
1. เปิด Postman
2. File > Import
3. วาง URL: `http://localhost:8000/api/academies/1/departments`

### 2. ตั้งค่า Environment Variables
```json
{
  "base_url": "http://localhost:8000/api",
  "academy_id": "1",
  "token": "your-auth-token"
}
```

### 3. ตั้งค่า Headers
```
Authorization: Bearer {{token}}
Content-Type: application/json
Accept: application/json
```

---

## 📝 Checklist การทดสอบ

### Phase 1: Academic ✅
- [ ] GET /departments - ดูรายการแผนก
- [ ] POST /departments - สร้างแผนก
- [ ] GET /classrooms - ดูรายการห้องเรียน
- [ ] POST /classrooms - สร้างห้องเรียน
- [ ] GET /curricula - ดูหลักสูตร
- [ ] GET /schedules - ดูตารางเรียน
- [ ] POST /schedules - สร้างตารางเรียน

### Phase 2: Finance ✅
- [ ] GET /fees/structures - ดูโครงสร้างค่าธรรมเนียม
- [ ] POST /fees/structures - สร้างโครงสร้าง
- [ ] GET /fees/tuitions - ดูค่าเทอม
- [ ] GET /payments - ดูการชำระเงิน
- [ ] POST /payments - บันทึกการชำระ
- [ ] GET /expenses - ดูค่าใช้จ่าย
- [ ] GET /budgets - ดูงบประมาณ

### Phase 3: Staff ✅
- [ ] GET /staff - ดูรายชื่อบุคลากร
- [ ] POST /staff - เพิ่มบุคลากร
- [ ] GET /staff-attendance - ดูบันทึกเข้างาน
- [ ] POST /staff-attendance/check-in - ลงเวลาเข้า
- [ ] GET /leave-requests - ดูใบลา
- [ ] POST /leave-requests - ยื่นใบลา
- [ ] GET /payroll - ดูเงินเดือน

### Phase 4: Communication ✅
- [ ] GET /announcements - ดูประกาศ
- [ ] POST /announcements - สร้างประกาศ
- [ ] GET /events - ดูกิจกรรม
- [ ] GET /emergency-alerts - ดูแจ้งเตือน
- [ ] GET /messages/threads - ดูสนทนา
- [ ] GET /meetings/slots - ดูช่วงเวลาประชุม

### Phase 5: Reports ✅
- [ ] GET /reports/definitions - ดูนิยามรายงาน
- [ ] GET /dashboard/widgets - ดู widgets
- [ ] GET /analytics/overview - ดูภาพรวม
- [ ] GET /analytics/kpis - ดู KPIs

---

## ❓ การแก้ปัญหาที่พบบ่อย

### 401 Unauthorized
```
ตรวจสอบ token ว่าถูกต้องและยังไม่หมดอายุ
```

### 404 Not Found
```
ตรวจสอบ:
1. academy_id ว่ามีอยู่จริง
2. URL endpoint ถูกต้อง
```

### 422 Validation Error
```
ตรวจสอบ request body ว่าส่งข้อมูลครบถ้วน
```

### 500 Server Error
```bash
# ดู log
tail -f storage/logs/laravel.log
```

---

## 📞 ติดต่อ

หากพบปัญหาในการทดสอบ สามารถดู routes ทั้งหมดได้:
```bash
php artisan route:list --path=academies
```
