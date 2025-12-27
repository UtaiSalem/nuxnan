# ทดสอบ Login - ขั้นตอนสุดท้าย

## 1. ทดสอบ Login

```bash
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "login": "0938403000",
  "password": "zfz0gLUV"
}
```

## 2. หลังจากทดสอบแล้ว ดู Log

```powershell
cd c:\wamp64\www\nuxni\api\nuxniravel
Get-Content storage\logs\laravel.log -Tail 20
```

## 3. แชร์ Log ที่ได้

กรุณาแชร์ log ทั้งหมดที่เห็น เพื่อให้ฉันช่วยวิเคราะห์ปัญหา

---

## สิ่งที่ต้องการทราบจาก Log

-   มี "LOGIN METHOD CALLED" หรือไม่?
-   มี "User not found" หรือไม่?
-   มี "Password mismatch" หรือไม่?
-   มี "Login successful" หรือไม่?
-   มี error อื่นๆ หรือไม่?
