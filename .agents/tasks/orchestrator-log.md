# Orchestrator Log — Letter Runner Game Mode
**Date:** 2026-06-02  
**Feature:** เกมใหม่ Letter Runner สำหรับ `/Play/Games/typing`

---

## Feature Summary

เพิ่ม game mode ใหม่: **Letter Runner**
- ตัวการ์ตูนวิ่งอัตโนมัติบน scrolling background (parallax)
- ตัวอักษรลอยเข้ามาจากขวา → พิมพ์ถูกเพื่อทำลาย
- เหรียญลอยบนทาง → เก็บอัตโนมัติเมื่อผ่าน
- 3 ชีวิต + timer 60 วินาที
- Combo system + particle effects

---

## Task Assignments

| ID | ไฟล์ | Agent | Status |
|----|------|-------|--------|
| C1 | `LetterRunnerMode.vue` (สร้างใหม่) | Codex | ✅ DONE |
| C2 | `play.vue` (import + switch case) | Codex | ✅ DONE |
| C3 | `index.vue` (เพิ่ม mode ในรายการ) | Codex | ✅ DONE |
| C4 | `stores/typing.ts` (เพิ่ม type) | Codex | ✅ DONE |
| G1 | `TypingSessionController.php` (เพิ่ม letter_runner) | Gemini | ✅ DONE |

---

## Task Files

- Codex อ่าน task ที่: `.agents/tasks/codex-task.md`
- Gemini อ่าน task ที่: `.agents/tasks/gemini-task.md`

---

## Verification Checklist (Orchestrator จะตรวจเมื่อ agents รายงาน DONE)

### Frontend (Codex)
- [ ] `LetterRunnerMode.vue` มีอยู่และไม่มี syntax error
- [ ] Background parallax เลื่อนได้
- [ ] ตัวละครมี running animation
- [ ] ตัวอักษรลอยเข้ามาจากขวา
- [ ] กด key ถูก → letter ถูก destroy + combo เพิ่ม
- [ ] กด key ผิด → combo reset
- [ ] เหรียญเคลื่อนเข้ามาและถูกเก็บอัตโนมัติ
- [ ] lives display ถูกต้อง (3 hearts)
- [ ] timer countdown 60s
- [ ] เมื่อ lives = 0 หรือ timer = 0 → emit finished + buildResult
- [ ] play.vue มี `letter_runner` ใน switch case
- [ ] index.vue มี Letter Runner ในรายการ modes
- [ ] stores/typing.ts มี `'letter_runner'` ใน GameMode type

### Backend (Gemini)
- [ ] POST /typing/sessions ด้วย game_mode=letter_runner → 200 ไม่ 422
