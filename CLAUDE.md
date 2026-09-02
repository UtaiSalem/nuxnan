# CLAUDE.md — nuxnan project

ไฟล์นี้คือคู่มือให้ Claude (และ subagents) เข้าใจโปรเจคนี้ก่อนเริ่มทำงาน อ่านไฟล์นี้ก่อนเสมอ

## Work Log (Context ข้ามที่ทำงาน)

**อ่านก่อนเริ่มทุกครั้ง:** [`.agents/worklog.md`](.agents/worklog.md)
ไฟล์นี้บันทึกงานที่ค้างอยู่และ context สำคัญที่ส่งต่อระหว่างที่บ้านและที่ทำงาน
ก่อนออกจากแต่ละที่ → อัพเดท worklog แล้ว `git push` เสมอ

## Keyword Workflows

### "อ่านบทวิเคราะห์"
เมื่อผู้ใช้พูดว่า "อ่านบทวิเคราะห์" ให้ทำตามขั้นตอนนี้:
1. อ่าน [`.agents/latest-analysis.md`](.agents/latest-analysis.md) section **User Analysis Input**
2. วิเคราะห์และตรวจสอบความถูกต้อง ความครบถ้วน ความเป็นไปได้
3. ปรับปรุงและเพิ่มเติมในสิ่งที่ขาดหายหรือคลุมเครือ
4. วางแผนขั้นตอนการทำงานที่ชัดเจนและเป็น action items
5. บันทึกแผนลงใน section **Work Plan** ของไฟล์เดิม

## สรุปโปรเจค

**nuxnan** เป็น LMS (Learning Management System) แบบ full-stack ที่ผู้ใช้สามารถสร้าง/เข้าร่วม Academy, Course, ทำ Quiz/Assignment, ติดตาม Lesson, มีระบบ social feed, points/gamification, wallet, marketplace และ chat แบบ realtime

## Tech Stack

### Frontend (`ui/`)
- **Framework**: Nuxt 3/4 + Vue 3 (Composition API) + TypeScript
- **State**: Pinia (`ui/stores/`)
- **Styling**: Tailwind CSS + PrimeVue (`@primeuix/themes`)
- **i18n**: `@nuxtjs/i18n`
- **Rich text**: TipTap
- **Icons**: Iconify (`@iconify/vue`)
- **Node**: `>=22.0.0`

### Backend (`api/nuxnanravel/`)
- **Framework**: Laravel 12 + PHP 8.4
- **Auth**: JWT (`php-open-source-saver/jwt-auth`)
- **Realtime**: Laravel Reverb (websockets)
- **Excel/Export**: `maatwebsite/excel`
- **Image**: `intervention/image`
- **DB**: MySQL (`nuxnan` database)
- **Datatables**: `yajra/laravel-datatables-oracle`
- **Tests**: PHPUnit
- **Code style**: Laravel Pint

## โครงสร้างโฟลเดอร์สำคัญ

```
nuxnan/
├── ui/                          # Nuxt frontend
│   ├── pages/                   # File-based routes (ระวัง dynamic [id])
│   │   ├── Learn/Courses/[id]/  # หน้า course หลัก
│   │   ├── academies/
│   │   ├── Earn/Marketplace.vue
│   │   └── Dashboard.vue
│   ├── components/              # ใช้ PascalCase, จัดเป็น domain folder
│   │   ├── learn/, academy/, share/, school/, wallet/, ...
│   │   └── Common/, atoms/, molecules/, organisms/  # design system
│   ├── composables/             # use*.ts (useApi, useAuth, useFiles, ...)
│   ├── stores/                  # Pinia stores (auth, course, feed, ...)
│   ├── services/                # API client wrappers
│   ├── server/                  # Nuxt server routes/middleware
│   ├── middleware/              # Route middleware
│   ├── plugins/                 # Nuxt plugins
│   ├── i18n/                    # Translations
│   └── nuxt.config.ts
│
└── api/nuxnanravel/             # Laravel API
    ├── app/
    │   ├── Http/Controllers/Api/   # API controllers (จัดเป็น domain เช่น Learn/, Play/, Earn/)
    │   ├── Models/                 # Eloquent models (200+ models)
    │   └── ...
    ├── routes/
    │   ├── api.php              # Main API routes
    │   ├── learn/, earn/, play/, admin/, debug/, ...
    │   └── web.php
    ├── database/
    │   ├── migrations/
    │   ├── seeders/
    │   └── factories/
    ├── tests/
    ├── config/
    └── .env
```

## คำสั่งที่ใช้บ่อย

### Frontend (รันใน `ui/`)
```bash
npm run dev           # dev server (Nuxt) - มักรันที่ port 3000
npm run build         # production build
npm run generate      # static generate
npm run preview       # preview build
```

