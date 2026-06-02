# Task for Gemini CLI
**Assigned by:** Claude Orchestrator  
**Date:** 2026-06-02  
**Status:** PENDING

---

## ข้อตกลงการรายงาน

- `PENDING` → กำลังรอ | `IN_PROGRESS` → กำลังทำ | `DONE` → เสร็จแล้ว | `BLOCKED: <เหตุผล>` → ติดปัญหา

ห้าม commit หรือ push โดยไม่ได้รับอนุญาตจาก Orchestrator  
ห้ามแก้ไขไฟล์นอกที่ระบุไว้ใน task

---

## TASK — เพิ่ม letter_runner ใน Backend Validation
**Status:** DONE  

### เหตุผล
มีการเพิ่ม game mode ใหม่ชื่อ `letter_runner` ใน frontend  
Backend ต้องยอมรับ mode นี้ในการ validate session submission

---

### ไฟล์ที่ต้องแก้ไข

#### ไฟล์ 1: `api/nuxnanravel/app/Http/Controllers/Api/Play/Typing/TypingSessionController.php`
**Line:** 31

เพิ่ม `letter_runner` ต่อท้าย enum:

```php
// ปัจจุบัน
'game_mode' => 'required|in:word_typing,time_attack,sentence_typing,monster_battle,falling_words,classroom_race,daily_challenge,key_training',

// แก้เป็น
'game_mode' => 'required|in:word_typing,time_attack,sentence_typing,monster_battle,falling_words,classroom_race,daily_challenge,key_training,letter_runner',
```

---

## เมื่อทำเสร็จ

อัพเดท Status เป็น `DONE` ในไฟล์นี้  
Orchestrator จะ verify และ release commit
