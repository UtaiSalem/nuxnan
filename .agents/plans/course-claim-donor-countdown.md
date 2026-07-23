# Plan: Course Claim with Donor Countdown (10s FIFO)

> เมื่อนักเรียนกด "รับแต้ม" จาก manual campaign → เปิด modal แสดง donor (FIFO) + นับถอยหลัง 10s + auto-claim ครบเวลา
> Cancel = ไม่ได้แต้ม (ต้องดูครบ)
> Pattern ต้นแบบ: `ui/pages/Earn/donates/index.vue` (countdown + points animation + auto-process)
> บันทึกข้อมูล: `viewed_donor_id` ในตาราง claim

## การตัดสินใจที่ล็อค (2026-07-23)
- FIFO — donation ที่เก่าสุดและยังมี remaining > 0
- Full profile — โชว์ donor เสมอ (override anonymous)
- Must watch 10s — cancel = ไม่ได้แต้ม (มีปุ่ม "หนี" + confirm)

## PHASE A — Backend

### A.1 Migration
`api/nuxnanravel/database/migrations/2026_07_23_..._add_donor_view_to_course_claims.php`
- `course_donations`:
  - `remaining_points` `bigint` nullable, default 0 — สำหรับ FIFO consumption tracking
  - Backfill: `UPDATE course_donations SET remaining_points = points_amount WHERE donation_type='point' AND status IN ('approved','completed')`
- `course_point_campaign_claims`:
  - `viewed_donor_id` unsignedBigInteger nullable, FK users nullOnDelete
  - `viewed_donation_id` unsignedBigInteger nullable, FK course_donations nullOnDelete
  - `viewed_at` timestamp nullable
  - Index `[viewed_donor_id]`

### A.2 Model updates
- `CourseDonation`: `$fillable`/`$casts` + คงเดิม (guarded). ตรวจ `remaining_points` อ่านเขียนได้
- `CoursePointCampaignClaim`: เพิ่ม fillable/relations `viewedDonor()`, `viewedDonation()`

### A.3 New endpoint: view
`POST /api/courses/{course}/points/campaigns/{campaign}/view`
- Route ใน `routes/learn/course.php` ในบล็อก points prefix
- Method ใหม่ใน `CoursePointCampaignController::view(Course, CoursePointCampaign)`
- Logic:
  - abort 404 ถ้า campaign ไม่ใช่ของ course
  - abort 422 ถ้า `!campaign->isClaimable()`
  - abort 403 ถ้า user ไม่ enrolled (CourseMember)
  - abort 422 ถ้าเคย claim campaign นี้แล้ว (dedupe check)
  - Query: `CourseDonation::where('course_id',$course->id)->where('donation_type','point')->whereIn('status',['approved','completed'])->where('remaining_points','>',0)->orderBy('created_at','asc')->first()`
  - ถ้ามี donation → return donor + donation info (full profile — user + profile relation)
  - ถ้าไม่มี (fund มาจาก ad revenue) → return `donor: null, donation: null, fallback_message: 'แต้มจากรายได้โฆษณา'`
  - Response:
    ```json
    {
      "donor": { "id","name","username","avatar","personal_code","profile":{...} } | null,
      "donation": { "id","points_amount","remaining_points","purpose","created_at","anonymous","donor_display_name" } | null,
      "expected_points": campaign.points_per_claim,
      "campaign_title": campaign.title,
      "view_duration_seconds": 10
    }
    ```
- **หมายเหตุ:** endpoint นี้ไม่ล็อค/จอง ไม่มี token — เพราะ actual claim ทำผ่าน `/claim` endpoint เดิม (ที่จะรับ `viewed_donor_id`/`viewed_donation_id`)

### A.4 Modify claim
- `POST /campaigns/{campaign}/claim` — รับ body optional `viewed_donor_id: int?`, `viewed_donation_id: int?`
- `CoursePointCampaignController::claim` — pass ไป service
- `CoursePointAccountService::claimManualCampaign` — signature เพิ่ม 2 params
- `grantCampaignClaim` — เพิ่ม params เก็บลง claim row
- ใน `CoursePointCampaignClaim::create(...)` — เพิ่ม fields
- **FIFO decrement:** หลัง claim สำเร็จ ถ้ามี `viewed_donation_id` → `CourseDonation::where('id',$viewedDonationId)->decrement('remaining_points', min($amount, currentRemaining))` (clamp)
- **Validation ที่ view time:** re-verify donation ยังมี remaining > 0 ตอน claim ด้วย — ถ้า concurrent gone ให้ pick new head หรือ set donor_id=null (ไม่ block claim เพื่อ UX)

