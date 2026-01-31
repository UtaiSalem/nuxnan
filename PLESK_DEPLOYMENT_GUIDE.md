# 🚀 คู่มือการ Deploy บน Plesk Panel อย่างละเอียด

เอกสารนี้ให้คำแนะนำแบบละเอียดสำหรับการ Deploy โปรเจกต์ Nuxnan บน Server ที่ใช้ **Plesk Control Panel** จัดการ

---

## ⚡ Quick Start (สำหรับผู้มีประสบการณ์)

```bash
# === BACKEND (Laravel API) ===
# 1. ใช้ Git clone ผ่าน Plesk ไปที่ httpdocs ของ main domain
# 2. ติดตั้ง dependencies
cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel
composer install --optimize-autoloader --no-dev

# 3. ตั้งค่า environment
cp production.env .env
nano .env  # แก้ไข DB credentials

# 4. Setup Laravel
php artisan key:generate
php artisan jwt:secret
php artisan storage:link
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache

# 5. ตั้งค่า permissions
chmod -R 775 storage bootstrap/cache

# === FRONTEND (Nuxt 3) ===
# 1. Git clone จะได้ source code ใน httpdocs/ui
# 2. Build บน server (หรือ local แล้ว push)
cd /var/www/vhosts/nuxnan.com/httpdocs/ui
npm install && npm run build

# 3. ตั้งค่า Node.js ใน Plesk
# - Version: 20.x LTS
# - Startup file: ui/.output/server/index.mjs
# - Mode: Production

# 4. เพิ่ม Environment Variables
# NUXT_PUBLIC_API_BASE=https://api.nuxnan.com
# NUXT_PUBLIC_SITE_URL=https://www.nuxnan.com

# 5. Enable Node.js และ Restart Application
```

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

### 1.1 System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              INTERNET                                        │
│                                  │                                           │
│                         ┌────────▼────────┐                                  │
│                         │  Cloudflare CDN │ (Optional)                       │
│                         │   SSL/WAF/DDoS  │                                  │
│                         └────────┬────────┘                                  │
│                                  │                                           │
└──────────────────────────────────┼───────────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼───────────────────────────────────────────┐
│                         PLESK SERVER                                         │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                      FIREWALL (iptables/firewalld)                      │ │
│  │                    Ports: 22, 80, 443, 8443, 3306                       │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                   │                                          │
│         ┌─────────────────────────┼─────────────────────────┐                │
│         │                         │                         │                │
│         ▼                         ▼                         ▼                │
│  ┌──────────────┐         ┌──────────────┐         ┌──────────────┐          │
│  │   NGINX      │         │   NGINX      │         │   MySQL      │          │
│  │   Reverse    │         │   Reverse    │         │   8.0+       │          │
│  │   Proxy      │         │   Proxy      │         │              │          │
│  │   :443       │         │   :443       │         │   :3306      │          │
│  └──────┬───────┘         └──────┬───────┘         └──────────────┘          │
│         │                        │                        ▲                  │
│         ▼                        ▼                        │                  │
│  ┌──────────────┐         ┌──────────────┐                │                  │
│  │   Node.js    │         │   PHP-FPM    │────────────────┘                  │
│  │   20.x LTS   │         │   8.3+       │                                   │
│  │              │         │              │                                   │
│  │  ┌────────┐  │         │  ┌────────┐  │                                   │
│  │  │ Nuxt 3 │  │◄───────►│  │Laravel │  │                                   │
│  │  │Frontend│  │  REST   │  │12 API  │  │                                   │
│  │  │ :3000  │  │  API    │  │        │  │                                   │
│  │  └────────┘  │         │  └────────┘  │                                   │
│  └──────────────┘         └──────────────┘                                   │
│                                                                              │
│  www.nuxnan.com           api.nuxnan.com                                     │
└──────────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Network Flow Diagram

```
User Request Flow:
──────────────────

1. Browser Request
   https://www.nuxnan.com/courses
          │
          ▼
2. DNS Resolution → Points to Server IP
          │
          ▼
3. SSL Termination (Let's Encrypt)
          │
          ▼
4. Nginx Reverse Proxy
          │
          ├──── Static Files (/public/*) → Serve directly
          │
          └──── Dynamic Routes → Node.js (port 3000)
                     │
                     ▼
5. Nuxt 3 SSR renders page
          │
          ▼
6. API calls to https://api.nuxnan.com
          │
          ▼
7. Nginx → PHP-FPM → Laravel → MySQL
          │
          ▼
8. JSON Response back to Nuxt
          │
          ▼
9. Complete HTML sent to Browser
```

### 1.3 Technology Stack Summary

| Layer | Technology | Version | Purpose |
|-------|------------|---------|---------|
| **Frontend** | Nuxt 3 | 3.15.x | SSR/SSG Framework |
| **UI Library** | PrimeVue | 4.5.x | UI Components |
| **State** | Pinia | 2.3.x | State Management |
| **Styling** | Tailwind CSS | 3.x | Utility CSS |
| **Backend** | Laravel | 12.x | REST API |
| **Auth** | JWT Auth | 2.8.x | Authentication |
| **Database** | MySQL | 8.0+ | Data Storage |
| **Server** | Nginx | Latest | Reverse Proxy |
| **Runtime** | Node.js | 20.x LTS | JS Runtime |
| **PHP** | PHP-FPM | 8.3+ | PHP Runtime |

### 1.4 โครงสร้าง Domain

```
Server (Plesk)
├── api.nuxnan.com (Subdomain)
│   └── Laravel API Backend
│       └── Document Root: httpdocs/api/nuxnanravel/public
│       └── PHP 8.3+
│
└── www.nuxnan.com (Main Domain)
    └── Nuxt 3 Frontend
        └── Document Root: httpdocs (Node.js points to ui/.output)
        └── Node.js 20.x LTS
```

**โครงสร้างโฟลเดอร์บน Server (ใช้ Git ผ่าน Plesk):**

⚠️ **เมื่อใช้ Git clone ผ่าน Plesk โครงสร้างจะเหมือนกับ local repository:**

```
/var/www/vhosts/nuxnan.com/
│
├── httpdocs/                     ← Git Repository Root (Main Domain)
│   │
│   ├── api/                      ← API Directory
│   │   ├── nuxnanaspire/         ← .NET Aspire (ถ้าใช้)
│   │   │
│   │   └── nuxnanravel/          ← Laravel API
│   │       ├── app/
│   │       │   ├── Http/
│   │       │   │   ├── Controllers/
│   │       │   │   └── Middleware/
│   │       │   └── Models/
│   │       ├── config/
│   │       │   ├── cors.php
│   │       │   ├── jwt.php
│   │       │   └── filesystems.php
│   │       ├── database/
│   │       │   ├── migrations/
│   │       │   └── seeders/
│   │       ├── public/           ← API Document Root (Subdomain points here)
│   │       │   ├── index.php
│   │       │   ├── .htaccess
│   │       │   └── storage/      ← Symlink
│   │       ├── routes/
│   │       │   ├── api.php
│   │       │   ├── homevisit/
│   │       │   ├── learn/
│   │       │   └── earn/
│   │       ├── storage/
│   │       │   ├── app/public/
│   │       │   ├── framework/
│   │       │   └── logs/
│   │       ├── vendor/
│   │       ├── .env
│   │       ├── production.env
│   │       └── composer.json
│   │
│   ├── ui/                       ← Nuxt 3 Frontend
│   │   ├── .output/              ← Build Output (หลัง npm run build)
│   │   │   ├── public/
│   │   │   │   ├── _nuxt/
│   │   │   │   └── favicon.ico
│   │   │   └── server/
│   │   │       └── index.mjs     ← Node.js Entry Point
│   │   ├── components/
│   │   ├── pages/
│   │   ├── composables/
│   │   ├── assets/
│   │   ├── nuxt.config.ts
│   │   ├── package.json
│   │   ├── .env
│   │   └── ecosystem.config.cjs
│   │
│   └── .gitignore                ← Excludes docs/, plans/, *.md
│
└── logs/                         ← Plesk Logs
    ├── access_log
    └── error_log
```

> 💡 **หมายเหตุ:** `docs/`, `plans/`, และไฟล์ `*.md` ถูกเพิ่มใน `.gitignore` แล้ว จะไม่ถูก push ไป production

### 1.5 Domain & Path Mapping

| Domain | Document Root | Points To |
|--------|---------------|----------|
| `www.nuxnan.com` | `/httpdocs` | Node.js → `ui/.output/server/index.mjs` |
| `api.nuxnan.com` | `/httpdocs/api/nuxnanravel/public` | PHP-FPM → Laravel |

---

## 2. การเตรียม Server (Server Preparation)

### 2.1 ตรวจสอบข้อกำหนดของ Server

