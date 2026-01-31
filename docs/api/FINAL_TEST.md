# ทดสอบ Login ครั้งสุดท้าย

## ขั้นตอนการทดสอบ

### 1. ลบ log เก่า

```powershell
Remove-Item storage\logs\laravel.log -Force
```

### 2. ทดสอบ login

```bash
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "login": "0938403000",
  "password": "zfz0gLUV"
}
```

### 3. ดู log ทันที

```powershell
Get-Content storage\logs\laravel.log
```

### 4. แชร์ log ทั้งหมด

---

## สิ่งที่ต้องการเห็นใน Log

1. `LoginRequest prepareForValidation` - แสดงว่า request ถึง validation
2. `LOGIN METHOD CALLED` - แสดงว่า request ถึง controller
3. `User not found` หรือ `Password mismatch` - แสดงปัญหา
4. `Login successful` - แสดงว่าสำเร็จ

**กรุณาทดสอบตามขั้นตอนและแชร์ log ทั้งหมด!**
