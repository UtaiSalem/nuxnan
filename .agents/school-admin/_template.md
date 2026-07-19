# <NN> — <ชื่อเมนู>

> ไฟล์รองของเมนู `<เมนูใน OVERVIEW.md>`
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)

## 1. Scope & Purpose
สิ่งที่เมนูนี้ครอบคลุม เป้าหมายเชิงธุรกิจ ผู้ใช้ที่เกี่ยวข้อง

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend
- Pages: `ui/pages/...` (ลิงก์ + สรุปสั้น ๆ)
- Components: `ui/components/...`
- Composables/Stores: `ui/composables/...`, `ui/stores/...`

### Backend
- Controllers: `app/Http/Controllers/Api/...`
- Routes: `routes/...`
- Models: `app/Models/...`
- Services / FormRequests / Policies: ...

### Database
- ตารางที่เกี่ยวข้อง + relation หลัก

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ... | ✅/⚠️/❌ | ... |

## 4. Permission Matrix

| Permission key | Owner | Admin | ฝ่าย admin | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| `<domain>.view` | ✅ | ✅ | ✅ (ในฝ่าย) | ⚠️ | ❌ | ❌ | ❌ |
| `<domain>.manage` | ✅ | ✅ | ✅ (ในฝ่าย) | ❌ | ❌ | ❌ | ❌ |

## 5. Gap Analysis
รายการ gap ที่พบ (ให้เลข G1, G2, ... เพื่ออ้างอิงใน tasks)

## 6. Implementation Tasks (ส่งให้ codex ทีละ step)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| S1 | ... | — | ... | ⚪ pending / 🟡 in codex / 🟢 verified / 🔴 rework |

**Rule:** ทุก step ต้องมี verification (build/test/manual browser check) ก่อนขึ้นสถานะ 🟢

## 7. Codex Prompt Template (ต่อ step)
```
Context: <ไฟล์รองของเมนู> §<step-id>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการ>
Task: <what codex should do>
Constraints: <list>
Verification: <build/test/manual>
Report back: <what claude expects>
```

## 8. Review Log
- **YYYY-MM-DD S1** — codex ทำ ..., claude ตรวจ ... → verdict