**Minimum Requirements:**
| Resource | Minimum | Recommended | Purpose |
|----------|---------|-------------|---------|
| **OS** | Ubuntu 20.04 | Ubuntu 22.04 LTS | Server OS |
| **RAM** | 2GB | 4GB+ | Application Memory |
| **CPU** | 1 vCPU | 2+ vCPU | Processing |
| **Disk** | 20GB SSD | 50GB+ SSD | Storage |
| **PHP** | 8.3 | 8.3+ | Laravel Runtime |
| **MySQL** | 8.0 | 8.0+ | Database |
| **Node.js** | 18.x | 20.x LTS | Nuxt Runtime |

**Supported Operating Systems:**
- ✅ Ubuntu 20.04/22.04 LTS (แนะนำ)
- ✅ Debian 10/11/12
- ✅ AlmaLinux 8/9
- ✅ Rocky Linux 8/9
- ✅ CentOS 7/8 (deprecated)

### 2.2 การตั้งค่า Firewall

**สำหรับ Ubuntu/Debian (UFW):**
```bash
# เปิดใช้งาน UFW
sudo ufw enable

# อนุญาต SSH
sudo ufw allow 22/tcp

# อนุญาต HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# อนุญาต Plesk Panel
sudo ufw allow 8443/tcp
sudo ufw allow 8880/tcp

# อนุญาต MySQL (เฉพาะ localhost)
# ไม่ต้องเปิด port 3306 ถ้าใช้ localhost

# ตรวจสอบ status
sudo ufw status verbose
```

**สำหรับ CentOS/AlmaLinux (firewalld):**
```bash
# เปิดใช้งาน firewalld
sudo systemctl start firewalld
sudo systemctl enable firewalld

# อนุญาต services
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-port=8443/tcp
sudo firewall-cmd --permanent --add-port=8880/tcp

# Reload firewall
sudo firewall-cmd --reload

# ตรวจสอบ
sudo firewall-cmd --list-all
```

**Port Summary:**
| Port | Service | Access |
|------|---------|--------|
| 22 | SSH | Admin only |
| 80 | HTTP | Public (redirects to HTTPS) |
| 443 | HTTPS | Public |
| 8443 | Plesk UI | Admin only |
| 8880 | Plesk Webmail | Optional |
| 3306 | MySQL | localhost only |
| 3000 | Node.js | Internal (proxied by Nginx) |

### 2.3 เข้าสู่ Plesk Panel

1. เข้า Plesk Panel ผ่าน URL: `https://your-server-ip:8443`
2. Login ด้วย username และ password ของ admin
3. เลือก **Extensions** จากเมนูด้านซ้าย

### 2.4 ตรวจสอบ Server Resources

```bash
# ตรวจสอบ RAM
free -h

# ตรวจสอบ Disk Space
df -h

# ตรวจสอบ CPU
lscpu

# ตรวจสอบ OS
cat /etc/os-release
```

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
   - **Related site:** เลือก `api.nuxnan.com`
   - **Database server:** MySQL (Default)
   - **Database user:** สร้าง user ใหม่ (เช่น `nuxnan_user`)
   - **Password:** ใส่รหัสผ่านที่ปลอดภัย (อย่างน้อย 16 ตัวอักษร)
4. คลิก **OK**

### 4.2 บันทึกข้อมูล Database

จดบันทึกข้อมูลต่อไปนี้ไว้ใช้ในการตั้งค่า `.env`:
```
Database Name: nuxnan_db
Database User: nuxnan_user
Database Password: [your_secure_password]
Database Host: localhost
Database Port: 3306
```

### 4.3 ตั้งค่า MySQL Configuration สำหรับ Production

**แก้ไขผ่าน Plesk:**
1. ไปที่ **Tools & Settings** → **Database Servers**
2. คลิก **Settings** ของ MySQL server
3. ปรับค่าต่อไปนี้:

**หรือแก้ไขผ่าน SSH (`/etc/mysql/mysql.conf.d/mysqld.cnf`):**
```ini
[mysqld]
# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Performance Tuning
innodb_buffer_pool_size = 512M    # 50-70% of RAM
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Connection Settings
max_connections = 150
wait_timeout = 600
interactive_timeout = 600

# Query Cache (disable for MySQL 8.0+)
query_cache_type = 0

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### 4.4 Import Seed Data (ถ้ามี)

**วิธีที่ 1: ผ่าน Laravel Seeder**
```bash
cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel

# Run migrations
php artisan migrate --force

# Run seeders (production safe seeders only)
php artisan db:seed --class=ProductionSeeder --force
```

**วิธีที่ 2: Import จาก SQL File**
```bash
# ผ่าน command line
mysql -u nuxnan_user -p nuxnan_db < /path/to/backup.sql

# หรือผ่าน Plesk phpMyAdmin
# 1. ไปที่ Databases → phpMyAdmin
# 2. เลือก database
# 3. คลิก Import → เลือกไฟล์ SQL
```

### 4.5 ตั้งค่า Database Backup

**สร้าง Backup Script (`/home/scripts/db_backup.sh`):**
```bash
#!/bin/bash
BACKUP_DIR="/var/www/vhosts/nuxnan.com/backups/db"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="nuxnan_db"
DB_USER="nuxnan_user"
DB_PASS="your_password"

# สร้าง directory ถ้ายังไม่มี
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# ลบ backup ที่เก่ากว่า 7 วัน
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR/db_backup_$DATE.sql.gz"
```

**ตั้งค่า Cron Job:**
```bash
# ทำ backup ทุกวันเวลา 02:00
0 2 * * * /home/scripts/db_backup.sh >> /var/log/db_backup.log 2>&1
```

---

## 5. Deploy Backend (Laravel API)

### 5.1 สร้าง Subdomain สำหรับ API

1. ไปที่ **Websites & Domains**
2. คลิก **Add Subdomain**
3. กรอกข้อมูล:
   - **Subdomain name:** `api`
   - **Document root:** `/httpdocs` (จะเปลี่ยนเป็น public หลัง clone)
4. คลิก **OK**

### 5.2 การใช้ Git ผ่าน Plesk (แนะนำ) 🌟

Plesk มี Git Extension ที่ช่วยให้ deploy ได้ง่ายและมี auto-deployment

#### 5.2.1 ติดตั้ง Git Extension

1. ไปที่ **Extensions** → **My Extensions**
2. ค้นหา **Git**
3. คลิก **Install** (ถ้ายังไม่ได้ติดตั้ง)

#### 5.2.2 เชื่อมต่อ Repository สำหรับ Backend

1. ไปที่ **Websites & Domains** → เลือก `api.nuxnan.com`
2. คลิก **Git**
3. กรอกข้อมูล Repository:

| Field | Value | Description |
|-------|-------|-------------|
| **Remote Git repository** | `https://github.com/your-username/nuxnan.git` | URL ของ repo |
| **Username** | `your-github-username` | หรือใช้ Personal Access Token |
| **Password/Token** | `ghp_xxxxxxxxxxxx` | GitHub Personal Access Token |
| **Deploy to** | `/httpdocs` | โฟลเดอร์ที่จะ deploy |
| **Branch** | `main` | Branch ที่จะ deploy |

4. คลิก **OK** เพื่อ clone repository

#### 5.2.3 ตั้งค่า Deployment Path

เนื่องจาก Laravel อยู่ใน `api/nuxnanravel/`:

**วิธีที่ 1: ใช้ Subdirectory**
```
Repository Structure:
├── api/
│   └── nuxnanravel/    ← Laravel files
├── ui/                  ← Nuxt files
└── docs/
```

หลัง clone แล้ว ตั้งค่า Document Root:
1. ไปที่ **Hosting Settings** ของ `api.nuxnan.com`
2. เปลี่ยน **Document root:** เป็น `/httpdocs/api/nuxnanravel/public`
3. คลิก **OK**

**⚠️ สำคัญ: Subdomain ใช้ไฟล์จาก Main Domain**

เนื่องจาก Git clone ไปที่ `httpdocs` ของ main domain แล้ว subdomain `api.nuxnan.com` จะ point ไปที่ path ภายใน:
```
Main Domain (nuxnan.com)
└── httpdocs/                    ← Git clone ที่นี่
    └── api/nuxnanravel/public/  ← API Subdomain Document Root
```

**วิธีที่ 2: แยก Repository สำหรับ API**
ถ้าต้องการแยก repo สำหรับ API โดยเฉพาะ:
```bash
# ในเครื่อง local - สร้าง repo ใหม่เฉพาะ API
cd api/nuxnanravel
git init
git remote add origin https://github.com/your-username/nuxnan-api.git
git add .
git commit -m "Initial commit"
git push -u origin main
```

แล้วใช้ URL ของ repo ใหม่ใน Plesk Git settings

#### 5.2.4 ตั้งค่า Additional Deployment Actions

หลังจาก Git clone สำเร็จ คุณสามารถตั้งค่า actions ที่จะรันหลังจากทุกครั้งที่ pull:

1. ในหน้า Git settings คลิก **Repository settings**
2. เปิดใช้งาน **Enable additional deploy actions**
3. เพิ่ม script ต่อไปนี้:

