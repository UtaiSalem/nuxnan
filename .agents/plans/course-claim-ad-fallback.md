# Plan: Ad Media Fallback + Empty Fallback for CampaignClaimModal

> ต่อยอด `course-claim-donor-countdown.md` — เพิ่มโหมด **ad** และ **empty** ใน modal
> ผู้ใช้ระบุ (2026-07-23): ดู ad ครบ = ตัว(ad delivery reward) + แต้ม campaign / ถ้าไม่มีทั้งคู่ = แสดง "ขอบคุณ + logo วิชา" + 10s
> ระบบหลักที่มีอยู่: `useAdDelivery` + `AdViewerModal.vue` + `/api/campaigns/widget?scope=course&course_id=X`
> ระดับโรงเรียน = รอบหน้า (คงเดิม)

## Modes ที่ modal จะรองรับ (3 modes)
1. **donor** — มี point donation (มีอยู่แล้ว, FIFO) → DonorCard + 10s
2. **ad** (ใหม่) — ไม่มี donation แต่มี ad campaign scope=course active → embed AdViewer + duration ของ ad + full delivery pipeline
3. **empty** (ใหม่) — ไม่มีทั้งคู่ → thank-you card + logo วิชา + 10s

## PHASE A — Backend

### A.1 Migration
`api/nuxnanravel/database/migrations/2026_07_23_..._add_viewed_ad_to_course_claims.php`
- เพิ่ม `viewed_ad_id` unsignedBigInteger nullable ใน `course_point_campaign_claims`
- FK → `campaigns.id` (ถ้าตารางชื่อนี้ — ยืนยันจาก CampaignController) nullOnDelete
- Index `[viewed_ad_id]`
- Reverse ใน down()
- รัน migrate หลัง implement

### A.2 Model
`CoursePointCampaignClaim`:
- $fillable / relation `viewedAd()` BelongsTo Campaign

### A.3 View endpoint expansion
`CoursePointCampaignController::view` — logic ใหม่ (คงพฤติกรรมเดิมเมื่อมี donation):
```
1. หา FIFO donation → ถ้ามี: return donor+donation (คงเดิม), view_duration_seconds=10
2. ถ้าไม่มี → หา ad campaign:
   - Campaign::where('scope_type','course')->where('course_id',$course->id)
     ->where('campaign_type','advertisement')->active(...)->orderBy('created_at','asc')->first()
   - (ยืนยัน scope logic กับ CampaignController::widget ที่มีอยู่ ให้ตรงกัน)
   - ถ้ามี: return { donor:null, donation:null, ad: {id, media_image, media_link, title, description, duration, advertiser}, view_duration_seconds: ad.duration }
3. ถ้าไม่มี ad ด้วย: return { donor:null, donation:null, ad:null, fallback:true, course_title, course_avatar, view_duration_seconds:10 }
```
Response ควรมี field `mode: 'donor'|'ad'|'empty'` เพื่อให้ frontend ไม่ต้อง infer

### A.4 Claim
- `POST /claim` รับ `viewed_ad_id` เพิ่ม (validate exists:campaigns,id, nullable, int)
- `CoursePointAccountService::claimManualCampaign` accepts `?int $viewedAdId = null`
- `grantCampaignClaim` เก็บใน claim row (extend extras array)

### A.5 Tests
เพิ่มใน `CoursePointClaimTest.php`:
- test_view_returns_ad_when_no_donation
- test_view_returns_fallback_when_no_donation_no_ad
- test_claim_records_viewed_ad

## PHASE B — Frontend

### B.1 Composable
`useCoursePoints.claimCampaign` extend รับ `viewed_ad_id?` เพิ่ม (merge กับ donor/donation)

### B.2 CampaignClaimModal.vue — 3 modes
- state: `mode: 'donor'|'ad'|'empty'`, `ad: {...}`, `deliveryToken/deliveryId` (สำหรับ ad mode)
- `start()` โหลด `viewCampaign()` → set mode จาก response
- **donor mode:** ปัจจุบัน (10s countdown + DonorCard + animated points) — คงเดิม
- **ad mode:**
  - เรียก `useAdDelivery().start(ad.id)` → รับ token + deliveryId + requiredDuration (คือ ad.duration)
  - Layout: media ใหญ่ (video autoplay muted loop หรือ img) + panel ข้าง ๆ (advertiser/title/description + countdown ring)
  - Heartbeat ทุก 5s ระหว่าง countdown (`document.visibilityState`)
  - ครบเวลา → `complete(deliveryId, token)` → toast "ได้รางวัลจาก ad: +N แต้ม" → เรียก `claimCampaign(id, { viewed_ad_id: ad.id })` → success 🎉 → close
  - Cancel policy = ต้องดูให้ครบ (เหมือน donor mode)
- **empty mode:**
  - Layout: logo วิชา (course avatar) + ข้อความ "ขอบคุณที่ร่วมสนับสนุนรายวิชานี้" + countdown 10s + animated points
  - ครบเวลา → `claimCampaign(id, {})` → success 🎉 → close

### B.3 Panel wire — คงเดิม (modal handle 3 modes เอง)

## Cleanup
- ไฟล์เดิม `CampaignClaimModal.vue` ที่มี fallback "🎁 แต้มจากรายได้โฆษณา" — แทนที่ทั้งบล็อกด้วย ad/empty modes
- Reuse markup ต้นแบบจาก `AdViewerModal.vue` สำหรับ ad mode

## Commit (ชุดเล็ก)
1. migration + model
2. view endpoint expansion + tests
3. claim signature + service (record viewed_ad_id) + tests
4. modal 3-mode + wire

## หมายเหตุ
- ยืนยันจาก `AdViewerModal.vue`: `useAdDelivery().start()` return `token/deliveryId/requiredDuration` — ใช้ requiredDuration เป็น countdown เลย
- Impression tracking ไม่ต้องแยกอีก — delivery pipeline ครอบคลุมแล้ว
- Concurrent safety: ถ้า ad ถูก consume ระหว่าง view/claim (rare) — best effort, ไม่ block claim
- ระดับโรงเรียน = round 2 (คงเดิม)