### A.5 Test
เพิ่มเทสต์ 2 case ใน `CoursePointClaimTest.php`:
- view: FIFO returns oldest donation with remaining > 0
- claim with viewed_donor_id → records ในตาราง claim + decrements donation.remaining_points

## PHASE B — Frontend

### B.1 Composable
`ui/composables/useCoursePoints.ts` — เพิ่ม:
- `viewCampaign(campaignId): Promise<{donor, donation, expected_points, campaign_title, view_duration_seconds}>`
- ปรับ `claimCampaign(campaignId, viewed?: {donorId?, donationId?})` — ส่ง body

### B.2 Modal component
`ui/components/learn/course/points/CampaignClaimModal.vue`:
- Props: `visible, courseId, campaignId, campaignTitle, pointsPerClaim`
- Emit: `update:visible, claimed(result)`
- Watch visible → true: call `viewCampaign(campaignId)` เพื่อโหลด donor info
- Countdown 10s (default จาก response) + animated points (0 → pointsPerClaim) — pattern เดียวกับ `Earn/donates/index.vue` แถวๆ line 240–255
- Layout modal ใหญ่ (max-w-2xl หรือ full screen แบบ AdViewerModal):
  - ซ้าย/บน: DonorCard-inspired display (avatar ใหญ่, ชื่อ, username, bio ถ้ามี, personal_code, จำนวนบริจาค, จุดประสงค์)
  - ขวา/ล่าง: countdown timer ตัวใหญ่ + points animation + progress ring
  - Fallback view (donor null): แสดง "🎁 แต้มจากรายได้โฆษณา" + logo วิชา
- ปุ่ม "หนี" (top-right X) → confirm dialog: "แน่ใจไหม? ยังไม่ครบ 10s จะไม่ได้รับแต้ม" — ถ้า OK → close โดยไม่ claim
- ครบ 10s → auto-call `claimCampaign(id, {donorId, donationId})` → success animation (party icon + points delta) → auto close 2s → refresh panel
- ระวัง: clear interval ตอน onUnmounted
- Style: rounded-3xl, backdrop-blur-md, gradient border (violet→pink→amber)

### B.3 Wire panel
`CourseSupportPanel.vue`:
- import `CampaignClaimModal`
- state: `claimingCampaign = ref<CoursePointCampaign | null>(null)`
- แก้ `claim(id)` → เดิม claim ตรง; ใหม่ → หา campaign จาก `campaigns` แล้ว set `claimingCampaign` (เปิด modal)
- Mount `<CampaignClaimModal v-if="claimingCampaign" v-model:visible=... :course-id="course.id" :campaign-id="claimingCampaign.id" :campaign-title="claimingCampaign.title" :points-per-claim="claimingCampaign.points_per_claim" @claimed="onClaimed"/>`
- `onClaimed` → toast + claimBump pulse + fetchAvailable + fetchAccount

### B.4 Card behavior (ไม่ต้องเปลี่ยน API แค่ปรับ emit path)
`CoursePointClaimCard.vue` — ปุ่ม emit `claim(id)` เหมือนเดิม (parent handles the modal)

## PHASE C — Cleanup / verify
- ไม่มีสิ่งต้องลบ
- Grep: ไม่มี direct call `claimCampaign` ในที่อื่นที่ต้องอัพเดต (จาก audit ก่อนหน้า)
- ตรวจ modal ไม่ block scroll พังบน mobile

## Commit strategy (ชุดเล็ก)
1. migration + models
2. view endpoint + route + controller method
3. claim signature update + service + FIFO decrement + tests
4. composable + `CampaignClaimModal` + panel wire

## หมายเหตุ
- Backend concurrency: view ไม่มี lock (แค่ preview). Claim มี lock อยู่แล้ว (grantCampaignClaim). FIFO decrement ใน same transaction กับ claim
- ถ้า donation ที่ preview หมด (concurrent) — claim ยังไปได้ (fallback ให้ donor_id=null); UX: nice-to-have re-pick head แต่ไม่ทำรอบนี้
- ไม่ตกแต่ง privacy override เพิ่ม (คุณสั่ง full profile เสมอ)
