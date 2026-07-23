# Plan: Consolidate Course Points into a Single Panel + Modals (Delight Pass)

> วิสัยทัศน์: หน้าเดียวจบ / modal / ปุ่มชัดเจนเป็นกลุ่ม / สวยทันสมัย / gamification / เป็นมิตร
> ล็อคขอบเขต (2026-07-23): ลบ wallet sub-pages, ทำครบตอนเดียว, animation = Vue Transition + Tailwind
> ระดับโรงเรียน/public + รวมโฆษณา = รอบหน้า (คงเดิม)

## 1. สร้าง Modal 3 ตัว (owner)

### 1.1 `ui/components/learn/course/points/CampaignCreateModal.vue`
- Props: `visible: boolean` (v-model), `courseId: number|string`, `availableBalance: number`
- Emit: `update:visible`, `created` (parent refresh)
- Body: form (title*, description, points_per_claim*, checkbox จำกัดผู้รับ→max_claims, starts_at, ends_at)
  - Live reserve preview + insufficient guard (แบบ `LessonRewardForm`)
  - ปุ่ม "สร้างแคมเปญ" gradient
- Wire: `useCoursePoints(courseId).createManualCampaign(...)` → toast + close + emit created

### 1.2 `ui/components/learn/course/points/CampaignManageModal.vue`
- Props: `visible`, `courseId`
- โหลด `fetchOwnerCampaigns()` เมื่อเปิด, filter `campaign_type === 'manual_claim'`
- แสดง card list (ไม่ใช่ตาราง — สวยกว่า): title, badge สถานะ, progress ring/bar `total_claimed / max_claims`, ปุ่ม pause/end (confirm swal ก่อน end)
- Empty state playful: icon + "ยังไม่มีแคมเปญ ลองสร้างสักอัน!"
- Wire: pauseCampaign/endCampaign แล้ว refetch list + emit `updated`

### 1.3 `ui/components/learn/course/points/WithdrawModal.vue`
- Props: `visible`, `courseId`
- 2 tabs: **ถอน** (form) / **ประวัติ** (list)
  - form: amount input (min 24000) + purpose textarea + ปุ่มส่งคำขอ, guard `< minimum_withdrawal`
  - ประวัติ: reuse `useCoursePointWithdrawals().fetchCourseHistory()` แสดง list พร้อม status badge + ปุ่มยกเลิก (ใช้ pattern จาก withdraw.vue เดิม)
- Wire: `useCoursePointWithdrawals().createRequest()` + `.cancel()`

## 2. Redesign `CourseSupportPanel.vue`

### Layout ใหม่ (mock)
```
╭─────────────────────────────────────────────╮
│  ✨ กองทุนคอร์ส              [ 💜 บริจาคแต้ม ] │ ← student
│                              [ ⚙️ …          ] │ ← owner ตัวเลือกเสริม
├─────────────────────────────────────────────┤
│    2,500      2         0                    │ ← count-up animation
│   ✨ แต้ม   🤝 คน    🎁 แจกแล้ว              │
├─── (owner) Action cluster ────────────────┤
│  ┌─────────┐ ┌─────────┐ ┌─────────┐        │
│  │   ⭐    │ │   📋   │ │   🏦   │           │
│  │ สร้าง   │ │จัดการ  │ │ ถอน    │            │
│  │ แคมเปญ  │ │แคมเปญ  │ │ แต้ม   │            │
│  └─────────┘ └─────────┘ └─────────┘        │
│  (hover lift, gradient border on hover)      │
├─── (student) รับแต้ม ──────────────────────┤
│  การ์ด CoursePointClaimCard (มีอยู่)         │
├─── ผู้สนับสนุนล่าสุด ──────────────────────┤
│  list                                        │
╰─────────────────────────────────────────────╯
```

### เปลี่ยน
- **ทั้ง 2 บทบาท:** count-up animation บนตัวเลข (simple `requestAnimationFrame` easing)
- **owner:** ลบลิงก์ `/wallet/campaigns` เดิม → เปลี่ยนเป็น **3 action cards** เปิด modal 1.1/1.2/1.3 ตามลำดับ
- **owner:** ลบ CTA "สร้างแคมเปญ" hero card เดิม → เข้าไปอยู่ในการ์ด "สร้างแคมเปญ" ใน cluster; hint "มีแต้มในกองทุนยังไม่ได้เปิดให้รับ" อาจย้ายเป็น badge บนการ์ด "สร้างแคมเปญ" (dot สีเหลืองกระพริบ + tooltip)
- **empty states** playful (icon ใหญ่ + คำพูดเป็นมิตร): เช่น donation list empty → "ยังไม่มีคนสนับสนุน ลองแชร์คอร์สให้เพื่อนดูสิ 🚀"
- **success animation ตอน claim:** simple pulse/scale (Tailwind `animate-ping` ทับ + fade-out) หรือ Vue Transition ผสมกับ CSS keyframe
- **gradient:** violet-500 → pink-500 → amber-400 (warm playful)
- **modal transitions:** Vue `<Transition name="modal-fade">` + `<Transition name="modal-slide">` — fade + slide-up-scale

### ยังเก็บ / ปรับ
- Header + ปุ่มบริจาค (นักเรียน) — คงไว้
- Donation list — คงไว้ปรับ empty state
- Footer link "ดูทั้งหมด" — **ลบ** (ไม่ต้อง navigate ออกไปแล้ว, panel = single surface)

## 3. Cleanup

### ลบไฟล์
- `ui/pages/courses/[id]/wallet/index.vue`
- `ui/pages/courses/[id]/wallet/campaigns.vue`
- `ui/pages/courses/[id]/wallet/withdraw.vue`
- ตรวจ + ลบโฟลเดอร์ `ui/pages/courses/[id]/wallet/` ถ้าว่าง

### ปรับ references
- `ui/pages/Learn/Courses/[id]/support.vue` — ลบลิงก์ "กระเป๋าแต้ม →" ใน owner transactions section (ไม่ใช้แล้ว) หรือเปลี่ยนเป็นปุ่มเปิด WithdrawModal
- `ui/pages/Learn/Courses/[id]/index.vue` — ไม่ต้องแตะ (panel ทำหน้าที่ทั้งหมด)
- grep ทั้ง ui/ หา `/wallet/campaigns` หรือ `/wallet/withdraw` ที่อาจเหลือค้าง

### ระวัง
- `useCoursePointWithdrawals` มีอยู่แล้วใน composable — reuse ตรง ๆ
- `useCoursePoints` มีครบ (createManualCampaign, pauseCampaign, endCampaign, fetchOwnerCampaigns, withdraw)
- **ไม่แตะ backend, ไม่แตะ QuizRewardForm/LessonRewardForm** (คนละ function set)

## 4. Verify (Claude ตรวจ)
- Panel + 3 modals ตรวจ import (pathPrefix), ไม่มี v-bind ว่าง
- ลบไฟล์ครบ, ไม่มี dangling `/wallet` reference
- Modal เปิด/ปิด/refresh data ถูกต้อง (grep + read)
- ไม่เพิ่ม npm dep, ไม่รัน `npm run build`
- ตรวจ Vue Transition ประกาศครบ (name attribute + CSS)

## 5. Commit (ชุดเล็ก)
1. Composable helpers เพิ่ม (ถ้ามี — น่าจะไม่ต้อง มีครบแล้ว)
2. 3 modals
3. Panel redesign + wire modals
4. ลบ sub-pages + cleanup references
5. ลบ plan file? — คงไว้ใน .agents/