```bash
#!/bin/bash
# Post-deployment script for Laravel
# Path: เมื่อใช้ Git clone ไปที่ httpdocs ของ main domain

LARAVEL_PATH="/var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel"
PHP_BIN="/opt/plesk/php/8.3/bin/php"
COMPOSER_BIN="/usr/local/bin/composer"

cd "$LARAVEL_PATH"

echo "=== Starting Laravel Deployment ==="
echo "Working directory: $(pwd)"

# ติดตั้ง dependencies
echo "Installing composer dependencies..."
$COMPOSER_BIN install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# ตั้งค่า permissions
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache

# Clear และ cache config
echo "Caching configuration..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache

# สร้าง storage link (ถ้ายังไม่มี)
if [ ! -L "public/storage" ]; then
    echo "Creating storage link..."
    $PHP_BIN artisan storage:link
fi

# Run migrations (ระวัง! uncomment เมื่อต้องการ)
# echo "Running migrations..."
# $PHP_BIN artisan migrate --force

# Restart queue workers
echo "Restarting queue workers..."
$PHP_BIN artisan queue:restart

echo "=== Laravel Deployment Completed at $(date) ==="
```

#### 5.2.5 ตั้งค่า Webhook สำหรับ Auto-Deploy

เพื่อให้ deploy อัตโนมัติเมื่อ push ไปยัง GitHub:

1. ในหน้า Git settings คลิก **Repository settings**
2. คัดลอก **Webhook URL** (จะมีลักษณะแบบนี้):
   ```
   https://api.nuxnan.com/plesk-git/smart-deploy?token=xxxxxxxx
   ```

3. ไปที่ GitHub Repository → **Settings** → **Webhooks**
4. คลิก **Add webhook**
5. กรอกข้อมูล:
   - **Payload URL:** วาง Webhook URL จาก Plesk
   - **Content type:** `application/json`
   - **Which events:** `Just the push event`
6. คลิก **Add webhook**

ตอนนี้ทุกครั้งที่ push ไปยัง `main` branch จะ auto-deploy!

#### 5.2.6 Manual Deployment

ถ้าต้องการ deploy ด้วยตัวเอง:

1. ไปที่ **Git** ของ subdomain
2. คลิก **Pull Now** หรือ **Deploy Now**
3. รอจนกว่าจะเสร็จ
4. ตรวจสอบ deployment log

### 5.3 อัปโหลดไฟล์ Laravel (วิธีอื่นๆ)

หากไม่ต้องการใช้ Git ผ่าน Plesk คุณสามารถอัปโหลดไฟล์ได้ด้วยวิธีดังต่อไปนี้:

**วิธีที่ 1: ผ่าน File Manager (Plesk UI)**
1. ไปที่ **Websites & Domains** → คลิก **File Manager** ของ `nuxnan.com`
2. นำทางไปที่โฟลเดอร์ `httpdocs`
3. อัปโหลดไฟล์ Laravel ทั้งหมดจากเครื่อง local
   - แนะนำให้ zip ไฟล์ก่อน แล้วค่อย unzip บน server

**วิธีที่ 2: ผ่าน SSH + SCP**
```bash
# ในเครื่อง local - สร้างไฟล์ zip (ไม่รวม vendor และ node_modules)
cd api/nuxnanravel
zip -r nuxnan-api.zip . -x "vendor/*" -x "node_modules/*" -x ".git/*"

# อัปโหลดไปยัง server
scp nuxnan-api.zip user@your-server-ip:/var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel/

# บน server - unzip
ssh user@your-server-ip
cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel
unzip nuxnan-api.zip
rm nuxnan-api.zip
```

### 5.4 ตั้งค่า Document Root

**สำคัญ:** หลังอัปโหลดไฟล์แล้ว ต้องเปลี่ยน Document Root ไปที่ `public/`

1. ไปที่ **Websites & Domains** → คลิก **Hosting Settings** ของ `api.nuxnan.com`
2. เปลี่ยน **Document root:** เป็น `/httpdocs/public`
3. คลิก **OK**

### 5.5 ติดตั้ง PHP Dependencies

**ผ่าน SSH (แนะนำ):**
```bash
cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel

# ติดตั้ง dependencies สำหรับ production
composer install --optimize-autoloader --no-dev --no-interaction

# ถ้า memory limit error
php -d memory_limit=-1 /usr/local/bin/composer install --optimize-autoloader --no-dev
```

**ผ่าน Plesk UI:**
1. ไปที่ **Websites & Domains** → คลิก **PHP Composer** ของ `api.nuxnan.com`
2. คลิก **Install** หรือ **Update**
3. รอให้การติดตั้งเสร็จสิ้น

### 5.5 ตั้งค่า Environment (.env)

1. ไปที่ **File Manager** ของ `api.nuxnan.com`
2. คัดลอกไฟล์ `production.env` เป็น `.env`
3. แก้ไขไฟล์ `.env` ด้วยค่าที่ถูกต้อง:

```env
#==============================================================================
# APPLICATION SETTINGS
#==============================================================================
APP_NAME=Nuxnan
APP_ENV=production
APP_KEY=                                    # จะ generate ทีหลัง
APP_DEBUG=false                             # สำคัญ! ต้องเป็น false
APP_URL=https://api.nuxnan.com
APP_TIMEZONE=Asia/Bangkok

#==============================================================================
# LOCALE SETTINGS
#==============================================================================
APP_LOCALE=th
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=th_TH

#==============================================================================
# DATABASE CONFIGURATION
#==============================================================================
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nuxnan_nuxnan_db
DB_USERNAME=nuxnan_nuxnan_admin
DB_PASSWORD=zfz0gLUV
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

#==============================================================================
# SESSION & CACHE CONFIGURATION
#==============================================================================
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.nuxnan.com                  # ใช้ dot prefix สำหรับ cross-subdomain

CACHE_STORE=database
CACHE_PREFIX=nuxnan_cache_

#==============================================================================
# QUEUE CONFIGURATION
#==============================================================================
QUEUE_CONNECTION=database

#==============================================================================
# FILESYSTEM CONFIGURATION
#==============================================================================
FILESYSTEM_DISK=public
FILESYSTEM_CLOUD=s3                         # Optional: ถ้าใช้ S3

#==============================================================================
# CORS CONFIGURATION
#==============================================================================
FRONTEND_URL=https://www.nuxnan.com
SANCTUM_STATEFUL_DOMAINS=www.nuxnan.com,nuxnan.com

#==============================================================================
# JWT CONFIGURATION
#==============================================================================
JWT_SECRET=                                 # จะ generate ทีหลัง
JWT_TTL=60                                  # Token หมดอายุใน 60 นาที
JWT_REFRESH_TTL=20160                       # Refresh token หมดอายุใน 14 วัน

#==============================================================================
# MAIL CONFIGURATION (Optional)
#==============================================================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@nuxnan.com
MAIL_FROM_NAME="${APP_NAME}"

#==============================================================================
# LOGGING CONFIGURATION
#==============================================================================
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error                             # production ใช้ error หรือ warning

#==============================================================================
# SECURITY SETTINGS
#==============================================================================
BCRYPT_ROUNDS=12
```

### 5.6 สร้าง Application Keys และ Setup

**ขั้นตอนที่ต้องทำตามลำดับ:**

```bash
cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel

# 1. สร้าง App Key
php artisan key:generate

# 2. สร้าง JWT Secret
php artisan jwt:secret

# 3. สร้าง Storage Link (เชื่อม public/storage → storage/app/public)
php artisan storage:link

# 4. Clear old caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Run Migrations
php artisan migrate --force

# 6. Run Seeders (ถ้ามี ProductionSeeder)
php artisan db:seed --class=ProductionSeeder --force

# 7. Optimize Laravel สำหรับ Production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. ตรวจสอบว่าทุกอย่างถูกต้อง
php artisan about
```

### 5.7 ตั้งค่า Permissions

**ผ่าน SSH:**
```bash
cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel

# ตั้งค่า ownership (แทน 'user' ด้วย Plesk subscription user)
chown -R user:psacln .

# ตั้งค่า permissions สำหรับไฟล์
find . -type f -exec chmod 644 {} \;

# ตั้งค่า permissions สำหรับ directories
find . -type d -exec chmod 755 {} \;

# ตั้งค่า permissions พิเศษสำหรับ storage และ cache
chmod -R 775 storage bootstrap/cache

# ตรวจสอบว่า storage มี subdirectories ที่จำเป็น
mkdir -p storage/app/public/home-visit
mkdir -p storage/app/public/uploads
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs

# ตั้งค่า ownership อีกครั้ง
chown -R user:psacln storage bootstrap/cache
```

**ตรวจสอบ Permissions:**
```bash
ls -la storage
# ควรได้:
# drwxrwxr-x  user psacln storage
# drwxrwxr-x  user psacln bootstrap/cache
```

