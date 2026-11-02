# Spec: POST /api/campaigns → "<no response> Failed to fetch"

## อาการ
หน้า `/Learn/Courses/25/support` → กด "สร้างแคมเปญ" ใน `CampaignCreateModal`
console: `[POST] "https://localhost:8000/api/campaigns": <no response> Failed to fetch`

## สาเหตุที่ตรวจแล้ว (Claude, 2026-11-03)
1. **Scheme ไม่ตรงกัน (ต้นเหตุหลัก — เป็นเรื่อง environment ไม่ใช่โค้ด)**
   - backend รันด้วย `php artisan serve` = `php -S 127.0.0.1:8000` → **HTTP เท่านั้น**
   - `curl https://localhost:8000/...` → `schannel: failed to receive handshake` (TLS ล้ม) · `http://` → 200
   - browser ยิงไป `https://localhost:8000` → handshake ล้ม → ไม่มี response → ofetch รายงาน "Failed to fetch"
   - `ui/.env` บนเครื่องนี้ = `NUXT_PUBLIC_API_BASE=http://localhost:8000` และ nuxt dev ก็เป็น http
     → ผู้ใช้เปิดหน้าจาก `https://localhost:3000` (setup ที่อื่น/proxy) ที่ apiBase เป็น https
   - `storage/logs/laravel.log` ไม่มี error ของ POST นี้เลย = request ไม่ถึง Laravel
2. **โค้ดซ้ำเติม (สิ่งที่ต้องแก้ใน repo)**
   - `ui/components/campaign/CampaignCreateModal.vue:148` และ `ui/pages/Earn/Advertise/create.vue:300`
     เรียก `$fetch` ตรง ๆ (ผิด convention ใน CLAUDE.md — ต้องใช้ `useApi`)
   - error ของ network ล้มถูกโชว์เป็นข้อความดิบ "Failed to fetch" ผู้ใช้ไม่รู้ว่าต้องทำอะไร
   - การอ่าน error: `$fetch` → `err.response._data`, `useApi` → `err.data` (ดู memory "three HTTP clients")

## งานที่ต้องทำ (ขอบเขต: 2 ไฟล์ใน `ui/` เท่านั้น)
### A. `ui/components/campaign/CampaignCreateModal.vue`
- แทน `$fetch(\`${apiBase}/api/campaigns\`, {...})` ด้วย `useApi().post('/api/campaigns', formData)`
  (อ่าน `ui/composables/useApi.ts` บรรทัด ~380–470 และ ~590 ก่อน — useApi ไม่ใส่ Content-Type ให้ FormData อยู่แล้ว และใส่ Authorization เอง)
- ลบ header Authorization ที่ใส่เอง และตัวแปร `apiBase`/`config` ถ้าไม่ได้ใช้แล้ว
- catch:
  - 422 → `fieldErrors = e.data?.errors` (ตาม shape ของ useApi — ตรวจใน useApi ว่า throw อะไรจริง)
  - ไม่มี response (network/TLS/CORS ล้ม: ไม่มี status) → ข้อความไทย
    `'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่อีกครั้ง'` แทน "Failed to fetch"
  - อื่น ๆ → `e.data?.message || e.message`
### B. `ui/pages/Earn/Advertise/create.vue` (submit ~บรรทัด 299–320)
- ทำแบบเดียวกับข้อ A (useApi + ข้อความ network error ภาษาไทย ใน Swal)

## ห้าม
- ห้ามแตะ backend, `.env`, `nuxt.config.ts`
- ห้ามเปลี่ยน markup/สไตล์ (ไม่มีงาน UI ใหม่ — แต่ถ้าแตะ markup ต้อง mobile-first: class ไม่มี prefix = มือถือ, touch target ≥44px)
- ห้ามรัน `npm run build`

## เกณฑ์ผ่าน (Claude จะตรวจเอง)
1. `grep -n "\$fetch" ui/components/campaign/CampaignCreateModal.vue ui/pages/Earn/Advertise/create.vue` → ไม่เจอ
2. `git diff --stat` แตะแค่ 2 ไฟล์นี้ และไม่มี deletion ผิดปกติ
3. SFC ยัง parse ได้ (dev server ไม่ error) และ POST ด้วย http apiBase ส่ง multipart ถึง backend จริง
4. ปิด backend แล้วกดสร้าง → เห็นข้อความไทย ไม่ใช่ "Failed to fetch"

## สิ่งที่ผู้ใช้ต้องทำเองเพื่อหายจริง (environment)
เปิดเว็บที่ `http://localhost:3000` (ไม่ใช่ https) **หรือ** ถ้าต้องใช้ https ให้ backend มี TLS ด้วย
(เช่น ผ่าน Apache vhost ของ WAMP) แล้วตั้ง `NUXT_PUBLIC_API_BASE` ให้ scheme ตรงกับที่ backend ฟังอยู่
