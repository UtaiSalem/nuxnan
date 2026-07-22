# Plan: Owner UI — Manual Campaign management + Quiz Reward setup

> ต่อยอดจาก API ที่มีแล้ว (commit ce1cc637..65a66382). ฝั่งนักเรียน (claim + auto quiz reward) เสร็จแล้ว
> งานนี้ = **หน้า/ฟอร์มฝั่งเจ้าของวิชา** เพื่อ (A) สร้าง/จัดการ manual campaign และ (B) ตั้ง quiz reward
> Pattern ต้นแบบ: `ui/components/learn/course/points/LessonRewardForm.vue` (มีอยู่แล้ว — mirror ให้มากสุด)
> ก่อนสร้าง UI ใช้ skill `hopeui-port` ดึง markup ต้นแบบ

## API ที่มีอยู่แล้ว (ใช้ได้เลย ไม่ต้องสร้างใหม่)
- Manual campaign (owner): `GET/POST /api/courses/{course}/points/campaigns`, `PATCH .../campaigns/{campaign}/pause`, `PATCH .../campaigns/{campaign}/end`
- Course account: `GET /api/courses/{course}/points/account` (มี `available_balance`)
- Quiz reward (owner): `GET/POST/DELETE /api/courses/{course}/quizzes/{quiz}/reward`

## Backend tweak เดียวที่ต้องทำ
`QuizRewardCampaignController::show` ปัจจุบันคืน raw model (`->first()`) — ปรับให้คืน shape เดียวกับ
`LessonRewardCampaignController::show`: `{ id, points_per_claim, max_claims, total_claimed, remaining, status, starts_at, ends_at }`
(remaining = max_claims ? max_claims - total_claimed : null; คืน `null` เมื่อไม่มี campaign). Pint หลังแก้.

## PHASE A — Composable (`ui/composables/useCoursePoints.ts`)
เพิ่ม (ผ่าน `useApi` เท่านั้น, อย่าชน ref `campaigns` เดิมที่ใช้กับ student available list):
- `ownerCampaigns = ref<any[]>([])`, `isLoadingOwnerCampaigns`
- `fetchOwnerCampaigns()` → GET `/campaigns` → เก็บใน `ownerCampaigns` (owner index คืน campaign ทุกสถานะ/ทุกชนิด — ฝั่ง UI filter เอาเฉพาะ `campaign_type === 'manual_claim'` มาแสดงในหน้า manual)
- `createManualCampaign(data)` → POST `/campaigns` (data: title, description?, points_per_claim, max_claims?, starts_at?, ends_at?)
- `pauseCampaign(id)` → PATCH `/campaigns/{id}/pause`
- `endCampaign(id)` → PATCH `/campaigns/{id}/end`
- Quiz reward (mirror lesson): `fetchQuizReward(quizId)` GET, `saveQuizReward(quizId, data)` POST, `cancelQuizReward(quizId)` DELETE — ที่ `/courses/{id}/quizzes/{quizId}/reward`
- export ทุกตัวเพิ่ม

## PHASE B — Quiz Reward Form (mirror LessonRewardForm)
- สร้าง `ui/components/learn/course/points/QuizRewardForm.vue` — clone `LessonRewardForm.vue` แต่:
  - props: `courseId`, `quizId`, `availableBalance`
  - ใช้ `fetchQuizReward/saveQuizReward/cancelQuizReward`
  - หัวข้อ "รางวัลเมื่อสอบได้คะแนนเต็ม" + hint ว่าให้แต้มเฉพาะทำคะแนนเต็ม (100%)
  - อ่าน stats จาก field ที่ถูกต้อง: `total_claimed`, `remaining` (อย่าใช้ `claimed_count`/`remaining_claims` — LessonRewardForm ใช้ผิดชื่อ ทำให้ stats ไม่ขึ้น; ของ quiz ให้ใช้ให้ถูก)
  - reserve preview + balance guard เหมือน lesson
- Mount: หน้าแก้ quiz `ui/pages/Learn/Courses/[id]/quizzes/[quizId]/edit.vue`
  - เพิ่ม tab ที่ 3 `reward` ("รางวัลแต้ม") ต่อจาก settings/questions (ดู pattern tab บรรทัด ~530)
  - ใน panel `v-show="activeTab === 'reward'"` วาง `<QuizRewardForm :course-id="courseId" :quiz-id="quizId" :available-balance="account?.available_balance || 0" />`
  - โหลด account balance ผ่าน `useCoursePoints(courseId).fetchAccount()` ใน onMounted (หน้านี้ owner-only อยู่แล้วผ่าน middleware isCourseAdmin)

## PHASE C — Manual Campaign management page
- สร้าง `ui/pages/courses/[id]/wallet/campaigns.vue` (layout 'course', middleware ['auth'] + guard isCourseAdmin เหมือน quiz edit)
  โครง 2 คอลัมน์เหมือน withdraw.vue:
  - ซ้าย: ฟอร์มสร้าง manual campaign (title*, description, points_per_claim*, checkbox จำกัดผู้รับ→max_claims, starts_at, ends_at)
    - แสดง available balance + reserve preview (points_per_claim × max_claims) + guard ไม่พอเหมือน LessonRewardForm
    - ปุ่ม "สร้างแคมเปญ" → createManualCampaign → refresh list + account
  - ขวา: ตารางรายการ manual campaign (title, แต้ม/คน, รับแล้ว total_claimed/max_claims, สถานะ badge, ปุ่ม pause/end ตามสถานะ)
    - pause แสดงเฉพาะ active, end แสดงเมื่อ active/paused, ยืนยันด้วย useSweetAlert ก่อน end
- Cross-link:
  - ใน `wallet/withdraw.vue` เพิ่มลิงก์ไป `campaigns` และในหน้า campaigns เพิ่มลิงก์กลับ wallet/withdraw
  - (ถ้ามี nav course สำหรับ owner ที่เหมาะ ให้เพิ่มลิงก์ "จัดการแต้ม/แคมเปญ" ด้วย — optional)

## Verify (Claude ตรวจ)
- Pint (backend show tweak)
- ไม่รัน `npm run build` (ผู้ใช้ทำเอง) — แต่ตรวจ type/handler ให้ครบ
- ตรวจ mount, ชื่อ auto-import component (`LearnCoursePointsQuizRewardForm`), route ของหน้าใหม่ (`/courses/{id}/wallet/campaigns`)
- (ไม่ต้องเพิ่ม PHPUnit — backend เปลี่ยนแค่ response shape ของ show)

## หมายเหตุ
- ต่อยอด commit เป็นชุดเล็ก: (1) backend show tweak (2) composable (3) QuizRewardForm + mount (4) manual campaign page + links
- LessonRewardForm มี bug ชื่อ field stats (`claimed_count`/`remaining_claims`) ที่ endpoint ไม่ได้คืน — นอก scope นี้ แจ้งเจ้าของแยก
