# ทดสอบ Login - ขั้นตอนถัดไป

## ⚠️ ได้ 401 Unauthorized

ฉันได้เพิ่ม logging แล้ว กรุณาทำตามขั้นตอนนี้:

### 1. ลบ log เก่า

```bash
cd c:\wamp64\www\nuxni\api\nuxniravel
Remove-Item storage\logs\laravel.log
```

### 2. ทดสอบ login อีกครั้ง

```bash
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "login": "0938403000",
  "password": "password123"
}
```

### 3. ดู log

```bash
Get-Content storage\logs\laravel.log
```

### 4. แชร์ log ที่ได้

กรุณาแชร์ log ทั้งหมดที่ได้รับมา

---

## หรือทดสอบด้วย curl

```bash
curl -X POST http://localhost:8000/api/login `
  -H "Content-Type: application/json" `
  -d '{"login":"0938403000","password":"password123"}'
```

---

## สิ่งที่ต้องการทราบ

1. Request ถึง AuthController หรือไม่
2. ถ้าถึง มีข้อมูลอะไรบ้าง
3. ถ้าไม่ถึง ถูกหยุดที่ไหน (middleware? validation?)

กรุณาทดสอบและแชร์ log ที่ได้!
