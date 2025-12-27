# การทดสอบ Login - อัปเดต

## ✅ Test Endpoint พร้อมใช้งาน

```bash
POST http://localhost/api/test-login-now
Content-Type: application/json

{
  "login": "0938403000",
  "password": "รหัสผ่านของคุณ"
}
```

## ขั้นตอนการทดสอบ

### 1. Reset รหัสผ่านก่อน (แนะนำ)

```bash
cd c:\wamp64\www\nuxni\api\nuxniravel
php artisan tinker reset_password_quick.php
```

จะตั้งรหัสผ่านเป็น `password123`

### 2. ทดสอบด้วย Test Endpoint

```bash
POST http://localhost/api/test-login-now
{
  "login": "0938403000",
  "password": "password123"
}
```

### 3. ดูผลลัพธ์

**ถ้าสำเร็จ:**

```json
{
    "input": "0938403000",
    "user_found": true,
    "user": {
        "id": 1,
        "name": "Utai Salem",
        "email": "utaisalem@gmail.com",
        "phone": "0938403000",
        "has_password": true
    },
    "password_matches": true,
    "token_created": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**ถ้ารหัสผ่านผิด:**

```json
{
  ...
  "password_matches": false
}
```

### 4. ทดสอบ Login จริง

ถ้า test endpoint ทำงาน ให้ทดสอบ login endpoint จริง:

```bash
POST http://localhost/api/login
{
  "login": "0938403000",
  "password": "password123"
}
```

## ทดสอบทุกรูปแบบ

```bash
# Email
{ "login": "utaisalem@gmail.com", "password": "password123" }

# Phone
{ "login": "0938403000", "password": "password123" }

# Personal Code
{ "login": "11111111", "password": "password123" }

# Username
{ "login": "Utai Salem", "password": "password123" }
```
