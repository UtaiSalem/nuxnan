# ทดสอบ URL ที่ถูกต้อง

## ขั้นตอนที่ 1: ทดสอบว่า Laravel ทำงาน

ทดสอบ ping endpoint:

```bash
GET http://localhost:8000/api/ping
```

**ถ้าได้:**

```json
{
    "status": "ok",
    "message": "Laravel is working!",
    "timestamp": "2025-12-11 17:03:00"
}
```

→ แสดงว่า Laravel ทำงาน ให้ไปขั้นตอนที่ 2

**ถ้าไม่ได้ (404 หรือ connection error):**

-   ลอง `http://localhost/api/ping`
-   ลอง `http://localhost/nuxni/api/nuxniravel/public/api/ping`
-   แจ้ง URL ที่ทำงาน

---

## ขั้นตอนที่ 2: ทดสอบ Login

ใช้ URL เดียวกับที่ ping ทำงาน:

```bash
POST [URL_ที่_ping_ทำงาน]/login
Content-Type: application/json

{
  "login": "0938403000",
  "password": "zfz0gLUV"
}
```

**ตัวอย่าง:**

-   ถ้า ping ทำงานที่ `http://localhost:8000/api/ping`
-   ให้ login ที่ `http://localhost:8000/api/login`

---

## กรุณาทดสอบ ping ก่อน แล้วแจ้ง:

1. URL ที่ ping ทำงาน
2. Response ที่ได้จาก ping
