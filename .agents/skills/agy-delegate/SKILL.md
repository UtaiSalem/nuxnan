---
name: agy-delegate
description: มอบงาน implement ให้ agy (Antigravity CLI) เป็นตัวช่วยหลัก แล้ว Claude ตรวจสอบผลด้วยตัวเองทุกครั้ง — ใช้กับงานเขียนโค้ดที่แตกเป็น shard ได้ ทั้ง ui/ และ api/
---

# agy-delegate — ใช้ agy เป็นตัวช่วยหลักในการลงมือเขียนโค้ด

**บทบาท:** Claude = วิเคราะห์ + แตก shard + เขียนสเปค + **ตรวจสอบ** · agy = ผู้ลงมือเขียนโค้ด
Claude ไม่เขียนโค้ดฟีเจอร์เอง (แก้เองได้เฉพาะจุดที่ agy ทำพลาดเล็กน้อยและอธิบายให้ผู้ใช้ทราบ)

## 0. เช็คก่อนเริ่ม

```bash
git status --short          # ต้องสะอาด — ต้องมี baseline ที่ diff ได้
```
ถ้ามีของค้าง → commit หรือถามผู้ใช้ก่อน ห้ามปล่อยให้ agy แก้ทับงานที่ยังไม่ commit

## 1. เขียนสเปคเป็นไฟล์ (ห้าม prompt ยาวใน command line — มันจะถูกตัด)

เขียนลง scratchpad เช่น `<scratchpad>/agy-<ชื่องาน>-shard1.txt` โครงสเปคที่พิสูจน์แล้วว่า agy ทำตาม:

1. **บริบท + root cause ที่วิเคราะห์จบแล้ว** (agy ไม่ต้องคิดสถาปัตยกรรมเอง — จุดที่ให้มันคิดเองคือจุดที่มันเริ่มลบของเดิมทิ้ง)
2. **ไฟล์ที่แก้ได้ — ระบุเป็นชื่อเต็มทีละไฟล์** และ **ไฟล์/โฟลเดอร์ห้ามแตะ** เป็นชื่อ ๆ (รวมไฟล์ที่ shard อื่นกำลังทำขนานกัน)
3. **แปะโค้ดปัจจุบันเต็ม ๆ ของไฟล์เป้าหมาย** ลงในสเปค + บอกว่าผลลัพธ์ต้องเป็นอย่างไร (ถ้ามี before/after ให้ copy ได้ ยิ่งแม่น)
4. **เกณฑ์ผ่านเป็นคำสั่ง shell** ที่รันแล้ววัดได้จริง
5. **ข้อห้ามประจำ:** ห้าม `git commit` · ห้าม `git push` · ห้าม `php artisan migrate` · ห้าม `npm run build` · ห้ามแตะ `.env`
6. ถ้าเป็นงาน add-only ให้เขียนชัดว่า **"ถ้ามีบรรทัด `-` ที่ไม่ได้สั่ง = ไม่ผ่าน"**

## 2. กติกา UI ที่ต้องแปะลงสเปคทุกครั้งที่งานอยู่ใน `ui/` — Mobile First (บังคับ)

ก๊อปบล็อกนี้ลงสเปคตรง ๆ:

```
กติกา UI บังคับ (mobile-first — ห้ามละเมิด):
- ออกแบบจากจอ 375px ขึ้นไป: class ที่ไม่มี prefix = สไตล์มือถือ แล้วค่อยเพิ่ม sm: md: lg:
  ห้ามเขียน desktop-first เช่น `flex-row md:flex-col` — ต้องเป็น `flex-col md:flex-row`
- ห้ามใช้ `hidden` ซ่อนข้อมูลสำคัญบนมือถือ ให้จัดวางใหม่แทน (ซ้อนเป็นแถว/ย้ายลงล่าง)
- ปุ่ม/ลิงก์ที่กดได้ต้อง touch target >= 44px บนมือถือ (`min-h-[44px]` หรือ `p-3`) แล้วค่อยลดที่ sm:
- แถว flex: ฝั่งที่ห้ามถูกบีบใส่ `flex-shrink-0 whitespace-nowrap`,
  ฝั่งข้อความใส่ `min-w-0 flex-1 break-words` (ภาษาไทยไม่มีช่องว่าง จะแตกแนวตั้งถ้าโดนบีบ)
- ตาราง/โค้ด/ไดอะแกรมกว้าง ต้องอยู่ในกล่อง `overflow-x-auto` ของตัวเอง — ทั้งหน้าห้ามเลื่อนแนวนอน
- padding/font ไล่เล็กไปใหญ่: `p-3 sm:p-6`, `text-sm sm:text-base`
- `<script setup lang="ts">` เป็นค่าเริ่มต้น · เรียก API ผ่าน `useApi` เท่านั้น ห้าม `$fetch`/`axios` ตรง ๆ
- ห้าม import อะไรจาก `@inertiajs/*` ในโค้ดใหม่
```

ถ้าเป็นการ **สร้างหน้า/component ใหม่** ให้ Claude ดึงต้นแบบ markup จากสกิล `hopeui-port` ก่อน แล้วแปะ markup นั้นลงสเปคพร้อมสั่งให้เขียน breakpoint ใหม่แบบ mobile-first

## 3. สั่ง agy

```bash
agy --print "Read <spec path> and follow it exactly." \
  --add-dir "C:\wamp64\www\nuxnan" \
  --add-dir "<scratchpad dir>" \
  --model gemini-3.1-pro-high \
  --mode accept-edits \
  --print-timeout 20m
```

- รันผ่าน Bash `run_in_background` เสมอ (งานกินเวลาหลายนาที)
- `--mode accept-edits` เท่านั้น — **ห้าม** `--dangerously-skip-permissions`, `--mode plan` คือโหมดอ่านอย่างเดียว
- `--add-dir` ต้องมีทั้ง repo และ scratchpad มิฉะนั้นมันอ่านสเปคไม่เจอ
- การอ่านไฟล์ในเรพแบบ relative ของมัน flaky — สเปคควรอ้าง path เต็มเสมอ
- 2 shard ที่ไฟล์ไม่ทับกันรันขนานได้จริง
- binary: `C:\Users\Bhupha\AppData\Local\agy\bin\agy.exe` · config: `~/.gemini/antigravity-cli/settings.json`
  (`permissions.allow` ต้องมี `command(*)` + `write_file(**)` และ `trustedWorkspaces` ต้องมี `C:\wamp64\www\nuxnan`)

## 4. ตรวจผล — ข้อนี้ห้ามข้าม รายงานของ agy เชื่อไม่ได้

agy เคยโกหกมาแล้ว 3 แบบ: (ก) รายงาน diff ของไฟล์ที่ไม่ได้แตะ · (ข) diff ถูกแต่ตัวเลข assertion ปลอม · (ค) ลบโค้ดทิ้ง 87 บรรทัดแล้วบอกว่า "ไม่ได้แตะของเดิม"

```bash
git status --short           # มีไฟล์นอกสเปคโผล่มาไหม
git diff --stat              # อ่านเลข deletion ทุกครั้ง — งาน add-only ที่มี `-` เยอะ = พัง
git diff                     # อ่านของจริงทุกบรรทัด
```

แล้วรันเกณฑ์ผ่าน **ด้วยตัวเอง** ห้าม paste ตัวเลขจากรายงานของ agy:
- backend: `./vendor/bin/pint --test` + `php artisan test --filter=...`
- frontend: compile SFC ที่แก้ (`@vue/compiler-sfc`) + ตรวจในเบราว์เซอร์จริงที่ **375px** ก่อน แล้วค่อย 768 / 1280
- `npm run build` ผู้ใช้รันเอง — Claude ไม่ต้องรัน

## 5. ปิดงาน

- สรุปให้ผู้ใช้ว่า **อะไรที่ตรวจเองแล้ว** vs **อะไรที่เชื่อรายงาน agy** (ควรเป็นศูนย์)
- commit เป็นชุดเล็ก ๆ ต่อ shard (commit เมื่อผู้ใช้สั่ง) แล้วอัพเดท `.agents/worklog.md`
