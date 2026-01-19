# 🚀 คู่มือการติดตั้งและ Deploy โปรเจกต์ Nuxnan

เอกสารนี้รวบรวมขั้นตอนอย่างละเอียดสำหรับการติดตั้งและ Deploy แอปพลิเคชัน **Nuxnan** บน Production Environment โดยครอบคลุมทั้ง **Ubuntu Server** (Manual Setup) และ **Plesk Control Panel**

---

## 📋 สารบัญ (Table of Contents)

1.  [ภาพรวมระบบ (Architecture Overview)](#1-ภาพรวมระบบ-architecture-overview)
2.  [สิ่งที่ต้องเตรียม (Prerequisites)](#2-สิ่งที่ต้องเตรียม-prerequisites)
3.  [ขั้นตอนการติดตั้ง Backend (Laravel API)](#3-ขั้นตอนการติดตั้ง-backend-laravel-api)
4.  [ขั้นตอนการติดตั้ง Frontend (Nuxt 3)](#4-ขั้นตอนการติดตั้ง-frontend-nuxt-3)
5.  [การตั้งค่า Web Server (Nginx Configuration)](#5-การตั้งค่า-web-server-nginx-configuration)
6.  [การ Deploy บน Plesk Hosting](#6-การ-deploy-บน-plesk-hosting)
7.  [การตั้งค่าเพิ่มเติมและการแก้ปัญหา (Troubleshooting)](#7-การตั้งค่าเพิ่มเติมและการแก้ปัญหา-troubleshooting)

---

## 1. ภาพรวมระบบ (Architecture Overview)

*   **Backend:** Laravel 12 (PHP Framework) ให้บริการ RESTful API
*   **Frontend:** Nuxt 3 (Vue.js Framework) ให้บริการ User Interface แบบ SSR/Hybrid
*   **Database:** MySQL / MariaDB
*   **Communication:** Frontend เชื่อมต่อกับ Backend ผ่าน HTTP API

---

## 2. สิ่งที่ต้องเตรียม (Prerequisites)

### Server Requirements
*   **OS:** Ubuntu 22.04 LTS หรือ 24.04 LTS (แนะนำ)
*   **Web Server:** Nginx (Recommended) หรือ Apache
*   **Database:** MySQL 8.0+ หรือ MariaDB 10.6+

### Software Requirements
*   **PHP:** เวอร์ชั่น **8.3** หรือสูงกว่า
    *   *Extensions:* `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`
*   **Composer:** เครื่องมือจัดการ Package ของ PHP
*   **Node.js:** เวอร์ชั่น **18.x** หรือ **20.x** (LTS)
*   **PM2:** Process Manager สำหรับรัน Node.js service (`npm install -g pm2`)

---

## 3. ขั้นตอนการติดตั้ง Backend (Laravel API)

สมมติว่าคุณติดตั้งโปรเจกต์ไว้ที่ `/var/www/nuxnan`

### 3.1 ดึงโค้ดและติดตั้ง Dependencies
เข้าไปที่โฟลเดอร์ API และติดตั้ง PHP packages:

```bash
cd /var/www/nuxnan/api/nuxnanravel
composer install --optimize-autoloader --no-dev
```

> **Note:** หากเกิดปัญหา `lock file` ไม่ตรงกับ `composer.json` ให้รัน `composer update` ก่อน

### 3.2 ตั้งค่า Environment (.env)
คัดลอกไฟล์ต้นแบบและแก้ไขค่า Config:

```bash
cp .env.example .env
nano .env
```

**ค่าที่ต้องกำหนด:**
```dotenv
APP_NAME=Nuxnan
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nuxnan_db
DB_USERNAME=nuxnan_user
DB_PASSWORD=secret_password

# ตั้งค่า CORS ให้ Frontend เข้าถึงได้
FRONTEND_URL=https://www.yourdomain.com
```

### 3.3 เตรียมความพร้อมระบบ
รันคำสั่งต่อไปนี้เพื่อสร้าง Key, Link storage และเตรียมฐานข้อมูล:

```bash
# 1. สร้าง App Key
php artisan key:generate

# 2. สร้าง Symlink สำหรับไฟล์ Public
php artisan storage:link

# 3. สร้าง JWT Secret (ถ้าใช้งาน JWT)
php artisan jwt:secret

# 4. อัปเดตโครงสร้างฐานข้อมูล (Migration)
php artisan migrate --force

# 5. แก้ไขสิทธิ์ไฟล์ (Permissions)
# ให้ Web Server (www-data) เขียนไฟล์ได้
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 3.4 เพิ่มประสิทธิภาพ (Optimization)
รันคำสั่งเหล่านี้ทุกครั้งที่มีการ Deploy เวอร์ชันใหม่:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 4. ขั้นตอนการติดตั้ง Frontend (Nuxt 3)

### 4.1 ติดตั้งและ Build
เข้าไปที่โฟลเดอร์ Frontend และทำการ Build สำหรับ Production:

```bash
cd /var/www/nuxnan/ui

# 1. ติดตั้ง Node Packages
npm install

# 2. สร้างไฟล์ .env สำหรับ Frontend
nano .env
```

**เนื้อหาใน .env:**
```dotenv
# URL ของ Backend API
NUXT_PUBLIC_API_BASE=https://api.yourdomain.com
# URL ของหน้าเว็บ Frontend
NUXT_PUBLIC_SITE_URL=https://www.yourdomain.com
```

```bash
# 3. Build Project
npm run build
```

> คำสั่งนี้จะสร้างโฟลเดอร์ `.output` ซึ่งมีไฟล์พร้อมใช้งาน

### 4.2 รัน Service ด้วย PM2
ใช้ PM2 เพื่อรัน Nuxt Server เป็น Background Service:

```bash
# Start Process
pm2 start .output/server/index.mjs --name "nuxnan-ui"

# Save Process List (ให้จำค่าหลัง Restart)
pm2 save

# Generate Startup Script (ให้รันอัตโนมัติเมื่อเปิดเครื่อง)
pm2 startup
```

---

## 5. การตั้งค่า Web Server (Nginx Configuration)

สร้างไฟล์ Config ที่ `/etc/nginx/sites-available/nuxnan`

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com; # Frontend Domain

    # Redirect HTTP to HTTPS (Optional but Recommended)
    # return 301 https://$host$request_uri;
    
    # --- Frontend Proxy (Nuxt) ---
    location / {
        proxy_pass http://127.0.0.1:3000; # Port ที่ PM2 รัน
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}

server {
    listen 80;
    server_name api.yourdomain.com; # API Domain
    root /var/www/nuxnan/api/nuxnanravel/public;
    index index.php;

    # --- Backend Handling (Laravel) ---
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # ตรวจสอบเวอร์ชัน PHP FPM
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block access to hidden files
    location ~ /\.ht {
        deny all;
    }
}
```

ตรวจสอบและ Reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/nuxnan /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. การ Deploy บน Plesk Hosting

เหมาะสำหรับผู้ใช้งาน Shared Hosting หรือ VS ที่ใช้ Plesk Panel

### ✅ 6.1 ตั้งค่า API (Backend)
1.  **Add Subdomain:** สร้าง `api.yourdomain.com`
2.  **Document Root:** ชี้ไปที่โฟลเดอร์ `api/nuxnanravel/public`
3.  **Upload Files:** อัปโหลดไฟล์ Laravel ทั้งหมดไปที่โฟลเดอร์ของ Subdomain นี้
4.  **Database:** สร้าง Database ในเมนู Databases และนำค่า (DB Name, User, Pass) ไปใส่ใน `.env`
5.  **Environment:** เปลี่ยนชื่อ `.env.example` เป็น `.env` และแก้ไขค่าต่างๆ
6.  **Composer:** ใช้เมนู **PHP Composer** กด "Install" หรือ "Update"
7.  **SSH/Terminal:** เข้า Terminal แล้วรัน `php artisan migrate --force`

### ✅ 6.2 ตั้งค่า Frontend (Nuxt)
1.  **Domain:** ใช้โดเมนหลัก `yourdomain.com`
2.  **Node.js Extension:** ต้องติดตั้ง Node.js Extension ใน Plesk ก่อน
3.  **Preparation (Local):**
    *   Build โปรเจกต์ในเครื่องตัวเองด้วย `npm run build`
    *   จะได้โฟลเดอร์ `.output`
4.  **Upload:**
    *   **สำคัญ:** ลบไฟล์และโฟลเดอร์เดิมทั้งหมดใน `httpdocs` ออกก่อน (เช่น `node_modules`, `package.json`, `server`, `public`) เพื่อป้องกันปัญหไฟล์เก่าตกค้าง
    *   อัปโหลดไฟล์ **ทั้งหมดที่อยู่ในโฟลเดอร์ .output** (เช่น `public`, `server`, `nitro.json`) ไปที่ `httpdocs` บน Server
5.  **Plesk Node.js Settings:**
    *   **Node.js Version:** 18.x หรือ 20.x
    *   **Document Root:** `/httpdocs/public` (โฟลเดอร์ static assets)
    *   **Application Startup File:** `server/index.mjs`
    *   คลิก **Enable Node.js**
    *   กด **NPM Install** (ถ้ามี package.json) และ **Restart Application**

---

## 7. การตั้งค่าเพิ่มเติมและการแก้ปัญหา (Troubleshooting)

### ❌ 500 Server Error (API)
*   **เช็ค Log:** ดูไฟล์ `storage/logs/laravel.log`
*   **เช็ค Permissions:** โฟลเดอร์ `storage` และ `bootstrap/cache` ต้องเป็น 775/777
*   **เช็ค .env:** ตรวจสอบรหัสผ่าน Database หรือ Syntax ผิดพลาด

### ❌ CORS Error (Frontend เรียก API ไม่ได้)
*   แก้ไขไฟล์ `config/cors.php` ใน Laravel
*   ตั้งค่า `supports_credentials` เป็น `true`
*   ตั้งค่า `allowed_origins` ให้ระบุโดเมน Frontend หรือใช้ `['*']` เพื่อทดสอบ (ไม่แนะนำสำหรับ Production)

### ❌ Nuxt Hydration Mismatch
*   เกิดจากข้อมูล HTML จาก Server ไม่ตรงกับ Client
*   ตรวจสอบว่า API URL (`NUXT_PUBLIC_API_BASE`) ถูกต้องและเข้าถึงได้จริงทั้งจากฝั่ง Server (Container/Localhost) และ Browser

### ❌ NPM Error: gulp-better-rollup / nodenv version mismatch
*   **อาการ:** ขึ้น Error ขณะกด NPM Install ว่า `code 1`, `conflicting peer dependency: rollup`, หรือ `nodenv: version not installed`
*   **สาเหตุ:** มีไฟล์ `package.json` หรือ `node_modules` เก่าตกค้างอยู่ในโฟลเดอร์ `httpdocs` ของ Server ซึ่งขัดแย้งกับไฟล์ใหม่
*   **วิธีแก้:**
    1.  ใช้ File Manager ใน Plesk ลบไฟล์และโฟลเดอร์ **ทั้งหมด** ใน `httpdocs` ทิ้ง (ลบ `node_modules`, `package.json` เก่าให้เกลี้ยง)
    2.  อัปโหลดไฟล์จากโฟลเดอร์ `.output` ขึ้นไปใหม่
    3.  กด **NPM Install** ในหน้า Plesk Node.js อีกครั้ง