### 5.8 ตั้งค่า PHP Version และ Extensions

1. ไปที่ **Websites & Domains** → คลิก **PHP Settings** ของ `api.nuxnan.com`
2. ตั้งค่า **PHP version:** เป็น **8.3** หรือสูงกว่า
3. ตั้งค่า PHP Options:
   ```
   memory_limit = 256M
   max_execution_time = 300
   max_input_time = 300
   post_max_size = 50M
   upload_max_filesize = 50M
   max_file_uploads = 20
   ```
4. ตรวจสอบ Extensions ที่จำเป็น (ทั้งหมดต้องถูกเปิด):
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
   - ✅ `gd` (สำหรับ image processing)
   - ✅ `exif` (สำหรับ image metadata)
5. คลิก **Apply**

### 5.9 ทดสอบ API Endpoint

```bash
# ทดสอบจาก server
curl -I https://api.nuxnan.com

# ควรได้ HTTP 200 หรือ redirect

# ทดสอบ API health check (ถ้ามี)
curl https://api.nuxnan.com/api/health

# ตรวจสอบ Laravel logs ถ้ามี error
tail -f storage/logs/laravel.log
```

---

## 6. Deploy Frontend (Nuxt 3)

### 6.1 เตรียมไฟล์ Build ในเครื่อง Local

```bash
# ในเครื่อง local
cd ui

# ติดตั้ง dependencies
npm install

# สร้างไฟล์ .env สำหรับ production build
```

**สร้างไฟล์ `.env.production`:**
```env
# Backend API URL
NUXT_PUBLIC_API_BASE=https://api.nuxnan.com

# Frontend URL
NUXT_PUBLIC_SITE_URL=https://www.nuxnan.com

# Node Environment
NODE_ENV=production
```

```bash
# Build สำหรับ Production (ใช้ .env.production)
cp .env.production .env
npm run build

# ตรวจสอบผลลัพธ์
ls -la .output/
# ควรมี:
# - public/     (static files)
# - server/     (Node.js server)
```

### 6.2 การใช้ Git ผ่าน Plesk สำหรับ Frontend 🌟

เช่นเดียวกับ Backend คุณสามารถใช้ Git ได้:

#### 6.2.1 เชื่อมต่อ Repository สำหรับ Frontend

1. ไปที่ **Websites & Domains** → เลือก `nuxnan.com`
2. คลิก **Git**
3. กรอกข้อมูล Repository:

| Field | Value |
|-------|-------|
| **Remote Git repository** | `https://github.com/your-username/nuxnan.git` |
| **Username** | `your-github-username` |
| **Password/Token** | `ghp_xxxxxxxxxxxx` |
| **Deploy to** | `/httpdocs` |
| **Branch** | `main` |

4. คลิก **OK**

#### 6.2.2 ⚠️ สำคัญ: Nuxt ต้อง Build ก่อน Deploy

**Git pull จะได้ source code ไม่ใช่ไฟล์ build!**

มี 2 วิธี:

**วิธีที่ 1: Build บน Local แล้ว Push ไฟล์ .output (ไม่แนะนำ)**
```bash
# ใน local
cd ui
npm run build
git add .output -f  # Force add เพราะปกติอยู่ใน .gitignore
git commit -m "Add build files"
git push
```

**วิธีที่ 2: Build บน Server หลัง Pull (แนะนำ)**

ตั้งค่า Post-deployment script:

1. ในหน้า Git settings คลิก **Repository settings**
2. เปิดใช้งาน **Enable additional deploy actions**
3. เพิ่ม script:

```bash
#!/bin/bash
# Post-deployment script for Nuxt 3
# Path: เมื่อใช้ Git clone ไปที่ httpdocs ของ main domain

UI_PATH="/var/www/vhosts/nuxnan.com/httpdocs/ui"
NPM_BIN="/opt/plesk/node/20/bin/npm"  # หรือ /usr/bin/npm

cd "$UI_PATH"

echo "=== Starting Nuxt 3 Deployment ==="
echo "Working directory: $(pwd)"

# ติดตั้ง dependencies
echo "Installing npm dependencies..."
$NPM_BIN ci --production=false

# สร้างไฟล์ .env (ถ้ายังไม่มี)
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cat > .env << EOF
NUXT_PUBLIC_API_BASE=https://api.nuxnan.com
NUXT_PUBLIC_SITE_URL=https://www.nuxnan.com
NODE_ENV=production
EOF
fi

# Build
echo "Building Nuxt application..."
$NPM_BIN run build

# ไม่ต้องย้าย .output เพราะ Node.js จะ point ไปที่ ui/.output โดยตรง
# Plesk Node.js startup file: ui/.output/server/index.mjs

# Restart Node.js
echo "Restarting Node.js application..."
/usr/local/psa/bin/nodejs restart -domain nuxnan.com 2>/dev/null || echo "Please restart Node.js manually in Plesk"

echo "=== Nuxt 3 Deployment Completed at $(date) ==="
```

**⚠️ ตั้งค่า Node.js Startup File ใน Plesk:**
```
Application startup file: ui/.output/server/index.mjs
```

**⚠️ หมายเหตุ:** การ build บน server ต้องมี:
- RAM เพียงพอ (อย่างน้อย 2GB)
- Node.js และ npm ติดตั้งแล้ว
- อาจใช้เวลานานในการ build

**วิธีที่ 3: ใช้ GitHub Actions Build แล้ว Deploy (แนะนำสำหรับ Production)**

ดูตัวอย่างใน Section 6.10 CI/CD Pipeline

#### 6.2.3 ตั้งค่า Webhook สำหรับ Auto-Deploy

1. คัดลอก **Webhook URL** จาก Plesk Git settings
2. เพิ่มใน GitHub Repository → Settings → Webhooks
3. ตั้งค่าให้ trigger เฉพาะเมื่อมีการเปลี่ยนแปลงใน `ui/` folder:
   - ใช้ GitHub Actions แทน direct webhook
   - หรือใช้ branch strategy (เช่น `deploy/frontend`)

### 6.3 สร้างไฟล์ Deploy Package (วิธี Manual)

```bash
# สร้าง zip สำหรับอัปโหลด
cd ui
zip -r nuxnan-ui.zip .output .env ecosystem.config.cjs

# หรือใช้ tar
tar -czvf nuxnan-ui.tar.gz .output .env ecosystem.config.cjs
```

**ไฟล์ที่ต้องมีใน package:**
```
nuxnan-ui.zip
├── .output/
│   ├── public/
│   │   ├── _nuxt/           # JavaScript/CSS bundles
│   │   ├── favicon.ico
│   │   └── ...
│   └── server/
│       ├── index.mjs        # Entry point
│       ├── chunks/
│       └── ...
├── .env                     # Environment variables
└── ecosystem.config.cjs     # PM2 config (optional)
```

### 6.3 อัปโหลดไฟล์ Frontend

**วิธีที่ 1: ผ่าน SSH + SCP (แนะนำ)**
```bash
# จากเครื่อง local
scp nuxnan-ui.zip user@your-server-ip:/var/www/vhosts/nuxnan.com/httpdocs/

# บน server
ssh user@your-server-ip
cd /var/www/vhosts/nuxnan.com/httpdocs

# ลบไฟล์เดิม (ถ้ามี)
rm -rf .output .env ecosystem.config.cjs

# Extract
unzip nuxnan-ui.zip
rm nuxnan-ui.zip

# ตรวจสอบโครงสร้าง
ls -la
# ควรมี .output/, .env, ecosystem.config.cjs
```

**วิธีที่ 2: ผ่าน File Manager (Plesk UI)**
1. ไปที่ **Websites & Domains** → คลิก **File Manager** ของ `nuxnan.com`
2. นำทางไปที่ `httpdocs`
3. อัปโหลดไฟล์ `nuxnan-ui.zip`
4. คลิกขวา → **Extract Files**
5. ลบไฟล์ zip

### 6.4 ตั้งค่า Node.js บน Plesk

1. ไปที่ **Websites & Domains** → คลิก **Node.js** ของ `nuxnan.com`
2. คลิก **Enable Node.js**
3. กรอกข้อมูล:

| Setting | Value | Description |
|---------|-------|-------------|
| **Node.js version** | 20.x LTS | หรือ 18.x LTS |
| **Package manager** | npm | ใช้ npm |
| **Document root** | `/httpdocs` | Web root |
| **Application root** | `/httpdocs` | App root |
| **Application startup file** | `.output/server/index.mjs` | Entry point |
| **Application mode** | `Production` | Production mode |

4. คลิก **Enable Node.js**

### 6.5 ตั้งค่า Environment Variables

**วิธีที่ 1: ผ่าน Plesk Node.js Settings (แนะนำ)**
1. ในหน้า Node.js settings
2. Scroll ลงไปที่ **Environment Variables**
3. เพิ่ม variables ทีละตัว:

