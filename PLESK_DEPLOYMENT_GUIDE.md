# 🚀 คู่มือการ Deploy บน Plesk Panel อย่างละเอียด

เอกสารนี้ให้คำแนะนำแบบละเอียดสำหรับการ Deploy โปรเจกต์ Nuxnan บน Server ที่ใช้ **Plesk Control Panel** จัดการ

---

## 📋 สารบัญ (Table of Contents)

1. [ภาพรวมโครงสร้าง (Architecture Overview)](#1-ภาพรวมโครงสร้าง-architecture-overview)
2. [การเตรียม Server (Server Preparation)](#2-การเตรียม-server-server-preparation)
3. [ติดตั้ง Extension ที่จำเป็น (Required Extensions)](#3-ติดตั้ง-extension-ที่จำเป็น-required-extensions)
4. [การตั้งค่า Database (Database Setup)](#4-การตั้งค่า-database-database-setup)
5. [Deploy Backend (Laravel API)](#5-deploy-backend-laravel-api)
6. [Deploy Frontend (Nuxt 3)](#6-deploy-frontend-nuxt-3)
7. [การตั้งค่า SSL Certificate](#7-การตั้งค่า-ssl-certificate)
8. [ระบบเยี่ยมบ้านนักเรียน (Home Visit System)](#8-ระบบเยี่ยมบ้านนักเรียน-home-visit-system)
9. [การตั้งค่า Cron Jobs](#9-การตั้งค่า-cron-jobs)
10. [การแก้ปัญหาและ Troubleshooting](#10-การแก้ปัญหาและ-troubleshooting)
11. [Checklist ก่อน Deploy](#11-checklist-ก่อน-deploy)

---

## 1. ภาพรวมโครงสร้าง (Architecture Overview)

```
Server (Plesk)
├── api.yourdomain.com (Subdomain)
│   └── Laravel API Backend
│       └── Document Root: api/nuxnanravel/public
│       └── PHP 8.3+
│
└── yourdomain.com (Main Domain)
    └── Nuxt 3 Frontend
        └── Document Root: httpdocs
        └── Node.js 18.x/20.x
```

**โครงสร้างโฟลเดอร์บน Server:**
```
/var/www/vhosts/yourdomain.com/
├── api.yourdomain.com/
│   └── api/nuxnanravel/
│       ├── app/
│       ├── config/
│       ├── database/
│       ├── public/          ← Document Root ของ API Subdomain
│       ├── storage/
│       ├── vendor/
│       ├── .env
│       └── composer.json
│
└── httpdocs/                ← Document Root ของ Main Domain
    ├── .output/
    │   ├── public/          ← Static Assets
    │   └── server/
    │       └── index.mjs    ← Node.js Entry Point
    ├── package.json
    └── .env
```

---

## 2. การเตรียม Server (Server Preparation)

### 2.1 ตรวจสอบข้อกำหนดของ Server

**Minimum Requirements:**
- **OS:** ที่รองรับ Plesk (Ubuntu 20.04/22.04, CentOS 7/8, AlmaLinux 8, Debian 10/11)
- **RAM:** ขั้นต่ำ 2GB (แนะนำ 4GB ขึ้นไป)
- **Disk Space:** ขั้นต่ำ 20GB (แนะนำ 50GB ขึ้นไป)
- **PHP Version:** 8.3 หรือสูงกว่า
- **MySQL/MariaDB:** 8.0+ หรือ 10.6+

### 2.2 เข้าสู่ Plesk Panel

1. เข้า Plesk Panel ผ่าน URL: `https://your-server-ip:8443`
2. Login ด้วย username และ password ของ admin
3. เลือก **Extensions** จากเมนูด้านซ้าย

---

## 3. ติดตั้ง Extension ที่จำเป็น (Required Extensions)

### 3.1 ติดตั้ง Node.js Extension

1. ไปที่ **Extensions** → **My Extensions**
2. ค้นหา **Node.js** หรือ **Node.js Support**
3. คลิก **Install** หรือ **Get it free**
4. รอให้ติดตั้งเสร็จสิ้น

### 3.2 ติดตั้ง PHP Composer Extension (ถ้ายังไม่มี)

1. ไปที่ **Extensions** → **My Extensions**
2. ค้นหา **Composer**
3. คลิก **Install**

### 3.3 ติดตั้ง Git Extension (Optional แต่แนะนำ)

1. ไปที่ **Extensions** → **My Extensions**
2. ค้นหา **Git**
3. คลิก **Install**

### 3.4 ตรวจสอบ PHP Extensions ที่จำเป็น

1. ไปที่ **Tools & Settings** → **PHP Settings**
2. เลือก PHP Version 8.3
3. ตรวจสอบว่า extensions ต่อไปนี้ถูกเปิดใช้งาน:
   - ✅ `bcmath`
   - ✅ `ctype`
   - ✅ `curl`
   - ✅ `dom`
   - ✅ `fileinfo`
   - ✅ `json`
   - ✅ `mbstring`
   - ✅ `openssl`
   - ✅ `pdo_mysql`
   - ✅ `tokenizer`
   - ✅ `xml`
   - ✅ `zip`

---

## 4. การตั้งค่า Database (Database Setup)

### 4.1 สร้าง Database สำหรับ Backend

1. ไปที่ **Databases**
2. คลิก **Add Database**
3. กรอกข้อมูล:
   - **Database name:** `nuxnan_db` (หรือชื่อที่คุณต้องการ)
   - **Related site:** เลือก `api.yourdomain.com`
   - **Database server:** MySQL (Default)
   - **Database user:** สร้าง user ใหม่ (เช่น `nuxnan_user`)
   - **Password:** ใส่รหัสผ่านที่ปลอดภัย
4. คลิก **OK**

### 4.2 บันทึกข้อมูล Database

จดบันทึกข้อมูลต่อไปนี้ไว้ใช้ในการตั้งค่า `.env`:
```
Database Name: nuxnan_db
Database User: nuxnan_user
Database Password: [your_secure_password]
Database Host: localhost
```

---

## 5. Deploy Backend (Laravel API)

### 5.1 สร้าง Subdomain สำหรับ API

1. ไปที่ **Websites & Domains**
2. คลิก **Add Subdomain**
3. กรอกข้อมูล:
   - **Subdomain name:** `api`
   - **Document root:** `/api/nuxnanravel/public`
4. คลิก **OK**

### 5.2 อัปโหลดไฟล์ Laravel

**วิธีที่ 1: ผ่าน File Manager**
1. ไปที่ **Websites & Domains** → คลิก **File Manager** ของ `api.yourdomain.com`
2. นำทางไปที่โฟลเดอร์ `httpdocs`
3. ลบไฟล์ทั้งหมดใน `httpdocs`
4. อัปโหลดไฟล์ Laravel ทั้งหมดจากเครื่อง local
   - แนะนำให้ zip ไฟล์ก่อน แล้วค่อย unzip บน server

**วิธีที่ 2: ผ่าน SSH (แนะนำ)**
```bash
# เข้า SSH ไปยัง server
ssh user@your-server-ip

# นำทางไปที่ subdomain
cd /var/www/vhosts/yourdomain.com/api.yourdomain.com/httpdocs

# ลบไฟล์เดิม
rm -rf *

# อัปโหลดไฟล์ผ่าน SCP จากเครื่อง local
# ในเครื่อง local รัน:
scp -r api/nuxnanravel/* user@your-server-ip:/var/www/vhosts/yourdomain.com/api.yourdomain.com/httpdocs/
```

### 5.3 ติดตั้ง PHP Dependencies

**ผ่าน Plesk UI:**
1. ไปที่ **Websites & Domains** → คลิก **PHP Composer** ของ `api.yourdomain.com`
2. คลิก **Install** หรือ **Update**
3. รอให้การติดตั้งเสร็จสิ้น

**ผ่าน SSH:**
```bash
cd /var/www/vhosts/yourdomain.com/api.yourdomain.com/httpdocs
composer install --optimize-autoloader --no-dev
```

### 5.4 ตั้งค่า Environment (.env)

1. ไปที่ **File Manager** ของ `api.yourdomain.com`
2. คัดลอกไฟล์ `.env.example` เป็น `.env`
3. แก้ไขไฟล์ `.env` ด้วยค่าที่ถูกต้อง:

```env
APP_NAME=Nuxnan
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nuxnan_db
DB_USERNAME=nuxnan_user
DB_PASSWORD=your_secure_password

# CORS Configuration
FRONTEND_URL=https://www.yourdomain.com

# JWT Configuration (ถ้าใช้ JWT)
JWT_SECRET=your_jwt_secret_key_here

# File Storage
FILESYSTEM_DISK=public
```

### 5.5 สร้าง Application Keys และ Setup

**ผ่าน SSH:**
```bash
cd /var/www/vhosts/yourdomain.com/api.yourdomain.com/httpdocs

# สร้าง App Key
php artisan key:generate

# สร้าง JWT Secret (ถ้าใช้)
php artisan jwt:secret

# สร้าง Storage Link
php artisan storage:link

# Run Migrations
php artisan migrate --force

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**ผ่าน Plesk Scheduled Tasks (ถ้าไม่มี SSH):**
1. ไปที่ **Scheduled Tasks**
2. คลิก **Add Task**
3. กรอกข้อมูล:
   - **Task type:** Run a command
   - **Command:** `php artisan key:generate`
   - **Run as:** ใส่ username ของ subscription
4. คลิก **OK** และ **Run Now**
5. ทำเช่นเดียวกันกับคำสั่งอื่นๆ

### 5.6 ตั้งค่า Permissions

**ผ่าน SSH:**
```bash
cd /var/www/vhosts/yourdomain.com/api.yourdomain.com/httpdocs

# ตั้งค่า permissions สำหรับ storage และ cache
chmod -R 775 storage bootstrap/cache

# ตั้งค่า owner ให้เป็น web server user
chown -R user:psacln storage bootstrap/cache
```

**ผ่าน Plesk File Manager:**
1. ไปที่ **File Manager**
2. คลิกขวาที่โฟลเดอร์ `storage` → **Change Permissions**
3. ตั้งค่าเป็น `775` หรือ `777` (ถ้าจำเป็น)
4. ทำเช่นเดียวกันกับโฟลเดอร์ `bootstrap/cache`

### 5.7 ตั้งค่า PHP Version

1. ไปที่ **Websites & Domains** → คลิก **PHP Settings** ของ `api.yourdomain.com`
2. ตรวจสอบว่า **PHP version** เป็น **8.3** หรือสูงกว่า
3. ตรวจสอบ extensions ทั้งหมดถูกเปิดใช้งาน
4. คลิก **Apply**

---

## 6. Deploy Frontend (Nuxt 3)

### 6.1 เตรียมไฟล์ Build ในเครื่อง Local

```bash
# ในเครื่อง local
cd ui

# ติดตั้ง dependencies
npm install

# สร้างไฟล์ .env
nano .env
```

**เนื้อหาใน .env:**
```env
# Backend API URL
NUXT_PUBLIC_API_BASE=https://api.yourdomain.com

# Frontend URL
NUXT_PUBLIC_SITE_URL=https://www.yourdomain.com
```

```bash
# Build สำหรับ Production
npm run build

# หลังจาก build เสร็จ จะได้โฟลเดอร์ .output
# ให้ zip โฟลเดอร์ .output
zip -r nuxnan-ui.zip .output
```

### 6.2 อัปโหลดไฟล์ Frontend

**ผ่าน File Manager:**
1. ไปที่ **Websites & Domains** → คลิก **File Manager** ของ `yourdomain.com`
2. นำทางไปที่ `httpdocs`
3. ลบไฟล์เดิมทั้งหมด (ถ้ามี)
4. อัปโหลดไฟล์ `nuxnan-ui.zip`
5. Extract ไฟล์ zip
6. ย้ายไฟล์ทั้งหมดจาก `.output` ไปที่ `httpdocs`

**ผ่าน SSH:**
```bash
# ในเครื่อง local
scp nuxnan-ui.zip user@your-server-ip:/var/www/vhosts/yourdomain.com/httpdocs/

# บน server
cd /var/www/vhosts/yourdomain.com/httpdocs
unzip nuxnan-ui.zip
mv .output/* .
rm -rf .output nuxnan-ui.zip
```

### 6.3 ตั้งค่า Node.js บน Plesk

1. ไปที่ **Websites & Domains** → คลิก **Node.js** ของ `yourdomain.com`
2. กรอกข้อมูล:
   - **Node.js version:** 18.x หรือ 20.x (LTS)
   - **Document root:** `/httpdocs`
   - **Application root:** `/httpdocs`
   - **Application startup file:** `server/index.mjs`
   - **Application mode:** `Production`
3. คลิก **Enable Node.js**
4. คลิก **NPM Install** (ถ้ามี package.json)
5. คลิก **Restart Application**

### 6.4 ตั้งค่า Environment Variables สำหรับ Nuxt

**ผ่าน Plesk Node.js Settings:**
1. ในหน้า Node.js settings
2. คลิกแท็บ **Environment Variables**
3. เพิ่ม variables:
   ```
   NUXT_PUBLIC_API_BASE=https://api.yourdomain.com
   NUXT_PUBLIC_SITE_URL=https://www.yourdomain.com
   NODE_ENV=production
   ```
4. คลิก **Apply**
5. คลิก **Restart Application**

**หรือสร้างไฟล์ .env ใน httpdocs:**
```bash
# ผ่าน SSH
cd /var/www/vhosts/yourdomain.com/httpdocs
nano .env
```

```env
NUXT_PUBLIC_API_BASE=https://api.yourdomain.com
NUXT_PUBLIC_SITE_URL=https://www.yourdomain.com
NODE_ENV=production
```

### 6.5 ตรวจสอบการทำงาน

1. เปิด browser และเข้า `https://www.yourdomain.com`
2. ตรวจสอบว่าหน้าเว็บแสดงผลได้ถูกต้อง
3. เปิด Developer Console (F12) → Network tab
4. ตรวจสอบว่า API calls ไปที่ `https://api.yourdomain.com` และได้รับ response ถูกต้อง

---

## 7. การตั้งค่า SSL Certificate

### 7.1 ติดตั้ง SSL สำหรับทั้งสอง Domain

**สำหรับ Main Domain (yourdomain.com):**
1. ไปที่ **Websites & Domains**
2. คลิก **Let's Encrypt** ของ `yourdomain.com`
3. กรอกอีเมลของคุณ
4. ติ๊ก **Secure www.yourdomain.com**
5. คลิก **Install**

**สำหรับ API Subdomain (api.yourdomain.com):**
1. ไปที่ **Websites & Domains**
2. คลิก **Let's Encrypt** ของ `api.yourdomain.com`
3. กรอกอีเมลของคุณ
4. คลิก **Install**

### 7.2 ตั้งค่า HTTP to HTTPS Redirect

**สำหรับ Main Domain:**
1. ไปที่ **Websites & Domains** → คลิก **Hosting Settings** ของ `yourdomain.com`
2. ติ๊ก **Permanent SEO-safe 301 redirect from HTTP to HTTPS**
3. คลิก **OK**

**สำหรับ API Subdomain:**
1. ไปที่ **Websites & Domains** → คลิก **Hosting Settings** ของ `api.yourdomain.com`
2. ติ๊ก **Permanent SEO-safe 301 redirect from HTTP to HTTPS**
3. คลิก **OK**

---

## 8. ระบบเยี่ยมบ้านนักเรียน (Home Visit System)

### 8.1 ภาพรวมระบบเยี่ยมบ้าน

ระบบเยี่ยมบ้านนักเรียนเป็นระบบที่ช่วยให้ครู นักเรียน และผู้บริหารจัดการการเยี่ยมบ้านอย่างมีประสิทธิภาพ ประกอบด้วย:

**ส่วนประกอบหลัก:**
- **Landing Page:** หน้าแรกสำหรับเลือกบทบาท (นักเรียน/ครู/ผู้บริหาร)
- **Student Dashboard:** ดูข้อมูลส่วนตัวและประวัติการเยี่ยมบ้าน
- **Teacher Dashboard:** ค้นหานักเรียน บันทึกการเยี่ยมบ้าน อัปโหลดรูปภาพ
- **Admin Dashboard:** จัดการโซน ดูรายงาน และสถิติ

### 8.2 การเข้าถึงระบบเยี่ยมบ้าน

ผู้ใช้สามารถเข้าถึงระบบเยี่ยมบ้านได้ผ่าน:

1. **Navigation Menu:** คลิกที่เมนู "Home Visit" ใน Top Navigation Bar
2. **Direct URL:** เข้าไปที่ `https://www.yourdomain.com/home-visit`
3. **API Endpoints:** ระบบใช้ API endpoints ที่ `/api/home-visit/*`

### 8.3 โครงสร้างหน้าเว็บ

```
/home-visit
├── index.vue                    # Landing Page - เลือกบทบาท
├── auth/
│   └── login.vue               # หน้า Login (นักเรียน/ครู/ผู้บริหาร)
├── student/
│   └── profile.vue             # Dashboard นักเรียน
├── teacher/
│   ├── dashboard.vue           # Dashboard ครู
│   ├── manage-student.vue     # จัดการข้อมูลนักเรียน
│   └── components/           # Components สำหรับครู
├── admin/
│   ├── dashboard.vue          # Dashboard ผู้บริหาร
│   └── components/          # Components สำหรับผู้บริหาร
└── components/              # Shared Components
```

### 8.4 การตั้งค่า Permissions สำหรับ Home Visit

ตรวจสอบให้แน่ใจว่า folders ต่อไปนี้มี permissions ที่ถูกต้อง:

```bash
# สำหรับการอัปโหลดรูปภาพ
chmod -R 775 storage/app/public/home-visit
chown -R www-data:www-data storage/app/public/home-visit

# สำหรับ logs
chmod -R 775 storage/logs
```

### 8.5 การทดสอบระบบเยี่ยมบ้าน

**ทดสอบ Landing Page:**
1. เข้าไปที่ `https://www.yourdomain.com/home-visit`
2. ตรวจสอบว่าแสดง 3 cards (นักเรียน, ครู, ผู้บริหาร)
3. คลิกแต่ละ card เพื่อทดสอบการ redirect ไปหน้า login

**ทดสอบการ Login:**
1. ทดสอบ login ด้วยบทบาทต่างๆ
2. ตรวจสอบว่า redirect ไป dashboard ที่ถูกต้อง

**ทดสอบการสร้างการเยี่ยมบ้าน (ครู):**
1. Login ด้วยบทบาทครู
2. ค้นหานักเรียน
3. สร้างการเยี่ยมบ้าน
4. อัปโหลดรูปภาพ
5. ตรวจสอบว่าบันทึกสำเร็จ

**ทดสอบการดูประวัติ (นักเรียน):**
1. Login ด้วยบทบาทนักเรียน
2. ตรวจสอบว่าแสดงประวัติการเยี่ยมบ้าน

**ทดสอบการจัดการโซน (ผู้บริหาร):**
1. Login ด้วยบทบาทผู้บริหาร
2. สร้างโซนใหม่
3. แก้ไขโซน
4. ลบโซน
5. ตรวจสอบรายงานและสถิติ

### 8.6 การแก้ปัญหา Home Visit System

**ปัญหา: ไม่สามารถอัปโหลดรูปภาพได้**
- ตรวจสอบ permissions ของ `storage/app/public/home-visit`
- ตรวจสอบ PHP configuration: `upload_max_filesize` และ `post_max_size`
- ตรวจสอบ disk configuration ใน `config/filesystems.php`

**ปัญหา: ไม่พบหน้า `/home-visit`**
- ตรวจสอบว่าไฟล์ `ui/pages/home-visit/index.vue` ถูกสร้างแล้ว
- ตรวจสอบ Nuxt routes ว่ามี `/home-visit` อยู่ใน routes

**ปัญหา: API endpoints ไม่ทำงาน**
- ตรวจสอบว่า route `api/nuxnanravel/routes/homevisit/homevisit.php` ถูก include ใน `routes/api.php`
- ตรวจสอบ CORS settings
- ตรวจสอบ Laravel logs

---

## 9. การตั้งค่า Cron Jobs

### 8.1 ตั้งค่า Laravel Scheduler

1. ไปที่ **Scheduled Tasks**
2. คลิก **Add Task**
3. กรอกข้อมูล:
   - **Task type:** Run a command
   - **Command:** `php artisan schedule:run`
   - **Run:** Every minute
   - **Run as:** ใส่ username ของ subscription
   - **Description:** Laravel Scheduler
4. คลิก **OK**

### 8.2 ตั้งค่า Queue Worker (ถ้าใช้ Laravel Queues)

1. ไปที่ **Scheduled Tasks**
2. คลิก **Add Task**
3. กรอกข้อมูล:
   - **Task type:** Run a command
   - **Command:** `php artisan queue:work --stop-when-empty --sleep=3 --tries=3`
   - **Run:** Every minute
   - **Run as:** ใส่ username ของ subscription
   - **Description:** Laravel Queue Worker
4. คลิก **OK**

### 8.3 ตั้งค่า Auto-Renew SSL

1. ไปที่ **Scheduled Tasks**
2. คลิก **Add Task**
3. กรอกข้อมูล:
   - **Task type:** Run a command
   - **Command:** `/usr/local/psa/admin/sbin/httpdmng --reconfigure-all`
   - **Run:** Weekly
   - **Description:** Renew SSL Certificates
4. คลิก **OK**

---

## 9. การแก้ปัญหาและ Troubleshooting

### 9.1 500 Internal Server Error (API)

**สาเหตุที่เป็นไปได้:**
1. Permissions ไม่ถูกต้อง
2. .env ไม่มีหรือตั้งค่าผิด
3. PHP extensions ไม่ครบ
4. Database connection ผิด

**วิธีแก้:**

**ตรวจสอบ Permissions:**
```bash
cd /var/www/vhosts/yourdomain.com/api.yourdomain.com/httpdocs
ls -la storage bootstrap/cache
```

ต้องเป็น:
```
drwxrwxr-x  user psacln storage
drwxrwxr-x  user psacln bootstrap/cache
```

ถ้าไม่ใช่:
```bash
chmod -R 775 storage bootstrap/cache
chown -R user:psacln storage bootstrap/cache
```

**ตรวจสอบ .env:**
```bash
cd /var/www/vhosts/yourdomain.com/api.yourdomain.com/httpdocs
cat .env
```

ตรวจสอบว่า:
- `APP_KEY` มีค่า (ไม่ใช่ base64:xxx)
- Database credentials ถูกต้อง
- ไม่มี space หรือ syntax error

**ตรวจสอบ Laravel Log:**
```bash
tail -f storage/logs/laravel.log
```

**ตรวจสอบ PHP Extensions:**
```bash
php -m | grep -E "bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pdo_mysql|tokenizer|xml|zip"
```

### 9.2 CORS Error

**อาการ:** Frontend ไม่สามารถเรียก API ได้ แสดง error ใน console:
```
Access to XMLHttpRequest at 'https://api.yourdomain.com/api/...' from origin 'https://www.yourdomain.com' has been blocked by CORS policy
```

**วิธีแก้:**

1. แก้ไขไฟล์ `config/cors.php`:
```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://www.yourdomain.com'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

2. Clear config cache:
```bash
php artisan config:clear
php artisan config:cache
```

3. รีสตาร์ท Node.js application ใน Plesk

### 9.3 404 Not Found (API Routes)

**สาเหตุ:** Apache/Nginx ไม่สามารถ route requests ไปยัง Laravel ได้

**วิธีแก้:**

ตรวจสอบ `.htaccess` ใน `public/`:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 9.4 Nuxt Hydration Mismatch

**อาการ:** Warning ใน console ว่า hydration mismatch

**สาเหตุ:** ข้อมูลที่ server render ไม่ตรงกับ client

**วิธีแก้:**

1. ตรวจสอบว่า `NUXT_PUBLIC_API_BASE` ถูกต้อง
2. ตรวจสอบว่า API สามารถเข้าถึงได้จากทั้ง server และ browser
3. ตรวจสอบ CORS settings
4. ลอง disable SSR ชั่วคราวเพื่อทดสอบ:
   ```javascript
   // nuxt.config.ts
   export default defineNuxtConfig({
     ssr: false, // ชั่วคราว
   })
   ```

### 9.5 Node.js Application ไม่ทำงาน

**ตรวจสอบ:**

1. ไปที่ **Websites & Domains** → **Node.js**
2. ตรวจสอบว่า **Node.js version** ถูกต้อง
3. ตรวจสอบว่า **Application startup file** คือ `server/index.mjs`
4. คลิก **Restart Application**
5. ตรวจสอบ log โดยคลิก **View Log**

**ถ้ายังไม่ได้:**

ตรวจสอบผ่าน SSH:
```bash
cd /var/www/vhosts/yourdomain.com/httpdocs
ls -la server/index.mjs
```

ต้องมีไฟล์ `server/index.mjs` อยู่

### 9.6 Database Connection Failed

**ตรวจสอบ:**

1. ไปที่ **Databases** → คลิก database ที่สร้าง
2. ตรวจสอบว่า database และ user ถูกต้อง
3. ลอง test connection:
```bash
mysql -u nuxnan_user -p nuxnan_db
```

4. ตรวจสอบ `.env` ว่า credentials ถูกต้อง

---

## 10. Checklist ก่อน Deploy

### ✅ ก่อน Deploy (Pre-Deployment)

- [ ] Backup ข้อมูลทั้งหมดใน local
- [ ] ทดสอบ application ใน local ว่าทำงานได้ปกติ
- [ ] ตรวจสอบว่าไม่มี hardcoded URLs หรือ paths
- [ ] ตรวจสอบว่า `.env.example` มีค่าที่ถูกต้อง
- [ ] ตรวจสอบว่าไม่มี sensitive data ใน code (passwords, API keys)
- [ ] ตรวจสอบว่า `APP_DEBUG=false` ใน production `.env`
- [ ] ตรวจสอบว่า CORS settings ถูกต้อง
- [ ] Build frontend สำหรับ production (`npm run build`)
- [ ] Run migrations ใน local เพื่อทดสอบ
- [ ] ทดสอบระบบเยี่ยมบ้านนักเรียน (Home Visit System) ว่าทำงานได้ปกติ
- [ ] ตรวจสอบว่า Navigation Menu มีเมนู Home Visit แล้ว

### ✅ ระหว่าง Deploy (During Deployment)

- [ ] ติดตั้ง Extensions ที่จำเป็น (Node.js, Composer)
- [ ] สร้าง Database และบันทึก credentials
- [ ] สร้าง Subdomain สำหรับ API
- [ ] อัปโหลดไฟล์ Backend และ Frontend
- [ ] ติดตั้ง PHP dependencies (`composer install`)
- [ ] ตั้งค่า `.env` สำหรับ Backend
- [ ] สร้าง App Key และ JWT Secret
- [ ] Run migrations (`php artisan migrate --force`)
- [ ] ตั้งค่า permissions สำหรับ storage และ cache
- [ ] ตั้งค่า Node.js สำหรับ Frontend
- [ ] ตั้งค่า environment variables สำหรับ Nuxt
- [ ] ติดตั้ง SSL Certificates
- [ ] ตั้งค่า HTTP to HTTPS redirect

### ✅ หลัง Deploy (Post-Deployment)

- [ ] ทดสอบ Frontend URL (`https://www.yourdomain.com`)
- [ ] ทดสอบ API URL (`https://api.yourdomain.com/api/...`)
- [ ] ตรวจสอบว่าไม่มี errors ใน browser console
- [ ] ตรวจสอบว่า API calls ทำงานได้
- [ ] ตรวจสอบว่า authentication ทำงานได้
- [ ] ตรวจสอบว่า file uploads ทำงานได้ (ถ้ามี)
- [ ] ตรวจสอบว่า email sending ทำงานได้ (ถ้ามี)
- [ ] ตั้งค่า Cron Jobs (Scheduler, Queue Worker)
- [ ] ตรวจสอบ Laravel logs (`storage/logs/laravel.log`)
- [ ] ตรวจสอบ Node.js logs
- [ ] ทดสอบ responsive design บน mobile
- [ ] ทดสอบ cross-browser compatibility
- [ ] ตั้งค่า backup schedule
- [ ] ตั้งค่า monitoring/alerts (ถ้ามี)
- [ ] ทดสอบระบบเยี่ยมบ้านนักเรียน (Home Visit System):
  - [ ] ทดสอบหน้า Landing Page (`/home-visit`)
  - [ ] ทดสอบการเข้าสู่ระบบนักเรียน
  - [ ] ทดสอบการเข้าสู่ระบบครู
  - [ ] ทดสอบการเข้าสู่ระบบผู้บริหาร
  - [ ] ทดสอบการสร้างการเยี่ยมบ้าน (ครู)
  - [ ] ทดสอบการอัปโหลดรูปภาพ
  - [ ] ทดสอบการดูประวัติการเยี่ยมบ้าน (นักเรียน)
  - [ ] ทดสอบการจัดการโซน (ผู้บริหาร)
  - [ ] ทดสอบการดูรายงานและสถิติ (ผู้บริหาร)

---

## 📝 ข้อแนะนำเพิ่มเติม

### Performance Optimization

1. **Enable OPcache:**
   - ไปที่ **PHP Settings** → ติ๊ก **opcache.enable**
   - ตั้งค่า `opcache.memory_consumption=128`
   - ตั้งค่า `opcache.interned_strings_buffer=8`
   - ตั้งค่า `opcache.max_accelerated_files=10000`

2. **Enable Gzip Compression:**
   - ไปที่ **Apache & nginx Settings**
   - ติ๊ก **Compress content**
   - ตั้งค่า **Compression level:** 6

3. **Enable Browser Caching:**
   - ไปที่ **Apache & nginx Settings**
   - ติ๊ก **Static files caching**
   - ตั้งค่า **Cache static files:** 7 days

### Security Best Practices

1. **Disable Directory Listing:**
   - ไปที่ **Apache & nginx Settings**
   - ติ๊ก **Directory browsing** → **Disable**

2. **Enable Hotlink Protection:**
   - ไปที่ **Apache & nginx Settings**
   - ติ๊ก **Hotlink protection**

3. **Regular Updates:**
   - อัปเดต PHP version อย่างสม่ำเสมอ
   - อัปเดต Laravel dependencies อย่างสม่ำเสมอ
   - อัปเดต Node.js version อย่างสม่ำเสมอ

### Backup Strategy

1. **Automated Database Backup:**
   - ไปที่ **Scheduled Tasks**
   - เพิ่ม task สำหรับ backup database:
   ```bash
   mysqldump -u nuxnan_user -p'password' nuxnan_db > /var/www/vhosts/yourdomain.com/backups/db_backup_$(date +\%Y\%m\%d).sql
   ```

2. **Automated File Backup:**
   - ใช้ Plesk Backup Manager
   - ตั้งค่า backup schedule (daily/weekly)
   - เก็บ backup ไว้บน remote location

### Monitoring

1. **Plesk Health Monitor:**
   - ไปที่ **Tools & Settings** → **Health Monitoring**
   - ตั้งค่า alerts สำหรับ:
     - CPU usage > 80%
     - Memory usage > 80%
     - Disk usage > 80%

2. **Laravel Telescope (Development Only):**
   - ติดตั้งสำหรับ monitoring ใน development
   - อย่าใช้ใน production

---

## 🆘 การขอความช่วยเหลือ

หากพบปัญหาที่ไม่สามารถแก้ไขได้:

1. **ตรวจสอบ Logs:**
   - Laravel: `storage/logs/laravel.log`
   - PHP: `/var/log/php-fpm/error.log`
   - Apache: `/var/log/apache2/error.log`
   - Nginx: `/var/log/nginx/error.log`

2. **ตรวจสอบ Plesk Logs:**
   - ไปที่ **Tools & Settings** → **Log Manager**

3. **ติดต่อ Support:**
   - ติดต่อ hosting provider
   - ติดต่อ Plesk support
   - ถามใน Laravel/Nuxt community forums

---

**ขอให้โชคดีกับการ Deploy! 🚀**