### Backend (รันใน `api/nuxnanravel/`)
```bash
php artisan serve                    # dev server ที่ port 8000
php artisan migrate                  # รัน migrations
php artisan migrate:fresh --seed     # reset DB + seed
php artisan make:model Foo -mcr      # สร้าง model + migration + controller + resource
php artisan make:controller Api/Foo/BarController
php artisan tinker                   # REPL
php artisan route:list               # ดู routes ทั้งหมด
php artisan test                     # PHPUnit tests
./vendor/bin/pint                    # format โค้ด
php artisan reverb:start             # websocket server
php artisan queue:work --queue=default   # queue worker (ต้องมี --queue=default เสมอ)
php artisan schedule:work                # scheduler (job ตามเวลาใน routes/console.php)
```

### Local environment
- ใช้ **WAMP** ที่ `C:\wamp64\` (Apache + MySQL บน Windows)
- DB name: `nuxnan` (mysql, port 3306)
- Frontend: `npm run dev` ที่ `ui/`
- Backend: `php artisan serve` ที่ `api/nuxnanravel/`
- Queue worker: `php artisan queue:work --queue=default` ที่ `api/nuxnanravel/` — **ต้องรันคู่กับ backend เสมอ**
  ถ้าไม่รัน: แต้ม/gamification, นำเข้านักเรียน, โคลนคอร์สหลังซื้อ จะค้างเงียบ ๆ ไม่ error
  🔴 ห้ามรัน `php artisan queue:work` เปล่า ๆ — คิว `backlog` คือที่พัก job เก่า ~15,869 งาน
  (สะสมตั้งแต่ 2026-05-25 ตอนไม่มี worker) การระบายมันต้องรอเจ้าของโปรเจคเคาะก่อน

## Conventions ของโปรเจคนี้

### Frontend

#### 🔴 Mobile First — นโยบายบังคับของทุก UI ใน `ui/`
ทุก markup ที่เขียนใหม่หรือแก้ไข **ต้องออกแบบจากจอมือถือขึ้นไป** ไม่ใช่ย่อจอ desktop ลงมา
- **class ที่ไม่มี prefix = สไตล์ของมือถือ** แล้วค่อยใช้ `sm:` `md:` `lg:` เพิ่มเมื่อจอกว้างขึ้น
  ห้ามเขียนแบบ desktop-first เช่น `flex-row md:flex-col` — ต้องเป็น `flex-col md:flex-row`
- **ห้ามใช้ `hidden` ซ่อนข้อมูลสำคัญบนมือถือ** ให้จัดวางใหม่ (ซ้อนเป็นแถว/ย้ายลงล่าง) แทนการตัดทิ้ง
- **touch target ขั้นต่ำ 44px** บนมือถือ (`min-h-[44px]` หรือ `p-3`) แล้วค่อยลดลงที่ `sm:`
- ทุกแถวที่เป็น flex ต้องกัน layout พัง: ฝั่งที่ห้ามถูกบีบใส่ `flex-shrink-0` + `whitespace-nowrap`,
  ฝั่งข้อความใส่ `min-w-0 flex-1` + `break-words` (ภาษาไทยไม่มีช่องว่าง จะแตกเป็นแนวตั้งถ้าโดนบีบ)
- ตาราง/โค้ด/ไดอะแกรมที่กว้าง ต้องอยู่ในกล่อง `overflow-x-auto` ของตัวเอง — **ห้ามให้ทั้งหน้าเลื่อนแนวนอน**
- padding/ขนาดตัวอักษรไล่จากเล็กไปใหญ่ เช่น `p-3 sm:p-6`, `text-sm sm:text-base`
- ตรวจจริงที่ **375px** ก่อนเสมอ แล้วค่อยตรวจ 768px / 1280px

- **เมื่อสร้าง/redesign หน้าหรือ component ใดๆ ใน `ui/`** → ใช้สกิล `hopeui-port` (`.agents/skills/hopeui-port/SKILL.md`) เพื่อดึงดีไซน์จาก HopeUI Pro (`hopa/`) มาเป็นต้นแบบ markup ก่อนเสมอ
  หมายเหตุ: markup ของ HopeUI เป็น **desktop-first** — ให้เอาโครงสร้าง/spacing มาใช้ แล้ว**เขียน breakpoint ใหม่เป็น mobile-first เสมอ**
- ใช้ `<script setup lang="ts">` เป็น default
- ตั้งชื่อ component แบบ **PascalCase** (เช่น `CourseProfileCover.vue`)
- จัด component เป็น **domain folder** (เช่น `components/learn/`, `components/academy/`) + design system folders (`atoms/`, `molecules/`, `organisms/`)
- Composables เริ่มด้วย `use` และเป็น TypeScript (`useApi.ts`)
- Pinia stores จัดเป็นไฟล์ละ store (เช่น `stores/auth.ts`)
- ใช้ Tailwind utility classes; PrimeVue components สำหรับ UI ที่ซับซ้อน
- หน้าที่มี dynamic param ใช้ `[param].vue` ตาม Nuxt convention
- เรียก API ผ่าน composable `useApi` หรือ services ที่ห่อไว้แล้ว ไม่ใช้ `$fetch` ตรงๆ

### Backend
- Controllers อยู่ใน `app/Http/Controllers/Api/<Domain>/` (เช่น `Api/Learn/Course/`, `Api/Play/`, `Api/Earn/`)
- Models อยู่ใน `app/Models/` ทั้งหมด (flat) — มีจำนวนมาก ดูชื่อก่อน duplicate
- Routes แยกไฟล์ตาม domain ใน `routes/learn/`, `routes/earn/`, ฯลฯ
- ใช้ JWT สำหรับ auth — middleware `auth:api`
- Validation ใช้ FormRequest หรือ `$request->validate()`
- Code style: Laravel Pint (รัน `./vendor/bin/pint` ก่อน commit)

### Git
- Branch หลัก: ตรวจด้วย `git branch --show-current`
- ก่อน commit ให้ตรวจ `git status` และ `git diff` เสมอ
- เขียน commit message สั้นและชัด (อังกฤษหรือไทยก็ได้)

## ไฟล์ที่ห้ามแตะ (โดยไม่ได้รับอนุญาต)
- `api/nuxnanravel/vendor/` — composer dependencies
- `ui/node_modules/` — npm dependencies
- `api/nuxnanravel/.env` — secrets (ใช้ `.env.example` เป็น reference)
- `api/nuxnanravel/storage/` (ยกเว้นกรณี debug logs)

## ลำดับงานที่แนะนำเมื่อแก้ feature

1. **อ่าน** ไฟล์ที่เกี่ยวข้อง (ทั้ง frontend + backend) ก่อนเขียน
2. ถ้าแก้ DB → เขียน migration (ห้ามแก้ schema ตรง phpMyAdmin)
3. ถ้าแก้ API → อัพเดท controller, route, validation, และ response shape
4. ถ้าแก้ frontend → อัพเดท composable/service, Pinia store, แล้วค่อย component/page
5. รัน lint/test ที่เกี่ยวข้อง: `./vendor/bin/pint` (backend), build check (frontend)
6. ตรวจ `git diff` แล้ว commit เป็นชุดที่เล็กพอจะ revert ได้

## การลงมือเขียนโค้ด — ใช้ agy เป็นตัวช่วยหลัก

งาน implement ที่ขอบเขตชัดเจน ให้ใช้สกิล `agy` (`.agents/skills/agy-delegate/SKILL.md`):
Claude = วิเคราะห์ + แตก shard + เขียนสเปคเป็นไฟล์ + **ตรวจผลเอง** · agy = ผู้เขียนโค้ด
ทุกสเปคที่เป็นงานใน `ui/` ต้องแปะบล็อกกติกา **mobile-first** ลงไปด้วยเสมอ
รายงานของ agy เชื่อไม่ได้ — ต้อง `git diff --stat` (อ่านเลข deletion) + `git diff` + รันเกณฑ์ผ่านเองทุกครั้ง

## Subagents ที่มีในโปรเจคนี้

ดู `.claude/agents/` มี:
- **frontend-vue** — งาน Nuxt/Vue (pages, components, composables, stores)
- **backend-laravel** — งาน Laravel (controllers, models, routes, validation)
- **database-migration** — schema, migrations, seeders, query optimization
- **code-reviewer** — review โค้ด, หา bug, security check

ใช้ผ่าน Task tool (`subagent_type: "frontend-vue"` ฯลฯ) หรือเรียกจาก slash menu

## หมายเหตุสำคัญสำหรับ autonomous mode

- **ก่อนแก้ไฟล์เสมอ**: อ่านไฟล์ก่อนด้วย Read tool
- **commit เป็นชุดเล็กๆ**: ไม่ commit ทุกอย่างใน commit เดียว
- **ห้าม `git push --force`** หรือ `git reset --hard` โดยไม่ confirm
- **ห้ามรัน `php artisan migrate:fresh`** บน DB ที่มี data จริง — ต้องถามก่อน
- **ห้ามแตะ `.env`** ที่มี secret จริง
- **อย่าลบไฟล์** ที่ไม่ได้สร้างใน session นี้โดยไม่ confirm