| Variable | Value |
|----------|-------|
| `NUXT_PUBLIC_API_BASE` | `https://api.nuxnan.com` |
| `NUXT_PUBLIC_SITE_URL` | `https://www.nuxnan.com` |
| `NODE_ENV` | `production` |
| `NITRO_PORT` | `3000` |
| `NITRO_HOST` | `127.0.0.1` |

4. คลิก **Apply** หลังเพิ่มแต่ละตัว

**วิธีที่ 2: ใช้ไฟล์ .env**
```bash
# สร้างหรือแก้ไข .env ใน httpdocs
cd /var/www/vhosts/nuxnan.com/httpdocs
nano .env
```

```env
# API Configuration
NUXT_PUBLIC_API_BASE=https://api.nuxnan.com
NUXT_PUBLIC_SITE_URL=https://www.nuxnan.com

# Node Configuration
NODE_ENV=production
NITRO_PORT=3000
NITRO_HOST=127.0.0.1
```

### 6.6 ตั้งค่า Nginx Reverse Proxy

Plesk จะตั้งค่า Nginx proxy ให้อัตโนมัติ แต่ถ้าต้องการปรับแต่งเพิ่ม:

1. ไปที่ **Websites & Domains** → **Apache & nginx Settings**
2. ในส่วน **Additional nginx directives** เพิ่ม:

```nginx
# Proxy to Node.js
location / {
    proxy_pass http://127.0.0.1:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_cache_bypass $http_upgrade;
    
    # Timeout settings
    proxy_connect_timeout 60s;
    proxy_send_timeout 60s;
    proxy_read_timeout 60s;
}

# Static files caching
location /_nuxt/ {
    alias /var/www/vhosts/nuxnan.com/httpdocs/.output/public/_nuxt/;
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}

# Public static files
location /favicon.ico {
    alias /var/www/vhosts/nuxnan.com/httpdocs/.output/public/favicon.ico;
    expires 30d;
    access_log off;
}
```

### 6.7 เริ่มต้น Application

**ผ่าน Plesk UI:**
1. ไปที่ **Node.js** settings
2. คลิก **Restart Application**
3. ตรวจสอบ status: ควรแสดง "Running"

**ตรวจสอบว่า application ทำงาน:**
```bash
# ผ่าน SSH
curl -I http://127.0.0.1:3000

# ควรได้ HTTP 200
```

### 6.8 ตั้งค่า PM2 (Optional - สำหรับ Advanced Deployment)

ถ้าต้องการควบคุม Node.js process ด้วย PM2:

**ไฟล์ `ecosystem.config.cjs`:**
```javascript
module.exports = {
  apps: [
    {
      name: 'nuxnan-ui',
      port: 3000,
      exec_mode: 'cluster',
      instances: 'max',          // ใช้ทุก CPU cores
      script: './.output/server/index.mjs',
      
      // Environment
      env_production: {
        NODE_ENV: 'production',
        NUXT_PUBLIC_API_BASE: 'https://api.nuxnan.com',
        NUXT_PUBLIC_SITE_URL: 'https://www.nuxnan.com',
      },
      
      // Logging
      error_file: './logs/pm2-error.log',
      out_file: './logs/pm2-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      
      // Restart policy
      max_memory_restart: '500M',
      restart_delay: 4000,
      
      // Watch (disable in production)
      watch: false,
    }
  ]
}
```

**การใช้งาน PM2:**
```bash
# ติดตั้ง PM2 globally
npm install -g pm2

# เริ่มต้น application
cd /var/www/vhosts/nuxnan.com/httpdocs
pm2 start ecosystem.config.cjs --env production

# ตรวจสอบ status
pm2 status

# ดู logs
pm2 logs nuxnan-ui

# Restart
pm2 restart nuxnan-ui

# Save และตั้งค่า auto-start
pm2 save
pm2 startup
```

### 6.9 ทดสอบ Frontend

```bash
# ทดสอบจาก server
curl -I https://www.nuxnan.com

# ควรได้ HTTP 200

# ทดสอบ static assets
curl -I https://www.nuxnan.com/_nuxt/entry.xxxxx.js

# ตรวจสอบ logs
# ถ้าใช้ Plesk Node.js
# ไปที่ Node.js settings → View Log

# ถ้าใช้ PM2
pm2 logs nuxnan-ui --lines 100
```

### 6.10 CI/CD Pipeline (Optional)

**ตัวอย่าง GitHub Actions Workflow (`.github/workflows/deploy.yml`):**

```yaml
name: Deploy to Production

on:
  push:
    branches:
      - main
    paths:
      - 'ui/**'

jobs:
  build-and-deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: ui/package-lock.json
      
      - name: Install dependencies
        working-directory: ./ui
        run: npm ci
      
      - name: Create .env file
        working-directory: ./ui
        run: |
          echo "NUXT_PUBLIC_API_BASE=${{ secrets.API_BASE }}" >> .env
          echo "NUXT_PUBLIC_SITE_URL=${{ secrets.SITE_URL }}" >> .env
      
      - name: Build
        working-directory: ./ui
        run: npm run build
      
      - name: Create deployment package
        working-directory: ./ui
        run: |
          tar -czvf ../deploy.tar.gz .output .env ecosystem.config.cjs
      
      - name: Deploy to server
        uses: appleboy/scp-action@master
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          source: "deploy.tar.gz"
          target: "/var/www/vhosts/nuxnan.com/httpdocs/"
      
      - name: Extract and restart
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/vhosts/nuxnan.com/httpdocs
            tar -xzvf deploy.tar.gz
            rm deploy.tar.gz
            # Restart Node.js via Plesk CLI
            plesk ext nodejs --restart -domain nuxnan.com
```

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

### 9.1 ตั้งค่า Laravel Scheduler

Laravel Scheduler ใช้สำหรับรัน scheduled tasks ต่างๆ เช่น cleanup, notifications, reports

**ผ่าน Plesk Scheduled Tasks:**
1. ไปที่ **Scheduled Tasks**
2. คลิก **Add Task**
3. กรอกข้อมูล:
   - **Task type:** Run a command
   - **Command:** 
     ```bash
     cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel && /opt/plesk/php/8.3/bin/php artisan schedule:run >> /dev/null 2>&1
     ```
   - **Run:** Every minute (Cron: `* * * * *`)
   - **Run as:** ใส่ username ของ subscription
   - **Description:** Laravel Scheduler
4. คลิก **OK**

**หรือผ่าน SSH (เพิ่มใน crontab):**
```bash
# เปิด crontab editor
crontab -e

# เพิ่มบรรทัดนี้
* * * * * cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel && /opt/plesk/php/8.3/bin/php artisan schedule:run >> /dev/null 2>&1
```

### 9.2 ตั้งค่า Queue Worker

สำหรับ Background Jobs เช่น email sending, image processing

**วิธีที่ 1: ใช้ Supervisor (แนะนำสำหรับ Production)**

```bash
# ติดตั้ง Supervisor
sudo apt install supervisor

# สร้าง config file
sudo nano /etc/supervisor/conf.d/nuxnan-worker.conf
```

**ไฟล์ `/etc/supervisor/conf.d/nuxnan-worker.conf`:**
```ini
[program:nuxnan-worker]
process_name=%(program_name)s_%(process_num)02d
command=/opt/plesk/php/8.3/bin/php /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=your_plesk_user
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/vhosts/nuxnan.com/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# ตรวจสอบ status
sudo supervisorctl status nuxnan-worker:*

# Restart workers
sudo supervisorctl restart nuxnan-worker:*
```

**วิธีที่ 2: ใช้ Scheduled Task (Simple)**
1. ไปที่ **Scheduled Tasks**
2. คลิก **Add Task**
3. กรอกข้อมูล:
   - **Task type:** Run a command
   - **Command:** 
     ```bash
     cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel && /opt/plesk/php/8.3/bin/php artisan queue:work --stop-when-empty --sleep=3 --tries=3 --max-jobs=100
     ```
   - **Run:** Every minute
   - **Run as:** ใส่ username ของ subscription
   - **Description:** Laravel Queue Worker
4. คลิก **OK**

### 9.3 ตั้งค่า Cache Cleanup

```bash
# เพิ่ม Scheduled Task สำหรับ cleanup

# Clear expired tokens (ทุกวันเวลา 03:00)
0 3 * * * cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel && /opt/plesk/php/8.3/bin/php artisan auth:clear-resets >> /var/log/laravel-cleanup.log 2>&1

# Prune old telescope entries (ถ้าใช้ Telescope)
0 4 * * * cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel && /opt/plesk/php/8.3/bin/php artisan telescope:prune --hours=48 >> /var/log/laravel-cleanup.log 2>&1

# Clear old sessions (ทุกสัปดาห์)
0 5 * * 0 cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel && /opt/plesk/php/8.3/bin/php artisan session:gc >> /var/log/laravel-cleanup.log 2>&1
```

### 9.4 ตั้งค่า Auto-Renew SSL

Let's Encrypt certificates หมดอายุทุก 90 วัน Plesk จะ auto-renew แต่ควรมี monitoring

