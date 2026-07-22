# Plan: Unified Course Support & Rewards Panel (course-level, template)

> ผู้ใช้ต้องการ UX สนับสนุน/บริจาคแต้มที่ **สอดคล้องกันทั้งโปรเจค** (public/โรงเรียน/รายวิชา)
> รอบนี้ทำ **ระดับรายวิชาก่อนเป็นต้นแบบ** โฟกัส support/บริจาค (โฆษณาไว้ทีหลัง)
> กลไกรับแต้ม = **campaign เดิม** (ไม่แตะ backend) แต่ **รวม donation list + การรับแต้ม ไว้หน้าเดียว** บนหน้า course
> เป้าหมาย UX: นักเรียนเข้ามาหน้ารายวิชา เห็นยอด/รายการบริจาค และถ้ามี campaign ให้รับ ก็กดรับได้ทันที ที่เดียวจบ

## หลักการ
- **ไม่แตะ backend** — API มีครบแล้ว: `points/campaigns/available`, `points/campaigns/{id}/claim`, `points/account`, `courses/{id}/donations`, donation modal
- Component ออกแบบให้ **templatable** (section ชัด, สไตล์ rounded-2xl กลาง) เพื่อนำไปใช้ระดับโรงเรียน/public รอบถัดไป
- ใช้ skill `hopeui-port` ดึง markup ต้นแบบก่อนสร้าง

## สร้าง: `ui/components/learn/course/points/CourseSupportPanel.vue`
Panel เดียวรวมทุกอย่าง ประกอบด้วย (บนลงล่าง):
1. **Header** — ไอคอน + "สนับสนุน & รับแต้ม" + ปุ่ม "บริจาคแต้ม" (แสดงเมื่อ `course.donation_enabled !== false`) เปิด `CourseDonationModal`
2. **Fund summary** (แถวสถิติเล็ก) — แต้มในกองทุน (`account.balance`), จำนวนผู้สนับสนุน (นับจาก donations), (optional) แต้มที่แจกแล้ว (`account.total_distributed`)
3. **Claim section "รับแต้ม"** — ลิสต์ `CoursePointClaimCard` จาก available campaigns
   - โหลดผ่าน `useCoursePoints(courseId).fetchAvailableCampaigns()` + `claimCampaign()` (มีอยู่แล้ว)
   - toast `+N แต้ม!` เมื่อสำเร็จ (useSweetAlert.toast)
   - ถ้าไม่มี campaign แต่กองทุน > 0 → hint "มีแต้มในกองทุน รอเจ้าของวิชาเปิดให้รับ" ; ถ้ากองทุน 0 → ซ่อน section
4. **Donations list "ผู้สนับสนุนล่าสุด"** — จาก `useCourseDonations().fetchCourseDonations(courseId, { per_page: 5 })`
   - แสดงเฉพาะ point donations ที่ approved/completed: `donor_display_name || 'ผู้สนับสนุนไม่ประสงค์ออกนาม'` + `points_amount`
   - empty state "ยังไม่มีผู้สนับสนุน"
5. **Footer link** — "ดูทั้งหมด/จัดการ" → `/Learn/Courses/{id}/support` (หน้ารายละเอียดเดิม)

Props: `{ course: any }` (มี id, name, user_id, donation_enabled, academy_id). ใช้ auth store สำหรับ balance ส่งเข้า modal
State: reuse composables ที่มี — ไม่เพิ่ม API

## Mount
- แก้ `ui/pages/Learn/Courses/[id]/index.vue` — แทนที่ `<LearnCoursePointsCoursePointClaimWidget ... />` (บรรทัด ~413)
  ด้วย `CourseSupportPanel` (import ตรงตาม pattern โปรเจค: `import CourseSupportPanel from '~/components/learn/course/points/CourseSupportPanel.vue'` — โปรเจคใช้ Nuxt default pathPrefix ต้อง import เอง)
- แสดงเฉพาะฝั่งนักเรียน (คง guard `!isCourseAdmin` เดิม) — เจ้าของใช้หน้า support/campaigns management อยู่แล้ว
- ส่ง `:course="course"` (object) แทน courseId เดิม

## ลบ/เก็บกวาด
- ลบ `ui/components/learn/course/points/CoursePointClaimWidget.vue` (สร้าง session นี้ ตอนนี้ถูกแทนด้วย panel — ยืนยันไม่มีที่อื่น import ก่อนลบ) ; **คง** `CoursePointClaimCard.vue` (panel reuse)
- ตรวจ `CourseSupportWidget.vue` — ถ้าไม่ถูก mount ที่ไหน (grep ยืนยัน) ให้ปล่อยไว้ก่อน (นอก scope) หรือแจ้ง deprecate

## Verify (Claude ตรวจ)
- ไม่รัน `npm run build` (ผู้ใช้ทำเอง) — ตรวจ import ถูก (pathPrefix), ไม่มี `:to=""`/v-bind ว่าง, ชื่อ component
- ยืนยัน panel แสดง 3 ส่วน (claim/donations/donate) + empty states
- ยืนยัน page mount แทน widget เดิม + guard `!isCourseAdmin`
- grep ยืนยันไม่มี dangling import ของ CoursePointClaimWidget หลังลบ

## Commit (ชุดเล็ก)
1. CourseSupportPanel component
2. mount ในหน้า course + ลบ widget เดิม

## หมายเหตุ (รอบถัดไป — ไม่ทำตอนนี้)
- นำ pattern panel ไปทำระดับโรงเรียน (`academies/[name].vue`) และ public/discovery ให้ consistent
- รวมโฆษณา (AdvertiseCtaWidget/CampaignWidget) เข้า pattern เดียวกัน
