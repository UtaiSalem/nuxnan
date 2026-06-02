# คำแนะนำสำหรับ Codex AI Agent

## วัตถุประสงค์

คุณคือ **Codex** — AI agent ที่รับผิดชอบงาน implementation ใน project nuxnan
คุณอ่านไฟล์งาน `.agents/codex-tasks.md` แล้วทำงานที่ถูกกำหนดไว้ และ mark สถานะเมื่อเสร็จ

## Project Context

- **Project**: nuxnan — LMS (Learning Management System)
- **Working directory**: `C:\wamp64\www\nuxnan`
- **Backend**: Laravel 12 (`api/nuxnanravel/`) — PHP 8.4, MySQL
- **Frontend**: Nuxt 4 + Vue 3 (`ui/`) — TypeScript, Tailwind CSS, PrimeVue

## Workflow ที่คุณต้องทำ

### ขั้นตอนที่ 1 — อ่านไฟล์งาน
```
อ่าน: .agents/codex-tasks.md
```
หา tasks ที่มี `assigned_to: codex` และ `status: pending`

### ขั้นตอนที่ 2 — ทำงาน
สำหรับแต่ละ task:
1. อ่านไฟล์ที่เกี่ยวข้องก่อนแก้ (ห้ามแก้โดยไม่อ่าน)
2. ทำตาม acceptance criteria ให้ครบ
3. ไม่เพิ่ม feature หรือ refactor นอกเหนือจากที่ task กำหนด

### ขั้นตอนที่ 3 — Mark สถานะ
เมื่อทำ task เสร็จแต่ละอัน ให้อัปเดต `.agents/codex-tasks.md`:
- เปลี่ยน `status: pending` → `status: done`
- เพิ่ม `completed_at: YYYY-MM-DD HH:MM` (เวลาจริงที่ทำเสร็จ)
- เพิ่ม `completion_notes:` สรุปสิ่งที่ทำไปอย่างสั้นๆ

### ขั้นตอนที่ 4 — อัปเดต worklog
เมื่อทำงานทั้งหมดเสร็จ เพิ่มบันทึกใน `.agents/worklog.md` ว่าทำอะไรไปบ้าง

## กฎสำคัญ

- **อ่านไฟล์ก่อนแก้เสมอ** ด้วย Read tool
- **ห้าม** `git push --force`, `php artisan migrate:fresh`, แก้ `.env`
- **ห้ามแตะ** `vendor/`, `node_modules/`
- **ทำทีละ task** อย่าข้าม เพราะ Claude จะตรวจทีละขั้น
- ใช้ path จาก `C:\wamp64\www\nuxnan\` เป็น base

## การรายงานปัญหา

หากพบปัญหาหรือ task ที่ทำไม่ได้ ให้:
1. เปลี่ยน `status: blocked`
2. เพิ่ม `block_reason:` อธิบายปัญหา

Claude จะตรวจสอบและปลดบล็อกให้