```bash
# ตรวจสอบ SSL expiry (ทุกวัน)
0 8 * * * /usr/local/psa/bin/certificate --list | grep -E "expires|domain" >> /var/log/ssl-status.log

# หรือใช้ certbot (ถ้าติดตั้งแยก)
0 0 1 * * /usr/bin/certbot renew --quiet
```

### 9.5 ตั้งค่า Database Optimization

```bash
# Optimize database tables (ทุกสัปดาห์)
0 2 * * 0 mysqlcheck -o -u nuxnan_user -p'password' nuxnan_db >> /var/log/mysql-optimize.log 2>&1

# Backup database (ทุกวันเวลา 02:00)
0 2 * * * /home/scripts/db_backup.sh >> /var/log/db_backup.log 2>&1
```

### 9.6 Cron Jobs Summary Table

| Task | Command | Schedule | Description |
|------|---------|----------|-------------|
| Laravel Scheduler | `php artisan schedule:run` | Every minute | Run scheduled tasks |
| Queue Worker | `php artisan queue:work` | Every minute | Process background jobs |
| Clear Resets | `php artisan auth:clear-resets` | Daily 03:00 | Clear expired password resets |
| Session Cleanup | `php artisan session:gc` | Weekly | Remove old sessions |
| DB Backup | `/home/scripts/db_backup.sh` | Daily 02:00 | Backup database |
| DB Optimize | `mysqlcheck -o` | Weekly | Optimize tables |
| Log Rotation | `logrotate` | Daily | Rotate log files |

### 9.7 Log Rotation

**สร้างไฟล์ `/etc/logrotate.d/nuxnan`:**
```
/var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        /usr/bin/pkill -USR1 php-fpm
    endscript
}

/var/www/vhosts/nuxnan.com/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
}
```

---

## 10. การแก้ปัญหาและ Troubleshooting

### 10.1 HTTP Error Codes Reference

| Error Code | ความหมาย | สาเหตุที่เป็นไปได้ | วิธีแก้ไข |
|------------|---------|-------------------|----------|
| **400** | Bad Request | Invalid request format | ตรวจสอบ request body/params |
| **401** | Unauthorized | Token หมดอายุ/ไม่มี | ตรวจสอบ JWT token |
| **403** | Forbidden | Permission denied | ตรวจสอบ permissions |
| **404** | Not Found | Route/file ไม่มี | ตรวจสอบ URL, .htaccess |
| **405** | Method Not Allowed | HTTP method ผิด | ตรวจสอบ GET/POST/PUT/DELETE |
| **419** | Page Expired | CSRF token หมดอายุ | Refresh page |
| **422** | Validation Error | Data validation failed | ตรวจสอบ input data |
| **429** | Too Many Requests | Rate limit exceeded | รอสักครู่แล้วลองใหม่ |
| **500** | Internal Server Error | Server/code error | ดู Laravel logs |
| **502** | Bad Gateway | Node.js ไม่ทำงาน | Restart Node.js app |
| **503** | Service Unavailable | Server overload | ตรวจสอบ resources |
| **504** | Gateway Timeout | Request timeout | เพิ่ม timeout values |

### 10.2 500 Internal Server Error (API)

**สาเหตุที่เป็นไปได้:**
1. Permissions ไม่ถูกต้อง
2. .env ไม่มีหรือตั้งค่าผิด
3. PHP extensions ไม่ครบ
4. Database connection ผิด
5. Composer dependencies ไม่ครบ

**ขั้นตอนการ Debug:**

```bash
# 1. เปิด debug mode ชั่วคราว (อย่าลืมปิดหลังแก้ไข!)
cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel
sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env
php artisan config:clear

# 2. ดู error ใน browser แล้วจด error message

# 3. ตรวจสอบ Laravel log
tail -100 storage/logs/laravel.log

# 4. ตรวจสอบ PHP error log
tail -100 /var/log/php-fpm/error.log

# 5. ปิด debug mode กลับ
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
php artisan config:cache
```

**ตรวจสอบ Permissions:**
```bash
cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel

# แสดง permissions
ls -la storage bootstrap/cache

# ต้องเป็น:
# drwxrwxr-x  user psacln storage
# drwxrwxr-x  user psacln bootstrap/cache

# แก้ไข permissions
chmod -R 775 storage bootstrap/cache
chown -R $(whoami):psacln storage bootstrap/cache
```

**ตรวจสอบ .env:**
```bash
# ตรวจสอบว่าไฟล์มีอยู่
ls -la .env

# ตรวจสอบเนื้อหา
cat .env | grep -E "APP_KEY|DB_|APP_DEBUG"

# APP_KEY ต้องมีค่า (ไม่ใช่ว่างเปล่า)
# APP_DEBUG ต้องเป็น false
# DB_* ต้องถูกต้อง
```

**ตรวจสอบ PHP Extensions:**
```bash
/opt/plesk/php/8.3/bin/php -m | grep -E "bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pdo_mysql|tokenizer|xml|zip"
```

### 10.3 CORS Error

**อาการ:** Frontend ไม่สามารถเรียก API ได้ แสดง error ใน console:
```
Access to XMLHttpRequest at 'https://api.nuxnan.com/api/...' from origin 'https://www.nuxnan.com' has been blocked by CORS policy
```

**วิธีแก้:**

**Step 1: ตรวจสอบ config/cors.php:**
```php
<?php
return [
    'paths' => ['api/*', 'auth/*', 'courses/*', 'users/*', 'profiles/*', 'attendances/*', 'storage/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'https://www.nuxnan.com',
        'https://nuxnan.com',
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => ['Authorization'],
    
    'max_age' => 86400,
    
    'supports_credentials' => true,
];
```

**Step 2: ตรวจสอบ .env:**
```env
FRONTEND_URL=https://www.nuxnan.com
SANCTUM_STATEFUL_DOMAINS=www.nuxnan.com,nuxnan.com
```

**Step 3: Clear cache:**
```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
```

**Step 4: ตรวจสอบ Nginx:**
ถ้า Nginx ก็ส่ง CORS headers อาจ conflict กัน:
```nginx
# ลบหรือ comment out CORS headers ใน nginx config ถ้ามี
# add_header Access-Control-Allow-Origin ...
```

### 10.4 404 Not Found (API Routes)

**สาเหตุ:** Apache/Nginx ไม่สามารถ route requests ไปยัง Laravel ได้

**ตรวจสอบ .htaccess:**
```bash
cat public/.htaccess
```

**ไฟล์ `.htaccess` ที่ถูกต้อง:**
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

**ตรวจสอบว่า routes ถูก cache:**
```bash
php artisan route:list | grep "api"

# ถ้าไม่แสดง routes ให้ clear cache
php artisan route:clear
php artisan route:cache
```

### 10.5 Nuxt Hydration Mismatch

**อาการ:** Warning ใน console ว่า hydration mismatch

**สาเหตุ:** ข้อมูลที่ server render ไม่ตรงกับ client

**วิธีแก้:**

```bash
# 1. ตรวจสอบ environment variables
cd /var/www/vhosts/nuxnan.com/httpdocs
cat .env

# ต้องมี:
# NUXT_PUBLIC_API_BASE=https://api.nuxnan.com
# NUXT_PUBLIC_SITE_URL=https://www.nuxnan.com

# 2. Restart Node.js application
# ผ่าน Plesk: Node.js → Restart Application

# 3. ถ้ายังไม่ได้ ลองปิด SSR ชั่วคราวเพื่อทดสอบ
# ใน nuxt.config.ts:
# export default defineNuxtConfig({
#   ssr: false,
# })
# แล้ว rebuild
```

### 10.6 Node.js Application ไม่ทำงาน (502 Bad Gateway)

**ตรวจสอบ Node.js Status:**

```bash
# ตรวจสอบว่า process ทำงานอยู่
ps aux | grep node

# ตรวจสอบ port
netstat -tlnp | grep 3000

# ลอง run manual
cd /var/www/vhosts/nuxnan.com/httpdocs
node .output/server/index.mjs
```

**ตรวจสอบ Log:**
1. ไปที่ **Websites & Domains** → **Node.js** → **View Log**
2. หรือผ่าน SSH:
```bash
# ถ้าใช้ PM2
pm2 logs nuxnan-ui --lines 100

# ถ้าใช้ Plesk Node.js
cat /var/www/vhosts/nuxnan.com/logs/nodejs.log
```

**Common Errors:**

| Error | สาเหตุ | วิธีแก้ |
|-------|-------|--------|
| `Cannot find module` | Missing dependencies | `npm install` |
| `EADDRINUSE` | Port 3000 ถูกใช้แล้ว | Kill process เดิม |
| `ENOENT: .output/server/index.mjs` | ไม่มีไฟล์ build | Rebuild และ upload ใหม่ |
| `EACCES: permission denied` | Permission error | แก้ไข file permissions |

### 10.7 Database Connection Failed

**ตรวจสอบ:**

```bash
# 1. ทดสอบ connection ด้วย mysql client
mysql -u nuxnan_user -p nuxnan_db -e "SELECT 1"

# 2. ตรวจสอบ .env
cat .env | grep DB_

# 3. ทดสอบผ่าน Laravel
php artisan tinker
>>> DB::connection()->getPdo()

# 4. ตรวจสอบ MySQL status
systemctl status mysql

# 5. ดู MySQL error log
tail -50 /var/log/mysql/error.log
```

**Common Database Errors:**

| Error | สาเหตุ | วิธีแก้ |
|-------|-------|--------|
| `SQLSTATE[HY000] [1045] Access denied` | Password ผิด | ตรวจสอบ DB_PASSWORD |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL ไม่ทำงาน | `systemctl start mysql` |
| `SQLSTATE[42S02] Table not found` | ยังไม่ได้ migrate | `php artisan migrate` |
| `SQLSTATE[22001] Data too long` | ข้อมูลยาวเกิน column | ตรวจสอบ column length |

### 10.8 File Upload Errors

**ตรวจสอบ PHP Configuration:**
```bash
# ดูค่า PHP settings
php -i | grep -E "upload_max_filesize|post_max_size|max_file_uploads"

# ควรได้:
# upload_max_filesize = 50M
# post_max_size = 50M
# max_file_uploads = 20
```

**ตรวจสอบ Storage Link:**
```bash
# ตรวจสอบ symlink
ls -la public/storage

# ควรได้:
# lrwxrwxrwx storage -> /var/www/.../storage/app/public

# ถ้าไม่มี ให้สร้างใหม่
php artisan storage:link
```

**ตรวจสอบ Permissions:**
```bash
ls -la storage/app/public
chmod -R 775 storage/app/public
```

### 10.9 JWT Token Errors

**Common JWT Errors:**

| Error | สาเหตุ | วิธีแก้ |
|-------|-------|--------|
| `Token not provided` | ไม่มี token ใน header | ส่ง `Authorization: Bearer <token>` |
| `Token has expired` | Token หมดอายุ | Refresh token หรือ login ใหม่ |
| `Token signature could not be verified` | JWT_SECRET ไม่ตรง | ตรวจสอบ JWT_SECRET ใน .env |
| `User not found` | User ถูกลบแล้ว | Login ใหม่ |

**วิธีแก้:**
```bash
# Regenerate JWT secret
php artisan jwt:secret --force

# Clear config cache
php artisan config:clear
php artisan config:cache
```

### 10.10 Performance Issues

**ตรวจสอบ Server Resources:**
```bash
# CPU และ Memory usage
htop

# Disk usage
df -h

# MySQL processes
mysqladmin -u root -p processlist
```

**Laravel Optimization:**
```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize autoloader
composer dump-autoload --optimize

# Clear old sessions
php artisan session:gc
```

**Nginx Optimization:**
```nginx
# เพิ่มใน nginx config
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_proxied expired no-cache no-store private auth;
gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;

# Static file caching
location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### 10.11 Quick Diagnostic Commands

```bash
#!/bin/bash
# save as: /home/scripts/diagnose.sh

echo "=== Server Status ==="
free -h
df -h
uptime

echo "=== MySQL Status ==="
systemctl status mysql --no-pager

echo "=== PHP Status ==="
systemctl status php8.3-fpm --no-pager

echo "=== Node.js Process ==="
ps aux | grep node

echo "=== Port Status ==="
netstat -tlnp | grep -E "80|443|3000|3306"

echo "=== Laravel Log (last 20 lines) ==="
tail -20 /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel/storage/logs/laravel.log

echo "=== Nginx Error Log (last 10 lines) ==="
tail -10 /var/log/nginx/error.log
```

### 10.12 Git Deployment Issues (Plesk Git)

**ปัญหาที่พบบ่อยเมื่อใช้ Git ผ่าน Plesk:**

#### ปัญหา: Authentication Failed

```
fatal: Authentication failed for 'https://github.com/...'
```

**วิธีแก้:**
1. ใช้ **Personal Access Token (PAT)** แทน password:
   - ไปที่ GitHub → Settings → Developer settings → Personal access tokens
   - สร้าง token ใหม่ด้วย scope: `repo`
   - ใช้ token เป็น password ใน Plesk Git settings

2. สำหรับ Private Repository:
   ```
   URL: https://github.com/your-username/nuxnan.git
   Username: your-github-username
   Password: ghp_xxxxxxxxxxxxxxxxxxxx  ← Personal Access Token
   ```

#### ปัญหา: Permission Denied หลัง Pull

```
error: cannot open .git/FETCH_HEAD: Permission denied
```

**วิธีแก้:**
```bash
# แก้ไข ownership
cd /var/www/vhosts/nuxnan.com/httpdocs
chown -R your-plesk-user:psacln .
chmod -R 755 .git
```

#### ปัญหา: Composer/npm ไม่ทำงานใน Deploy Script

**วิธีแก้:**
ใช้ full path ใน script:
```bash
# แทนที่จะใช้
composer install

# ใช้
/usr/local/bin/composer install

# สำหรับ PHP
/opt/plesk/php/8.3/bin/php artisan ...

# สำหรับ npm
/usr/bin/npm install
# หรือ
/opt/plesk/node/20/bin/npm install
```

#### ปัญหา: Deploy Script ไม่ทำงาน

**ตรวจสอบ:**
1. ดู deployment log ใน Plesk Git settings
2. ตรวจสอบว่า script มี execute permission:
   ```bash
   chmod +x /path/to/deploy-script.sh
   ```
3. ตรวจสอบ syntax ของ script:
   ```bash
   bash -n /path/to/deploy-script.sh
   ```

#### ปัญหา: Webhook ไม่ทำงาน

**ตรวจสอบ:**
1. ไปที่ GitHub → Settings → Webhooks → Recent Deliveries
2. ดูว่ามี error อะไร
3. ตรวจสอบว่า Webhook URL ถูกต้อง
4. ตรวจสอบว่า SSL certificate valid

**Common Webhook Errors:**

| Error | สาเหตุ | วิธีแก้ |
|-------|-------|--------|
| `SSL certificate problem` | SSL ไม่ถูกต้อง | ติดตั้ง SSL ใน Plesk |
| `Connection timed out` | Firewall block | เปิด port 443 |
| `404 Not Found` | URL ผิด | ตรวจสอบ Webhook URL |
| `401 Unauthorized` | Token หมดอายุ | สร้าง Webhook URL ใหม่ |

#### ปัญหา: Git Pull ช้ามาก

**วิธีแก้:**
```bash
# ใช้ shallow clone
git clone --depth 1 https://github.com/...

# หรือใน Plesk ให้ลบ repo แล้ว clone ใหม่ด้วย shallow
```

#### ปัญหา: Merge Conflicts

**วิธีแก้:**
```bash
cd /var/www/vhosts/nuxnan.com/httpdocs

# Reset to remote
git fetch origin
git reset --hard origin/main

# หรือ stash local changes
git stash
git pull
git stash pop  # ถ้าต้องการ local changes กลับ
```

### 10.13 Git Best Practices สำหรับ Plesk

1. **ใช้ Branch Strategy:**
   ```
   main         → Production (auto-deploy)
   develop      → Staging
   feature/*    → Development
   ```

2. **อย่า commit sensitive data:**
   - ใช้ `.gitignore` สำหรับ `.env`
   - สร้าง `.env` บน server แยก

3. **ใช้ .gitignore ที่เหมาะสม:**
   ```gitignore
   # Laravel
   /vendor/
   /node_modules/
   .env
   storage/*.key
   
   # Nuxt
   .output/
   .nuxt/
   node_modules/
   ```

4. **ตั้งค่า Deploy Actions อย่างระมัดระวัง:**
   - อย่ารัน `migrate --force` อัตโนมัติถ้าไม่แน่ใจ
   - ใส่ error handling ใน script
   - Log ผลลัพธ์ทุกครั้ง

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

---

## 📜 Appendix: Deployment Scripts

### A.1 Backend Deploy Script

สร้างไฟล์ `/home/scripts/deploy-backend.sh`:

```bash
#!/bin/bash
#==============================================================================
# Nuxnan Backend Deployment Script
# Usage: ./deploy-backend.sh [--migrate] [--seed]
#==============================================================================

set -e

# Configuration
APP_DIR="/var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel"
PHP_BIN="/opt/plesk/php/8.3/bin/php"
COMPOSER_BIN="/usr/local/bin/composer"
BACKUP_DIR="/var/www/vhosts/nuxnan.com/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Parse arguments
RUN_MIGRATE=false
RUN_SEED=false
for arg in "$@"; do
    case $arg in
        --migrate) RUN_MIGRATE=true ;;
        --seed) RUN_SEED=true ;;
    esac
done

cd "$APP_DIR"

log_info "Starting deployment at $TIMESTAMP"

# 1. Put application in maintenance mode
log_info "Enabling maintenance mode..."
$PHP_BIN artisan down --render="errors.503" --retry=60

# 2. Backup current state
log_info "Creating backup..."
mkdir -p "$BACKUP_DIR"
mysqldump -u nuxnan_user -p'password' nuxnan_db | gzip > "$BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"

# 3. Pull latest code (if using Git)
# log_info "Pulling latest code..."
# git pull origin main

# 4. Install/update dependencies
log_info "Installing dependencies..."
$COMPOSER_BIN install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 5. Run migrations (if requested)
if [ "$RUN_MIGRATE" = true ]; then
    log_info "Running migrations..."
    $PHP_BIN artisan migrate --force
fi

# 6. Run seeders (if requested)
if [ "$RUN_SEED" = true ]; then
    log_info "Running seeders..."
    $PHP_BIN artisan db:seed --force
fi

# 7. Clear and cache configs
log_info "Optimizing application..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache

# 8. Restart queue workers
log_info "Restarting queue workers..."
$PHP_BIN artisan queue:restart

# 9. Bring application back online
log_info "Disabling maintenance mode..."
$PHP_BIN artisan up

log_info "Deployment completed successfully!"
log_info "Backup saved to: $BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"
```

### A.2 Frontend Deploy Script

สร้างไฟล์ `/home/scripts/deploy-frontend.sh`:

```bash
#!/bin/bash
#==============================================================================
# Nuxnan Frontend Deployment Script
# Usage: ./deploy-frontend.sh <package.zip>
#==============================================================================

set -e

# Configuration
APP_DIR="/var/www/vhosts/nuxnan.com/httpdocs"
BACKUP_DIR="/var/www/vhosts/nuxnan.com/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# Check arguments
if [ -z "$1" ]; then
    log_error "Usage: $0 <package.zip>"
fi

PACKAGE="$1"

if [ ! -f "$PACKAGE" ]; then
    log_error "Package not found: $PACKAGE"
fi

cd "$APP_DIR"

log_info "Starting frontend deployment at $TIMESTAMP"

# 1. Backup current deployment
log_info "Backing up current deployment..."
mkdir -p "$BACKUP_DIR"
if [ -d ".output" ]; then
    tar -czvf "$BACKUP_DIR/frontend_backup_$TIMESTAMP.tar.gz" .output .env 2>/dev/null || true
fi

# 2. Extract new package
log_info "Extracting new package..."
rm -rf .output.new
unzip -q "$PACKAGE" -d .output.new

# 3. Swap directories
log_info "Swapping directories..."
if [ -d ".output" ]; then
    mv .output .output.old
fi
mv .output.new/.output .output

# 4. Copy environment file if not exists
if [ -f ".output.new/.env" ] && [ ! -f ".env" ]; then
    cp .output.new/.env .env
fi

# 5. Restart Node.js application
log_info "Restarting Node.js application..."
/usr/local/psa/bin/nodejs restart -domain nuxnan.com 2>/dev/null || \
    pm2 restart nuxnan-ui 2>/dev/null || \
    log_warn "Could not restart Node.js automatically. Please restart manually."

# 6. Cleanup
log_info "Cleaning up..."
rm -rf .output.old .output.new
rm -f "$PACKAGE"

log_info "Frontend deployment completed successfully!"
```

### A.3 Health Check Script

สร้างไฟล์ `/home/scripts/health-check.sh`:

```bash
#!/bin/bash
#==============================================================================
# Nuxnan Health Check Script
# Usage: ./health-check.sh [--notify]
#==============================================================================

# Configuration
API_URL="https://api.nuxnan.com/api/health"
FRONTEND_URL="https://www.nuxnan.com"
SLACK_WEBHOOK=""  # Add your Slack webhook URL

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0
REPORT=""

check_service() {
    local name=$1
    local url=$2
    local expected_code=${3:-200}
    
    response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$url")
    
    if [ "$response" = "$expected_code" ]; then
        echo -e "${GREEN}✓${NC} $name: OK ($response)"
        REPORT+="✓ $name: OK\n"
    else
        echo -e "${RED}✗${NC} $name: FAILED (got $response, expected $expected_code)"
        REPORT+="✗ $name: FAILED (HTTP $response)\n"
        ((ERRORS++))
    fi
}

check_mysql() {
    if mysqladmin -u nuxnan_user -p'password' ping 2>/dev/null | grep -q "alive"; then
        echo -e "${GREEN}✓${NC} MySQL: OK"
        REPORT+="✓ MySQL: OK\n"
    else
        echo -e "${RED}✗${NC} MySQL: FAILED"
        REPORT+="✗ MySQL: FAILED\n"
        ((ERRORS++))
    fi
}

check_disk() {
    usage=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
    if [ "$usage" -lt 80 ]; then
        echo -e "${GREEN}✓${NC} Disk Usage: ${usage}%"
        REPORT+="✓ Disk: ${usage}%\n"
    else
        echo -e "${RED}✗${NC} Disk Usage: ${usage}% (HIGH)"
        REPORT+="✗ Disk: ${usage}% (HIGH)\n"
        ((ERRORS++))
    fi
}

check_memory() {
    usage=$(free | awk 'NR==2 {printf "%.0f", $3*100/$2}')
    if [ "$usage" -lt 90 ]; then
        echo -e "${GREEN}✓${NC} Memory Usage: ${usage}%"
        REPORT+="✓ Memory: ${usage}%\n"
    else
        echo -e "${YELLOW}⚠${NC} Memory Usage: ${usage}% (HIGH)"
        REPORT+="⚠ Memory: ${usage}% (HIGH)\n"
    fi
}

echo "=== Nuxnan Health Check ==="
echo "Timestamp: $(date)"
echo ""

check_service "Frontend" "$FRONTEND_URL"
check_service "API" "$API_URL"
check_mysql
check_disk
check_memory

echo ""
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}All checks passed!${NC}"
else
    echo -e "${RED}$ERRORS check(s) failed!${NC}"
    
    # Send notification if requested
    if [ "$1" = "--notify" ] && [ -n "$SLACK_WEBHOOK" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"🚨 Nuxnan Health Check Failed!\n$REPORT\"}" \
            "$SLACK_WEBHOOK"
    fi
fi

exit $ERRORS
```

### A.4 Rollback Script

สร้างไฟล์ `/home/scripts/rollback.sh`:

```bash
#!/bin/bash
#==============================================================================
# Nuxnan Rollback Script
# Usage: ./rollback.sh <backup_file>
#==============================================================================

set -e

BACKUP_DIR="/var/www/vhosts/nuxnan.com/backups"
API_DIR="/var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel"
PHP_BIN="/opt/plesk/php/8.3/bin/php"

if [ -z "$1" ]; then
    echo "Available backups:"
    ls -la "$BACKUP_DIR"
    echo ""
    echo "Usage: $0 <backup_file.sql.gz>"
    exit 1
fi

BACKUP_FILE="$BACKUP_DIR/$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "⚠️  WARNING: This will restore the database from: $1"
echo "All current data will be lost!"
read -p "Are you sure? (type 'yes' to confirm): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Rollback cancelled."
    exit 0
fi

cd "$API_DIR"

# Put in maintenance mode
$PHP_BIN artisan down

# Restore database
echo "Restoring database..."
gunzip -c "$BACKUP_FILE" | mysql -u nuxnan_user -p'password' nuxnan_db

# Clear caches
$PHP_BIN artisan cache:clear
$PHP_BIN artisan config:cache

# Bring back online
$PHP_BIN artisan up

echo "Rollback completed successfully!"
```

---

## 📞 การขอความช่วยเหลือ

หากพบปัญหาที่ไม่สามารถแก้ไขได้:

### Log Files Location

| Log | Path |
|-----|------|
| Laravel | `/var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel/storage/logs/laravel.log` |
| PHP-FPM | `/var/log/php-fpm/error.log` |
| Nginx | `/var/log/nginx/error.log` |
| Apache | `/var/log/apache2/error.log` |
| MySQL | `/var/log/mysql/error.log` |
| Node.js | Check Plesk Node.js → View Log |
| PM2 | `~/.pm2/logs/` |

### Quick Debug Commands

```bash
# ดู Laravel logs แบบ realtime
tail -f /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel/storage/logs/laravel.log

# ดู error logs รวม
tail -f /var/log/nginx/error.log /var/log/php-fpm/error.log

# ตรวจสอบ services
systemctl status nginx php8.3-fpm mysql

# ตรวจสอบ ports
netstat -tlnp | grep -E "80|443|3000|3306"
```

### ติดต่อ Support

1. **Plesk Logs:** Tools & Settings → Log Manager
2. **Plesk Support:** https://support.plesk.com
3. **Laravel Community:** https://laracasts.com/discuss
4. **Nuxt Community:** https://github.com/nuxt/nuxt/discussions

---

**ขอให้โชคดีกับการ Deploy! 🚀**
