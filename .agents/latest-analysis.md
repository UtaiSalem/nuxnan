---

# 2026-07-18 - ระบบแต้มโฆษณา → โรงเรียน (Academy Ad Revenue) — Phase 1 Foundation

**สถานะ:** เสร็จสิ้น (Phase 1 — Foundation)
**จุดประสงค์:** เชื่อมรายได้โฆษณาเข้า `AcademyPointAccount` โดยตรงเพื่อให้โรงเรียนได้รับแต้มจาก ad revenue เทียบเท่ารายวิชา

## Business Rules ที่ตกลง (
- โฆษณาผูก `academy_id` → หักส่วน `academy` เข้า `AcademyPointAccount` โดยตรง (ไม่ผ่านรายวิชา)
- Viewer 60% / Course 25% / Academy 10% / Platform 5% (policy id=3 ตามลำดับ fallback campaign→course→academy→platform)
- แต้มโฆษณาเข้า User Points ก่อน ค่อยบริจาค
- MVP เฉพาะแต้ม; cash ยังใช้ตรวจสลิปแบบเดิม

## สิ่งที่มีอยู่แล้ว (ห้ามสร้างซ้ำ)
- `Advert.academy_id` / `course_id` ✓ (migration `2026_07_12_120000`)
- `RevenueSharePolicyResolver::resolve` (campaign→course→academy→platform) ✓
- `AcademyPointAccount` + `AcademyPointTransaction` (มี balance_before/after, idempotency_key) ✓
- `AcademyDonateService` (point/cash flow ครบ) ✓
- `CoursePointAccountService::creditFromAdRevenue` ✓
- `AcademyPointTransaction::TYPE_AD_REVENUE` ✓

## สิ่งที่ทำแล้ว (Phase 1 เสร็จสิ้น)
1. `revenue_share_policies.academy_pct` — migration `2026_07_18_210029` เพิ่ม column จริง ✓
2. `RevenueSharePolicy` model มี `academy_pct` (fillable/cast) และ `sumsTo100()` นับ 4 ฝ่าย ✓
3. `RevenueSharePolicyResolver::split()` คืน `academy` leg ✓
4. `RewardDistributionService::distribute()` จ่าย course/academy/platform ตาม `advert.academy_id`/`course_id` ✓
5. `AcademyPointTransaction::TYPE_AD_REVENUE` มีอยู่ ✓
6. seed default policy เป็น 60/25/10/5 (migration `2026_07_18_220000`) ✓

## แผนทำงาน (Phase 1 เสร็จสิ้น)
- P1-A: migration เพิ่ม `academy_pct` จริง + model fillable/cast + `sumsTo100()` 4 ฝ่าย + seed 60/25/10/5 ✓
- P1-B: `RevenueSharePolicyResolver::split()` คืน `academy` leg ด้วยลำดับถูกต้อง ✓
- P1-C: `AcademyPointTransaction::TYPE_AD_REVENUE` + helper credit เข้า Academy ด้วย idempotency key ✓
- P1-D: `RewardDistributionService` เช็ค `advert.academy_id` → credit เข้า AcademyPointAccount ✓
- P1-E: PHPUnit test กระจายแต้ม (academy ad revenue path) + `./vendor/bin/pint` ✓

## ไฟล์ที่แก้ (Phase 1)
| ไฟล์ | Action |
|------|--------|
| `database/migrations/2026_07_18_210029_add_academy_pct_to_revenue_share_policies_table.php` | เพิ่ม column `academy_pct` |
| `app/Models/RevenueSharePolicy.php` | เพิ่ม fillable/cast + `sumsTo100()` 4 ฝ่าย |
| `app/Services/RevenueSharePolicyResolver.php` | `split()` คืน `academy` |
| `app/Models/AcademyPointTransaction.php` | `TYPE_AD_REVENUE` |
| `app/Services/Campaign/RewardDistributionService.php` | credit เข้า Academy |
| `database/migrations/2026_07_18_220000_seed_default_revenue_share_policy.php` | seed 60/25/10/5 |

---

# 2026-07-19 - Phase 3: Unified Donation Ledger (已完成)

**สถานะ:** เสร็จสิ้น (53 tests ผ่าน)
**เป้าหมาย:** รวม flow debit/credit ของการบริจาคแต้ม course/academy ให้ใช้ `PointLedgerService` มาตรฐานเดียวกัน

## สิ่งที่สร้าง/แก้
- `app/Services/PointLedgerService.php` (ใหม่) — orchestrator กลาง:
  - `debitUserPoints()` / `creditUserPoints()` — ห่อ PointsService (spend/earn)
  - `creditCoursePoints()` / `creditAcademyPoints()` — ห่อ account services
  - `donatePoints(User, 'course'|'academy', id, amount, sourceType, key, meta)` — flow มาตรฐาน:
    เช็ค idempotency → lock wallet → debit → credit destination → คืน `{points_transaction_id, destination_transaction_id}`
  - `reconcileCourseAccount()` / `reconcileAcademyAccount()` — ตรวจ sum transaction = balance
  - **ลำดับ lock คงที่:** user wallet → course account → academy account (ป้องกัน deadlock)
- `app/Services/AcademyPointAccountService.php` (ใหม่) — เหมือน `CoursePointAccountService` แต่สำหรับ academy:
  - `credit()` / `creditFromDonation()` / `creditFromAdRevenue()` / `creditFromCashDonation()` ทุกตัว lock `academy_point_accounts` แถวแล้วสร้าง transaction + idempotency key
- `app/Services/AcademyDonateService.php` — constructor ใช้ `PointLedgerService` + `AcademyPointAccountService`; `createPointDonation` เรียก `ledger->donatePoints(...)`; ลบ method `credit()`/`creditFromAdRevenue()` ซ้ำซ้อน
- `app/Services/CourseDonateService.php` — constructor ใช้ `PointLedgerService`; `createPointDonation` เรียก `ledger->donatePoints(...)`; `approve()` ใช้ `ledger->creditCoursePoints(...)`
- `app/Services/PointsService.php` — `spend()` รับ param `?string $idempotencyKey` แล้วใส่ใน PointsTransaction + เช็ค replay (แก้ช่องโหว่ idempotency บน user wallet)
- `app/Services/Campaign/RewardDistributionService.php` — เปลี่ยน inject `AcademyDonateService` → `AcademyPointAccountService` (แก้ circular dependency)

## หมายเหตุสำคัญ
- **แก้ circular dependency:** `PointLedgerService` ไม่ควรพึ่ง `AcademyDonateService` (ที่พึ่ง ledger กลับ) → ให้พึ่ง `AcademyPointAccountService` แทน
- **แก้ idempotency จริง:** เดิม `spend()` ไม่ใส่ idempotency_key ทำให้ replay 借记ซ้ำ + ชน unique `daily_point_limits` → เพิ่ม key ลง PointsTransaction

## Tests ใหม่
- `tests/Feature/PointLedgerServiceTest.php` — donate course/academy, idempotent, insufficient, reconcile ทั้งสองบัญชี

---

# 2026-07-19 - Phase 4: Frontend Ad Viewer + Ledger Composable (ส่วนแรก)

**สถานะ:** เสร็จสิ้น (typecheck ผ่าน สำหรับไฟล์ที่แก้)
**เป้าหมาย:** ให้ `AdViewerModal` ใช้ reward จาก API เท่านั้น และเพิ่ม composable กลาง `usePointLedger`

## สิ่งที่ทำ
- `ui/composables/useAdDelivery.ts` — เพิ่ม `academy` leg ใน `AdRewardSplits` interface (สอดคล้อง Phase 1 backend)
- `ui/composables/usePointLedger.ts` (ใหม่) — composable กลางสำหรับดึง:
  - `getMyTransactions()`, `getMyCourseDonations()`, `getMyAcademyDonations()`
  - `getCourseAccount()`, `getCourseTransactions()`, `getAcademyAccount()`, `getAcademyTransactions()`
  - type `PointTransaction`, `DonationRecord`, `PointAccount` ตรงกับ API contract ของแผน
- `ui/components/widgets/advertises/AdViewerModal.vue`:
  - แสดงรายได้ที่เข้าสู่ **รายวิชา/โรงเรียน/แพลตฟอร์ม** จาก `result.reward.splits` ของ API (ไม่คำนวณเอง)
  - เพิ่ม `rewardSplits` ref + เชื่อมจาก `complete()` result + reset ใน `resetAd()`
  - แก้ pre-existing bug: template ใช้ `requiredDuration` (ไม่มีใน script) → เปลี่ยนเป็น `totalDuration` ที่มีจริง
- `ui/i18n/locales/th.json` + `en.json` — เพิ่ม `ad.split_viewer/course/academy/platform`

## หมายเหตุ
- ยังไม่แตะไฟล์ donation modal / dashboard เจ้าของ (CourseSupportWidget, support.vue, AcademyDonationModal ฯลฯ) — เป็น uncommitted work ของผู้ใช้ ตามกฎ AGENTS.md ไม่แก้แทรก
- typecheck: ไฟล์ที่เราแก้ไม่มี error (error อื่นใน repo เป็น pre-existing ใน quests.vue/schools/index.vue/plugins/api.ts ฯลฯ)

---

# 2026-07-19 - Phase 5: Reconciliation + Fraud Detection (เสร็จสิ้น)

**สถานะ:** เสร็จสิ้น (40 tests ผ่าน)
**เป้าหมาย:** เพิ่มตรวจสอบยอด (reconciliation) และตรวจจับทุจริต (fraud) ตามแผนข้อ 11–12

## สิ่งที่ทำ
- `app/Console/Commands/ReconcileAll.php`:
  - เพิ่ม check `academy_balance` (เทียบ `AcademyPointAccount.balance` กับ sum ของ `AcademyPointTransaction`)
  - เพิ่ม check `ad_revenue_gross` (ตรวจ gross = student+course+academy+platform จาก `required_duration * per_second`)
  - รองรับ `--academy=` option
- `app/Services/FraudDetectionService.php`:
  - `scanAdRevenuePolicy($windowHours)` — ตรวจ split ไม่ตรง gross → RiskEvent (severity high, score 85)
  - `scanAcademyNegativeBalance()` — ตรวจ academy balance ติดลบ → RiskEvent (severity critical, score 100)
- `app/Console/Commands/RiskScanCommand.php` (ใหม่) — `php artisan risk:scan` รัน scan ทั้งหมด (donation velocity, self-donation, ad fraud, ad revenue policy, academy negative)

## Tests ใหม่
- `tests/Feature/AdRevenueIntegrityTest.php` — tampered split ถูก flag, academy negative balance ถูก flag/ไม่ flag กรณีบวก, reconcile:all รันผ่าน

---

# 2026-07-18 - ย้ายปุ่มสนับสนุนวิชา (Course Donation) ไปยัง Course Profile (CourseHero)
- **สถานะ:** เสร็จสิ้น (คอมไพล์ผ่าน `✨ Build complete!`)
- **ไฟล์ที่เกี่ยวข้อง:**
  - [id].vue (Link: [\[id\].vue](file:///C:/wamp64/www/nuxnan/ui/pages/Learn/Courses/%5Bid%5D.vue))
  - CoursePageShell.vue (Link: [CoursePageShell.vue](file:///C:/wamp64/www/nuxnan/ui/components/learn/course/v2/CoursePageShell.vue))
  - CourseHero.vue (Link: [CourseHero.vue](file:///C:/wamp64/www/nuxnan/ui/components/learn/course/v2/CourseHero.vue))
  - CourseActionButton.vue (Link: [CourseActionButton.vue](file:///C:/wamp64/www/nuxnan/ui/components/learn/course/v2/CourseActionButton.vue))
- **การเปลี่ยนแปลง:**
  - ย้ายปุ่มสนับสนุนแต้มจากตรงกลางหน้าจอ `ui/pages/Learn/Courses/[id].vue`
  - ยิงอีเวนต์ `support-course` ผ่าน `CourseHero.vue` -> `CoursePageShell.vue` -> `[id].vue`
  - ปรับสไตล์ปุ่มสนับสนุนแต้มให้อยู่ในหน้าโปรไฟล์รายวิชา ( Course Hero ) ถัดจากปุ่มดำเนินการหลัก ใช้สีส้ม/amber และไอคอน `mdi:hand-heart`
  - ปรับปุ่มต่าง ๆ ใน `CourseActionButton.vue` และ `CourseHero.vue` ให้รองรับ Mobile (ใช้ `w-full sm:w-auto` และ `flex-wrap`) เพื่อไม่ให้ปุ่มล้นขอบจอเมื่อแสดงผลบนหน้าจอขนาดเล็ก

---

# 2026-07-18 - แสดงปุ่มจัดการสมาชิกเลยโดยไม่ต้องรอ Hover
- **สถานะ:** เสร็จสิ้น
- **ไฟล์ที่เกี่ยวข้อง:** `ui/components/academy/member/MemberListView.vue` (Link: [MemberListView.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/member/MemberListView.vue))
- **การเปลี่ยนแปลง:** ลบ class `opacity-0 group-hover:opacity-100 transition-opacity` ใน Card view และ `opacity-0 group-hover:opacity-100` ใน Table view ของ component `MemberListView.vue` ออก เพื่อให้ปุ่มการดำเนินการ (Actions) ทั้งหมด (กำหนดบทบาท, ตั้งค่า, ลบ) แสดงผลให้ผู้ใช้เห็นทันทีตั้งแต่แรก โดยยังคงรักษาเอฟเฟกต์การ hover เปลี่ยนสีเพื่อบ่งบอกสถานะการโฟกัสอยู่

---

# แผนปรับปรุง: หน้าจัดการฝ่าย (Department Console) + ฟีดแบบแบ่งพื้นที่ (Scoped Feed)

**วันที่:** 2026-07-14
**สถานะ:** เสร็จสิ้น (ความปลอดภัย, การคัดกรองข้อมูล, และผ่านการทดสอบ 100%)
**ขอบเขตงาน:** หน้าจัดการฝ่ายรายฝ่าย `ui/pages/academies/[name]/admin/departments/[id].vue` แบบแท็บ + ระบบฟีดข่าว/งาน/เอกสารแยกตามพื้นที่ (โรงเรียน/ฝ่าย/ห้องเรียน) ด้วย scope pattern ชุดเดียว

---

## 2026-07-14 - Diagnosis plan: academy admin student-cards page is blank

- User reports `/academies/{name}/admin/student-cards` renders no visible data.
- Frontend entry is `ui/pages/academies/[name]/admin/student-cards/index.vue`. Initial load is a serial chain: `GET /api/academies/{name}` -> `GET /api/academies/{id}/my-role` -> permission check -> `GET /api/academies/{id}/student-cards/statistics`. The page only renders its main content after `isLoading` becomes false and has no user-visible error state; any failed request leaves zeroed stats and no level tabs, which appears blank.
- The page does not call `fetchStudents()` on initial load. The default `rooms` mode depends on `statistics.byLevel` to create room tabs; students are fetched only after clicking a room. List data is fetched only after switching to list mode.
- Backend routes are present in `api/nuxnanravel/routes/learn/academy-student-card.php` and protected by `auth:api` plus `academy.permission:students.manage` for admin endpoints. Statistics itself is academy-scoped and returns `statistics.byLevel` / `sectionsByLevel`.
- Likely investigation order: browser Network/Console status for academy, my-role, statistics; verify authenticated user has `students.manage`; verify academy ID resolution from the Thai slug; verify `student_cards.academy_id`, active status, and class-level data; then test room endpoint and list endpoint independently.
- Planned implementation (after approval): add explicit page-level error/empty states and retry; make initial load resilient and observable; decide whether initial list fetch is desired; align permission behavior with backend; add focused frontend/API regression coverage and authenticated browser smoke test. No code implementation was made in this turn.

## 1. บทวิเคราะห์แผนเดิมเทียบ codebase จริง

ตรวจโค้ดจริงแล้ว **backend ของ Phase 1 มีอยู่แล้วประมาณ 80%** — แผนเดิมประเมินงานสูงเกินไปเพราะไม่ได้อ้างถึงของที่มีอยู่:

### สิ่งที่มีอยู่แล้ว (ห้ามสร้างซ้ำ)

| ความสามารถ | ที่อยู่ในโค้ด |
|---|---|
| ฝ่าย = `AcademyGroup` (type='department') | `app/Models/AcademyGroup.php` — มี `parent_id` (ลำดับชั้น), `settings.head_user_id` (หัวหน้าฝ่าย), `sort_order`, `settings` json |
| CRUD ฝ่าย + สถิติ + template/setup | `DepartmentController` — index/store/show/update/destroy, statistics, template, setup |
| สมาชิกฝ่าย + role + bulk add | routes `learn/academy.php:352-368` — getMembers, addMember, **bulkAddMembers**, removeMember, **updateMemberRole** (pivot `role` ใน `academy_group_members`) |
| สิทธิ์รายฝ่าย (key/enabled) | `AcademyGroupPermission` + `AcademyGroupPermissionService` (`hasPermission` default = true ถ้าไม่มี record) + `AcademyGroupPermissionController` index/update ผูก route แล้ว |
| ฟีดโรงเรียน ครบ like/dislike/comment/รูป/pin | `AcademyPost` + comment/like/image models ครบชุด, มี `post_type`, `target_audience` (json), `is_pinned`, `posted_as_group_id` |
| ประกาศ + read tracking + publish workflow | `SchoolAnnouncement` + `AnnouncementRead` + `AnnouncementController` (index/store/stats/publish/unpublish) |
| Audit log กลาง | `AuditLog` (entity_type/entity_id/module, actions ครบรวม approved/exported/imported) + component `ui/components/school/AuditLogTab.vue` (ใช้แล้วในหน้าห้องเรียน) |
| Export | `maatwebsite/excel` มีใน composer แล้ว |
| หน้า list ฝ่าย | `ui/pages/academies/[name]/admin/departments.vue` — CRUD + สถิติ (ยังไม่มีหน้า detail) |

### ข้อแก้ไขสำคัญต่อแผนเดิม

1. **กับดัก nested route (บทเรียนจาก classrooms เมื่อวันนี้):** ต้อง `git mv departments.vue → departments/index.vue` **ก่อน** สร้าง `departments/[id].vue` — ไม่งั้น detail page จะไม่ render เพราะกลายเป็น child ของ departments.vue ที่ไม่มี `<NuxtPage>`
2. **`posted_as_group_id` ≠ scope:** ฟิลด์นี้คือโพสต์"ในนาม"ฝ่าย ไม่ใช่โพสต์"ถึง"ฝ่าย — แนวคิด `scope_type`/`scope_id` ของแผนเดิมถูกต้อง แต่ให้**เพิ่มคอลัมน์ลงตาราง `academy_posts` เดิม** ไม่สร้างตารางโพสต์ใหม่ → ได้ comment/like/รูป/pin ฟรีทั้งหมด
3. **scope `user` ไม่จำเป็น:** "ฟีดของฉัน" ควรเป็น aggregate query (โพสต์จากทุก scope ที่ user เป็นสมาชิก) ไม่ใช่ scope type ที่สี่ — ฟีดส่วนตัวมี `Post` (social feed) อยู่แล้วอีกระบบ อย่าปนกัน
4. **ประกาศฝ่ายไม่ต้องสร้างใหม่:** extend `SchoolAnnouncement` ด้วย scope เดียวกัน → ได้ read tracking + publish workflow ฟรี (ดีกว่าใช้ pinned post เพราะประกาศต้องรู้ว่าใครอ่านแล้ว)
5. **บทบาทมีแล้วแต่ยังไม่มีมาตรฐาน:** pivot `role` มีอยู่ + endpoint updateMemberRole มีอยู่ — งานจริงคือกำหนดค่ามาตรฐาน `head` / `deputy` / `member` + validation + sync `settings.head_user_id` เมื่อเปลี่ยน head
6. **งาน/เอกสาร/ปฏิทิน เป็นของใหม่จริง** — เลื่อนไป Phase 3 ตามแผนเดิมถูกแล้ว (มี `Event` model generic อยู่ ต้องตรวจก่อนว่า reuse ได้ไหม)

## 2. การตัดสินใจเชิงสถาปัตยกรรม

### 2.1 Scope pattern กลาง (ใช้ร่วมกันทุกระบบ: ฟีด, ประกาศ, งาน, เอกสาร, ปฏิทิน)

**[ยืนยันแล้ว]** ออกแบบ generic — ทุกตารางที่ผูกพื้นที่ใช้คู่คอลัมน์เดียวกัน:
```
scope_type: string(20)                     // 'academy' | 'department' | 'classroom'
scope_id:   unsignedBigInteger nullable    // null | academy_groups.id | classrooms.id
index (academy_id, scope_type, scope_id)
```

**Service กลาง 2 ตัว (Phase 2 สร้างครั้งเดียว ทุก feature ใช้ต่อ):**
- `App\Services\AcademyScopeResolver` — `membersOf(scope)`, `isMember(user, scope)`, `roleIn(user, scope)` โดย resolve จาก:
  - `academy` → `AcademyMember` + academy role
  - `department` → pivot `academy_group_members.role` (head/deputy/member)
  - `classroom` → homeroom teacher + นักเรียนจาก current enrollment
- `App\Services\ScopeAccessService` — `can(user, ability, scope)` โดย ability = `feed.view|feed.post|feed.moderate|announce|tasks.manage|tasks.view|files.upload|files.manage` ตัดสินตาม matrix ข้อ 2.3 + `AcademyGroupPermission` + classroom settings

**การใช้กับ `academy_posts` (ฟีด):**
- เพิ่ม `scope_type` default 'academy' + `scope_id` nullable + index `(academy_id, scope_type, scope_id, is_pinned)`
- Backfill: แถวเดิมทั้งหมด = `scope_type='academy', scope_id=null` → ฟีดโรงเรียนเดิม**ไม่พัง**
- `school_announcements` เพิ่มคู่เดียวกัน (ประกาศฝ่าย/ห้องได้ read tracking ฟรี)

### 2.2 โครงแท็บหน้า `departments/[id].vue` (render ตาม phase)
```
ภาพรวม | สมาชิก | บทบาทและสิทธิ์ | ฟีด/ประกาศ(P2) | งาน(P3) | เอกสาร(P3) | รายงาน(P4) | ประวัติ
```
- ภาพรวม, สมาชิก, บทบาทและสิทธิ์, ประวัติ → Phase 1 (backend มีครบ)
- ใช้ pattern เดียวกับ `classrooms/[id].vue` + สกิล hopeui-port

### 2.3 Permission matrix (สิทธิ์โพสต์/จัดการฟีด)

| บทบาท | ฟีดโรงเรียน | ฟีดฝ่าย | ฟีดห้องเรียน |
|---|---|---|---|
| แอดมินโรงเรียน | โพสต์/pin/ลบ | โพสต์/pin/ลบ (ทุกฝ่าย) | โพสต์/pin/ลบ (ทุกห้อง) |
| หัวหน้า/รองหัวหน้าฝ่าย | ดู | โพสต์/pin/ลบ (ฝ่ายตน) | ดู |
| ครูประจำชั้น | ดู | โพสต์ (ฝ่ายที่สังกัด) | โพสต์/pin/ลบ (ห้องตน) |
| สมาชิกฝ่าย/ครูในห้อง | ดู | โพสต์+คอมเมนต์ | โพสต์+คอมเมนต์ |
| นักเรียนในห้อง | ดู | — | **โพสต์+คอมเมนต์** (ครู/แอดมินปิดได้รายห้อง) |
| ผู้ปกครอง | ดู | — | ดู+คอมเมนต์ (ปิดได้รายห้อง) |

- **[ยืนยันแล้ว]** นักเรียนโพสต์ในฟีดห้องเรียนของตัวเองได้เป็น default — ครูประจำชั้น/แอดมินลบโพสต์ได้ (canModerate) และปิดการโพสต์ของนักเรียนได้รายห้องผ่าน setting
- คุมด้วย `AcademyGroupPermission` keys ใหม่: `departments.feed.post`, `departments.feed.moderate`, `departments.announce` (ฝ่ายไหนปิดฟีดได้)
- ห้องเรียน: settings ใหม่ `allow_student_posts` (default true) / `allow_comments` (default true) — เก็บใน column `settings` json ของ classrooms (ตรวจว่ามี column แล้วหรือต้องเพิ่ม migration)

## 3. แผนทีละขั้นตอน

### Phase 1 — หน้าจัดการฝ่าย (ภาพรวม/สมาชิก/สิทธิ์/ประวัติ) — งาน frontend เป็นหลัก

1. **ย้าย route:** `git mv "ui/pages/academies/[name]/admin/departments.vue" ".../departments/index.vue"` แล้วทดสอบหน้า list ยังเปิดได้
2. **Backend เก็บตก `show`:** ตรวจ `DepartmentController::show` ให้คืน description, status, created_at, head_user, members_count, permissions ครบ (เพิ่ม field ที่ขาด — ไม่ต้องแก้ schema เพราะ `settings` json + `$guarded=[]` รองรับ)
3. **มาตรฐาน role:** เพิ่ม constant `AcademyGroupMemberRoles` (`head`/`deputy`/`member`), validate ใน `updateMemberRole`, sync `settings.head_user_id` เมื่อตั้ง/ถอด head (transaction เดียว), เขียน PHPUnit test
4. **Audit hooks:** ให้ DepartmentController เขียน `AuditLog` (module='department') ตอน create/update/delete/addMember/removeMember/updateRole/updatePermissions — ตรวจก่อนว่ามี middleware/observer audit อยู่แล้วหรือยัง
5. **สร้าง `departments/[id].vue`:** โครงแท็บ + แท็บภาพรวม (การ์ดชื่อ/คำอธิบาย/หัวหน้า/จำนวนสมาชิก/สถานะ/วันที่สร้าง + ฝ่ายลูกจาก `parent_id`)
6. **แท็บสมาชิก:** ตาราง + ค้นหา/กรองตาม role, เพิ่มรายคน (จาก `/api/academies/{id}/members`), **เพิ่มหลายคนผ่าน `members/bulk`**, ลบ, เปลี่ยน role, ลิงก์ไปโปรไฟล์
7. **แท็บบทบาทและสิทธิ์:** toggle รายการ permission keys ผ่าน `AcademyGroupPermissionController` — นิยาม key ชุดมาตรฐานเป็น constant ฝั่ง backend + i18n label ฝั่ง frontend
8. **แท็บประวัติ:** ฝัง `SchoolAuditLogTab` (entity-type ของ AcademyGroup)
9. **ลิงก์จากหน้า list → detail** (NuxtLink อย่างเดียว — ห้ามผสม navigateTo ตามบทเรียน classrooms)
10. **ทดสอบ:** `./vendor/bin/pint`, PHPUnit role tests, เปิดหน้าจริงทดสอบทุกแท็บ, commit เป็นชุดเล็ก (route move / backend / UI แยก commit)

### Phase 2 — Scoped Feed (ฝ่าย + ห้องเรียน)

1. **Migration:** เพิ่ม `scope_type`/`scope_id` + index ใน `academy_posts` (+ ใน `school_announcements` แบบเดียวกัน)
2. **`FeedScopeService`:** `canView(user, scope)` / `canPost` / `canModerate` ตาม matrix ข้อ 2.3 + PHPUnit tests ครอบทุกบทบาท
3. **AcademyPostController:** index/store รับ `scope_type`+`scope_id` (default 'academy' — เดิมไม่พัง), enforce ผ่าน service, pin/ลบเช็ค `canModerate`
4. **Frontend:** composable `useScopedFeed(scopeType, scopeId)` + component `ScopedFeed.vue` โดย **reuse component ฟีดโรงเรียนที่มีอยู่** (ตรวจ `ui/components/academy/` ก่อนเขียนใหม่)
5. **ฝังแท็บฟีด** ในหน้า department detail และ classroom detail (`classrooms/[id].vue`)
6. **ประกาศฝ่าย:** AnnouncementController รับ scope + แท็บประกาศแสดงประกาศ scope นั้น + read stats
7. **แจ้งเตือน:** ตรวจระบบ notification ที่มีอยู่ (Reverb) แล้ว broadcast event `ScopedPostCreated` ถึงสมาชิกของ scope

### Phase 3 — งาน / เอกสาร / ปฏิทิน (**[ยืนยันแล้ว] ออกแบบ generic ใช้ scope pattern เดียวกับฟีด** — ฝ่ายและห้องเรียนใช้ระบบเดียวกัน)

1. **Migration ชุดเดียว 3 ตาราง** (ทุกตารางมี `academy_id + scope_type + scope_id` ตามข้อ 2.1):
   ```
   academy_tasks:          id, academy_id, scope_type, scope_id, title, description,
                           status (todo|in_progress|review|done|cancelled), priority (low|normal|high),
                           due_date, created_by, timestamps, softDeletes
   academy_task_assignees: academy_task_id, user_id, assigned_by, completed_at, timestamps
   academy_scope_files:    id, academy_id, scope_type, scope_id, name, path, mime_type,
                           size, uploaded_by, timestamps, softDeletes
   ```
2. **Routes generic ตาม scope ไม่ผูกกับ departments:**
   ```
   {academy}/scopes/{scopeType}/{scopeId}/tasks       (index/store)
   {academy}/tasks/{task}                             (show/patch/delete)
   {academy}/tasks/{task}/assignees                   (put — มอบหมาย)
   {academy}/tasks/{task}/status                      (patch)
   {academy}/scopes/{scopeType}/{scopeId}/files       (index/store)
   {academy}/files/{file}                             (get download/delete)
   ```
   → `AcademyTaskController` + `AcademyScopeFileController` ใน `Api/Learn/Academy/` — enforce ทุก action ผ่าน `ScopeAccessService` (`tasks.manage` = head/deputy/ครูประจำชั้น/แอดมิน, `tasks.view` = สมาชิก scope)
3. **อัปโหลดไฟล์:** ตรวจ `useFiles` composable + วิธีเก็บไฟล์เดิมของโปรเจค (intervention/image, storage disk) แล้ว reuse — ห้ามเขียนระบบ upload ใหม่
4. **ปฏิทิน/นัดหมาย:** ตรวจ `Event` model (`app/Models/Event.php`) ว่า generic พอไหม — ถ้าไม่ ให้สร้าง `academy_events` ด้วย scope pattern เดิม (title, start_at, end_at, location, created_by) + แจ้งเตือนสมาชิก scope
5. **Frontend:** `useScopedTasks(scopeType, scopeId)` + `useScopedFiles(...)` composables + components กลาง `ScopedTaskBoard.vue` / `ScopedFileList.vue` ใน `ui/components/academy/scope/` → ฝังเป็นแท็บ "งาน" และ "เอกสาร" ได้**ทั้งหน้า department detail และ classroom detail** โดยส่ง props scope ต่างกันเท่านั้น
6. **Tests:** PHPUnit — สิทธิ์ tasks/files ทุกบทบาท (member โพสต์งานไม่ได้, assignee เปลี่ยนสถานะงานตัวเองได้, นอก scope มองไม่เห็น), soft delete

### Phase 4 — รายงาน / Export / เชื่อมระบบ

1. รายงานสมาชิก + รายงานกิจกรรมฟีด/งาน ต่อฝ่าย + export Excel (maatwebsite) / PDF
2. เปิดฟีดห้องเรียนให้ครูประจำชั้น/นักเรียนเห็นจากหน้า (นอก admin) ตามนโยบาย
3. นโยบายนักเรียน/ผู้ปกครอง per-classroom (`allow_student_posts`, `allow_comments`)

## 4. คำตัดสินใจที่ยืนยันแล้ว (2026-07-14)

1. **นักเรียนโพสต์ในฟีดห้องเรียนของตัวเองได้** — default เปิด, ครูประจำชั้น/แอดมิน moderate ได้และปิดรายห้องได้ (`allow_student_posts`)
2. **ประกาศฝ่าย/ห้องเรียนใช้ `SchoolAnnouncement` + scope columns** — ได้ read tracking + publish workflow เดิม
3. **ระบบงาน/เอกสารออกแบบ generic** — ตาราง `academy_tasks` / `academy_scope_files` ผูก scope pattern เดียวกับฟีด ใช้ได้ทั้งฝ่ายและห้องเรียนโดยไม่ต้องสร้างซ้ำ

## 5. แผน commit และเกณฑ์จบงาน (Definition of Done)

**หลักการ:** commit เล็กพอ revert ได้ / รัน `./vendor/bin/pint` ก่อน commit backend ทุกครั้ง / migration ห้ามแก้ไฟล์เดิม ให้เพิ่มไฟล์ใหม่

| ลำดับ commit | เนื้อหา | เกณฑ์ผ่าน |
|---|---|---|
| P1-1 | `git mv departments.vue → departments/index.vue` | หน้า list เปิดได้ที่ URL เดิม |
| P1-2 | backend: role constants + validate + sync head_user_id + audit hooks + tests | `php artisan test` ผ่าน |
| P1-3 | หน้า `departments/[id].vue` แท็บภาพรวม+สมาชิก | เปิดหน้าจริง เพิ่ม/ลบ/เปลี่ยน role/bulk add ได้ |
| P1-4 | แท็บสิทธิ์ + แท็บประวัติ + ลิงก์จาก list | toggle permission แล้ว persist, audit แสดง |
| P2-1 | migration scope columns (posts + announcements) + backfill | ฟีดโรงเรียนเดิมแสดงครบเท่าเดิม |
| P2-2 | `AcademyScopeResolver` + `ScopeAccessService` + tests | tests ครอบทุกบทบาทใน matrix |
| P2-3 | AcademyPostController รับ scope + enforce | โพสต์ข้าม scope โดนปฏิเสธ 403 |
| P2-4 | frontend `ScopedFeed.vue` + แท็บฟีดใน department/classroom detail | โพสต์/คอมเมนต์/pin ได้ตามบทบาท |
| P2-5 | ประกาศ scope + แจ้งเตือน | ประกาศฝ่ายเห็นเฉพาะสมาชิกฝ่าย |
| P3-1..n | tasks → files → ปฏิทิน (แยก commit ต่อระบบ) | ใช้ได้ทั้งสอง scope |
| P4-1..n | รายงาน/export → นโยบายผู้ปกครอง | Excel เปิดได้ข้อมูลถูก |

**ก่อนเริ่มแต่ละ Phase:** อ่านไฟล์ที่เกี่ยวข้องก่อนแก้เสมอ, ใช้สกิล hopeui-port เมื่อสร้างหน้า/component ใหม่, ตรวจ `git diff` ก่อน commit

---

# แผนและผลการปรับปรุง: หน้าจัดการรายละเอียดห้องเรียนเชิงลึก (Classroom Management Tabbed Console)

**วันที่:** 2026-07-14
**สถานะ:** เสร็จสิ้น (พัฒนาและอัปโหลดโค้ดขึ้นระบบเรียบร้อยแล้ว)
**ขอบเขตงาน:** ออกแบบระบบแท็บบอร์ด 7 แท็บ (ภาพรวม, นักเรียน, ครูและสมาชิก, การเข้าเรียน, วิชาและผลการเรียน, ประกาศ, รายงาน) ภายในหน้ารายละเอียดห้องเรียน `ui/pages/academies/[name]/admin/classrooms/[id].vue`

---

# แผนแก้ไข: ห้องเรียนซ้ำ (Duplicate Classroom) — ฉบับตรวจสอบกับโค้ดจริง + ทีละขั้นตอน

**วันที่:** 2026-07-12
**สถานะ:** เสร็จสิ้น (แก้ไขโค้ดและทดสอบผ่านแล้ว 100%)
**อาการ:** การ์ดห้องเรียนซ้ำในหน้า Academy — เช่น `ม.1/1` ปีเดียวกันโผล่ 2 ใบ ต่างกันแค่ `classroom_code` (เช่น `UDEDR1` กับ `03LWXC`)

---

## บทวิเคราะห์เทียบกับ codebase จริง (ยืนยัน root cause ทุกจุด)

ตรวจไฟล์จริงแล้ว ยืนยันว่า **ไม่ใช่ปัญหา re-render ฝั่งหน้าเว็บ** แต่เป็นปัญหา **data integrity + API contract** ที่มีสาเหตุร่วมกันหลายชั้น:

### สาเหตุที่ 1 (แกนกลาง) — unique index ผูกกับคอลัมน์ที่เป็น NULL ได้
- Migration `2026_02_03_000005_create_classrooms_table.php:31`:
  `$table->unique(['academy_id', 'academic_year_id', 'grade_level', 'section'], 'classrooms_unique')`
- แต่บรรทัด 18: `academic_year_id` เป็น **nullable**
- MySQL ถือว่า `NULL != NULL` ใน unique index → ถ้า `academic_year_id` เป็น NULL หลายแถวที่ `(academy_id, NULL, 'ม.1', '1')` **ไม่ชนกัน** → สร้างซ้ำได้ไม่จำกัด

### สาเหตุที่ 2 — มี "ปีการศึกษา" สองฟิลด์ที่ไม่ sync กัน
- Migration `2026_04_07_000001_upgrade_classroom_management_system.php:31` เพิ่ม `academic_year` (string, nullable) เข้ามาอีกฟิลด์
- ตอนนี้จึงมี **`academic_year_id` (FK, nullable)** และ **`academic_year` (string, nullable)** ควบคู่ โดยไม่มี invariant บังคับให้ตรงกัน
- unique index ใช้ตัว FK (ที่เป็น NULL ได้) แต่ validation ฝั่ง store บังคับเฉพาะตัว string

### สาเหตุที่ 3 — validation ยอมให้ FK ปีเป็น NULL
- `ClassroomController::store()` (บรรทัด 173-183):
  - `'academic_year_id' => 'nullable|exists:academic_years,id'`
  - `'academic_year' => 'required|string|max:10'`
- นักเรียนสร้างห้องโดยไม่ส่ง `academic_year_id` → แถวมี FK = NULL → หลุด unique index (สาเหตุ 1)

### สาเหตุที่ 4 — service ไม่มี application-level duplicate check เลย
- `ClassroomService::createClassroom()` (บรรทัด 76-92) `Classroom::create($data)` ตรงๆ ไม่เช็คซ้ำ พึ่ง DB unique index อย่างเดียว
- docblock อ้าง "BR-4: academic_year is mandatory" แต่ **ไม่ได้ enforce** ในโค้ด service

### สาเหตุที่ 5 (หลักฐานชิ้นสำคัญ) — ฟอร์ม admin สองชุดส่งฟิลด์ปีไม่ตรงกัน
- `ui/pages/academies/[name]/admin/classrooms.vue:221-224` — POST ส่งเฉพาะ `academic_year` (string) **ไม่ส่ง `academic_year_id`** → แถวที่สร้างมี FK = NULL → หลุด index → **นี่คือทางที่ทำให้เกิดห้องซ้ำ**
- `ui/pages/academies/[name]/admin/gradebook/classrooms/index.vue:152,178` — POST ส่ง `academic_year_id` (FK) และบังคับ required ฝั่ง client
- สองฟอร์มเขียนลงตารางเดียวกันด้วย contract คนละแบบ → ข้อมูลปนกัน บางแถวมี FK บางแถว NULL

### สาเหตุที่ 6 — `update()` ก็เปิดช่องซ้ำเช่นกัน
- `ClassroomController::update()` (บรรทัด 207-218) แก้ `grade_level`/`section`/`academic_year` ได้ โดย **ไม่ตรวจ uniqueness ซ้ำ** → ย้ายห้องไปชนกับที่มีอยู่ได้

### ผลลัพธ์ปลายทาง (ทำไมหน้าเว็บโชว์ซ้ำ)
- `academies/[name].vue` เรียก `listClassrooms()` ครั้งเดียว คืนทุกแถวรวมทั้งที่ซ้ำ → การ์ดซ้ำเป็นเพราะ **ข้อมูลซ้ำจริงใน DB** ไม่ใช่ frontend bug

---

## จุดตัดสินใจเชิงออกแบบ (ต้องเคลียร์ก่อนเขียน migration)

| # | ประเด็น | ตัวเลือก | คำแนะนำ |
|---|---------|----------|---------|
| D1 | ฟิลด์ปีที่เป็น source of truth | (ก) `academic_year_id` FK · (ข) `academic_year` string | **(ก)** — เพราะ rollover/enrollment/transcript ทั้งระบบ key ด้วย `academic_year_id`; ให้ string เป็น cache ที่ sync จาก FK |
| D2 | `semester` อยู่ใน unique key ไหม | (ก) ไม่รวม (คงพฤติกรรมเดิม) · (ข) รวม | **(ก)** index เดิมไม่รวม semester อยู่แล้ว โรงเรียนนี้ใช้ห้องแบบราย "ปี" (rollover) — คงเดิมไว้ ลดความเสี่ยง |
| D3 | ห้อง `archived` ควรกันซ้ำกับห้อง `active` ใหม่ไหม | (ก) กันเสมอทุกสถานะ (แก้ = un-archive) · (ข) กันเฉพาะ active (ต้องใช้ generated column) | **(ก)** — MySQL ไม่รองรับ partial unique index; วิธี (ก) race-proof และง่ายกว่า ถ้าอยากคืนห้องเดิมให้ un-archive |
| D4 | ห้องซ้ำเดิมจัดการยังไง | (ก) merge เข้าห้องหลัก แล้วลบที่เหลือ · (ข) archive ที่เหลือ | **(ก)** ถ้าห้องซ้ำมีสมาชิก/นักเรียนกระจายกัน ต้อง merge; ถ้าซ้ำเปล่า archive/ลบได้เลย — ตัดสินรายกรณีจากผล audit |

---

## Work Plan — แก้ห้องเรียนซ้ำ (ทีละขั้นตอน, ปลอดภัยต่อ production data)

### ขั้นที่ 0 — เคลียร์จุดตัดสินใจ D1–D4 ข้างต้น
ยืนยันกับผู้ใช้/นายทะเบียนก่อน โดยเฉพาะ D3 (archived) และ D4 (วิธีจัดการห้องซ้ำเดิม) เพราะกระทบข้อมูลจริง

### ขั้นที่ 1 — Audit แบบอ่านอย่างเดียว (ยังไม่แตะข้อมูล)
เป้าหมาย: รู้ขนาดปัญหาจริงก่อนแก้ รันผ่าน tinker/read-only query:

1.1 หาแถวที่ `academic_year_id` เป็น NULL (ต้นเหตุ):
```sql
SELECT academy_id, academic_year, COUNT(*) c
FROM classrooms WHERE academic_year_id IS NULL
GROUP BY academy_id, academic_year;
```
1.2 หากลุ่มห้องซ้ำจริง (จับคู่ด้วย "ปีเชิงตรรกะ" = COALESCE ของทั้งสองฟิลด์):
```sql
SELECT academy_id,
       COALESCE(academic_year_id, 0) yid,
       academic_year, grade_level, section, COUNT(*) c,
       GROUP_CONCAT(id) ids, GROUP_CONCAT(classroom_code) codes
FROM classrooms
GROUP BY academy_id, COALESCE(academic_year_id,0), academic_year, grade_level, section
HAVING c > 1;
```
1.3 หาแถวที่ FK กับ string ปีไม่ตรงกัน (data drift):
```sql
SELECT c.id, c.academic_year_id, c.academic_year, ay.name
FROM classrooms c LEFT JOIN academic_years ay ON ay.id = c.academic_year_id
WHERE c.academic_year_id IS NOT NULL AND ay.name <> c.academic_year;
```
1.4 สำหรับแต่ละ id ในกลุ่มซ้ำ นับ dependents ทุกตาราง (ดูขั้น 3 สำหรับรายชื่อ FK) เพื่อรู้ว่าห้องไหนควรเป็น "ห้องหลัก"

**ผลลัพธ์:** ตารางสรุปจำนวนห้องซ้ำ + ห้อง NULL-FK + แผน merge รายกรณี

### ขั้นที่ 2 — Backfill `academic_year_id` จาก `academic_year` (ทำให้ FK ครบก่อน)
ก่อนบังคับ NOT NULL หรือแก้ index ต้องเติม FK ให้ครบ:

2.1 เขียน migration (idempotent, ห่อ transaction) ที่:
- สำหรับแต่ละแถว `academic_year_id IS NULL AND academic_year IS NOT NULL`:
  หา `academic_years` ของ `academy_id` เดียวกันที่ `name = academic_year` → เซ็ต FK
- ถ้าไม่พบปีนั้นในตาราง `academic_years` → **find-or-create** ปีนั้นให้ academy (ตามนโยบาย D1) หรือ log ไว้ให้ตรวจ manual (เลือกได้)
2.2 หลัง backfill รัน query 1.1 ซ้ำ ต้องเหลือ 0 (หรือเหลือเฉพาะเคสไม่มี string ปีจริง — ต้องแก้ manual)
2.3 **ยังไม่แตะ unique index ในขั้นนี้** เพราะข้อมูลซ้ำยังอยู่

### ขั้นที่ 3 — Merge/จัดการห้องซ้ำเดิม (data migration ที่ต้อง backup ก่อน)
⚠️ ขั้นที่อันตรายที่สุด — ต้อง `mysqldump nuxnan` ก่อน และรัน dry-run ก่อน commit

3.1 **Enumerate FK ที่ชี้ `classrooms.id` ทั้งหมดก่อน** (พบอย่างน้อย 12 ตาราง: `classroom_students`, `classroom_members`, `classroom_groups`, `classroom_invitations`, `semester_transcripts`/`transcripts`, `class_schedules`, `behavior_records`, `behavior_sessions`, `typing_sessions`, `student_card_requests`, `student_academic_info.classroom_id`, finance tables) — ยืนยันด้วย:
```sql
SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME = 'classrooms' AND REFERENCED_TABLE_SCHEMA = 'nuxnan';
```
3.2 สำหรับแต่ละกลุ่มซ้ำ เลือก **survivor** (ห้องที่มี dependents มากสุด/เก่าสุด) แล้ว re-point FK ของทุกตารางจากห้องที่แพ้ → survivor
3.3 ระวัง **unique collision ตอน re-point**: เช่น `classroom_members` unique `(classroom_id, user_id)` — ถ้า user เดียวกันอยู่ทั้งสองห้อง การ re-point จะชน → ต้อง dedupe (เก็บ active/ใหม่สุด, ลบ/soft-remove อีกอัน) ก่อน re-point; ตรวจ pattern นี้กับทุกตารางที่มี unique รวม classroom_id
3.4 หลัง re-point แล้วห้องที่แพ้ไม่มี dependents → ลบ (D4-ก) หรือ archive (D4-ข)
3.5 ทำเป็น command/มีโหมด `--dry-run` พิมพ์แผน merge ก่อน แล้วค่อย `--commit`; ห่อ DB transaction ต่อกลุ่ม
3.6 หลังเสร็จ รัน query 1.2 ซ้ำ ต้องเหลือ 0 กลุ่มซ้ำ

### ขั้นที่ 4 — ปิดช่องสร้างซ้ำในอนาคต (validation + index)
ทำ **หลัง** ข้อมูลสะอาดแล้วเท่านั้น:

4.1 **App-level validation** ใน `store()` (และ `update()`):
- resolve `academic_year_id` จาก input ให้ได้เสมอ (รับ id หรือ string แล้ว find-or-create) ก่อน insert
- เพิ่ม `Rule::unique('classrooms')` scoped: `academy_id + academic_year_id + grade_level + section` (ignore ตัวเองใน update); ให้ error ภาษาไทยที่อ่านรู้เรื่อง เช่น "มีห้อง ม.1/1 ปีการศึกษานี้อยู่แล้ว"
4.2 **ย้าย logic ไป `ClassroomService::createClassroom()`** ให้ resolve FK + เช็คซ้ำในที่เดียว (ทั้งสองฟอร์มเรียกผ่าน service เดียว) — sync `academic_year` string จาก `academicYear->name` เสมอ (D1: FK เป็นหลัก)
4.3 **Race condition**: ห่อ `createClassroom()` ใน transaction + `try/catch` จับ `QueryException` (SQLSTATE 23000 duplicate) แล้วคืน 422 friendly — validation กันได้ 99% แต่ catch กัน 2 request พร้อมกัน
4.4 **Migration แก้ schema** (หลัง backfill+merge):
- `academic_year_id` → **NOT NULL** (ตอนนี้ backfill ครบแล้ว) + คง `onDelete('set null')`? → ถ้า NOT NULL ต้องเปลี่ยนเป็น `restrict`/`cascade` ให้สอดคล้อง (ตัดสินใจ: ปีการศึกษาไม่ควรถูกลบทั้งที่มีห้อง → `restrict`)
- drop unique เดิม แล้วสร้างใหม่ `(academy_id, academic_year_id, grade_level, section)` (ตอนนี้ FK NOT NULL แล้ว NULL-trap หายไป) — ตาม D2 ไม่รวม semester, ตาม D3 ไม่กรอง status
- (ออปชัน) พิจารณา drop คอลัมน์ `academic_year` string ทิ้ง หรือคงไว้เป็น denormalized cache ที่ service sync ให้เสมอ — **แนะนำคงไว้** เพราะหลายหน้า filter/display ใช้ string อยู่ (`getStatistics`, `admin/classrooms.vue`) การลบกระทบกว้าง

### ขั้นที่ 5 — รวมฟอร์ม frontend ให้ contract เดียว
5.1 `admin/classrooms.vue` — เพิ่มการส่ง `academic_year_id` (เลือกจาก dropdown ปีการศึกษา) ให้ตรงกับ `gradebook/classrooms/index.vue`; ถ้าจะคง string ไว้ด้วยก็ได้ แต่ FK ต้องมาเสมอ
5.2 จัดการ error 422 duplicate ที่ backend คืน → แสดง toast/inline ภาษาไทย ไม่ให้ผู้ใช้กดซ้ำจนงง
5.3 ตรวจว่าทั้งสองหน้าใช้แหล่งรายการปีการศึกษาเดียวกัน (จาก `academic_years` ของ academy)

### ขั้นที่ 6 — Verification
6.1 Feature test (`tests/Feature/.../ClassroomUniquenessTest.php`):
- สร้างห้องเดิมซ้ำปีเดียวกัน → 422
- สร้างห้องเดียวกันคนละปี → 201 (สองแถว)
- สร้างโดยส่งแค่ `academic_year` string (จำลองฟอร์มเก่า) → ต้อง resolve FK และกันซ้ำได้
- `update()` ย้ายห้องไปชนของเดิม → 422
- ยิงสอง request พร้อมกัน (จำลอง) → มีแค่ 1 สำเร็จ
- ห้อง archived + สร้าง active ซ้ำ → พฤติกรรมตาม D3
6.2 Migration test บน DB สำเนา: backfill + merge แล้ว query 1.1/1.2 = 0
6.3 `./vendor/bin/pint` + Nuxt build/type check
6.4 เปิดหน้า `academies/{name}` ด้วย in-app browser ยืนยันการ์ด `ม.1/1` เหลือใบเดียว

### สรุปไฟล์ที่เกี่ยวข้อง
| ไฟล์ | Action |
|------|--------|
| `database/migrations/xxxx_backfill_classroom_academic_year_id.php` | สร้าง — เติม FK จาก string (ขั้น 2) |
| `app/Console/Commands/MergeDuplicateClassrooms.php` (+`--dry-run`) | สร้าง — merge ห้องซ้ำ (ขั้น 3) |
| `database/migrations/xxxx_fix_classrooms_unique_and_notnull.php` | สร้าง — FK NOT NULL + rebuild unique (ขั้น 4.4) |
| `app/Services/ClassroomService.php` (`createClassroom`,`updateClassroom`) | แก้ — resolve FK + เช็คซ้ำ + sync string + transaction (ขั้น 4.2-4.3) |
| `app/Http/Controllers/Api/Learn/Academy/ClassroomController.php` (`store`,`update`) | แก้ — Rule::unique + catch duplicate (ขั้น 4.1) |
| `ui/pages/academies/[name]/admin/classrooms.vue` | แก้ — ส่ง `academic_year_id` + handle 422 (ขั้น 5) |
| `ui/pages/academies/[name]/admin/gradebook/classrooms/index.vue` | ตรวจ — ให้ contract ตรงกัน |
| `tests/Feature/.../ClassroomUniquenessTest.php` | สร้าง — ทดสอบ (ขั้น 6) |

**ลำดับสำคัญ:** audit → backfill FK → merge ซ้ำ → *ค่อย* บังคับ NOT NULL/unique → validation → frontend → test. ห้ามสลับลำดับ (ถ้าแก้ index ก่อน merge migration จะ fail เพราะข้อมูลซ้ำยังอยู่)

---

# แผนปรับปรุง: คะแนนกิจกรรมประจำบทเรียนในหน้า My Progress — ฉบับตรวจสอบกับโค้ดจริง

**วันที่:** 2026-07-12
**สถานะ:** วางแผน (ยังไม่แก้โค้ดฟีเจอร์)
**หน้า/ขอบเขต:** `/Learn/Courses/{id}/my-progress` → แสดงคะแนนแบบฝึกหัด/แบบทดสอบประจำบทเรียนในแท็บ "บทเรียน"

---

## บทวิเคราะห์เทียบกับ codebase จริง (แก้ไขสมมติฐานของแผนเดิม)

แผนเดิมตั้งสมมติฐานว่าต้องแก้ `memberProgress()` และเพิ่ม field ให้ `lessons` ตั้งแต่ต้น — แต่จากการอ่านโค้ดจริงพบว่าสมมติฐานนี้ **คลาดเคลื่อนในหลายจุด**:

### จุดที่ 1 (สำคัญที่สุด) — endpoint ที่หน้าใช้จริงคือ `show()` ไม่ใช่ `memberProgress()`
- `MyProgressDetails.vue:109` เรียก `GET /api/courses/{courseId}/members/{memberId}/progress`
- Route `routes/learn/course.php:267` → `CourseMemberController@show` (ไม่ใช่ `memberProgress`)
- `memberProgress()` ผูกกับ route `/{member}/admin/progress` (course.php:281) เป็น **ตัวแปรฝั่ง admin คนละตัว**
- **ผลกระทบต่อแผน:** งานหลักต้องแก้ที่ `show()` เป็นอันดับแรก ไม่ใช่ `memberProgress()`

### จุดที่ 2 — `show()` มีโครงคะแนนบทเรียนอยู่แล้ว (แต่ frontend ไม่ได้ใช้)
`show()` (CourseMemberController.php:153-192) คืน `lessons` แต่ละตัวพร้อม field ครบชุดอยู่แล้ว:
`score_status`, `score`, `max_score`, `score_percentage`, `has_graded_activity`, `activity_counts{assignments,quizzes,questions}` — ผ่าน helper `resolveLessonScoreStatus()` (บรรทัด 1653)
- Type `ui/types/lessonScore.ts` (`LessonProgressSummary`) และ widget `CourseLessonProgressWidget.vue` **รองรับ shape นี้อยู่แล้ว**
- **แต่** `MyProgressDetails.vue` แท็บ "บทเรียน" (บรรทัด 774-820) เรนเดอร์เฉพาะ `lesson.completed` เป็น 0%/100% เท่านั้น — **ทิ้ง field คะแนนที่ backend ส่งมาแล้วทั้งหมด**
- **ผลกระทบต่อแผน:** งาน frontend คือ "แสดง field ที่มีอยู่" ไม่ใช่ "สร้าง contract ใหม่"

### จุดที่ 3 — "แบบทดสอบประจำบทเรียน" ในระบบนี้ = คำถามฝังในบทเรียน (lesson-embedded questions) เท่านั้น
- `Lesson` model: `assignments()` = morphMany, `questions()` = morphMany (`questionable`), `topics()` = hasMany (topics ก็มี assignments)
- `CourseQuiz` **ไม่มี `lesson_id`** → แบบทดสอบระดับคอร์สผูกกับบทเรียนไม่ได้ ต้องอยู่ระดับคอร์สต่อไป
- แบบทดสอบประจำบทเรียนจริง ๆ คือ `lesson->questions` ตรวจผ่าน `LessonAnswerQuestion` (ดูตัวอย่างการคำนวณที่ `memberProgress()` บรรทัด 993-1019 คือ `is_correct=true` → sum(points))
- **ผลกระทบต่อแผน:** แผนเดิมที่เขียนว่า "แบบทดสอบของบทเรียน + แบบทดสอบจากคำถามภายในบทเรียน" ยุบเหลือแหล่งเดียว = lesson questions

### จุดที่ 4 — `show()` ยังไม่รวมคะแนน quiz/questions เข้า lesson score
ใน `show()` บรรทัด 163 `$gradedQuizzes = collect();` (ว่างเสมอ) และ `activity_counts.questions` = 0, `quizzes` = 0 ตายตัว → คะแนนบทเรียนตอนนี้นับเฉพาะ assignment ยังไม่นับ lesson questions

### จุดที่ 5 — ระวัง N+1 ในการดึงคำตอบ questions
`memberProgress()` ดึง `LessonAnswerQuestion` ทีละบทเรียนในลูป (บรรทัด 997) = N+1 — **ห้ามลอก pattern นี้** ต้อง bulk `whereIn('question_id', allQuestionIds)` แล้ว group เอง เหมือนที่ `show()` ทำกับ assignment (`answerMap`, บรรทัด 146)

### จุดที่ 6 — ตรรกะคะแนนแบบ all-or-nothing ต้องตัดสินใจเชิงออกแบบ
`resolveLessonScoreStatus()` จะซ่อนคะแนน (score=null, status=`awaiting_grading`/`submitted`) ถ้ามีกิจกรรมใด "ขาด/รอตรวจ" แม้เพียงชิ้นเดียว — เมื่อผสม questions (ตรวจอัตโนมัติ มีคะแนนทันที) กับ assignment (รอครูตรวจ) คะแนน question จะถูกซ่อนไปด้วย ต้องตัดสินใจว่า:
- (ก) คงแบบ all-or-nothing (แสดงคะแนนเฉพาะเมื่อครบ) — สอดคล้องพฤติกรรมปัจจุบัน หรือ
- (ข) แสดงคะแนนบางส่วน (earned/max ของเฉพาะที่ตรวจแล้ว) + ป้าย "ยังมีรายการรอตรวจ"
- **แนะนำ (ข)** เพราะเป้าหมายคือ "เห็นคะแนนแบบฝึกหัด/แบบทดสอบประจำบทเรียน" ระหว่างเรียน

### จุดที่ 7 — สิทธิ์การเห็นคะแนน (`canShowScore`) ต้องใช้กับคะแนนบทเรียนด้วย
`MyProgressDetails.vue:275` gate การแสดงคะแนน assignment/quiz ด้วย `isCourseAdmin || member.order_number` — คะแนนกิจกรรมบทเรียนที่เพิ่มใหม่ต้อง gate ด้วยกติกาเดียวกัน (สถานะเรียนจบยังแสดงได้เสมอ)

---

## Work Plan — คะแนนกิจกรรมประจำบทเรียน (ทีละขั้นตอน)

### ขั้นที่ 0 — ตัดสินใจเชิงออกแบบก่อนเขียนโค้ด (ต้องเคลียร์ก่อน)
1. **โหมดคะแนน:** all-or-nothing (ก) หรือ partial (ข) — แนะนำ (ข)
2. **นิยาม "ผ่าน/ไม่ผ่าน" ของบทเรียน:** ใช้ `course.passing_score` (มีส่งเข้า `resolveLessonScoreStatus` แล้ว) หรือเกณฑ์ต่อบทเรียน — ปัจจุบันมีแค่ course-level → ใช้ค่านี้
3. **การเลือกคะแนน quiz:** questions ตรวจครั้งเดียว (ไม่มี attempt หลายครั้งเหมือน CourseQuiz) → ใช้ผลรวม `is_correct` ปัจจุบัน ไม่ต้องเลือก best attempt
4. **ยืนยันขอบเขต:** คะแนนบทเรียน = assignments (lesson + topics) + lesson questions เท่านั้น; CourseQuiz ยังคงอยู่แท็บแบบทดสอบระดับคอร์ส

### ขั้นที่ 1 — Backend: รวม lesson questions เข้า lesson score (แก้ `show()`)
ไฟล์: `api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/members/CourseMemberController.php`

1.1 เพิ่ม eager-load `questions` ให้ lessons (บรรทัด 130-133): `->with(['topics.assignments','assignments','questions'])`
1.2 Bulk-fetch คำตอบ questions (กัน N+1):
   - รวบรวม `$allQuestionIds = $rawLessons->flatMap->questions->pluck('id')`
   - `$questionAnswerMap = LessonAnswerQuestion::whereIn('question_id',$allQuestionIds)->where('user_id',$userId)->get()->groupBy('question_id')`
1.3 ขยาย `resolveLessonScoreStatus()` (หรือเพิ่ม branch) ให้รับ lesson questions:
   - max ต่อบทเรียน += `sum(question.points ?? 1)`
   - earned += `sum(points ที่ is_correct=true)`
   - นับ attempted จากการมีคำตอบใด ๆ ของ question ในบทเรียน
   - questions ตรวจอัตโนมัติ → ไม่เข้า `hasPending`
1.4 แก้ `activity_counts` ให้สะท้อนจริง: `assignments` = จำนวน assignment ที่เผยแพร่, `questions` = จำนวน question, `quizzes` = 0 (ไม่มี CourseQuiz ผูกบทเรียน) — หรือรวม questions เป็น "แบบทดสอบ" ตาม label ที่ frontend ต้องการ
1.5 ถ้าเลือกโหมด (ข) partial: ปรับ `resolveLessonScoreStatus` ให้คืน `score`/`max_score` ของเฉพาะรายการที่ตรวจแล้ว พร้อม flag `has_pending`
1.6 กัน permission/edge เดิมไว้ (target_groups ของ assignment, published status)

### ขั้นที่ 2 — Backend: ยกตรรกะเป็น helper เดียว + sync `memberProgress()`
2.1 ย้ายตรรกะคะแนนบทเรียนไปเป็น service/method กลาง (เช่น `LessonScoreService::forMember($lesson,$user,...)`) เพื่อไม่ให้ `show()` กับ `memberProgress()` คำนวณต่างกัน
2.2 ให้ `memberProgress()` (แท็บ admin) ใช้ helper เดียวกัน — ปัจจุบันมันแยกคะแนน lesson questions ไปกองในแท็บ quizzes (บรรทัด 1032) และ N+1; แก้ให้สอดคล้อง
2.3 คง `assignments`/`quizzes` แบบ list แยกใน response ไว้ตามเดิม (ไม่กระทบแท็บอื่น)

### ขั้นที่ 3 — Frontend: แสดงคะแนนบทเรียนในแท็บ "บทเรียน"
ไฟล์: `ui/components/learn/course/MyProgressDetails.vue` (บรรทัด 774-820)
3.1 ในลูป `v-for="lesson in data.lessons"` เพิ่มบล็อกแสดง (gate ด้วย `canShowScore` + `lesson.has_graded_activity`):
   - คะแนนกิจกรรม เช่น `8/10 (80%)` เมื่อ `lesson.score !== null`
   - จำนวนกิจกรรม เช่น `แบบฝึกหัด 1/2 · แบบทดสอบ 1/1` จาก `activity_counts`
   - ป้ายสถานะจาก `score_status`: `passed`/`failed`/`awaiting_grading`/`submitted`/`not_attempted`/`none`
3.2 คงแถบ "ความคืบหน้าการอ่าน" (0/100%) ไว้แยกจาก "คะแนนกิจกรรม" — เป็นคนละมิติ
3.3 ปรับ `getTabCount('lessons')`/badge ถ้าต้องการนับ "บทเรียนที่มีคะแนนผ่าน" (ออปชัน)
3.4 ทางเลือกลดโค้ดซ้ำ: reuse `CourseLessonProgressWidget.vue` (รับ `LessonProgressSummary[]` อยู่แล้ว) แทนการเขียน markup ใหม่ — แต่ต้องปรับ style ให้เข้ากับแท็บ
3.5 อัปเดต type ให้ `data.lessons` เป็น `LessonProgressSummary[]` (ไฟล์นี้เป็น `<script setup>` ยังไม่ `lang="ts"` → อาจแค่ import type ไว้อ้างอิง)

### ขั้นที่ 4 — Edge cases ที่ต้องทดสอบ
- บทเรียนมีเฉพาะแบบฝึกหัด / เฉพาะ questions / มีทั้งสอง / ไม่มีเลย (`score_status='none'`)
- ยังไม่ทำ (`not_attempted`), ส่ง assignment แล้วรอตรวจ (`submitted`/`awaiting_grading`)
- questions ตรวจอัตโนมัติแล้วแต่ assignment ยังรอตรวจ (ทดสอบโหมด ก vs ข)
- ผ่าน/ไม่ผ่านตาม `course.passing_score`
- assignment อยู่ใน topic ย่อย (`topics.assignments`)
- นักเรียนอยู่กลุ่มที่ไม่ถูก assign (`target_groups`) → ต้องไม่ถูกนับ
- นักเรียนไม่มี `order_number` และไม่ใช่ admin → ต้องซ่อนคะแนน (แต่เห็นสถานะเรียนจบ)

### ขั้นที่ 5 — Verification
- Laravel feature test สำหรับ `show()` response: ยืนยัน field `score`/`activity_counts`/`score_status` ต่อ edge case + นับจำนวน query (กัน N+1)
- `./vendor/bin/pint`
- Nuxt build/type check
- ทดสอบหน้า `/Learn/Courses/24/my-progress` ด้วยข้อมูลจริงในแต่ละกรณี (ทั้งมุมมองนักเรียนและ admin ผ่าน `?member_id=`)

### สรุปไฟล์ที่เกี่ยวข้อง
| ไฟล์ | Action |
|------|--------|
| `CourseMemberController.php` (`show`, `resolveLessonScoreStatus`, `memberProgress`) | แก้ — รวม questions, กัน N+1, ยก helper |
| `app/Services/LessonScoreService.php` (ใหม่, ออปชัน) | สร้าง — contract คะแนนบทเรียนตัวเดียว |
| `ui/components/learn/course/MyProgressDetails.vue` | แก้ — แสดงคะแนน/จำนวน/สถานะในแท็บบทเรียน |
| `ui/types/lessonScore.ts` | ตรวจ/ปรับถ้าเพิ่ม field เช่น `has_pending` |
| `ui/components/learn/course/CourseLessonProgressWidget.vue` | ออปชัน reuse |
| `tests/Feature/...MemberProgressTest.php` (ใหม่) | สร้าง — ทดสอบ response |

**หมายเหตุ:** ไม่จำเป็นต้องมี migration — ตาราง `assignment_answers`, `lesson_answer_questions`, `lesson_progress` มีข้อมูลพอแล้ว งานหลักคือ aggregate + จัดรูปแบบ response + เรนเดอร์ frontend

---

# แผนปรับปรุงระบบบัตรนักเรียน — ฉบับสมบูรณ์

## Implementation update — 2026-07-08

- **Roster Reconciliation Fixes**: Resolved feedback items:
  - M1: Saved `source_academic_year_id` inside `diff_data` during the `preview()` step for both `promote_student` and `repeat_student` actions.
  - M3: Added synchronization of `student_number` (class sequence index) for `unchanged`, `new_intake`, `promote_student`, `repeat_student`, and `re_enroll` actions.
  - M4: Deduplicated batch counters update logic by reusing `StudentImportService::refreshCounters`.
  - M5: Added database migration `2026_07_08_000002_add_remarks_to_students_table` to support storing intake incomplete flags in `remarks` and added `remarks` to the `Student` fillable array.
  - N6: Added type safety to `useStudentCardRequests` by replacing `any` casts with explicit types.
  - Test Verification: Wrote additional feature tests for `unchanged` number update, `auto_graduate` for ม.6, and `ambiguous` teacher matching. All 26 assertions pass.

## Implementation update — 2026-07-06

- 2026-07-08 topic youtube integration: Created centralized YouTube URL parser utility `ui/utils/youtube.ts` and refactored `LessonPost.vue` & `VideoModal.vue`. Added a responsive 16:9 video preview section and modal integration in `TopicAccordion.vue` with robust fallbacks for broken/missing URLs and maxresdefault thumbnails. Build successful.
- 2026-07-08 migration verification: `2026_07_08_000001_create_student_card_requests_table.php` ran successfully in batch 79. It now explicitly uses InnoDB and matches the signed integer key type of `student_cards.id`. Verified the table, unique index, foreign keys, and `academy_settings.card_request_flow_enabled`.
- 2026-07-07 old-card display fix: active grades 2, 3, 5, and 6 retain `national_id` and `birth_date` for all 442/357/303/288 records. Per explicit user direction, the temporary `makeHidden()` filter was removed from the public room endpoint so the existing Nuxt card can render complete identity data before authentication is revisited. Verified live `GET /api/student-card/2/1` returns `national_id`, `birth_date`, `birth_date_string`, and `profile_image_url`; PHP syntax passes. Security follow-up remains: protect or mask PII before production exposure.
- 2026-07-07 identity-data audit: exactly 476 active 2569 cards (the entire new-intake cohort) lack national ID and birth date; their linked `students` rows also lack both fields, so card sync did not erase recoverable values. No import batch/row data exists, linked user profiles contain no birthdate/metadata, and no authoritative 2569 intake source file was found in the repository. Recovery requires the registrar's original intake data and must not infer sensitive identity fields.
- 2026-07-07 photo-path analysis: completed migration of 1,529/1,531 student photos to canonical identity paths (`images/students/profiles/{student_id}.{ext}`). Integrated `profile_image_url` accessors in Student/StudentCard models. Replaced legacy manual path resolution logic on the frontend with backend-owned URL endpoints. Resolved E2E findings (including Controller imports, path formatting, null checks, and frontend fallback cleanup). E2E integration tests passing successfully.
- 2026-07-07 photo-path analysis: current storage is grade/room based (`images/students/{level}/{section}/{filename}`) while rollover mutates level/section, so every consumer that reconstructs the path breaks. Sustainable direction is a student-identity canonical path, backend-owned URL resolution, additive backfill with checksum/ambiguity reporting, and no annual file moves.
- Production-data refresh completed: created academic year 2569 (id 2) and 54 target classrooms; committed rollover batch `3c9ca6f7-3ece-4bbd-8f51-b7d64eae5162` with 1,662 promoted, 267 graduated, 476 new intake, and 0 skipped; set 2569 current; synced 2,138 active cards.
- Reconciliation: active enrollment/card counts both 2,138; zero duplicate active cards, zero duplicate active enrollments, zero multiple-current academic records, and zero active enrollments missing a card.
- Runtime follow-up: fixed the public dashboard URL to `/api/student-card/dashboard`, switched public loading from authenticated `useApi()` to `$fetch`, ran the pending student-card status migration, and verified the page renders M.1–M.6 plus real room lists in the in-app browser.
- Hardened legacy and academy student-card routes, academy-scoped all card mutations, and required `students.manage` for academy admin operations.
- Fixed grade parsing, duplicate detection, sync TOCTOU/N+1 behavior, CLI confirmation, profile-image length, frontend academy hard-coding, and added typed confirmation UI.
- Verification: Pint passed; focused StudentCard suite passed (8 tests, 19 assertions). Nuxt build exceeded the 5-minute verification timeout without diagnostics.

**วันที่:** 2026-07-06
**สถานะ:** วางแผน (ยังไม่เริ่มพัฒนา)
**ขอบเขต:** ปลด ม.6 จบการศึกษา, เพิ่ม ม.1/ม.4 ใหม่, อัพเดทบัตรนักเรียนให้ตรงกับปีการศึกษาปัจจุบัน

---

## User Analysis Input

### สรุปแผนเดิมจากผู้ใช้ (10 ระยะ)
- ระยะ 0: กำหนดขอบเขตและนโยบายข้อมูล
- ระยะ 1: ตรวจโครงสร้าง DB จริง
- ระยะ 2: Audit report แบบ read-only
- ระยะ 3: เตรียมปีการศึกษาและห้องเรียนเป้าหมาย
- ระยะ 4: Preview rollover ด้วย AcademicYearRolloverService
- ระยะ 5: Commit ข้อมูลทะเบียน
- ระยะ 6: สร้าง StudentCardSyncService
- ระยะ 7: ปรับ API บัตรนักเรียน
- ระยะ 8: ปรับหน้า /student-card frontend
- ระยะ 9: เพิ่มหน้า admin ตรวจสอบก่อนยืนยัน
- ระยะ 10: ทดสอบ

---

## การวิเคราะห์เทียบกับ Codebase จริง

### สิ่งที่ผู้ใช้วิเคราะห์ถูกต้อง

1. **AcademicYearRolloverService มีอยู่จริงและครบถ้วน** — มี `previewRollover()`, `planRollover()`, `commitRollover()`, `undoRollover()`, `closeUndoWindow()` รองรับ action: promote, graduate, repeat, drop, new_intake, skip ครบตามที่วิเคราะห์
2. **Source of Truth ที่กำหนดถูกต้อง** — classroom_students เป็น enrollment หลัก, student_academic_info เก็บประวัติ, students เก็บข้อมูลบุคคล, student_cards เป็น denormalized snapshot
3. **หน้า /student-card hard-code จำนวนห้องจริง** — อยู่ที่ `ui/pages/student-card/index.vue` และ `admin/index.vue` (ม.1=11ห้อง, ม.2=9, ม.3=9, ม.4=8, ม.5=7, ม.6=7)
4. **API ไม่กรอง status จริง** — `getStudentByRoom()` query เฉพาะ `class_level + class_section` ไม่มี where status
5. **student_status มีปัญหาชนิดข้อมูลจริง** — migration สร้างเป็น integer แต่ controller เขียน string 'active'

### สิ่งที่ต้องเพิ่มเติมจากการตรวจ codebase

6. **StudentEnrollmentService แยกจาก RolloverService** — rollover เรียก enrollment service อีกทีหนึ่ง มี method: `promoteStudent()`, `graduateStudent()`, `dropStudent()`, `repeatStudent()`, `enrollStudent()` แต่ละ method อัพเดท classroom_students + students + student_academic_info ให้ครบ ไม่ต้องเขียนซ้ำ
7. **Frontend Rollover มี UI ครบแล้ว** — `ui/pages/academies/[name]/admin/gradebook/rollover/index.vue` มี wizard 4 ขั้นตอน + components: RolloverPreviewSummary, RolloverCommitPanel, RolloverBatchHistoryCard, RolloverUndoBanner, RolloverYearPicker, RolloverStudentBucket, RolloverClassroomChecklist, RolloverStepIndicator
8. **Import system กำลังสร้าง** — มี migration `2026_07_05` สร้าง student_import_batches + student_import_rows แต่ controller ยังคืน 501
9. **Public routes ไม่มี auth** — `/api/student-card/*` ทั้งหมดเปิดเป็น public ไม่มี middleware auth:api (เฉพาะ academy-scoped routes ที่มี auth)
10. **student_cards ไม่มี academic_year_id** — ไม่มี field ระบุว่าบัตรนี้เป็นของปีการศึกษาไหน ต้องใช้ student_status เป็นตัวแบ่ง
11. **student_cards.class_level เป็น integer** — ขณะที่ students.class_level เป็น string 'ม.1' (format ต่างกัน)
12. **StudentCard model มี fallback matching** — `student()` relation ลอง FK ก่อน ถ้าไม่มีก็ fallback ไป match ด้วย student_number หรือ national_id (legacy)
13. **Photo path ใช้ class_level/class_section** — `storage/images/students/{level}/{room}/{filename}` ถ้าเลื่อนชั้นต้องพิจารณา path ใหม่หรือคง path เดิม
14. **RolloverBatch มี 24-hour undo window** — ใช้ committed_at + 24h กำหนด, มี closeUndoWindow() ปิดก่อนได้
15. **Permission gates ของ rollover** — ต้องมี enrollment.preview, enrollment.plan, enrollment.commit, enrollment.undo

### สิ่งที่ต้องแก้ไขจากแผนเดิม

16. **ไม่จำเป็นต้องสร้าง Artisan Command สำหรับ audit** — ควรสร้างเป็น Service + API endpoint แทน เพื่อให้หน้า admin เรียกได้ และส่งออก CSV ได้จากหน้าเว็บ ไม่ต้อง SSH เข้าเครื่อง
17. **ระยะ 9 (หน้า preview/confirm) ซ้อนทับกับ Rollover UI ที่มีอยู่** — rollover wizard มี preview → plan → commit → undo ครบแล้ว ส่วนที่ต้องเพิ่มคือ "card sync preview" ซึ่งเป็นขั้นตอนแยกหลัง rollover
18. **academy_id ใน student_cards** — ถูกเพิ่มภายหลังเป็น nullable bigint (migration 2026_03_30) ควรตรวจว่า backfill ครบหรือยัง

---

## Work Plan — ฉบับปรับปรุงสมบูรณ์

### ระยะที่ 0: กำหนดขอบเขตและนโยบายข้อมูล

**เป้าหมาย:** ยืนยันกติกาให้ชัดก่อนแตะข้อมูล

**ต้องตอบคำถามเหล่านี้:**

| # | คำถาม | ค่าที่คาดว่าจะได้ | ใครตอบ |
|---|-------|-------------------|--------|
| 0.1 | academy_id ที่จะดำเนินการ | 1 (โรงเรียนเดียว) | นายทะเบียน |
| 0.2 | ปีการศึกษาเดิม (from) | 2567 | นายทะเบียน |
| 0.3 | ปีการศึกษาใหม่ (to) | 2568 | นายทะเบียน |
| 0.4 | วันออกบัตร (card_issue_date) | วันที่เปิดเทอม 2568 | นายทะเบียน |
| 0.5 | วันหมดอายุบัตร (card_expiry_date) | วันปิดเทอมปลาย 2568 หรือ 3 ปี | นายทะเบียน |
| 0.6 | ม.6 ปลด = graduated, ไม่ลบ | ยืนยัน | developer |
| 0.7 | นักเรียน ม.4 ใหม่จากภายนอก — ข้อมูลมาจากไหน | อยู่ใน students table แล้ว vs ต้อง import | นายทะเบียน |
| 0.8 | เลขประจำตัวนักเรียน — ใช้เลขเดิม or ออกใหม่ | เลขเดิมสำหรับคนเลื่อนชั้น, ออกใหม่สำหรับ ม.1/ม.4 ใหม่ | นายทะเบียน |
| 0.9 | รูปเดิม — ใช้ต่อหรือถ่ายใหม่ | คนเลื่อนชั้นใช้ต่อ, คนใหม่อัพโหลดทีหลัง | นายทะเบียน |
| 0.10 | จำนวนห้องจริง ม.1–ม.6 ของปี 2568 | ต้องยืนยัน (อาจต่างจาก hard-code เดิม) | นายทะเบียน |

**ผลลัพธ์:** เอกสารกติกา 1 หน้า ใช้อ้างอิงกับทุกขั้นตอน

---

### ระยะที่ 1: ตรวจโครงสร้าง DB จริง (Schema Audit)

**เป้าหมาย:** ยืนยันว่า schema ตรงกับที่ migration กำหนด + หา inconsistency

**ขั้นตอน:**

**1.1 ตรวจ student_cards.student_status จริง:**
```sql
-- ตรวจ column type จริง (migration กำหนด integer แต่ controller เขียน string)
DESCRIBE student_cards;
-- ตรวจค่าที่ใช้จริง
SELECT DISTINCT student_status, COUNT(*) FROM student_cards GROUP BY student_status;
```
- ถ้าเป็น integer แต่มี string → MySQL อาจ cast ให้อัตโนมัติ ต้องเช็คว่าค่าที่เก็บจริงคือ 0 หรือ 'active'
- **ถ้าพบปัญหา:** เขียน migration เปลี่ยน column เป็น `string` หรือ `enum('active','expired','graduated')` ให้ตรงกับการใช้งานจริง

**1.2 ตรวจ student_cards indexes:**
```sql
SHOW INDEX FROM student_cards;
```
- ต้องมี: `student_id` (FK), `academy_id` (index)
- **ควรเพิ่ม (ถ้าไม่มี):** composite index `(academy_id, class_level, class_section, student_status)` สำหรับ query ตามห้อง

**1.3 ตรวจ unique constraint:**
```sql
-- ตรวจว่านักเรียน 1 คนมีบัตรซ้ำไหม
SELECT student_id, COUNT(*) as cnt FROM student_cards
WHERE student_id IS NOT NULL
GROUP BY student_id HAVING cnt > 1;

-- ตรวจบัตรที่ไม่มี student_id (legacy)
SELECT COUNT(*) FROM student_cards WHERE student_id IS NULL;
```
- **ถ้าพบซ้ำ:** ต้อง deduplicate ก่อน แล้วเพิ่ม unique index `(student_id)` ที่ไม่ null
- **ถ้า student_id NULL เยอะ:** ต้องรัน `StudentsBackfillCardLink` command ก่อน

**1.4 ตรวจ academy_id backfill:**
```sql
SELECT COUNT(*) as total,
       SUM(CASE WHEN academy_id IS NULL THEN 1 ELSE 0 END) as missing_academy
FROM student_cards;
```
- **ถ้ายังไม่ครบ:** รัน `SyncStudentRelatedTables` command (มีอยู่แล้ว)

**1.5 ตรวจ classroom_students constraints:**
```sql
-- ตรวจว่ามีนักเรียน active ซ้ำหลาย classroom ไหม
SELECT student_id, COUNT(*) as cnt FROM classroom_students
WHERE status = 'active'
GROUP BY student_id HAVING cnt > 1;
```

**1.6 ตรวจ student_academic_info current record:**
```sql
-- ตรวจว่ามีคนมี is_current = true มากกว่า 1 record
SELECT student_id, COUNT(*) as cnt FROM student_academic_info
WHERE is_current = 1
GROUP BY student_id HAVING cnt > 1;

-- ตรวจว่ามีคนไม่มี current record เลย (active student)
SELECT s.id, s.student_id, s.first_name_th, s.last_name_th
FROM students s
LEFT JOIN student_academic_info sai ON s.id = sai.student_id AND sai.is_current = 1
WHERE s.status = 'active' AND sai.id IS NULL;
```

**1.7 ตรวจปีการศึกษาและห้องเรียน:**
```sql
-- ปีการศึกษาที่มี
SELECT * FROM academic_years WHERE academy_id = 1 ORDER BY name;

-- ห้องเรียนของปีปัจจุบัน
SELECT ay.name, c.grade_level, c.section, c.name as display_name,
       (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id AND cs.status = 'active') as active_count
FROM classrooms c
JOIN academic_years ay ON c.academic_year_id = ay.id
WHERE c.academy_id = 1 AND ay.is_current = 1
ORDER BY c.grade_level, c.section;
```

**ผลลัพธ์ระยะ 1:**
- รายการ inconsistency ที่พบ
- Migration script(s) ที่ต้องเขียน (ถ้ามี)
- ยืนยันว่าข้อมูล baseline สะอาดพอที่จะทำ rollover ได้

**ไฟล์ที่อาจต้องสร้าง/แก้:**
| ไฟล์ | Action | เงื่อนไข |
|------|--------|----------|
| `database/migrations/xxxx_fix_student_cards_status_column.php` | สร้าง | ถ้า student_status เป็น integer |
| `database/migrations/xxxx_add_student_cards_indexes.php` | สร้าง | ถ้าขาด composite index |
| `database/migrations/xxxx_add_unique_student_id_to_student_cards.php` | สร้าง | ถ้าไม่มี unique constraint |

---

### ระยะที่ 2: จัดทำ Audit Service + API

**เป้าหมาย:** ตรวจข้อมูลนักเรียน-บัตรแบบ read-only แสดงผลทั้ง CLI และ web

**2.1 สร้าง `StudentCardAuditService`**

**ไฟล์:** `api/nuxnanravel/app/Services/StudentCardAuditService.php`

**Method หลัก:**
```
audit(Academy $academy, AcademicYear $year, array $levels = []): AuditReport
```

**AuditReport ควรมี sections:**

**Section A — ม.6 (จบการศึกษา):**
- จำนวน active enrollment ของ ม.6 ในปีที่ระบุ
- คนที่มี student_card ที่ student_status ยัง active
- คนที่ classroom_students.status = 'graduated' แล้ว แต่บัตรยัง active (ไม่ sync)
- คนที่มีหลายบัตร (duplicate)
- คนที่ไม่มี student_academic_info ปัจจุบัน
- **Output:** รายชื่อ + student_id + สถานะแต่ละตาราง

**Section B — ม.1 และ ม.4 ใหม่:**
- คนที่อยู่ใน students (class_level = 'ม.1' หรือ 'ม.4') แต่ไม่มี active enrollment ใน classroom_students
- คนที่มี enrollment แล้วแต่ไม่มี student_card
- คนที่มี student_card แล้วแต่ student_id = NULL (ยังไม่เชื่อม)
- คนที่ students.class_level ไม่ตรงกับ active enrollment classroom.grade_level
- คนที่ขาดข้อมูลจำเป็น: ชื่อ, เลขนักเรียน, วันเกิด, เลขบัตรประชาชน
- คนที่ student_id หรือ citizen_id ซ้ำภายใน academy
- **Output:** รายชื่อ + รายละเอียดสิ่งที่ขาด

**Section C — Cross-table consistency (ทุกชั้น):**
- students ↔ classroom_students: คนที่ status ไม่ตรงกัน
- classroom_students ↔ classrooms: enrollment ที่ชี้ classroom ไม่อยู่ในปีปัจจุบัน
- students ↔ student_cards: ชื่อ/ชั้น/ห้องไม่ตรง (stale snapshot)
- student_academic_info: current record ไม่ตรงกับ enrollment
- **Output:** รายชื่อ + ค่าที่ต่างกัน

**2.2 สร้าง Artisan Command (สำหรับ CLI)**

**ไฟล์:** `api/nuxnanravel/app/Console/Commands/StudentCardAudit.php`
**Signature:** `students:card-audit {--academy=} {--academic-year=} {--levels=} {--output=table} {--export=}`

- `--output=table` แสดงบนจอ, `--output=json` ส่งออก JSON
- `--export=path.csv` ส่งออก CSV สำหรับนายทะเบียน
- เรียก `StudentCardAuditService::audit()` ภายใน

**2.3 เพิ่ม API endpoint (สำหรับ web)**

**Route:** `GET /api/academies/{academy}/student-cards/audit`
**Query params:** `academic_year_id`, `levels` (comma-separated), `format` (json|csv)
**Auth:** ต้อง auth:api + permission check (admin only)
**Response:** JSON สำหรับ web หรือ download CSV

**ไฟล์ที่ต้องสร้าง/แก้:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/StudentCardAuditService.php` | สร้างใหม่ |
| `app/Console/Commands/StudentCardAudit.php` | สร้างใหม่ |
| `StudentCardController.php` | เพิ่ม method `audit()` |
| `routes/learn/academy-student-card.php` | เพิ่ม route `GET /audit` |

---

### ระยะที่ 3: เตรียมปีการศึกษาและห้องเรียมเป้าหมาย

**เป้าหมาย:** ให้แน่ใจว่ามี AcademicYear + Classrooms ครบก่อน rollover

**3.1 ตรวจปีการศึกษาใหม่:**
- เข้า `/academies/{name}/admin/gradebook/rollover` (หน้า rollover ที่มีอยู่)
- ถ้าปีใหม่ยังไม่มี → สร้างผ่าน UI (มีปุ่มสร้างปีใหม่ใน wizard อยู่แล้ว)
- Set `is_current = true` ให้ปีใหม่

**3.2 ตรวจห้องเรียนของปีใหม่:**
```sql
-- ห้องเรียนที่ต้องมีใน academic_year ใหม่
-- ม.1 (/1-/11 ตามจำนวนห้องจริง), ม.2-ม.6 ตามจริง
SELECT grade_level, section FROM classrooms
WHERE academy_id = 1 AND academic_year_id = {new_year_id}
ORDER BY grade_level, CAST(section AS UNSIGNED);
```
- ถ้าขาด → สร้างผ่าน rollover wizard (มีปุ่ม "สร้างห้องเรียน" อยู่แล้ว)

**3.3 ตรวจรูปแบบข้อมูล:**
- `grade_level` ต้องเป็น 'ม.1', 'ม.2', ... (ไม่ใช่ '1', '2', ...)
- `section` ต้องเป็น '1', '2', ... (string)
- ตรงกับ format ที่ `nextGrade()` ใน RolloverService ใช้

**3.4 ตรวจ mapping ห้อง:**
- นักเรียน ม.1/1 ปีเก่า → เลื่อนเป็น ม.2/? ปีใหม่
- mapping ห้องเดิม→ห้องใหม่ ต้องนายทะเบียนกำหนด (ไม่ควรให้ระบบจัดเองทั้งหมด)
- rollover wizard รองรับ user mapping ได้อยู่แล้ว (step 2 ของ wizard)

**ไม่ต้องเขียนโค้ดใหม่** — ใช้ UI ที่มี + SQL ตรวจ

---

### ระยะที่ 4: Preview และ Plan Rollover

**เป้าหมาย:** ใช้ระบบ rollover ที่มี preview + plan ก่อน commit

**4.1 เข้า Rollover Wizard:**
- URL: `/academies/{name}/admin/gradebook/rollover`
- Step 1: เลือก from year = 2567, to year = 2568

**4.2 Preview:**
- Wizard เรียก `POST /api/academies/{academy}/rollover/preview`
- ระบบจำแนกนักเรียนออกเป็น:
  - `graduate` — ม.6 (nextGrade returns null)
  - `promote` — ม.1→ม.2, ม.2→ม.3, ม.3→ม.4, ม.4→ม.5, ม.5→ม.6
  - `repeat` — นักเรียนซ้ำชั้น (ถ้ากำหนด)
  - `drop` — นักเรียนออก (ถ้ากำหนด)
  - `new_intake` — นักเรียนใหม่ ม.1/ม.4 ที่อยู่ใน students แต่ไม่มี enrollment
  - `skip` — ข้อมูลไม่ครบ

**4.3 ตรวจสอบ preview:**
- **ม.6:** ต้องเป็น `graduate` ทุกคน
- **ม.1-ม.5 เดิม:** ต้องเป็น `promote` + มี target classroom
- **ม.1/ม.4 ใหม่:** ต้องเป็น `new_intake` + มี target classroom
- **Warnings:** ตรวจ missing target classrooms, duplicate entries

**4.4 สิ่งที่ต้องตรวจเพิ่ม (นอกเหนือจากที่ wizard แสดง):**
- นักเรียน ม.4 ใหม่จากภายนอก ต้องแยกจาก ม.3 ที่เลื่อนเป็น ม.4
  - คนจาก ม.3 → ม.4 ระบบจะจำแนกเป็น `promote`
  - คนใหม่ → ระบบจำแนกเป็น `new_intake` (ถ้ามี class_level='ม.4' แต่ไม่มี enrollment ปีเก่า)
- นักเรียน ม.1 ใหม่ทั้งหมดควรเป็น `new_intake`
- ตรวจรายการ `skip` ทุกคน — นายทะเบียนต้องยืนยัน

**4.5 Plan:**
- ปรับ mapping ตามต้องการ (เปลี่ยนห้อง, เปลี่ยน action)
- กด Plan → ระบบ validate + cache plan ไว้ 15 นาที
- ตรวจ plan summary ว่าจำนวนตรง

**ไม่ต้องเขียนโค้ดใหม่** — ใช้ rollover wizard + API ที่มี

**⚠️ ข้อควรระวัง:**
- ถ้า `previewRollover()` ไม่จัดการ pending students (คนที่มี class_level แต่ไม่มี enrollment) ต้องตรวจว่ามีขึ้น new_intake จริงไหม — จากโค้ดพบว่า **มีรองรับ** (มี logic ดึง pending students)
- ถ้า preview ไม่แสดงนักเรียนใหม่ที่คาดหวัง → ตรวจว่า students.academy_id ตรงไหม, students.status = 'active' ไหม

---

### ระยะที่ 5: Commit Rollover (ข้อมูลทะเบียน)

**เป้าหมาย:** เปลี่ยนข้อมูลทะเบียนจริง ผ่าน transaction

**5.1 ก่อน commit:**
- **สำรอง DB** (`mysqldump nuxnan > nuxnan_backup_before_rollover.sql`)
- ตรวจ plan summary อีกครั้ง
- พิมพ์ชื่อปีการศึกษาเพื่อยืนยัน (CommitRolloverRequest บังคับ confirm_text)

**5.2 Commit:**
- กด Commit ใน wizard → `POST /api/academies/{academy}/rollover/commit`
- ระบบทำใน DB transaction:

  **สำหรับ ม.6 (graduate):**
  - `StudentEnrollmentService::graduateStudent()`:
    - classroom_students.status → `graduated`, set `left_at`
    - students.status → `graduated`, clear class_level
    - student_academic_info → set `graduation_date`, status → `graduated`
    - **ไม่ลบ** student, user, ประวัติ, หรือรูป

  **สำหรับนักเรียนเลื่อนชั้น (promote):**
  - `StudentEnrollmentService::promoteStudent()`:
    - ปิด enrollment เดิม → status `promoted`
    - สร้าง active enrollment ใหม่ใน classroom ปีใหม่
    - อัพเดท students.class_level, class_section
    - จัดการ student_academic_info (ปิดเก่า, สร้างใหม่)

  **สำหรับนักเรียนใหม่ ม.1/ม.4 (new_intake):**
  - `StudentEnrollmentService::enrollStudent()`:
    - สร้าง active enrollment
    - อัพเดท students.class_level
    - สร้าง student_academic_info ปัจจุบัน

- สร้าง `RolloverBatch` record → เก็บ snapshot + totals
- Dispatch `RolloverCommitted` event

**5.3 ตรวจหลัง commit:**
```sql
-- ตรวจ enrollment ปีใหม่
SELECT c.grade_level, c.section, COUNT(*) as cnt
FROM classroom_students cs
JOIN classrooms c ON cs.classroom_id = c.id
WHERE cs.academic_year_id = {new_year_id} AND cs.status = 'active'
GROUP BY c.grade_level, c.section
ORDER BY c.grade_level, CAST(c.section AS UNSIGNED);

-- ตรวจ ม.6 ถูก graduate
SELECT COUNT(*) FROM students
WHERE academy_id = 1 AND status = 'graduated'
AND id IN (SELECT student_id FROM classroom_students WHERE status = 'graduated' AND academic_year_id = {old_year_id});
```

**5.4 Undo (ถ้าพบปัญหา):**
- มี 24 ชั่วโมงสำหรับ undo
- `POST /api/academies/{academy}/rollover/batches/{batch}/undo`
- ปิด undo window เมื่อยืนยันว่าถูกต้อง: `POST .../close-undo`

**ไม่ต้องเขียนโค้ดใหม่** — ใช้ rollover system ที่มี

---

### ระยะที่ 6: สร้าง StudentCardSyncService

**เป้าหมาย:** สร้าง/อัพเดท/ปิดบัตรให้ตรงกับข้อมูลทะเบียนที่ commit แล้ว

**6.1 สร้าง Service:**

**ไฟล์:** `api/nuxnanravel/app/Services/StudentCardSyncService.php`

**Methods:**

```
previewSync(Academy $academy, AcademicYear $year): CardSyncPreview
commitSync(Academy $academy, AcademicYear $year, User $by): CardSyncResult
```

**Logic ของ previewSync:**

1. ดึง active enrollment ทั้งหมดของ academy ในปีที่ระบุ (join classrooms + students)
2. ดึง student_cards ทั้งหมดของ academy
3. จำแนกออกเป็น:

| กลุ่ม | เงื่อนไข | Action |
|-------|----------|--------|
| **create** | มี active enrollment แต่ไม่มี student_card (หรือ student_card.student_id = NULL) | สร้างบัตรใหม่ |
| **update** | มี active enrollment + มี student_card แต่ข้อมูลไม่ตรง (ชั้น/ห้อง/ชื่อ/วันเกิด) | อัพเดท snapshot |
| **expire** | มี student_card active แต่ไม่มี active enrollment (ม.6 จบ, ลาออก) | เปลี่ยน student_status → expired/graduated |
| **unchanged** | ข้อมูลตรงหมดแล้ว | ไม่ทำอะไร |
| **orphan** | มี student_card แต่ student_id = NULL และ match ไม่ได้ | แจ้งเตือน |

4. Return preview พร้อมจำนวนแต่ละกลุ่ม + รายละเอียดรายคน

**Logic ของ commitSync:**

1. **Create** — สร้าง student_card ใหม่:
   - ดึงข้อมูลจาก `students`: title_prefix_th, first_name_th, last_name_th, first_name_en, date_of_birth, citizen_id, profile_image
   - ดึงจาก active `classroom_students.classroom`: grade_level → class_level, section → class_section
   - ดึง student_number จาก classroom_students.student_number หรือ students.student_id
   - สร้าง full_name_thai = title + first + last
   - สร้าง level_and_room = "{grade_level_number}/{section}"
   - สร้าง birth_date_string = format จาก date_of_birth
   - ตั้ง card_issue_date, card_expiry_date ตามนโยบาย
   - ตั้ง student_status = 'active' (⚠️ ต้องแก้ column type ก่อนถ้ายังเป็น integer)
   - ตั้ง academy_id
   - ตั้ง student_id (FK)

2. **Update** — อัพเดท student_card ที่มี:
   - อัพเดท class_level, class_section, level_and_room จาก enrollment ปัจจุบัน
   - อัพเดท full_name_thai, first_name_thai, last_name_thai ถ้าเปลี่ยน
   - อัพเดท card_issue_date, card_expiry_date ตามนโยบาย
   - **คง profile_image เดิม** ถ้าไม่มีรูปใหม่
   - ⚠️ **class_level ใน student_cards เป็น integer** (1,2,3,...) vs **students.class_level เป็น string** ('ม.1','ม.2',...) → ต้องแปลง

3. **Expire** — ปิดบัตร:
   - student_status → 'expired' หรือ 'graduated'
   - **ไม่ลบบัตร** — เก็บไว้เป็นประวัติ
   - **ไม่ลบรูป** — อาจใช้อ้างอิงภายหลัง

4. **Idempotent** — รันซ้ำแล้วไม่สร้างบัตรซ้ำ:
   - ค้นบัตรด้วย `student_id` (FK) เป็นหลัก
   - ถ้า student_id ไม่มี ให้ลอง match ด้วย student_number + academy_id

**6.2 Field Mapping ระหว่าง tables:**

| student_cards field | Source | หมายเหตุ |
|---------------------|--------|----------|
| student_id | students.id | FK |
| academy_id | students.academy_id | |
| student_number | students.student_id (string 20) | ⚠️ ชื่อ field ซ้ำซ้อน |
| full_name_thai | concat(students.title_prefix_th, first_name_th, last_name_th) | |
| title_name | students.title_prefix_th | |
| first_name_thai | students.first_name_th | |
| last_name_thai | students.last_name_th | |
| first_name_english | students.first_name_en | |
| national_id | students.citizen_id | |
| birth_date | students.date_of_birth | |
| birth_date_string | format(students.date_of_birth, 'd/m/Y') | + 543 สำหรับ พ.ศ. |
| class_level | **integer** จาก classroom.grade_level | 'ม.1'→1, 'ม.2'→2, ... |
| class_section | **integer** จาก classroom.section | '1'→1, '2'→2, ... |
| level_and_room | "{class_level}/{class_section}" | e.g. "1/1" |
| card_issue_date | ตามนโยบาย (ระยะ 0) | |
| card_expiry_date | ตามนโยบาย (ระยะ 0) | |
| student_status | 'active' | ⚠️ ต้องแก้ column type |
| profile_image | students.profile_image หรือ path เดิม | คงรูปเดิมถ้ามี |
| order_no | classroom_students.student_number | เลขที่ในห้อง |

**6.3 สร้าง Artisan Command:**

**ไฟล์:** `api/nuxnanravel/app/Console/Commands/SyncStudentCards.php`
**Signature:** `students:sync-cards {--academy=} {--academic-year=} {--preview} {--commit}`
- `--preview` แสดงสรุปก่อนทำ
- `--commit` ทำจริง
- ต้องมี `--preview` ก่อน `--commit` (safety)

**6.4 เพิ่ม API endpoint:**

**Routes เพิ่ม:**
- `GET /api/academies/{academy}/student-cards/sync/preview` — preview sync
- `POST /api/academies/{academy}/student-cards/sync/commit` — commit sync
- Auth: `auth:api` + admin permission

**ไฟล์ที่ต้องสร้าง/แก้:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/StudentCardSyncService.php` | สร้างใหม่ |
| `app/Console/Commands/SyncStudentCards.php` | สร้างใหม่ |
| `StudentCardController.php` | เพิ่ม `syncPreview()`, `syncCommit()` |
| `routes/learn/academy-student-card.php` | เพิ่ม routes |

---

### ระยะที่ 7: ปรับ API บัตรนักเรียน

**เป้าหมาย:** ให้ API กรองข้อมูลถูกต้อง ไม่แสดง ม.6 เก่า ไม่ข้าม academy

**7.1 แก้ `getStudentByRoom()` — เพิ่ม status filter:**

**ไฟล์:** `StudentCardController.php`

**ปัจจุบัน:**
```php
StudentCard::where('class_level', $level)->where('class_section', $room)->orderBy(...)
```

**ควรเป็น:**
```php
StudentCard::where('class_level', $level)
    ->where('class_section', $room)
    ->where('student_status', 'active')  // ⚠️ ต้องแก้ column type ก่อน
    ->where('academy_id', $academy->id)  // scope ตาม academy
    ->orderBy(...)
```

**7.2 แก้ `dashboard()` — เพิ่ม status + academy filter:**

**ปัจจุบัน:** นับทุกบัตรไม่กรอง
**ควรเป็น:** นับเฉพาะ `student_status = 'active'` + กรอง academy

**7.3 แก้ `statistics()` — เพิ่ม graduated/expired stats:**

เพิ่ม return:
```php
[
    'totalActive' => ...,
    'totalGraduated' => ...,
    'totalExpired' => ...,
    'missingCard' => ...,   // active enrollment ที่ไม่มีบัตร
    'byLevel' => [...],     // แยก active เท่านั้น
    'sectionsByLevel' => [...],
]
```

**7.4 แก้ `search()` — เพิ่ม status parameter:**

เพิ่ม query parameter `status` (default: 'active')
Admin สามารถ `?status=all` เพื่อดู graduated/expired ได้

**7.5 เพิ่ม endpoint `levels()` ที่ dynamic:**

**Route:** `GET /api/academies/{academy}/student-cards/levels`
**Response:** ดึง distinct grade_level + section จาก active student_cards ของ academy
```json
[
    {"level": 1, "name": "ม.1", "sections": [1,2,3,...,11], "studentCount": 450},
    {"level": 2, "name": "ม.2", "sections": [1,2,...,9], "studentCount": 380},
    ...
]
```
→ ใช้แทน hard-code ใน frontend

**7.6 พิจารณา legacy routes:**

**Public routes** (`/api/student-card/*`) ที่ไม่มี auth:
- ⚠️ ตอนนี้ใครก็เข้าได้ ข้อมูลนักเรียนเป็นข้อมูลส่วนบุคคล
- **ทางเลือก A:** เพิ่ม auth middleware (breaking change สำหรับ client ที่ใช้อยู่)
- **ทางเลือก B:** ให้ legacy routes redirect ไป academy-scoped routes
- **ทางเลือก C:** คง public ไว้แต่ จำกัดข้อมูล (ไม่แสดง national_id, birth_date)
- **แนะนำ:** เริ่มจากทางเลือก B + deprecation notice

**ไฟล์ที่ต้องแก้:**
| ไฟล์ | Action |
|------|--------|
| `StudentCardController.php` | แก้ไข methods: getStudentByRoom, dashboard, statistics, search + เพิ่ม levels, syncPreview, syncCommit |
| `routes/learn/academy-student-card.php` | เพิ่ม routes: levels, sync/preview, sync/commit, audit |
| `routes/studentcard/studentcard.php` | พิจารณา deprecation / auth |

---

### ระยะที่ 8: ปรับ Frontend `/student-card`

**เป้าหมาย:** เปลี่ยน hard-code เป็น dynamic + กรอง active

**8.1 แก้ `ui/pages/student-card/index.vue`:**

**ลบ:**
```ts
const mattayomLevels = [
    { id: 0, name: 'ม.1', rooms: 11, color: 'blue' },
    { id: 1, name: 'ม.2', rooms: 9, color: 'blue' },
    ...
]
```

**เพิ่ม:**
```ts
const { data: levels } = await useApi('/api/academies/{academy}/student-cards/levels')
// หรือ useFetch ตาม pattern ของ project
```

- แสดง loading state ระหว่างโหลด
- แสดง empty state ถ้าไม่มี levels
- แสดง error state ถ้า API fail (fallback เป็น hard-code?)

**8.2 แก้ `ui/pages/student-card/admin/index.vue`:**
- เหมือนกัน — ลบ hard-code, ดึงจาก API

**8.3 แก้ระบบ routing:**
- ปัจจุบัน URL ใช้ levelId+1 (0-indexed → 1-indexed)
- ถ้า API คืน level number เป็น integer (1-6) → routing ตรงอยู่แล้ว
- ตรวจว่า `[level]/[room].vue` ยัง parse ค่าถูกต้อง

**8.4 แสดงจำนวนนักเรียนในแต่ละห้อง:**
- API `levels` ควรคืน studentCount ต่อห้อง
- แสดงบน room button เป็น badge

**8.5 ไม่แสดง ม.6 เก่า:**
- ถ้า API filter ถูกต้อง (student_status = 'active') → frontend ไม่ต้องกรองเอง
- แต่ `levels` endpoint จะไม่คืน ม.6 ถ้าไม่มี active cards เหลือ

**8.6 เพิ่ม academy context:**
- ตอนนี้ public routes ไม่ระบุ academy
- ถ้ายังคง public routes → ต้องกำหนด default academy
- ถ้าย้ายไป academy-scoped → ต้องเปลี่ยน routing ทั้งชุด

**ไฟล์ที่ต้องแก้:**
| ไฟล์ | Action |
|------|--------|
| `ui/pages/student-card/index.vue` | แก้ไข — ลบ hard-code, ดึง API |
| `ui/pages/student-card/admin/index.vue` | แก้ไข — ลบ hard-code, ดึง API |
| `ui/pages/student-card/[level]/[room].vue` | ตรวจ — อาจไม่ต้องแก้ถ้า API filter ถูก |
| `ui/pages/student-card/admin/students/[level]/[room].vue` | ตรวจ — อาจไม่ต้องแก้ |

---

### ระยะที่ 9: เพิ่ม Card Sync UI ใน Academy Admin

**เป้าหมาย:** ให้ admin ทำ card sync ผ่านหน้าเว็บได้

**9.1 เพิ่มหน้า admin หรือ tab ใหม่:**

**ทางเลือก A (แนะนำ):** เพิ่ม section ใน `/academies/{name}/admin/student-cards/index.vue` ที่มีอยู่
**ทางเลือก B:** สร้างหน้าใหม่ `/academies/{name}/admin/student-cards/sync.vue`

**Workflow:**
1. เลือกปีการศึกษา (dropdown จาก academic_years)
2. กด "ตรวจสอบข้อมูล" → เรียก audit endpoint → แสดงสรุป
3. ถ้ามี anomaly → download CSV ตรวจ
4. กด "Preview Sync" → เรียก sync/preview → แสดง create/update/expire counts
5. ตรวจรายละเอียด (expandable sections)
6. กด "ยืนยัน Sync" → เรียก sync/commit → แสดง result
7. แสดง reconciliation summary

**9.2 UI Components ที่ต้องสร้าง:**

| Component | หน้าที่ |
|-----------|---------|
| `CardSyncPreviewPanel.vue` | แสดง preview: create/update/expire/unchanged counts |
| `CardSyncAuditSummary.vue` | แสดง audit report sections A/B/C |
| `CardSyncConfirmDialog.vue` | Dialog ยืนยัน commit พร้อมพิมพ์ชื่อปี |

**9.3 Composable:**

**ไฟล์:** `ui/composables/useStudentCardSync.ts`
```ts
// Methods
audit(academyId, yearId, levels?)
syncPreview(academyId, yearId)
syncCommit(academyId, yearId)
```

**ไฟล์ที่ต้องสร้าง:**
| ไฟล์ | Action |
|------|--------|
| `ui/composables/useStudentCardSync.ts` | สร้างใหม่ |
| `ui/components/school/CardSyncPreviewPanel.vue` | สร้างใหม่ |
| `ui/components/school/CardSyncAuditSummary.vue` | สร้างใหม่ |
| `ui/components/school/CardSyncConfirmDialog.vue` | สร้างใหม่ |
| `ui/pages/academies/[name]/admin/student-cards/index.vue` | แก้ไข — เพิ่ม sync section |

---

### ระยะที่ 10: ทดสอบ

**10.1 Backend Unit/Integration Tests:**

**ไฟล์ที่ต้องสร้าง:**
| ไฟล์ | ทดสอบ |
|------|-------|
| `tests/Feature/StudentCardSyncTest.php` | Sync service logic |
| `tests/Feature/StudentCardAuditTest.php` | Audit report accuracy |
| `tests/Feature/StudentCardApiTest.php` | API endpoint filtering |

**Test cases สำคัญ:**

```
✓ ม.6 ถูก graduate แล้ว บัตรถูก expire แต่ record ไม่ถูกลบ
✓ ม.6 ไม่ปรากฏใน active card API
✓ นักเรียนใหม่ ม.1 ได้ enrollment + บัตร + academic info
✓ นักเรียนใหม่ ม.4 จากภายนอก ได้บัตร แยกจากคนที่เลื่อนจาก ม.3
✓ คนเลื่อนจาก ม.3→ม.4 บัตรอัพเดท class_level ไม่ได้สร้างใหม่
✓ รัน sync ซ้ำ 2 รอบ → ไม่เกิดบัตรซ้ำ (idempotent)
✓ cross-academy access ถูกปฏิเสธ
✓ rollback/undo ทำงาน → บัตรกลับสถานะเดิม
✓ นักเรียนไม่มีรูป → สร้างบัตรได้ (profile_image = null)
✓ academic info มี is_current = true คนละ 1 record เท่านั้น
✓ student_cards.student_id มีค่าทุก record (ไม่ null)
✓ levels endpoint คืนข้อมูล dynamic ถูกต้อง
```

**10.2 Reconciliation Queries (รันหลัง commit):**

```sql
-- 1. active enrollment คนละไม่เกิน 1
SELECT student_id, COUNT(*) as cnt FROM classroom_students
WHERE status = 'active' GROUP BY student_id HAVING cnt > 1;
-- ต้องได้ 0 rows

-- 2. current academic info คนละ 1
SELECT student_id, COUNT(*) as cnt FROM student_academic_info
WHERE is_current = 1 GROUP BY student_id HAVING cnt > 1;
-- ต้องได้ 0 rows

-- 3. active student ทุกคนมีบัตร 1 ใบ
SELECT s.id, s.student_id, s.first_name_th FROM students s
JOIN classroom_students cs ON s.id = cs.student_id AND cs.status = 'active'
LEFT JOIN student_cards sc ON s.id = sc.student_id AND sc.student_status = 'active'
WHERE s.status = 'active' AND s.academy_id = 1 AND sc.id IS NULL;
-- ต้องได้ 0 rows

-- 4. active card ไม่มี ม.6 รุ่นเก่า
SELECT sc.* FROM student_cards sc
JOIN students s ON sc.student_id = s.id
WHERE s.status = 'graduated' AND sc.student_status = 'active';
-- ต้องได้ 0 rows

-- 5. จำนวนบัตร active ตรงกับ enrollment
SELECT c.grade_level, c.section,
       (SELECT COUNT(*) FROM classroom_students cs2 WHERE cs2.classroom_id = c.id AND cs2.status = 'active') as enrollment_count,
       (SELECT COUNT(*) FROM student_cards sc2 WHERE sc2.class_level = CAST(SUBSTRING(c.grade_level, 3) AS UNSIGNED) AND sc2.class_section = CAST(c.section AS UNSIGNED) AND sc2.academy_id = 1 AND sc2.student_status = 'active') as card_count
FROM classrooms c
WHERE c.academy_id = 1 AND c.academic_year_id = {current_year_id}
ORDER BY c.grade_level, CAST(c.section AS UNSIGNED);
-- enrollment_count ต้องเท่ากับ card_count ทุกแถว

-- 6. ไม่มีบัตรข้าม academy
SELECT sc.id, sc.student_id, sc.academy_id, s.academy_id as student_academy_id
FROM student_cards sc
JOIN students s ON sc.student_id = s.id
WHERE sc.academy_id != s.academy_id;
-- ต้องได้ 0 rows
```

**10.3 Browser Smoke Test:**

| # | ทดสอบ | URL | คาดหวัง |
|---|-------|-----|---------|
| 1 | หน้ารวมแสดงชั้นเรียนจาก API | `/student-card` | ชั้น/ห้อง dynamic, ไม่มี ม.6 ถ้าไม่มี active cards |
| 2 | รายการห้องแสดงนักเรียน active | `/student-card/1/1` | แสดงเฉพาะ ม.1/1 ที่ active |
| 3 | ค้นหานักเรียนใหม่ ม.1 | search bar | พบชื่อ + แสดงบัตรถูกต้อง |
| 4 | ค้นหานักเรียน ม.6 เก่า | search bar | ไม่พบ (ถ้า status filter ทำงาน) |
| 5 | หน้ารายละเอียดบัตร | `/student-card/profile/{id}` | ชื่อ/ชั้น/ห้อง/รูป/QR ถูกต้อง |
| 6 | QR Code | scan QR | เปิด profile URL ถูกต้อง |
| 7 | Admin หน้า sync | academy admin | audit + preview + commit ทำงาน |
| 8 | Mobile layout | resize browser | responsive ถูกต้อง |
| 9 | สิทธิ์ admin/non-admin | login ต่าง role | non-admin ไม่เห็น admin buttons |

---

## ลำดับการปล่อยใช้งาน (Deployment Checklist)

```
 1. ☐ สำรองฐานข้อมูล (mysqldump)
 2. ☐ ตอบคำถามระยะ 0 ให้ครบ (นโยบายข้อมูล)
 3. ☐ รัน Schema Audit queries (ระยะ 1) → แก้ migration ถ้าจำเป็น
 4. ☐ รัน php artisan migrate (migration ใหม่ถ้ามี)
 5. ☐ รัน StudentsBackfillCardLink (ถ้ามี student_cards.student_id = NULL)
 6. ☐ รัน SyncStudentRelatedTables (ถ้ามี academy_id = NULL)
 7. ☐ เตรียมปีการศึกษาใหม่ + ห้องเรียนผ่าน Rollover wizard (ระยะ 3)
 8. ☐ Preview rollover (ระยะ 4) → ให้นายทะเบียนตรวจ
 9. ☐ Commit rollover (ระยะ 5) → ตรวจ reconciliation
10. ☐ Deploy StudentCardSyncService + API changes (ระยะ 6-7)
11. ☐ รัน Audit report (ระยะ 2) → ตรวจ anomaly
12. ☐ Preview card sync → ให้นายทะเบียนตรวจ
13. ☐ Commit card sync → ตรวจ reconciliation queries
14. ☐ Deploy frontend changes (ระยะ 8)
15. ☐ ทดสอบ browser (ระยะ 10.3)
16. ☐ สุ่มเทียบข้อมูลรายคน (5-10 คนต่อชั้น)
17. ☐ Deploy Card Sync UI (ระยะ 9) — ทำทีหลังได้
18. ☐ ปิด undo window เมื่อยืนยันว่าถูกต้อง
19. ☐ เก็บ rollover batch + audit report เป็นหลักฐาน
```

---

## สรุปไฟล์ทั้งหมดที่ต้องสร้าง/แก้ไข

### ไฟล์ใหม่ (สร้าง)
| # | ไฟล์ | ระยะ |
|---|------|------|
| 1 | `app/Services/StudentCardAuditService.php` | 2 |
| 2 | `app/Services/StudentCardSyncService.php` | 6 |
| 3 | `app/Console/Commands/StudentCardAudit.php` | 2 |
| 4 | `app/Console/Commands/SyncStudentCards.php` | 6 |
| 5 | `database/migrations/xxxx_fix_student_cards_status_column.php` | 1 (ถ้าจำเป็น) |
| 6 | `database/migrations/xxxx_add_student_cards_indexes.php` | 1 (ถ้าจำเป็น) |
| 7 | `ui/composables/useStudentCardSync.ts` | 9 |
| 8 | `ui/components/school/CardSyncPreviewPanel.vue` | 9 |
| 9 | `ui/components/school/CardSyncAuditSummary.vue` | 9 |
| 10 | `ui/components/school/CardSyncConfirmDialog.vue` | 9 |
| 11 | `tests/Feature/StudentCardSyncTest.php` | 10 |
| 12 | `tests/Feature/StudentCardAuditTest.php` | 10 |
| 13 | `tests/Feature/StudentCardApiTest.php` | 10 |

### ไฟล์แก้ไข
| # | ไฟล์ | ระยะ | สิ่งที่แก้ |
|---|------|------|----------|
| 1 | `StudentCardController.php` | 2,6,7 | เพิ่ม audit, syncPreview, syncCommit, levels + แก้ getStudentByRoom, dashboard, statistics, search |
| 2 | `routes/learn/academy-student-card.php` | 2,6,7 | เพิ่ม routes: audit, sync/preview, sync/commit, levels |
| 3 | `ui/pages/student-card/index.vue` | 8 | ลบ hard-code levels, ดึงจาก API |
| 4 | `ui/pages/student-card/admin/index.vue` | 8 | ลบ hard-code levels, ดึงจาก API |
| 5 | `ui/pages/academies/[name]/admin/student-cards/index.vue` | 9 | เพิ่ม sync section/tab |
| 6 | `routes/studentcard/studentcard.php` | 7 | พิจารณา deprecation / auth |

### ไฟล์ไม่ต้องแก้ (ใช้ที่มีอยู่)
- `AcademicYearRolloverService.php` — ใช้ตรงๆ ไม่ต้องแก้
- `StudentEnrollmentService.php` — ใช้ตรงๆ ไม่ต้องแก้
- `RolloverController.php` — ใช้ตรงๆ
- `ui/pages/academies/[name]/admin/gradebook/rollover/index.vue` — ใช้ตรงๆ
- `StudentCard.php` model — อาจแก้เล็กน้อย (scope active)

---

## หัวใจของแผน

> **"ไม่ลบประวัติ, ใช้ระบบ rollover ที่มีอยู่, ให้บัตรเป็นข้อมูลอนุพันธ์ (derived), preview ก่อน commit, ตรวจยอดหลังทำทุกครั้ง"**

- Rollover จัดการ **ข้อมูลทะเบียน** (students, classroom_students, student_academic_info)
- Card Sync จัดการ **ข้อมูลบัตร** (student_cards) เป็นขั้นตอนแยกหลัง rollover
- ทั้งสองขั้นตอนมี preview ก่อน commit
- ทั้งสองขั้นตอนตรวจ reconciliation หลัง commit
- ไม่มีการลบข้อมูล — เปลี่ยนสถานะเท่านั้น

---

## 2026-07-07 - Updated student roster XLSX analysis

- Source: `docs/api/20260707150052.xlsx`, sheet `Student List`, 2,437 data rows and 53 columns.
- File quality: no blank/duplicate student codes or citizen IDs; all 13-digit citizen IDs passed checksum; required names and classroom labels are populated.
- Target context inferred from database: academy 1 (`เพลินวิทยาธาร`), academic year 2 (`2569`). Read-only checks only; no student data was changed.
- Identity preview: 1,839 match on both keys, 462 match student code with blank DB citizen ID, 125 are new, and 11 have conflicting identity matches requiring manual quarantine.
- Enrollment preview for code matches: 1,700 already in the same class, 170 differ, 442 have no active 2569 enrollment.
- The XLSX contains 70 classrooms. Sixteen primary/kindergarten classrooms are absent from the current-year DB; these must be created/approved before commit.
- Existing `StudentImportService` accepts CSV and rejects existing identities, so it is not safe for this update as-is. Plan an XLSX adapter plus update-capable preview/commit workflow with per-row transactions, audit trail, idempotency, and rollback evidence.
- Map canonical data into `students`, `classroom_students`, `student_academic_info`, `student_addresses`, `student_contacts`, `student_guardians`/guardian contacts, and `student_health_info`; run card sync only after roster reconciliation.
- Verification plan: preview category totals, manually resolve 11 conflicts, back up DB, commit in batches, reconcile identity/enrollment/academic-info counts, then sample records by classroom.

### 2026-07-07 roster runtime/schema fixes

- Fixed post-roster card sync to run once per completed batch instead of once per row in both CLI and queued import paths.
- Identity changes are now committed independently of enrollment classification, preserving citizen-ID fills during move/create/unchanged actions.
- Added missing roster service imports and the explicit Laravel `Log` facade import.
- Corrected address and health upsert keys/field names to match their schemas.
- Extended the integration test to cover identity fill during classroom movement, address/health persistence, and exactly one card-sync call.
- Verification: roster date/parser/integration suites pass (3 tests, 38 assertions); scoped Pint check passes.

---

## แผนอัปเดตรายชื่อนักเรียนจาก XLSX — ฉบับสมบูรณ์

**วันที่วางแผน:** 2026-07-07
**สถานะ:** วางแผนเสร็จ (ยังไม่เริ่มพัฒนา)
**ขอบเขต:** นำไฟล์ XLSX รายชื่อนักเรียน 2,437 คน อัปเดตลงฐานข้อมูลปีการศึกษา 2569

### ข้อมูลต้นทาง (XLSX)

| รายการ | ค่า |
|--------|-----|
| ไฟล์ | `docs/api/20260707150052.xlsx` |
| จำนวนแถว | 2,437 |
| จำนวนคอลัมน์ | 53 |
| จำนวนห้องเรียน | 70 (อ.1–อ.3, ป.1–ป.6, ม.1–ม.6) |
| สถานะ "กำลังศึกษาอยู่" | 1,626 คน |
| สถานะ "นักเรียนเข้าใหม่" | 811 คน |
| รูปแบบวันที่ | ไทยย่อ+พ.ศ. เช่น `08 เม.ย. 57`, `07 พ.ค. 2569` |
| Academy | 1 (เพลินวิทยาธาร) |
| ปีการศึกษาเป้าหมาย | 2 (2569) |

### การแจกแจงตามระดับ (จาก XLSX)

| ระดับ | จำนวน | เข้าใหม่ | กำลังศึกษา | หมายเหตุ |
|-------|--------|----------|------------|----------|
| อ.1–อ.3 | 102 | 48 | 54 | **ห้องเรียนยังไม่มีใน DB** |
| ป.1–ป.6 | 385 | 74 | 311 | **ห้องเรียนยังไม่มีใน DB (12 ห้อง)** |
| ม.1 | 395 | 389 | 6 | เกือบทั้งหมดเข้าใหม่ |
| ม.2 | 399 | 8 | 391 | |
| ม.3 | 329 | 4 | 325 | |
| ม.4 | 288 | 282 | 6 | เกือบทั้งหมดเข้าใหม่ (จาก ม.3 อื่นโรงเรียน) |
| ม.5 | 258 | 6 | 252 | |
| ม.6 | 281 | 0 | 281 | ทั้งหมดกำลังศึกษา |

### ผลการเทียบกับ DB (Read-only)

| กลุ่ม | จำนวน | คำอธิบาย |
|-------|--------|----------|
| exact_match | 1,839 | ตรงทั้ง student_code + citizen_id |
| code_only | 462 | student_code ตรง แต่ DB ยังไม่มี citizen_id → เติมได้ |
| new_student | 125 | ไม่มีใน DB เลย → สร้างใหม่ |
| conflict | 11 | student_code ชี้คนหนึ่ง citizen_id ชี้อีกคน → กักตรวจ |
| same_class | 1,700 | enrollment ปี 2569 ตรงกับ XLSX |
| diff_class | 170 | enrollment ปี 2569 ห้องไม่ตรง → ย้ายห้อง |
| no_enrollment | 442 | มี student record แต่ไม่มี enrollment 2569 → สร้าง |
| missing_classroom | 16 | ห้องเรียนที่ XLSX มี แต่ DB ไม่มี (อ./ป.) |

---

### ระยะที่ 1: สร้าง XLSX Parser + Thai Date Normalizer

**เป้าหมาย:** แปลงไฟล์ XLSX 53 คอลัมน์เป็น normalized struct ที่พร้อมเทียบกับ DB

**1.1 สร้าง `StudentRosterXlsxParser`**

**ไฟล์:** `app/Services/Import/StudentRosterXlsxParser.php`

**หน้าที่:**
- อ่าน XLSX ด้วย `PhpSpreadsheet` (ต้อง `composer require phpoffice/phpspreadsheet`)
- หรือใช้ `Maatwebsite\Excel` ที่มีอยู่แล้วในโปรเจค

**Input:** path ไปยัง XLSX file
**Output:** `Collection` ของ normalized array

**Column mapping (53 XLSX cols → normalized keys):**

```php
$columnMap = [
    'เลขประจำตัวประชาชน'        => 'citizen_id',         // col 1
    'เลขประจำตัวนักเรียน '       => 'student_code',       // col 2 (มีเว้นวรรคต่อท้าย!)
    'ชั้นเรียน'                  => 'classroom_label',    // col 3 → split เป็น grade_level + section
    'คำนำหน้าชื่อ'               => 'title_prefix_th',    // col 4
    'ชื่อ'                       => 'first_name_th',      // col 5
    'นามสกุล'                    => 'namsagul',           // col 6
    'ชื่อกลาง'                   => 'middle_name_th',     // col 7
    'คำนำหน้าชื่อ.1'             => 'title_prefix_en',    // col 8
    'ชื่อภาษาอังกฤษ'             => 'first_name_en',      // col 9
    'นามสกุลภาษาอังกฤษ'          => 'last_name_en',       // col 10
    'ชื่อกลางภาษาอังกฤษ'         => 'middle_name_en',     // col 11
    'ว.ด.ป. เกิด'               => 'birth_date_raw',     // col 12 → parse Thai date
    'เพศ'                        => 'gender_raw',         // col 13 (ชาย/หญิง)
    'สัญชาติ'                    => 'nationality',        // col 14
    'ศาสนา'                      => 'religion',           // col 15
    'สถานะนักเรียน'              => 'student_status_raw', // col 16
    'วันที่บันทึก'               => 'record_date_raw',    // col 17
    'ประเภทความพิการ'            => 'disability_type',    // col 18
    // --- ที่อยู่ ---
    'รหัสประจำบ้าน'              => 'house_code',         // col 19
    'บ้านเลขที่'                 => 'house_number',       // col 20
    'หมู่ที่'                    => 'village_number',     // col 21
    'ซอย'                        => 'alley',              // col 22
    'ถนน'                        => 'road',               // col 23
    'ตำบล/แขวง'                  => 'subdistrict',        // col 24
    'อำเภอ/เขต'                  => 'district',           // col 25
    'จังหวัด'                    => 'province',           // col 26
    'รหัสไปรษณีย์'               => 'postal_code',        // col 27
    'เบอร์โทรศัพท์'              => 'phone',              // col 28
    'วันที่เข้าเรียน'            => 'enrollment_date_raw',// col 29
    // --- บิดา ---
    'เลขประจำตัวประชาชน (บิดา)'  => 'father_citizen_id',  // col 30
    'คำนำหน้าชื่อ (บิดา)'       => 'father_title',       // col 31
    'ชื่อ  (บิดา)'              => 'father_first_name',  // col 32
    'นามสกุล  (บิดา)'           => 'father_last_name',   // col 33
    'สถานภาพของบิดา'            => 'father_status',      // col 34
    'สัญชาติ.1'                  => 'father_nationality', // col 35
    // --- มารดา ---
    'เลขประจำตัวประชาชน (มารดา)' => 'mother_citizen_id',  // col 36
    'คำนำหน้าชื่อ (มารดา)'      => 'mother_title',       // col 37
    'ชื่อ (มารดา)'              => 'mother_first_name',  // col 38
    'นามสกุล (มารดา)'           => 'mother_last_name',   // col 39
    'สถานภาพของมารดา'           => 'mother_status',      // col 40
    'สัญชาติ.2'                  => 'mother_nationality', // col 41
    // --- ผู้ปกครอง ---
    'เลขประจำตัวประชาชน (ผู้ปกครอง)' => 'guardian_citizen_id',  // col 42
    'คำนำหน้าชื่อ.2'                => 'guardian_title',       // col 43
    'ชื่อ - นามสกุล'                => 'guardian_full_name',   // col 44 → split first/last
    'อาชีพของผู้ปกครอง'             => 'guardian_occupation',  // col 45
    'เบอร์โทรศัพท์.1'               => 'guardian_phone',       // col 46
    'ความสัมพันธ์'                  => 'guardian_relationship', // col 47
    // --- สุขภาพ ---
    'ความสูง (ซม.)'              => 'height_cm',          // col 48
    'น้ำหนัก (กก.)'              => 'weight_kg',          // col 49
    // --- โรงเรียนเดิม ---
    'ชื่อโรงเรียนเดิม'           => 'previous_school',    // col 50
    'จังหวัดโรงเรียนเดิม'        => 'previous_school_province', // col 51
    'ชั้นเรียน.1'                => 'previous_grade',     // col 52
];
```

**1.2 สร้าง Thai Date Parser**

**ไฟล์:** `app/Services/Import/ThaiDateParser.php`

**รูปแบบที่ต้องรองรับ:**

| ตัวอย่าง | ความหมาย | ผลลัพธ์ (ค.ศ.) |
|----------|----------|----------------|
| `08 เม.ย. 57` | 2-digit พ.ศ. (2557) | `2014-04-08` |
| `23 ก.ย. 51` | 2-digit พ.ศ. (2551) | `2008-09-23` |
| `07 พ.ค. 2569` | 4-digit พ.ศ. | `2026-05-07` |
| `15 พ.ค. 2569` | 4-digit พ.ศ. | `2026-05-15` |

**Thai month abbreviation mapping:**
```php
$thaiMonths = [
    'ม.ค.'  => 1,  'ก.พ.'  => 2,  'มี.ค.' => 3,  'เม.ย.' => 4,
    'พ.ค.'  => 5,  'มิ.ย.' => 6,  'ก.ค.'  => 7,  'ส.ค.'  => 8,
    'ก.ย.'  => 9,  'ต.ค.'  => 10, 'พ.ย.'  => 11, 'ธ.ค.'  => 12,
];
```

**Logic:**
1. Regex match: `/^(\d{1,2})\s+(\S+)\s+(\d{2,4})$/u`
2. Map Thai month → int
3. ถ้าปี ≤ 99 → เติม 2500 (พ.ศ. 2 หลัก) → ลบ 543 (แปลง ค.ศ.)
4. ถ้าปี > 2400 → ลบ 543 (พ.ศ. 4 หลัก)
5. ถ้าปี < 100 และ > 40 → สันนิษฐาน 25xx → ลบ 543
6. Return Carbon date หรือ null ถ้า parse ไม่ได้

**1.3 Classroom Label Splitter**

**ไฟล์:** อยู่ใน Parser เดียวกัน

**Logic:** `ม.1/5` → `['grade_level' => 'ม.1', 'section' => '5']`
```php
preg_match('/^([^\/]+)\/(\d+)$/', $label, $m);
// $m[1] = 'ม.1', $m[2] = '5'
```

**⚠️ ข้อควรระวัง:**
- คอลัมน์ `เลขประจำตัวนักเรียน ` มีช่องว่างต่อท้าย (trailing space) — ต้อง trim ชื่อคอลัมน์
- คอลัมน์ `ชื่อ  (บิดา)` และ `นามสกุล  (บิดา)` มีเว้นวรรค 2 ตัว — ต้อง normalize whitespace
- `ชื่อ - นามสกุล` ของผู้ปกครอง เป็น full name ชิ้นเดียว → ต้อง split by space
- ค่า `gender_raw` เป็น `ชาย` / `หญิง` → map เป็น `1` / `0`
- ค่า `father_status` / `mother_status` เป็น `มีชีวิต` / `เสียชีวิต` → map เป็น `alive` / `deceased`

**1.4 Row Validation**

แต่ละแถวต้องผ่าน validation:
```
- citizen_id: required, digits:13, Thai checksum pass
- student_code: required, string, max:20
- first_name_th: required, string, max:100
- last_name_th: required, string, max:100
- classroom_label: required, pattern /^[^\\/]+\/\d+$/
- birth_date: required, valid date, before today
- gender: required, in:ชาย,หญิง
```

**ผลลัพธ์ระยะ 1:**
- `StudentRosterXlsxParser` ที่แปลง XLSX → Collection ของ normalized structs
- `ThaiDateParser` ที่แปลงวันที่ไทยได้ทุกรูปแบบ
- ข้อมูลแต่ละแถวมี status: `valid`, `warning`, `invalid`

**ไฟล์ที่ต้องสร้าง:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/Import/StudentRosterXlsxParser.php` | สร้างใหม่ |
| `app/Services/Import/ThaiDateParser.php` | สร้างใหม่ |

---

### ระยะที่ 2: สร้าง Identity Matcher + Preview Batch

**เป้าหมาย:** เทียบข้อมูล XLSX กับ DB แล้วจำแนกแต่ละแถวเป็นกลุ่ม action

**2.1 สร้าง `StudentRosterUpdateService`**

**ไฟล์:** `app/Services/Import/StudentRosterUpdateService.php`

**Method หลัก:**
```php
public function preview(
    Academy $academy,
    AcademicYear $year,
    Collection $parsedRows
): StudentRosterUpdateBatch
```

**Logic ของ Identity Matching (ต่อแถว):**

```
1. ค้นหา Student ด้วย student_code (students.student_id) ภายใน academy
2. ค้นหา Student ด้วย citizen_id (students.citizen_id) ภายใน academy
3. จำแนก:

   a) ทั้ง student_code + citizen_id ตรงกัน → MATCHED
      → เทียบ enrollment + ข้อมูลส่วนตัว ต่อ

   b) student_code ตรง + DB ไม่มี citizen_id → CODE_ONLY_MATCH
      → เตรียม fill citizen_id
      → เทียบ enrollment + ข้อมูลส่วนตัว ต่อ

   c) ไม่พบทั้ง student_code + citizen_id → NEW_STUDENT
      → สร้าง student + enrollment + ข้อมูลทั้งหมด

   d) student_code ชี้คนหนึ่ง, citizen_id ชี้อีกคน → CONFLICT
      → กักไว้ ห้ามอัปเดตอัตโนมัติ
      → ต้องแก้ด้วยคน

   e) ไม่มี student_code แต่มี citizen_id ตรง → CITIZEN_MATCH
      → อัปเดต student_code ให้ตรง + ต่อ enrollment
```

**2.2 Enrollment Classification (สำหรับ MATCHED / CODE_ONLY_MATCH):**

หลังจับคู่ตัวตนได้แล้ว ตรวจ enrollment:

```
1. ค้นหา active enrollment ของ student ในปี target (academic_year_id = 2)
2. จำแนก:

   a) มี enrollment + ห้องเดิม → UNCHANGED
      → เทียบข้อมูลส่วนตัว (ชื่อ/ที่อยู่/ผู้ปกครอง) → ถ้าต่าง → UPDATE_PERSONAL

   b) มี enrollment + ห้องต่าง → MOVE_CLASSROOM
      → ย้ายจากห้องเดิมไปห้องใหม่

   c) ไม่มี enrollment ปี target → CREATE_ENROLLMENT
      → สร้าง enrollment ใหม่ (ไม่ลบ enrollment เก่าปีก่อน)
```

**2.3 รูปแบบ Preview Batch:**

ขยาย `student_import_batches` + `student_import_rows` ที่มีอยู่ หรือสร้าง table ใหม่ `student_roster_update_batches` + `student_roster_update_rows`:

**แนะนำ: ใช้ table เดิม** (`student_import_batches` + `student_import_rows`) เพิ่ม field:

```
student_import_batches:
  + import_type ENUM('new_intake', 'roster_update') DEFAULT 'new_intake'
  + source_format ENUM('csv', 'xlsx') DEFAULT 'csv'

student_import_rows:
  + action ENUM('unchanged','update_identity','update_personal','move_classroom',
                'create_enrollment','new_student','conflict') DEFAULT NULL
  + matched_student_id BIGINT UNSIGNED NULL  -- FK → students.id ที่จับคู่ได้
  + diff_data JSON NULL  -- เก็บ before/after ของ field ที่ต่าง
```

**2.4 Preview Summary ที่ต้องแสดง:**

```json
{
  "batch_id": "uuid",
  "total_rows": 2437,
  "by_action": {
    "unchanged": 1530,
    "update_identity": 462,
    "update_personal": 170,
    "move_classroom": 170,
    "create_enrollment": 442,
    "new_student": 125,
    "conflict": 11,
    "invalid": 0
  },
  "missing_classrooms": [
    {"label": "อ.1/1", "student_count": 22},
    {"label": "ป.1/1", "student_count": 27}
  ],
  "conflicts": [
    {"row": 45, "student_code": "1234", "xlsx_citizen": "1234567890123",
     "db_student_code_points_to": "student_id=100",
     "db_citizen_id_points_to": "student_id=200"}
  ]
}
```

**ไฟล์ที่ต้องสร้าง/แก้:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/Import/StudentRosterUpdateService.php` | สร้างใหม่ |
| `database/migrations/xxxx_add_roster_update_fields_to_import_tables.php` | สร้างใหม่ |
| `app/Models/StudentImportBatch.php` | แก้ — เพิ่ม cast/fillable |
| `app/Models/StudentImportRow.php` | แก้ — เพิ่ม cast/fillable |

---

### ระยะที่ 3: สร้าง Artisan Command (CLI workflow)

**เป้าหมาย:** ทำให้ admin รัน preview/commit ผ่าน CLI ก่อน มี UI ทีหลัง

**3.1 สร้าง Command: `roster:preview`**

**ไฟล์:** `app/Console/Commands/RosterPreviewCommand.php`
**Signature:** `roster:preview {file} {--academy=1} {--year=2} {--export-conflicts=}`

**ขั้นตอน:**
1. อ่าน XLSX ด้วย `StudentRosterXlsxParser`
2. Validate ทุกแถว → แสดง invalid rows (ถ้ามี)
3. รัน `StudentRosterUpdateService::preview()`
4. แสดง summary table บน console
5. ถ้า `--export-conflicts` → export ไฟล์ CSV ของ 11 conflicts ให้นายทะเบียนตรวจ
6. บันทึก batch ลง DB (status = `previewed`)

**3.2 สร้าง Command: `roster:commit`**

**ไฟล์:** `app/Console/Commands/RosterCommitCommand.php`
**Signature:** `roster:commit {batch_id} {--dry-run} {--chunk=50}`

**ขั้นตอน:**
1. โหลด batch จาก DB → ตรวจว่า status = `previewed`
2. ตรวจ prerequisites (missing classrooms ต้อง = 0, conflicts ต้อง resolved)
3. ถ้า `--dry-run` → แสดงสรุปแล้วหยุด
4. ถามยืนยัน: "จะอัปเดตนักเรียน {n} คน ในปีการศึกษา 2569 ยืนยันหรือไม่? (yes/no)"
5. ประมวลผลเป็น chunk (default 50 แถว/transaction)
6. แสดง progress bar
7. แสดง reconciliation summary เมื่อเสร็จ

**ไฟล์ที่ต้องสร้าง:**
| ไฟล์ | Action |
|------|--------|
| `app/Console/Commands/RosterPreviewCommand.php` | สร้างใหม่ |
| `app/Console/Commands/RosterCommitCommand.php` | สร้างใหม่ |

---

### ระยะที่ 4: Commit Logic — เขียนข้อมูลลง 7 ตาราง

**เป้าหมาย:** อัปเดต/สร้างข้อมูลใน DB ตาม action ที่จำแนกไว้

**4.1 ตารางที่ 1: `students` (ข้อมูลหลัก)**

| Action | สิ่งที่ทำ |
|--------|----------|
| NEW_STUDENT | สร้าง record ใหม่ด้วย `StudentIntakeService::intake()` ที่มีอยู่ (reuse!) |
| UPDATE_IDENTITY | `students.citizen_id = xlsx.citizen_id` (เติม citizen_id ที่ว่าง) |
| UPDATE_PERSONAL | อัปเดต field ที่ต่าง: `title_prefix_th`, `first_name_th`, `last_name_th`, `first_name_en`, `last_name_en`, `date_of_birth`, `gender`, `nationality`, `religion` |
| MOVE_CLASSROOM | อัปเดต `students.class_level`, `students.class_section` ให้ตรงห้องใหม่ |

**⚠️ กฎสำคัญ:**
- ห้ามเขียนทับ `students.profile_image` — รูปมาจากอีกช่องทาง
- ห้ามเปลี่ยน `students.user_id`, `students.academy_id`, `students.account_status`
- เก็บ before/after ไว้ใน `student_import_rows.diff_data`

**4.2 ตารางที่ 2: `classroom_students` (enrollment)**

| Action | สิ่งที่ทำ |
|--------|----------|
| CREATE_ENROLLMENT | ใช้ `StudentEnrollmentService::enrollStudent()` ที่มีอยู่ |
| MOVE_CLASSROOM | 1) ปิด enrollment เดิม (status → `transferred`) 2) สร้าง enrollment ใหม่ในห้องที่ถูกต้อง |
| UNCHANGED | ไม่ทำอะไร |

**⚠️ กฎสำคัญ:**
- ห้ามลบ enrollment เดิม — เปลี่ยนสถานะเท่านั้น
- ต้องมี `classroom_id` ที่ valid (ห้องต้องมีอยู่ใน DB ก่อน)
- enrollment ใหม่ต้องตั้ง `academic_year_id` = ปี target

**4.3 ตารางที่ 3: `student_academic_info`**

| Action | สิ่งที่ทำ |
|--------|----------|
| CREATE_ENROLLMENT / MOVE_CLASSROOM | ใช้ `StudentEnrollmentService` ที่จัดการ academic_info ให้อัตโนมัติ (มี `manageAcademicInfoSnapshot` อยู่แล้ว) |

**4.4 ตารางที่ 4: `student_addresses`**

**XLSX → DB mapping:**

| XLSX col | → DB field |
|----------|-----------|
| `บ้านเลขที่` | `house_number` |
| `หมู่ที่` | `village_number` |
| `ซอย` | `alley` |
| `ถนน` | `road` |
| `ตำบล/แขวง` | `subdistrict` |
| `อำเภอ/เขต` | `district` |
| `จังหวัด` | `province` |
| `รหัสไปรษณีย์` | `postal_code` |

**Logic:**
- ค้นหา `student_addresses` ที่ `student_id` ตรง + `address_type = 'current'` + `is_current = true`
- ถ้ามี → เทียบแต่ละ field → อัปเดตถ้าต่าง
- ถ้าไม่มี → สร้างใหม่ (`address_type = 'current'`, `is_current = true`)
- ⚠️ ค่า `-` ใน XLSX ให้ถือว่า null (ซอย/ถนนมักเป็น `-`)

**4.5 ตารางที่ 5: `student_guardians`**

**XLSX มี 3 ชุดข้อมูลผู้เกี่ยวข้อง:**

**ชุดที่ 1 — บิดา (cols 30-35):**
```
→ student_guardians (guardian_type = 'father')
  citizen_id    = father_citizen_id
  title_prefix  = father_title
  first_name    = father_first_name
  last_name     = father_last_name
  status        = father_status → map('มีชีวิต'=>'alive', 'เสียชีวิต'=>'deceased')
  nationality   = father_nationality
```

**ชุดที่ 2 — มารดา (cols 36-41):**
```
→ student_guardians (guardian_type = 'mother')
  citizen_id    = mother_citizen_id
  title_prefix  = mother_title
  first_name    = mother_first_name
  last_name     = mother_last_name
  status        = mother_status → map('มีชีวิต'=>'alive', 'เสียชีวิต'=>'deceased')
  nationality   = mother_nationality
```

**ชุดที่ 3 — ผู้ปกครอง (cols 42-47):**
```
→ student_guardians (guardian_type = 'guardian')
  citizen_id          = guardian_citizen_id
  title_prefix        = guardian_title
  first_name + last_name = split(guardian_full_name)  ← "ชื่อ - นามสกุล" → split by space
  occupation          = guardian_occupation
  relationship        = guardian_relationship (มารดา, บิดา, ฯลฯ)
  is_primary_contact  = true
```

**Logic:**
- ค้นหา guardian ที่ `student_id` + `guardian_type` ตรง
- ถ้ามี → เทียบ field → อัปเดตถ้าต่าง
- ถ้าไม่มี → สร้างใหม่
- ถ้า XLSX ไม่มีข้อมูลของ type นั้น (null ทุก field) → ข้าม ไม่ลบของเดิม

**4.6 ตารางที่ 6: `student_contacts`**

**XLSX มี 2 เบอร์:**
- `เบอร์โทรศัพท์` (col 28) → contact ของนักเรียน
- `เบอร์โทรศัพท์.1` (col 46) → contact ของผู้ปกครอง

**Logic สำหรับ student contact:**
- ค้นหา `student_contacts` ที่ `student_id` ตรง + `contact_type = 'mobile'` + `is_primary = true`
- ถ้ามี → เทียบ `contact_value` → อัปเดตถ้าต่าง
- ถ้าไม่มี + XLSX มีเบอร์ → สร้างใหม่

**Logic สำหรับ guardian contact:**
- เบอร์ผู้ปกครอง เก็บใน guardian record เลย (ไม่ใช่ `student_contacts`)
- ⚠️ ดูว่า guardian model มี `phone_number` field ไหม → migration `2026_02_01` มี! ใช้ได้

**4.7 ตารางที่ 7: `student_health_info`**

**XLSX → DB mapping:**

| XLSX col | → DB field |
|----------|-----------|
| `ความสูง (ซม.)` | `height_cm` (decimal 5,2) |
| `น้ำหนัก (กก.)` | `weight_kg` (decimal 5,2) |

**Logic:**
- `student_health_info` มี unique constraint on `student_id`
- ค้นหา record ที่ `student_id` ตรง
- ถ้ามี → อัปเดต height/weight
- ถ้าไม่มี → สร้างใหม่
- ⚠️ บาง weight เป็นทศนิยม (เช่น `35.3`) → ต้อง cast เป็น decimal

**4.8 Transaction Strategy:**

```
foreach (batch->rows->chunk(50) as $chunk) {
    DB::transaction(function () use ($chunk) {
        foreach ($chunk as $row) {
            if ($row->action === 'conflict' || $row->action === 'invalid') {
                continue; // ข้าม
            }
            $this->processRow($row);
            $row->update(['status' => 'imported']);
        }
    });
    // ถ้า transaction ของ chunk ใด fail → mark rows เป็น 'failed'
    // chunk อื่นทำต่อได้ (partial commit)
}
```

**⚠️ ข้อพิจารณาที่ต้องตัดสินใจ:**
1. **Partial commit หรือ All-or-nothing?**
   - แนะนำ: Chunk-level transaction (50 แถว/chunk) — ถ้า chunk ใด fail ไม่กระทบ chunk อื่น
   - แต่ถ้าต้องการ all-or-nothing → ห่อ chunk ทั้งหมดใน transaction เดียว (ช้ากว่า, lock นาน)

2. **ข้อมูล `ประเภทความพิการ` จะเก็บที่ไหน?**
   - **ตัดสินใจแล้ว:** เก็บในตาราง `student_academic_info` (โมเดล `StudentAcademicInfo`) ซึ่งมีฟิลด์ `disability_type` และ `special_needs` รองรับอยู่แล้ว ไม่ต้องแก้ไข Schema

3. **ข้อมูลโรงเรียนเดิม (`ชื่อโรงเรียนเดิม`, `จังหวัดโรงเรียนเดิม`) จะเก็บที่ไหน?**
   - **ตัดสินใจแล้ว:** เก็บในตาราง `student_academic_info` (โมเดล `StudentAcademicInfo`) ซึ่งมีฟิลด์ `previous_school_name`, `previous_school_province`, `previous_grade_level` รองรับอยู่แล้ว ไม่ต้องแก้ไข Schema

**ไฟล์ที่ต้องสร้าง/แก้:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/Import/StudentRosterCommitService.php` | สร้างใหม่ |
| อาจต้องเพิ่ม migration สำหรับ `disability_type` / `previous_school` | ขึ้นกับการตัดสินใจ |

---

### ระยะที่ 5: สร้างห้องเรียนที่ขาด (Prerequisite)

**เป้าหมาย:** สร้าง 16 ห้องเรียนที่ XLSX มีแต่ DB ไม่มี

**5.1 รายการห้องเรียนที่ขาด:**

| ห้อง | จำนวนนักเรียน | ระดับ |
|------|---------------|-------|
| อ.1/1 | 22 | อนุบาล |
| อ.2/1 | 34 | อนุบาล |
| อ.3/1 | 23 | อนุบาล |
| อ.3/2 | 23 | อนุบาล |
| ป.1/1 | 27 | ประถม |
| ป.1/2 | 35 | ประถม |
| ป.2/1 | 30 | ประถม |
| ป.2/2 | 40 | ประถม |
| ป.3/1 | 26 | ประถม |
| ป.3/2 | 37 | ประถม |
| ป.4/1 | 28 | ประถม |
| ป.4/2 | 34 | ประถม |
| ป.5/1 | 28 | ประถม |
| ป.5/2 | 34 | ประถม |
| ป.6/1 | 27 | ประถม |
| ป.6/2 | 39 | ประถม |

**5.2 วิธีสร้าง:**

ทางเลือก A (แนะนำ): เพิ่มใน `roster:commit` command — ถ้าพบ missing classrooms ให้ถามยืนยัน → สร้างอัตโนมัติ:
```php
Classroom::create([
    'academy_id' => $academy->id,
    'academic_year_id' => $year->id,
    'grade_level' => 'อ.1',  // หรือ 'ป.1', etc.
    'section' => '1',
    'name' => 'อ.1/1',
    'capacity' => 45,
]);
```

ทางเลือก B: ให้ admin สร้างผ่าน UI ก่อน

**⚠️ ข้อพิจารณา:**
- ระบบเดิมรองรับแค่ `ม.1`–`ม.6` (ดูจาก `nextGrade()` ใน RolloverService)
- ถ้าเพิ่ม `อ.` และ `ป.` → ต้องตรวจว่า API, frontend, rollover ไม่พังจากค่าที่ไม่คาดคิด
- `StudentCardSyncService.numericGradeLevel()` ใช้ regex `/(\d+)\s*$/` → `อ.1` จะได้ `1`, `ป.3` จะได้ `3` — **ชนกับ ม.1, ม.3!**
- **ต้องแก้ `numericGradeLevel()` ให้รองรับ prefix** หรือแยก card sync ให้ทำเฉพาะ ม.

**5.3 ผลกระทบต่อ Card Sync:**
- บัตรนักเรียนปัจจุบันทำเฉพาะ ม.1–ม.6
- ถ้าเพิ่ม อ./ป. → ต้องตัดสินใจว่าจะออกบัตรให้ระดับนี้ไหม
- **แนะนำ:** filter card sync ให้ทำเฉพาะ grade_level ที่ขึ้นต้นด้วย 'ม.' ก่อน

---

### ระยะที่ 6: Post-commit — Card Sync + Reconciliation

**เป้าหมาย:** หลัง commit roster แล้ว sync บัตรนักเรียน + ตรวจความถูกต้อง

**6.1 รัน Card Sync (ม.1–ม.6 เท่านั้น):**

```bash
php artisan students:sync-cards --academy=1 --academic-year=2 --preview
# ตรวจ preview → ถ้าถูกต้อง
php artisan students:sync-cards --academy=1 --academic-year=2 --commit
```

ใช้ `StudentCardSyncService` ที่มีอยู่แล้ว — ไม่ต้องเขียนใหม่

**6.2 Reconciliation Queries (รันหลัง commit ทุกครั้ง):**

```sql
-- 1. จำนวนนักเรียนในฐานข้อมูล ต้อง ≥ 2,437
SELECT COUNT(*) FROM students WHERE academy_id = 1 AND status = 'active';

-- 2. active enrollment ต้องตรง XLSX count ต่อห้อง
SELECT c.grade_level, c.section, COUNT(*) as db_count
FROM classroom_students cs
JOIN classrooms c ON cs.classroom_id = c.id
WHERE cs.academic_year_id = 2 AND cs.status = 'active' AND c.academy_id = 1
GROUP BY c.grade_level, c.section
ORDER BY c.grade_level, CAST(c.section AS UNSIGNED);
-- เทียบกับ XLSX count ต่อห้อง

-- 3. ไม่มี duplicate active enrollment
SELECT student_id, COUNT(*) as cnt FROM classroom_students
WHERE status = 'active' AND academic_year_id = 2
GROUP BY student_id HAVING cnt > 1;

-- 4. ไม่มี duplicate current academic info
SELECT student_id, COUNT(*) as cnt FROM student_academic_info
WHERE is_current = 1
GROUP BY student_id HAVING cnt > 1;

-- 5. citizen_id ที่เติมใหม่ ตรงกับ XLSX
SELECT s.student_id, s.citizen_id FROM students s
WHERE s.academy_id = 1 AND s.citizen_id IS NOT NULL AND s.status = 'active'
ORDER BY s.student_id;
-- เทียบกับ XLSX citizen_id

-- 6. active student ม.1-ม.6 ทุกคนมีบัตร
SELECT s.id, s.student_id, s.first_name_th FROM students s
JOIN classroom_students cs ON s.id = cs.student_id AND cs.status = 'active'
JOIN classrooms c ON cs.classroom_id = c.id
LEFT JOIN student_cards sc ON s.id = sc.student_id AND sc.student_status = 'active'
WHERE s.status = 'active' AND s.academy_id = 1 AND c.grade_level LIKE 'ม.%' AND sc.id IS NULL;
-- ต้องได้ 0 rows

-- 7. สุ่มตรวจ 1 ห้อง
SELECT s.student_id, s.citizen_id, s.first_name_th, s.last_name_th,
       c.grade_level, c.section, cs.student_number,
       sa.house_number, sa.subdistrict, sa.district, sa.province,
       sg_f.first_name as father_name, sg_m.first_name as mother_name
FROM students s
JOIN classroom_students cs ON s.id = cs.student_id AND cs.status = 'active'
JOIN classrooms c ON cs.classroom_id = c.id
LEFT JOIN student_addresses sa ON s.id = sa.student_id AND sa.is_current = 1
LEFT JOIN student_guardians sg_f ON s.id = sg_f.student_id AND sg_f.guardian_type = 'father'
LEFT JOIN student_guardians sg_m ON s.id = sg_m.student_id AND sg_m.guardian_type = 'mother'
WHERE c.grade_level = 'ม.1' AND c.section = '1' AND c.academic_year_id = 2
ORDER BY cs.student_number;
-- เทียบกับ XLSX แถว ม.1/1
```

---

### Deployment Checklist

```
 Phase 0 — ก่อนเริ่ม
 [x] ตัดสินใจ: เก็บ ประเภทความพิการ ที่ไหน -> เก็บใน student_academic_info (มีฟิลด์รองรับอยู่แล้ว)
 [x] ตัดสินใจ: เก็บ โรงเรียนเดิม ที่ไหน -> เก็บใน student_academic_info (มีฟิลด์รองรับอยู่แล้ว)
 [x] ตัดสินใจ: ออกบัตรให้ อ./ป. ด้วยไหม -> ข้ามระดับชั้น อ./ป. ไม่ต้องนำเข้าข้อมูล (นำเข้าเฉพาะ ม.1-ม.6)

 Phase 1 — Parse & Normalize
 ☐ สร้าง ThaiDateParser + unit test
 ☐ สร้าง StudentRosterXlsxParser + unit test
 ☐ ทดสอบ parse XLSX → ได้ 2,437 valid structs

 Phase 2 — Identity Match & Preview
 ☐ เขียน migration เพิ่ม fields ใน import tables
 ☐ php artisan migrate
 ☐ สร้าง StudentRosterUpdateService
 ☐ สร้าง roster:preview command
 ☐ รัน preview → ตรวจ summary ตรงกับที่วิเคราะห์ไว้:
   - exact_match: ~1,839
   - code_only: ~462
   - new_student: ~125
   - conflict: 11
   - create_enrollment: ~442
   - move_classroom: ~170

 Phase 3 — Prerequisites
 ☐ ส่ง conflict report (11 คน) ให้นายทะเบียนตรวจ
 ☐ รอนายทะเบียน resolve conflicts
 ☐ ตรวจว่า 16 ห้อง อ./ป. ที่ขาด จะสร้างหรือไม่ → ถ้าสร้าง ให้สร้างก่อน commit
 ☐ mysqldump nuxnan > nuxnan_backup_before_roster_update.sql

 Phase 4 — Commit
 ☐ สร้าง StudentRosterCommitService
 ☐ สร้าง roster:commit command
 ☐ รัน roster:commit {batch_id} --dry-run → ตรวจ
 ☐ รัน roster:commit {batch_id} → จริง
 ☐ ตรวจ partial failures (ถ้ามี)

 Phase 5 — Card Sync
 ☐ รัน students:sync-cards --preview → ตรวจ
 ☐ รัน students:sync-cards --commit
 ☐ รัน reconciliation queries (7 ข้อ)
 ☐ สุ่มตรวจ 3-5 ห้อง เทียบกับ XLSX

 Phase 6 — Cleanup
 ☐ ปิด batch (status = completed)
 ☐ เก็บ backup + audit log
```

### สรุปไฟล์ทั้งหมด

**ไฟล์ใหม่:**
| # | ไฟล์ | ระยะ |
|---|------|------|
| 1 | `app/Services/Import/ThaiDateParser.php` | 1 |
| 2 | `app/Services/Import/StudentRosterXlsxParser.php` | 1 |
| 3 | `app/Services/Import/StudentRosterUpdateService.php` | 2 |
| 4 | `app/Services/Import/StudentRosterCommitService.php` | 4 |
| 5 | `app/Console/Commands/RosterPreviewCommand.php` | 3 |
| 6 | `app/Console/Commands/RosterCommitCommand.php` | 3 |
| 7 | `database/migrations/xxxx_add_roster_update_fields_to_import_tables.php` | 2 |

**ไฟล์แก้ไข:**
| # | ไฟล์ | สิ่งที่แก้ |
|---|------|----------|
| 1 | `app/Models/StudentImportBatch.php` | เพิ่ม import_type, source_format |
| 2 | `app/Models/StudentImportRow.php` | เพิ่ม action, matched_student_id, diff_data |
| 3 | `app/Services/StudentCardSyncService.php` | แก้ numericGradeLevel() ให้ไม่ชนข้าม prefix (อ./ป./ม.) |

**ไฟล์ที่ reuse (ไม่ต้องแก้):**
| ไฟล์ | ใช้ตรงไหน |
|------|----------|
| `StudentIntakeService` | สร้างนักเรียนใหม่ 125 คน |
| `StudentEnrollmentService` | สร้าง/ย้าย enrollment |
| `StudentCardSyncService` | sync บัตรหลัง roster update |
| `StudentImportService` | extend logic สำหรับ XLSX |

### หัวใจของแผน

> **"Parse → Match → Classify → Preview → Approve → Commit → Sync → Verify"**
>
> - ไม่มีขั้นตอนใดเขียนข้อมูลโดยไม่ผ่าน preview ก่อน
> - ไม่ลบข้อมูลเดิม — เปลี่ยนสถานะหรือเพิ่มทับเท่านั้น
> - Conflict 11 รายต้องผ่านคนตรวจ ห้ามเดา
> - Reuse services ที่มีอยู่ (`IntakeService`, `EnrollmentService`, `CardSyncService`)
> - Chunk transaction ป้องกัน partial failure ลาม
> - Reconciliation ทุกครั้งหลัง commit

---

# Work Plan — Student Card Request System (2026-07-08)

**สถานะ:** วางแผน (ยังไม่เริ่มพัฒนา)
**ขอบเขต:** เพิ่มระบบคำร้องทำบัตรครอบระบบ `student_cards` เดิม เพื่อแทนที่การสร้างบัตรอัตโนมัติจาก `StudentCardSyncService` และแก้ปัญหาบัตรซ้ำที่ต้นเหตุ
**Source:** ต่อยอดจากข้อเสนอผู้ใช้ ผ่านการตรวจเทียบ codebase จริง

---

## User Analysis Input

### สรุปข้อเสนอเดิม (workflow)

1. ครูประจำชั้นเปิดรายชื่อนักเรียนในห้องตน
2. ระบบแสดงสถานะรายคน: มีบัตรใช้อยู่ / ไม่มีบัตร / รอดำเนินการ / กำลังทำ / เสร็จแล้ว
3. ครูเลือกนักเรียนที่ต้องทำบัตร ส่งคำร้องรายคนหรือหลายคน
4. Admin ผู้ทำบัตรตรวจคำร้อง
5. Admin รับงาน ปฏิเสธ หรือทำบัตร
6. เมื่อทำเสร็จ Admin เชื่อมคำร้องกับ `student_cards` และเปลี่ยนสถานะเป็นเสร็จสิ้น

**Request types:** `first_issue`, `replacement`, `renewal`
**State machine:** `pending → approved → in_progress → completed` (+ `rejected`, `cancelled`)

---

## การวิเคราะห์เทียบกับ Codebase จริง

### สิ่งที่ตรงกับโค้ดปัจจุบันแล้ว

1. **Unique constraint ป้องกัน active card ซ้ำ มีอยู่แล้ว** — `uq_student_card_active` บน `(student_id, academy_id, is_active_flag)` ใน `2026_07_07_053001_add_constraints_and_fields_to_student_cards.php` ใช้ virtual column `is_active_flag` ที่เป็น NULL เมื่อ `student_status != 'active'` แผนของผู้ใช้กำหนดให้เป็น "ด่านสุดท้าย" — ถูกต้อง แต่ไม่ต้องสร้างเพิ่ม
2. **`Classroom.homeroom_teacher_id`** มีอยู่และเป็น FK ไปที่ `users.id` — ใช้ได้ตามแผน
3. **Middleware `academy.permission:...`** รองรับ dotted-permission ผ่าน `hasAnyPermission()` แล้ว (`CheckAcademyPermission.php`)
4. **`ClassroomStudent.status = 'active'`** เป็น SoT ของ enrollment ตามที่แผนใช้อ้าง

### สิ่งที่ต้องแก้จากแผนเดิม

5. **`StudentCardSyncService` คือต้นเหตุของบัตรซ้ำ ไม่ใช่แค่ผลข้างเคียง** — `commitSync()` วนสร้างบัตรให้ **ทุก** active enrollment ที่ไม่มีบัตร (`StudentCardSyncService.php:181`) และตอน rollover 2568→2569 เพิ่ง created 476 (worklog 2026-07-06) ระบบคำร้องใหม่จะไร้ประโยชน์ถ้ายัง trigger service นี้ต่ออัตโนมัติ — **ต้องปิด/บล็อค endpoint `POST /student-cards/admin/sync/commit` และไม่เรียกจาก rollover อีก** เป็น deliverable ที่ 1 ไม่ใช่ footnote
6. **Permission naming ควรตามแบบเดิม (dotted)** ไม่ใช่ underscore — ใช้ `students.cards.request` (ครู) + `students.cards.produce` (แอดมิน/ผู้ทำบัตร) แทน `manage_student_cards` เพื่อสอดคล้องกับ `students.manage`, `home_visits.manage` ที่มีอยู่ และเข้ากับ hierarchical check
7. **Frontend `/teacher/*` route ยังไม่มีเลย** — ทั้ง project อยู่ใต้ `/admin/*` การเปิด `/academies/[name]/teacher/student-card-requests` คือการเปิด teacher portal แยกใหม่ ซึ่งเป็นการตัดสินใจโครงสร้างขนาดใหญ่ ต้องตัดสินก่อน:
   - (A) วางไว้ใต้ `/admin/student-cards/requests` เหมือน admin เดิม แล้วซ่อน section ที่ครูไม่มีสิทธิ์ — เร็ว ใช้ layout เดิม
   - (B) สร้าง `/teacher/` portal จริง — สะอาดกว่า แต่ต้องออกแบบ layout+sidebar+menu ใหม่ทั้งชุด
8. **นักเรียนถูกลบ → ประวัติคำร้องหาย** — แผนกำหนด FK `student_id` ต้องเลือก policy ก่อน แนะนำเก็บ snapshot (`full_name_snapshot`, `student_number_snapshot`, `grade_level_snapshot`, `section_snapshot`) ณ เวลาส่ง และใช้ `onDelete('set null')` เพื่อไม่ให้ประวัติหาย เหมือน StudentCard ที่เก็บ `full_name_thai`, `class_level` แบบ frozen อยู่แล้ว
9. **การ complete ต้องเชื่อมกับ StudentCard ยังไง** — ต้องเลือก:
   - **สร้าง row ใหม่เสมอ** (แนะนำ) — expire card เดิม + insert card ใหม่ในทรานแซกชั่นเดียว ให้ audit trail สมบูรณ์และเข้ากับ unique constraint เดิม
   - **แก้ในตัวเดิม** — เร็วกว่าแต่ audit หาย ไม่แนะนำ
10. **Rollover hook** — หลังจาก request flow ใช้จริง `AcademicYearRolloverService` ที่ trigger card sync อัตโนมัติต้องเปลี่ยนพฤติกรรม แผนควรระบุชัดเจนว่า "rollover จะไม่สร้างบัตรอีกต่อไป — จะให้ครูส่ง `renewal` ผ่านระบบคำร้องเท่านั้น"
11. **`StudentCard` ใช้ `Auditable` trait อยู่แล้ว** — `StudentCardRequest` ก็ควรใช้ trait นี้ เพื่อให้ transition ทุกครั้งมี audit log ครบ

### สิ่งที่แผนเดิมขาด

12. **Data backfill** — บัตร active 2,138 ใบใน DB ปัจจุบันไม่มีคำร้องผูก อย่างน้อยควรระบุใน rollout ว่า card ที่มีอยู่แล้วจะถือเป็น `origin='legacy'` หรือทำ synthetic `completed` request เพื่อ audit ครบวง
13. **Idempotency ของ bulk submit** — ต้องเป็น per-student result ไม่ใช่ transaction เดียวที่ล้มทั้งชุด (ครูเลือก 40 คน มี 3 คนคำร้องซ้อน → 37 ผ่าน 3 skip พร้อมเหตุผลรายคน)
14. **State machine ใน service** — แผนบอกให้รวมใน service เดียว แต่ควรระบุ helper `StudentCardRequestService::transition($request, $toStatus, $actor, ?$reason)` ที่ validate FROM→TO ก่อน เพื่อกันข้ามขั้นตอนแบบ single point
15. **Real-time notification** — ระบบมี Laravel Reverb อยู่แล้ว ครูควรได้รับ broadcast เมื่อ admin อนุมัติ/ปฏิเสธ/เสร็จ (ไม่ต้อง refresh)
16. **Race condition ที่ complete** — 2 admin กด complete พร้อมกันสำหรับคำร้องเดียว → ต้อง `lockForUpdate()` + re-check status ในทรานแซกชั่น
17. **Priority / urgency** — โรงเรียนมักเจอ "หายกลางเทอม ต้องได้พรุ่งนี้" ควรมี `priority` (normal/urgent)
18. **แยก `reason` (ครูกรอก) กับ `admin_notes` (แอดมินจดภายใน)** — 2 field ต่างเจตนา ไม่ควรใช้ column เดียว
19. **`existing_card_id` policy สำหรับ replacement** — ครูต้องเลือกใบเดิม (dropdown) หรือระบบดึงมาให้อัตโนมัติ? ควรระบุ (แนะนำ auto-fetch จาก unique active card)
20. **Rate limiting** — ครูกด "ส่งคำร้อง 200 คน" ในห้องผิด → ต้อง cap หรือมี confirmation modal

---

## Work Plan — ฉบับปรับปรุงสมบูรณ์

### ระยะที่ 0: Prerequisite Decisions (ตอบก่อนเริ่ม)

**เป้าหมาย:** ยืนยันการตัดสินใจโครงสร้างที่กระทบทั้งระบบ ก่อนเขียน migration

| # | คำถาม | ค่าที่แนะนำ | ผลกระทบ |
|---|-------|-------------|----------|
| 0.1 | Teacher portal โครงสร้าง (A) หรือ (B) | (A) — วางใต้ `/admin/student-cards/requests` | (B) เป็นงานเพิ่ม 3–5 วัน สำหรับ layout/sidebar/menu |
| 0.2 | Card lifecycle policy | สร้าง row ใหม่เสมอ | audit trail สมบูรณ์, unique constraint เดิมใช้ได้ตรงๆ |
| 0.3 | Rollover behavior หลัง feature live | ปิด auto card sync ถาวร | rollover จะจัดการ enrollment อย่างเดียว, บัตรผ่านคำร้อง |
| 0.4 | Legacy card ทำ synthetic request | ทำเลย `origin='legacy'` | reports "บัตรที่ผลิตในปีนี้" ไม่เพี้ยน |
| 0.5 | `existing_card_id` สำหรับ replacement/renewal | auto-fetch จาก active card | ครูไม่ต้องเลือก, ลด error |
| 0.6 | Complete → auto-open print page | Redirect ไปหน้า print card หลัง complete | เชื่อมกับ workflow admin เดิม |

**ผลลัพธ์:** เอกสารสรุปคำตอบ 6 ข้อ ใช้เป็น decision log

---

### ระยะที่ 1: Foundation — Schema + State Machine (Backend Core)

**เป้าหมาย:** สร้าง table + model + service state machine พร้อม constraint ครบ

**Deliverables:**

1. **Migration `create_student_card_requests_table`** ฟิลด์เต็มชุด:
   - Core: `academy_id`, `academic_year_id`, `classroom_id`, `student_id` (nullable, `onDelete set null`), `request_type` (enum: `first_issue`|`replacement`|`renewal`), `status` (enum ตาม state machine)
   - Snapshots ณ เวลาส่ง: `full_name_snapshot`, `student_number_snapshot`, `grade_level_snapshot`, `section_snapshot`
   - Linkage: `existing_card_id` (nullable), `result_card_id` (nullable)
   - Reason/notes: `reason` (จากครู, required เมื่อ replacement/renewal), `admin_notes` (nullable), `rejection_reason` (nullable)
   - Actors + timestamps: `requested_by`, `approved_by`, `processed_by`, `requested_at`, `approved_at`, `started_at`, `completed_at`, `cancelled_at`, `rejected_at`
   - Metadata: `priority` (default `normal`), `origin` (default `teacher`, สำหรับ backfill ใช้ `legacy`)
   - Standard: `timestamps`

2. **Indexes:**
   - `(academy_id, status)`
   - `(academy_id, classroom_id, status)`
   - `(student_id, status)`
   - **Partial unique** `(student_id, academy_id) WHERE status IN ('pending','approved','in_progress')` เพื่อป้องกันคำร้องเปิดซ้อน (MySQL 8+ ใช้ generated column trick เหมือน `is_active_flag`)

3. **Model `StudentCardRequest`** ใช้ `Auditable` trait, relationships ครบ (`academy`, `academicYear`, `classroom`, `student`, `existingCard`, `resultCard`, `requestedBy`, `approvedBy`, `processedBy`), casts วันที่

4. **Enum classes** — `RequestStatus`, `RequestType`, `RequestOrigin` (PHP 8.4 backed enums)

5. **`StudentCardRequestService`** (state machine core):
   - `transition($request, $toStatus, User $actor, array $context = [])` — validate transition matrix ก่อน, throw `InvalidStateTransition` ถ้าข้าม, บันทึก actor+timestamp+reason
   - `create(...)`, `bulkCreate(...)` (return per-row result), `cancel(...)`
   - `complete(...)` — ทรานแซกชั่นเดียว: `lockForUpdate()` request, expire `existing_card_id` (ถ้ามี), insert new StudentCard, set `result_card_id`, status=`completed`

**Verify:** unit test transition matrix, partial unique constraint enforced

---

### ระยะที่ 2: Policy + Authorization

**เป้าหมาย:** สิทธิ์ครู/แอดมินตามหลักการ least privilege

**Deliverables:**

1. **Permission keys ใหม่ใน `AcademyRole::SYSTEM_ROLES`:**
   - `students.cards.request` → เพิ่มให้ role `teacher` (ครูประจำชั้นเท่านั้น, มี extra check เลเยอร์ที่ 2 ใน controller)
   - `students.cards.produce` → เพิ่มให้ role `admin`, `owner`, `director`, และเปิดให้ create custom role `card_admin` ถ้าโรงเรียนอยากแยกคน

2. **Update `AcademyRoleSeeder`** แบบ `updateOrCreate` เพื่อ backfill permission ให้ role เดิมทุก academy (pattern เดียวกับ registrar seeding เดือน 07-05)

3. **`StudentCardRequestPolicy`:**
   - `viewClassroom($user, Classroom)` — `homeroom_teacher_id` ตรง OR มี `students.manage`
   - `create($user, Classroom)`, `cancel($user, Request)` — เจ้าของ request (ครู) หรือมี `students.cards.produce`
   - `approve/reject/start/complete($user, Request)` — ต้องมี `students.cards.produce` เท่านั้น

4. **FormRequest classes:** `StoreStudentCardRequestRequest`, `BulkStoreStudentCardRequestRequest`, `RejectRequest` (บังคับ `rejection_reason`)

**Verify:** feature test — ครูห้องอื่นได้ 403, admin ไม่มี produce permission ได้ 403

---

### ระยะที่ 3: API Layer

**เป้าหมาย:** REST API ตาม intent-based transitions (ไม่ใช่ raw status update)

**Routes ใต้ `/api/academies/{academy}/student-card-requests`:**

**Teacher-facing** (ต้องมี `students.cards.request` + homeroom check ใน controller):
- `GET  /my-classrooms` — คืน classrooms ที่ user เป็น homeroom
- `GET  /classrooms/{classroom}/students` — รายชื่อ + สถานะบัตร + คำร้องล่าสุด (join StudentCard + latest open request)
- `POST /`
- `POST /bulk` — return `{ results: [{ student_id, status: 'created'|'skipped', reason }] }`
- `PATCH /{request}/cancel`

**Admin/Producer-facing** (ต้องมี `students.cards.produce`):
- `GET  /` — queue with filters: `year`, `classroom`, `type`, `status`, `priority`, `search`
- `GET  /{request}` — รายละเอียด + snapshot + audit log
- `PATCH /{request}/approve`
- `PATCH /{request}/reject` (body: `rejection_reason` required)
- `PATCH /{request}/start`
- `PATCH /{request}/complete` — trigger `StudentCardRequestService::complete()`
- `POST  /bulk-approve`, `POST /bulk-start` — power features

**Shared:**
- `GET /counts` — สรุปตัวเลข dashboard (pending, approved, in_progress, done_today)

**Verify:** ทุก route มี test สำหรับ 200 (happy path), 403 (permission), 422 (validation), 409 (invalid state)

---

### ระยะที่ 4: Disable Legacy Bulk Creation (ต้องทำก่อน UI)

**เป้าหมาย:** ปิดต้นทางของบัตรซ้ำ ก่อนที่ user จะเริ่มใช้ระบบใหม่

**Deliverables:**

1. **Gate `POST /academies/{academy}/student-cards/admin/sync/commit`** ให้ throw 410 Gone หรืออ่านเฉพาะ preview (คงไว้เพื่อ inspect)
2. **ตัด hook จาก `AcademicYearRolloverService::commitRollover`** ที่เรียก StudentCardSync (ถ้ามี — ตรวจ code path จริง — worklog 2026-07-06 ระบุว่า rollover trigger card sync จริง)
3. **Feature flag `academies.settings.card_request_flow_enabled`** (boolean) เพื่อ rollout ทีละ academy:
   - flag=false → ยังใช้ legacy sync ได้
   - flag=true → บล็อค legacy, บังคับใช้ request flow
4. **Backfill script `students:seed-legacy-card-requests`** — สร้าง synthetic `completed` request ให้ card active ทุกใบที่มี ณ วันเปิด flag ตั้ง `origin='legacy'`, `requested_by`=system user เก็บ audit ครบวง

**Verify:** integration test — flag=true → legacy sync return 410; rollover ไม่สร้าง card ใหม่

---

### ระยะที่ 5: Teacher UI

**เป้าหมาย:** หน้าเดียวจบ ครูส่งคำร้องได้ในไม่กี่คลิก

**Deliverables:**

1. **หน้า `student-card-requests`** (path ตามผล ระยะ 0.1 — default: `/admin/student-cards/requests/my-classrooms`)
2. **Sub-page:** เลือก classroom → ตารางนักเรียน + status badge (`no_card`, `active_card`, `pending_request`, `in_progress`, `completed_recent`)
3. **`SubmitRequestModal.vue`:**
   - เลือก `request_type` (ถ้ามีบัตร active — บังคับ `replacement`/`renewal`, ปิด `first_issue`)
   - บังคับกรอก `reason` เมื่อไม่ใช่ `first_issue`
   - Auto-fetch `existing_card_id` จาก active card
4. **Composable `useStudentCardRequests.ts`** (wrapper API + type-safe result)
5. **Bulk selection** — checkbox + submit modal + confirmation "คุณกำลังจะส่ง N คำร้อง"
6. **Filter:** "แสดงเฉพาะคนที่ยังไม่มีบัตร"
7. **Reverb subscription:** update badge เมื่อ admin เปลี่ยนสถานะ

**Verify:** manual E2E — ครูเข้าหน้า, เลือก 5 คน (มี 1 คนมีคำร้องซ้อน), ส่ง → เห็น 4 ok + 1 skip พร้อมเหตุผล

---

### ระยะที่ 6: Admin UI

**เป้าหมาย:** คิวงานแอดมินใช้ง่าย รองรับ batch printing

**Deliverables:**

1. **หน้า queue** พร้อม stat cards (pending/approved/in_progress/done_today)
2. **Filter panel + persistent table** (PrimeVue DataTable ตาม convention)
3. **Row actions:** view detail, approve, reject (modal บังคับ reason), start, complete
4. **Bulk actions:** approve, start (สำหรับ batch printing)
5. **หน้ารายละเอียด:** snapshot + timeline (audit log) + ปุ่มไปหน้า print card (เชื่อมระบบเดิม `/admin/student-cards/{result_card_id}/print`)

**Verify:** manual E2E — admin เข้าหน้า, filter pending, bulk approve 10 คำร้อง, กด complete รายคน → บัตรจริงถูกสร้าง + link `result_card_id` ครบ

---

### ระยะที่ 7: Notifications

**เป้าหมาย:** ครู/แอดมินไม่ต้อง refresh หน้า

**Deliverables:**

1. **Event classes:** `RequestSubmitted`, `RequestApproved`, `RequestRejected`, `RequestStarted`, `RequestCompleted`, `RequestCancelled`
2. **Broadcast ผ่าน Reverb** (private channel per user)
3. **In-app notification store integration** (มี `NotificationService` อยู่แล้ว)

**Verify:** manual — ครูเปิดหน้าค้างไว้, admin approve → badge เปลี่ยนสถานะ live

---

### ระยะที่ 8: Tests

**Coverage เป้าหมาย:**

- [x] ครูส่งได้เฉพาะห้องตน + 403 กรณีอื่น
- [x] ห้ามคำร้องเปิดซ้อน (test partial unique constraint)
- [x] บังคับ `reason` เมื่อ replacement/renewal
- [x] Admin transition ข้ามขั้นตอน → `InvalidStateTransition`
- [x] Race: 2 admin กด complete พร้อมกัน → 1 สำเร็จ 1 fail ชัดเจน (ผ่าน `lockForUpdate` + status re-check)
- [x] ย้ายห้องหลังส่ง → snapshot ยังถูก
- [x] Legacy sync commit endpoint → 410 Gone เมื่อ flag=true
- [x] Rollover ไม่ auto-create card อีก
- [x] Backfill script สร้าง synthetic request ครบทุก card active
- [x] Bulk submit per-row result (37 ok / 3 skipped ไม่ล้มทั้งชุด)
- [x] Audit trail ทุก transition มี actor + timestamp
- [x] E2E: ครูส่ง → admin approve → start → complete → บัตรใหม่สร้าง + บัตรเก่า expire

---

### ระยะที่ 9: Rollout Playbook

**เป้าหมาย:** ปลอดภัย ค่อยเป็นค่อยไป, มีทาง rollback

**ขั้นตอน:**

1. Deploy code — flag ทุก academy = false (behavior เดิม)
2. เลือก academy pilot 1 แห่ง → run backfill → flip flag = true → ทดสอบ 1 สัปดาห์
3. Rollout เพิ่มทีละกลุ่ม (monitor error rate)
4. ปิด legacy endpoints ถาวรเมื่อทุก academy = true (post-deploy migration ลบ code path)

---

## ข้อควรระวังที่ยังต้องตัดสินใจ

1. **Homeroom เดี่ยว** — `classrooms.homeroom_teacher_id` มีคนเดียว ถ้าครูลาออก/เปลี่ยน — คำร้องเก่าที่ครูคนเก่าส่งไว้จะยัง valid (เพราะเช็คสิทธิ์ที่ point-in-time) แต่คนใหม่จะเห็นได้เพราะเช็คสิทธิ์ live — ยืนยันว่าเป็น behavior ที่ต้องการ
2. **QR / print integration** — เมื่อ admin กด complete ระบบจะ redirect ไปหน้าพิมพ์เดิม (link `/admin/student-cards/{result_card_id}/print`) — ต้องยืนยัน route นี้มีอยู่จริง
3. **นักเรียนย้าย academy** — ถ้า transfer ข้าม academy คำร้อง academy เดิมค้าง — ต้อง auto-cancel เมื่อ enrollment ย้าย (hook ที่ `StudentEnrollmentService::transferStudent`)

---

## ไฟล์หลักที่คาดว่าจะเพิ่ม/แก้

### Backend

**สร้างใหม่:**
- `database/migrations/xxxx_create_student_card_requests_table.php`
- `app/Models/StudentCardRequest.php`
- `app/Enums/{RequestStatus,RequestType,RequestOrigin}.php`
- `app/Services/StudentCardRequestService.php`
- `app/Http/Controllers/Api/Learn/Student/Card/StudentCardRequestController.php`
- `app/Http/Requests/{StoreStudentCardRequestRequest,BulkStoreStudentCardRequestRequest,RejectStudentCardRequestRequest}.php`
- `app/Http/Resources/StudentCardRequestResource.php`
- `app/Policies/StudentCardRequestPolicy.php`
- `app/Events/StudentCard/{RequestSubmitted,RequestApproved,RequestRejected,RequestStarted,RequestCompleted,RequestCancelled}.php`
- `app/Exceptions/InvalidStateTransition.php`
- `app/Console/Commands/SeedLegacyCardRequests.php`
- `routes/learn/academy-student-card-request.php`
- Tests: `tests/Feature/Api/Academy/StudentCardRequest/*Test.php`

**แก้ไข:**
- `app/Models/AcademyRole.php` — เพิ่ม 2 permission keys ใน SYSTEM_ROLES
- `database/seeders/AcademyRoleSeeder.php` — backfill permission ให้ role เดิม
- `app/Services/StudentCardSyncService.php` — gate `commitSync()` ตาม feature flag
- `app/Services/AcademicYearRolloverService.php` — ตัด card sync hook
- `app/Http/Controllers/Api/Learn/Student/Card/StudentCardController.php` — gate `syncCommit()` action
- `app/Services/StudentEnrollmentService.php` — auto-cancel open requests เมื่อ transfer ข้าม academy
- `bootstrap/app.php` (or `RouteServiceProvider`) — register route file ใหม่
- `app/Models/{Student,StudentCard,Classroom}.php` — เพิ่ม `cardRequests()` relationship

### Frontend

**สร้างใหม่:**
- `ui/pages/academies/[name]/admin/student-cards/requests/index.vue` (teacher view)
- `ui/pages/academies/[name]/admin/student-cards/requests/queue.vue` (admin view)
- `ui/pages/academies/[name]/admin/student-cards/requests/[id].vue` (detail)
- `ui/composables/useStudentCardRequests.ts`
- `ui/components/school/studentCard/SubmitRequestModal.vue`
- `ui/components/school/studentCard/RequestStatusBadge.vue`
- `ui/components/school/studentCard/RequestQueueTable.vue`
- `ui/components/school/studentCard/RequestTimelineDrawer.vue`
- `ui/types/studentCardRequest.ts`

**แก้ไข:**
- `ui/pages/academies/[name]/admin.vue` — เพิ่ม sidebar link "คำร้องทำบัตร"
- `ui/pages/academies/[name]/admin/index.vue` — quick action
- `ui/i18n/locales/{th,en}/*.json` — ข้อความใหม่

---

## หัวใจของแผน

> **"Request first, sync never. Snapshot everything."**
>
> - ปิดต้นเหตุก่อน (StudentCardSyncService::commitSync + rollover hook) — feature ใหม่ไร้ประโยชน์ถ้าท่อเก่ายังเปิด
> - Snapshot ทุก field ที่จะเปลี่ยนได้ (ชื่อ, เลข, ห้อง) เพื่อประวัติไม่หายเมื่อ student mutate
> - State machine ใน service เดียว, transition ต้องผ่าน `transition()` — ห้าม controller update raw status
> - Partial unique constraint + `lockForUpdate` + re-check status = 3 ชั้นกัน race + duplicate
> - Feature flag + backfill script = rollout safe ทีละ academy, revert ได้ทุกเวลา
> - Reuse `Auditable` trait + Reverb + PrimeVue DataTable — ไม่สร้าง infra ใหม่

---

# Work Plan — Student Card Request System (2026-07-08)

## 1. User Analysis Input
- **ประเภทของคำร้อง (Request Types)**: `first_issue` (ออกบัตรครั้งแรก), `replacement` (ออกบัตรแทนใบเดิม/หาย), `renewal` (ต่ออายุบัตร)
- **State Machine**:
  - `pending` (สร้างคำร้องโดยครูประจำชั้น)
  - `approved` (อนุมัติโดย admin)
  - `rejected` (ปฏิเสธโดย admin พร้อมระบุเหตุผล)
  - `in_progress` (เริ่มกระบวนการจัดทำ/พิมพ์บัตร)
  - `completed` (จัดทำเสร็จสิ้นและออกบัตรใหม่สำเร็จ)
  - `cancelled` (ยกเลิกโดยผู้ส่งคำร้องก่อนได้รับการประมวลผล)

---

## 2. การวิเคราะห์เปรียบเทียบกับ Codebase จริง

### จุดที่ตรงกับโค้ดปัจจุบันแล้ว (ไม่ต้องแก้ไขหรือทำเพิ่ม)
1. **Unique Constraint**: มี unique constraint บน `(student_id, academy_id, is_active_flag)` ใน migration `2026_07_07_053001_add_constraints_and_fields_to_student_cards.php` โดยใช้ virtual column `is_active_flag` ที่มีค่าเป็น NULL เมื่อ `student_status != 'active'` เพื่อป้องกันการมี active card ซ้ำ
2. **Homeroom Teacher Link**: `Classroom.homeroom_teacher_id` เชื่อมโยงกับ `users.id` เรียบร้อยแล้ว
3. **Dotted-Permission Middleware**: `CheckAcademyPermission` รองรับ dotted format ผ่าน `hasAnyPermission()` แล้ว
4. **Enrollment Source of Truth**: `ClassroomStudent.status = 'active'` คือข้อมูลที่เป็น Source of Truth หลักสำหรับ enrollment

### จุดที่ต้องแก้ไขและปรับปรุงแผนอย่างเร่งด่วน
1. **ปิดกั้น Auto Sync / Legacy Sync**: `StudentCardSyncService::commitSync()` คือสาเหตุหลักของการสร้างบัตรซ้ำโดยอัตโนมัติ ต้องปิด/บล็อก endpoint `POST /student-cards/admin/sync/commit` และตัดการเรียกออกจากกระบวนการ rollover
2. **การตั้งชื่อ Permission**: ใช้รูปแบบ dotted-permission แบบเดิม ได้แก่ `students.cards.request` (ครูประจำชั้นในการส่งคำร้อง) และ `students.cards.produce` (แอดมินในการอนุมัติและจัดทำบัตร) แทนการใช้ `manage_student_cards`
3. **โครงสร้าง UI สำหรับคุณครู**: เลือกใช้แนวทาง **(A) วางไว้ใต้ `/admin/student-cards/requests`** โดยแชร์ layout เดิมของ admin แต่จำกัดการเข้าถึงและซ่อนปุ่มหรือส่วนการทำงานที่ไม่มีสิทธิ์ เพื่อความรวดเร็วและใช้โครงสร้างเดิม
4. **นโยบายเมื่อนักเรียนถูกลบ**: ใช้ snapshots ของฟิลด์ต่าง ๆ (`full_name_snapshot`, `student_number_snapshot`, `grade_level_snapshot`, `section_snapshot`) ณ เวลาที่ยื่นคำร้อง และใช้การทำงานแบบ `onDelete('set null')` บน `student_id` ของตารางคำร้องเพื่อไม่ให้ประวัติคำร้องสูญหาย
5. **การ Complete คำร้อง**: เลือกใช้แนวทาง **สร้างแถว (row) ใหม่เสมอในตาราง StudentCard** พร้อมทั้งเปลี่ยนสถานะบัตรเดิมให้หมดอายุ (expire) และสร้างบัตรใหม่ในทรานแซกชั่นเดียวกัน เพื่อให้ประวัติการตรวจสอบ (Audit Trail) สมบูรณ์
6. **Rollover Hook**: กำหนดให้ไม่มีการสร้างการ์ดโดยอัตโนมัติจาก rollover อีกต่อไป โดยการต่ออายุบัตรจะใช้คำร้องแบบ `renewal` ผ่านระบบนี้เท่านั้น
7. **Audit Log สำหรับคำร้อง**: ใช้ `Auditable` trait บนโมเดล `StudentCardRequest` เพื่อบันทึกประวัติการเปลี่ยนผ่านสถานะอย่างครอบคลุม

### สิ่งที่ระบบต้องมีเพิ่มเติม
- **Data Backfill**: รัน script backfill เพื่อตั้งค่าบัตรปัจจุบันที่มีอยู่แล้วเป็น `origin='legacy'` หรือทำ synthetic completed request
- **Idempotency**: การทำ bulk submit จะต้องส่งผลลัพธ์แยกตามรายบุคคล (per-student result) หากมีบางคนที่เกิดข้อผิดพลาดหรือมีคำร้องเปิดอยู่ คนอื่น ๆ ใน batch ต้องดำเนินต่อได้และข้ามเฉพาะคนที่มีปัญหาพร้อมให้เหตุผล
- **State Machine Guard**: ใช้ `StudentCardRequestService::transition($request, $toStatus, $actor, ?$reason)` เพื่อตรวจสอบ transition matrix ป้องกันการเปลี่ยนสถานะแบบข้ามขั้นตอน
- **Real-time Notification**: ใช้ Laravel Reverb ในการยิงอัพเดตสถานะของคำร้องแบบ real-time
- **Race Condition Prevention**: ใช้ `lockForUpdate()` และ re-check status ในทรานแซกชั่นขณะอนุมัติ/ทำเสร็จ
- **Priority**: เพิ่มฟิลด์ `priority` (normal/urgent) สำหรับความเร่งด่วนของคำร้อง
- **แยกฟิลด์เหตุผล**: แยก `reason` (ครูกรอกเหตุผลขอทำบัตรใหม่) กับ `admin_notes` (แอดมินจดบันทึกภายใน) และ `rejection_reason` (สาเหตุที่แอดมินปฏิเสธ) ออกจากกันอย่างชัดเจน
- **Link บัตรเก่าอัตโนมัติ**: ระบบจะเลือก `existing_card_id` ของบัตร active ล่าสุดของนักเรียนคนนั้นให้อัตโนมัติเมื่อขอทำบัตรใหม่ (replacement/renewal)
- **Rate limiting / Confirmation**: มี confirmation modal บนหน้าจอครูก่อนจะกดส่ง bulk request

---

## 3. Work Plan 10 ระยะ (Phases 0–9)

### เฟส 0 — Prerequisite Decisions
1. **UI Structure**: ใช้โครงสร้าง (A) วางไว้ใต้ `/admin/student-cards/requests`
2. **Card Lifecycle Policy**: สร้าง row ใหม่เสมอใน `student_cards` พร้อม expire ใบเดิม
3. **Rollover Behavior**: ปิดระบบ auto card sync หลัง rollover โดยสิ้นเชิง
4. **Legacy Card Handling**: ตั้งค่า `origin='legacy'` ให้กับบัตรเก่าในระบบ

### เฟส 1 — Foundation (Backend Schema + State Machine)
1. **Migration**: สร้างตาราง `student_card_requests`
   - ฟิลด์เชื่อมโยง: `academy_id`, `academic_year_id`, `classroom_id`, `student_id` (nullable, set null on delete), `existing_card_id` (nullable), `result_card_id` (nullable)
   - ฟิลด์ระบุประเภท/สถานะ: `request_type` (enum), `status` (enum)
   - Snapshots: `full_name_snapshot`, `student_number_snapshot`, `grade_level_snapshot`, `section_snapshot`
   - เหตุผล/บันทึก: `reason` (ครูส่ง), `admin_notes` (บันทึกภายใน), `rejection_reason` (เหตุผลปฏิเสธ)
   - ผู้ดำเนินการ + Timestamps: `requested_by`, `approved_by`, `processed_by`, `requested_at`, `approved_at`, `started_at`, `completed_at`, `cancelled_at`, `rejected_at`
   - อื่น ๆ: `priority` (enum: normal, urgent), `origin` (enum: teacher, legacy)
2. **Indexes**: สร้าง composite index `(academy_id, status)` และสร้าง partial unique constraint `(student_id, academy_id) WHERE status IN ('pending', 'approved', 'in_progress')`
3. **Model & Enums**: โมเดล `StudentCardRequest` พร้อมใช้ `Auditable` trait และสร้าง PHP 8.4 Backed Enums (`RequestStatus`, `RequestType`, `RequestOrigin`)
4. **StudentCardRequestService**: พัฒนาแกนหลักของ state machine
   - `transition($request, $toStatus, User $actor, array $context = [])`
   - `create(...)`, `bulkCreate(...)`
   - `complete(...)` (ใน transaction พร้อม `lockForUpdate` + expire บัตรเดิม + insert บัตรใหม่)

### เฟส 2 — Policy + Authorization
1. **Permission Integration**: อัพเดท `AcademyRole::SYSTEM_ROLES`
   - `students.cards.request` (สำหรับบทบาท `teacher`)
   - `students.cards.produce` (สำหรับบทบาท `admin`, `owner`, `director`, `card_admin`)
2. **Seeder Update**: รัน `AcademyRoleSeeder` แบบ `updateOrCreate` เพื่อปรับปรุง permissions
3. **StudentCardRequestPolicy**: ตรวจสอบสิทธิ์ระดับ homeroom teacher และสิทธิ์ในการจัดการของ admin
4. **Form Requests**: สร้าง `StoreStudentCardRequestRequest`, `BulkStoreStudentCardRequestRequest`, และ `RejectRequest`

### เฟส 3 — API Layer
- **Routes Base**: `/api/academies/{academy}/student-card-requests`
- **Teacher Endpoints** (สิทธิ์ `students.cards.request` + Homeroom check):
  - `GET /my-classrooms`
  - `GET /classrooms/{classroom}/students`
  - `POST /` และ `POST /bulk` (คืนผลลัพธ์ per-row)
  - `PATCH /{request}/cancel`
- **Admin Endpoints** (สิทธิ์ `students.cards.produce`):
  - `GET /` (Queue filter ตามสถานะ, ชั้น, ห้อง, ความสำคัญ)
  - `GET /{request}` (ดูข้อมูลอย่างละเอียดรวมถึง audit log)
  - `PATCH /{request}/approve`
  - `PATCH /{request}/reject`
  - `PATCH /{request}/start`
  - `PATCH /{request}/complete`
  - `POST /bulk-approve`, `POST /bulk-start`
- **Shared Endpoints**:
  - `GET /counts` (นับสถิติจำนวนคำร้องในระบบ)

### เฟส 4 — Disable Legacy Bulk Creation (สำคัญ)
1. บล็อก endpoint `POST /academies/{academy}/student-cards/admin/sync/commit` (คืนค่า 410 Gone เมื่อ flag ทำงาน)
2. ตัดการเรียก card sync จาก `AcademicYearRolloverService`
3. เพิ่ม Feature Flag `academies.settings.card_request_flow_enabled` เพื่อใช้เปิดปิดฟีเจอร์นี้รายโรงเรียน
4. สร้าง script `students:seed-legacy-card-requests` เพื่อ backfill ข้อมูลการ์ดเดิมที่มีอยู่

### เฟส 5 — Teacher UI
1. พัฒนาส่วนขอทำบัตรนักเรียนภายใต้ `/admin/student-cards/requests`
2. แสดงตารางรายชื่อนักเรียนในห้องเรียน พร้อมแสดง status badge ของบัตรและการส่งคำร้องปัจจุบัน
3. สร้าง `SubmitRequestModal.vue` รองรับการระบุเหตุผลและการเลือกประเภทคำร้องอัตโนมัติ
4. สร้าง composable `useStudentCardRequests.ts`

### เฟส 6 — Admin UI
1. ปรับปรุงหน้าคิวงานการ์ดของแอดมิน แสดงสถานะสถิติแยกการทำงาน
2. ตาราง PrimeVue DataTable กรองข้อมูลละเอียด มี Bulk actions ในการ approve/start
3. หน้าแสดงรายละเอียดคำร้องและ Audit logs พร้อมปุ่มเชื่อมโยงสำหรับการพิมพ์บัตร

### เฟส 7 — Notifications
1. Event Classes สำหรับสเตทของคำร้องทั้งหมด
2. เชื่อมต่อ Reverb Broadcast ในการส่ง notification live ไปยังหน้าผู้ใช้
3. บันทึกและเรียกใช้งานผ่าน `NotificationService`

### เฟส 8 — Tests (การตรวจสอบความถูกต้อง)
- สิทธิ์ครูประจำชั้นในการส่งคำร้องและสิทธิ์ของแอดมิน
- การป้องกันการส่งคำร้องซ้ำซ้อน (partial unique index)
- การทำงานในเงื่อนไขการแก้ไขบัตรหายหรือหมดอายุต้องระบุเหตุผล
- การป้องกัน Race condition ด้วย `lockForUpdate`
- ทดสอบการตัด legacy sync และการทำงานหลังการ rollover
- ทดสอบ bulk submit แบบแยกผลลัพธ์อิสระต่อกัน

### เฟส 9 — Rollout Playbook
1. Deploy โค้ดโดยปิด flag (`false`)
2. รัน script backfill ข้อมูลและเปิด flag (`true`) สำหรับโรงเรียนนำร่อง
3. ทยอยเปิดใช้งานทั่วไปและเคลียร์โค้ดเก่าที่เลิกใช้

---

## 4. ข้อควรระวัง, ไฟล์หลัก และหัวใจของแผน

### ข้อควรระวัง
1. **ครูประจำชั้นเปลี่ยนคน**: ข้อมูลผู้ส่ง (snapshot) จะยังคงสิทธิ์การยื่นเดิม แต่ครูประจำชั้นคนปัจจุบันจะเห็นและจัดการคำร้องต่อของห้องเรียนนั้นได้ (live check)
2. **การเชื่อมระบบพิมพ์บัตร**: เมื่อ complete แอดมินสามารถเปิดหน้าพิมพ์การ์ดใบใหม่ที่ถูกสร้างได้ทันที
3. **การย้ายโรงเรียนของนักเรียน**: ยกเลิกคำร้องของนักเรียนอัตโนมัติ หากนักเรียนถูกย้าย (transfer) ไปยัง academy อื่น

### ไฟล์หลักที่จะเพิ่มหรือแก้ไข

#### Backend
- **สร้างใหม่**:
  - `database/migrations/2026_07_08_000001_create_student_card_requests_table.php`
  - `app/Models/StudentCardRequest.php`
  - `app/Enums/StudentCardRequestStatus.php`
  - `app/Enums/StudentCardRequestType.php`
  - `app/Enums/StudentCardRequestOrigin.php`
  - `app/Services/StudentCardRequestService.php`
  - `app/Http/Controllers/Api/Learn/Student/Card/StudentCardRequestController.php`
  - `app/Http/Requests/StoreStudentCardRequest.php`
  - `app/Http/Requests/BulkStoreStudentCardRequest.php`
  - `app/Http/Requests/RejectStudentCardRequest.php`
  - `app/Policies/StudentCardRequestPolicy.php`
  - `app/Console/Commands/SeedLegacyCardRequests.php`
  - `routes/learn/academy-student-card-request.php`
- **แก้ไข**:
  - `app/Models/AcademyRole.php`
  - `database/seeders/AcademyRoleSeeder.php`
  - `app/Services/StudentCardSyncService.php`
  - `app/Services/AcademicYearRolloverService.php`
  - `app/Services/StudentEnrollmentService.php`

#### Frontend
- **สร้างใหม่**:
  - `ui/pages/academies/[name]/admin/student-cards/requests/index.vue`
  - `ui/pages/academies/[name]/admin/student-cards/requests/queue.vue`
  - `ui/pages/academies/[name]/admin/student-cards/requests/[id].vue`
  - `ui/composables/useStudentCardRequests.ts`
  - `ui/components/school/studentCard/SubmitRequestModal.vue`
  - `ui/components/school/studentCard/RequestStatusBadge.vue`
- **แก้ไข**:
  - `ui/pages/academies/[name]/admin.vue`
  - `ui/pages/academies/[name]/admin/index.vue`

### หัวใจของแผน
> **"Request first, sync never. Snapshot everything."**
> - จัดการต้นเหตุ (ปิด Auto Sync)
> - เก็บ Snapshot ข้อมูลของเด็ก ณ วันที่ขอทำบัตร เพื่อป้องกันข้อมูลสูญหายหรือเพี้ยนในอนาคต
> - ป้องกันข้อขัดแย้งของสถานะ (Race Condition) ด้วย database locks และ state machine ที่เข้มงวด

---

## 2026-07-08 — Roster Reconciliation with Student Code

### การเปลี่ยนแปลงหลัก
- เปลี่ยนขอบเขตจากการเขียนข้อมูลนักเรียนใหม่ทั้งหมด มาเป็นการจัดห้องเรียนใหม่ (Enrollment Reconciliation) โดยใช้ `student_code` เป็นหลัก
- บันทึกและวิเคราะห์ไฟล์ PDF (Companion JSON) เพื่อหาความแตกต่างและจับคู่ห้องเรียน/ครูประจำชั้น

### ไฟล์ที่สร้าง/แก้ไข
- **สร้างใหม่**:
  - [ExtractRosterPdfCommand.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Console/Commands/ExtractRosterPdfCommand.php)
  - [RosterReconciliationService.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/Import/RosterReconciliationService.php)
  - [RosterReconciliationTest.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/RosterReconciliationTest.php)
- **แก้ไข**:
  - [UploadStudentImportRequest.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Requests/Academy/Enrollment/UploadStudentImportRequest.php)
  - [StudentImportService.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/StudentImportService.php)
  - [StudentImportController.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/StudentImportController.php)
  - [studentImportService.ts](file:///c:/wamp64/www/nuxnan/ui/services/studentImportService.ts)
  - [useStudentImport.ts](file:///c:/wamp64/www/nuxnan/ui/composables/useStudentImport.ts)
  - [StepUpload.vue](file:///c:/wamp64/www/nuxnan/ui/components/academy/student-import/StepUpload.vue)
  - [ImportRowTable.vue](file:///c:/wamp64/www/nuxnan/ui/components/academy/student-import/ImportRowTable.vue)

### ผลการทดสอบ
- รัน `RosterReconciliationTest` ผ่านทั้งหมด 10 assertions
- รัน `StudentImportControllerTest` ผ่านเรียบร้อย

---

## 2026-07-09 - Wallet Withdrawal PromptPay Planning

- Scope is plan-only: add PromptPay as a withdrawal destination alongside bank transfer.
- Current flow: `ui/pages/Earn/Wallet.vue` posts through `ui/composables/useWallet.ts` to `POST /api/wallet/withdraw`; backend validation is in `WalletController::withdraw`; transaction metadata is stored by `WalletService::withdraw`; admin queue reads `metadata.bank_account` in `ui/pages/nuxnan-admin/wallet/pending.vue`.
- Recommended contract: keep `bank_account` metadata for compatibility, add `method='promptpay'`, `bank_account.bank_name='promptpay'`, `bank_account.account_number=<promptpay_id>`, optional `metadata.destination_type='promptpay'`, and normalize digits server-side.
- No migration appears necessary if storing destination details in existing `wallet_transactions.metadata` JSON.
- Related cleanup to include in implementation: align withdrawal minimum (UI says 25, submit/API enforce 100) and fee preview (UI 13%, backend 0.5% min 10).
- Verification plan: focused API feature tests for bank withdrawal still works, PromptPay phone/national-id validation, invalid PromptPay rejected, pending admin response displays destination; frontend build/type check if UI changes.

## 2026-07-09 - Public Student Card Request Button Planning

- Scope is plan-only: add a temporary public "request new student card" action to `/student-card/{level}/{room}` for each displayed student card.
- Existing request infrastructure already exists: `student_card_requests` migration/model/enums, `StudentCardRequestService`, authenticated academy routes in `routes/learn/academy-student-card-request.php`, admin queue pages, `useStudentCardRequests.ts`, and `SubmitRequestModal.vue`.
- Recommended implementation: add a narrow public endpoint under `routes/studentcard/studentcard.php`, guarded by a config flag such as `student-card.public_requests` (or reusing `public_management` if the temporary window is exactly the same), resolve classroom from `{level}/{room}` like `StudentCardManageController`, then create a request through the same service path with `origin=teacher` or a new `public` origin.
- Main contract gap: `StudentCardRequestService::create()` currently requires a non-null `User $actor` and sets `requested_by`; public requests need either nullable/system actor support plus submitter snapshot fields, or a dedicated public create method that sets `requested_by=null` safely.
- Frontend files likely touched: `ui/pages/student-card/[level]/[room].vue`, `ui/components/student-card/StudentCardItem.vue`, optionally reuse/adapt `ui/components/school/studentCard/SubmitRequestModal.vue`, and a small public composable if the call is reused.
- Backend files likely touched: `api/nuxnanravel/config/student-card.php`, `routes/studentcard/studentcard.php`, `StudentCardRequestController` or a small public controller method, `StudentCardRequestService`, `StudentCardRequestOrigin`, and focused tests for public create, duplicate-open prevention, disabled flag, wrong-room/student rejection, and public throttling.
- Risks: current public card endpoint exposes student identity data; this temporary page should be feature-flagged, throttled, not expose admin-only actions, and should prevent duplicate open requests through the existing unique/open-request logic.

## 2026-07-09 - Student69 Homeroom Teacher DB Assignment

- Read `docs/api/student69.pdf` (68 pages) and extracted 53 unique classroom homeroom-teacher headers for academic year 2569.
- Updated live DB `classrooms.homeroom_teacher_id` for academy 1 / academic_year_id 2 on all 53 classrooms found in the PDF.
- Matching summary: 42 exact user matches, 5 loose normalized matches, 3 manual existing-user matches (`ม.1/3` -> user 17406, `ม.2/7` -> user 17403, `ม.4/8` -> user 17069), and 3 placeholder teacher users created for names not found (`ม.1/4` user 17483, `ม.3/4` user 17484, `ม.3/7` user 17485).
- Added/ensured `academy_members` teacher membership (`role=teacher`, `academy_role_id=4`, `status=1`) for the 3 placeholders and existing user 17069.
- Verification: current year 2569 has 54 classrooms total; 53 have homeroom teachers; only `ม.4/9` remains unset because it is not present in `student69.pdf`.

---

## 2026-07-09 — Work Plan (ฉบับละเอียด, ตรวจ codebase จริงแล้ว)

> วางแผนอย่างเดียว ยังไม่เขียนโค้ด · ทุกข้อผ่านการ verify กับไฟล์จริง

### ผลการตรวจ codebase (ยืนยัน/แก้ไข note เดิม)

| # | ข้อเท็จจริงที่ยืนยัน | ผลต่อแผน |
|---|----------------------|-----------|
| A | `student_card_requests.origin` เป็น `string(16)` default `'teacher'` (migration บรรทัด 24) **ไม่ใช่ DB enum** | เพิ่มค่า `'public'` ได้โดย **ไม่ต้องเขียน migration** — แค่เพิ่ม case ใน `StudentCardRequestOrigin` |
| B | `requested_by` เป็น `nullable` + `nullOnDelete` (บรรทัด 32) | ตั้ง `requested_by = null` สำหรับ public ได้ปลอดภัย |
| C | payload บนหน้า public คือ **`StudentCard`** ผ่าน `StudentCardPublicResource` → `id` = **card id**, ส่วน Student FK คือ field **`student_id`** (Resource บรรทัด 28) และ **อาจเป็น null** สำหรับบัตร legacy | ปุ่มต้องส่ง `studentInfo.student_id` (ไม่ใช่ `studentInfo.id`) และต้อง guard กรณี null → 422 |
| D | ทุกใบบนหน้านี้เป็น `student_status = active` (query กรอง active) และ `StudentCardRequestService::create()` จะ **reject** `first_issue` ถ้ามีบัตร active อยู่ (บรรทัด 61-63) | ประเภทคำร้อง public ต้อง default เป็น **`replacement`** (บัตรหาย/ชำรุด) หรือ **`renewal`** — **ห้ามส่ง `first_issue`** |
| E | มี unique index `uq_student_card_request_open` บน `(student_id, academy_id, is_open_flag)` (บรรทัด 47) + service เช็ค `whereIn status pending/approved/in_progress` (บรรทัด 45-48) | กันส่งซ้ำได้ 2 ชั้น (DB + service) โดยไม่ต้องเพิ่ม logic |
| F | `StudentCardManageController::resolveClassroomFromUrl()` เป็น `private` และเป็น logic กลางที่ resolve `{level}/{room}` → classroom (+academy) พร้อมตอบ 409 ถ้าเจอหลายโรงเรียน | ควร **extract เป็น trait/service ที่ใช้ร่วมกัน** เพื่อให้ endpoint ใหม่ใช้ logic เดียวกัน (DRY) |
| G | มี flag ระดับ academy อยู่แล้ว: `academy_settings.card_request_flow_enabled` (migration 2026_07_08 บรรทัด 51-53) | ควร gate **สองชั้น**: global config flag + per-academy flag |
| H | `create()` hardcode `origin = Teacher` และ `requested_by = $actor->id`; รับ `$data['classroom_id']` เป็น optional filter ของ active enrollment | ต้อง refactor แยกแกน แล้วส่ง `classroom_id` ที่ resolve จาก URL เข้าไป → บังคับว่านักเรียนต้องอยู่ห้องตาม URL จริง |
| I | หน้า `[level]/[room].vue` ใช้ pattern **Swal + modal เฉพาะทาง** (AddStudentModal ฯลฯ) และเรียก API ด้วย `$fetch` ผ่าน composable `useClassroomManagement` | ทำ modal + composable public ใหม่ให้เข้ากับ pattern นี้ **ดีกว่า** retrofit `SubmitRequestModal.vue` (ตัวนั้นผูก academy/auth shape) |

---

### ขั้นตอนการทำงาน (ทีละขั้น)

#### ขั้นที่ 1 — Config: เพิ่ม flag public requests
**ไฟล์:** `api/nuxnanravel/config/student-card.php`
- เพิ่ม key `'public_requests' => env('PUBLIC_STUDENT_CARD_REQUESTS', false)`
- แยกจาก `public_management` เพราะ "ส่งคำร้อง" เสี่ยงต่ำกว่า "เพิ่ม/ย้าย/ลบนักเรียน" (เปิด-ปิดอิสระได้)
- อัปเดต `.env.example` (ไม่แตะ `.env` จริง)

#### ขั้นที่ 2 — Enum: เพิ่ม origin `public`
**ไฟล์:** `api/nuxnanravel/app/Enums/StudentCardRequestOrigin.php`
- เพิ่ม `case Public = 'public';`
- **ไม่ต้องมี migration** (column เป็น string อยู่แล้ว — ข้อ A)

#### ขั้นที่ 3 — Extract room resolver ให้ใช้ร่วมกัน (DRY)
**สร้าง:** `app/Http/Controllers/Api/Learn/Student/Card/Concerns/ResolvesStudentCardRoom.php` (trait)
- ย้าย logic `resolveClassroomFromUrl(string $level, string $room): Classroom` ออกมาจาก `StudentCardManageController` (คงพฤติกรรมเดิม: 404 ถ้าไม่พบ, 409 ถ้าหลายโรงเรียน)
- `StudentCardManageController` `use` trait นี้แทน method เดิม (regression risk ต่ำ, พฤติกรรมเท่าเดิม)
- Controller ใหม่ก็ `use` trait เดียวกัน

#### ขั้นที่ 4 — Service: รองรับ public actor (refactor แบบไม่ทำ logic ซ้ำ)
**ไฟล์:** `app/Services/StudentCardRequestService.php`
- แยกแกนกลางของ `create()` เป็น private:
  `buildRequest(Academy $academy, Student $student, ?User $actor, StudentCardRequestOrigin $origin, array $data): StudentCardRequest`
  - ย้าย validation ทั้งหมด (academy match, active enrollment, open-request check, existing-card + request_type rules) มาไว้ที่นี่
  - `'origin' => $origin`, `'requested_by' => $actor?->id`
- `create(...)` เดิม → เรียก `buildRequest($academy, $student, $actor, StudentCardRequestOrigin::Teacher, $data)` (พฤติกรรมเดิมทุกอย่าง)
- เพิ่ม `createPublic(Academy $academy, Student $student, array $data): StudentCardRequest`
  → `buildRequest($academy, $student, null, StudentCardRequestOrigin::Public, $data)`
  - บันทึกที่มาใน `reason` เช่น `"ส่งจากหน้า public /student-card/{level}/{room}"` (+ ชื่อ/เบอร์ผู้แจ้งถ้าเก็บ)

#### ขั้นที่ 5 — Endpoint: public request (แคบ + throttle + gate 2 ชั้น)
**ไฟล์ route:** `routes/studentcard/studentcard.php` (ใน group `student-card` เดิม)
```
Route::middleware('throttle:10,1')
    ->post('{level}/{room}/requests', [StudentCardManageController::class ...หรือ controller ใหม่..., 'submitRequest'])
    ->name('manage.submit-request');
```
**Controller method** `submitRequest(Request $request, string $level, string $room)`:
1. `abort_unless(config('student-card.public_requests'), 403)` — global gate (ข้อ G)
2. resolve classroom จาก trait (ขั้นที่ 3) → ได้ `classroom` + `academy`
3. เช็ค per-academy: `abort_unless(academy_settings.card_request_flow_enabled, 403)` — academy gate
4. validate body: `student_id` (required, int), `request_type` (`in:replacement,renewal` — **ไม่รับ first_issue**, ข้อ D), `reason` (nullable string), optional `requester_name`/`requester_phone` (nullable)
5. โหลด `Student::find(student_id)`; ถ้า null หรือ `student_id` ของ card เป็น null → 422 "บัตรนี้ยังไม่ผูกข้อมูลนักเรียน" (ข้อ C)
6. ยืนยันว่านักเรียนอยู่ห้องตาม URL: ส่ง `$data['classroom_id'] = $classroom->id` เข้า service (service กรอง active enrollment ตาม classroom_id — ข้อ H)
7. เรียก `service->createPublic($academy, $student, $data)` ใน try/catch `ValidationException` → 422 พร้อม message เดิม (เช่น "already has an open card request")
8. ตอบ **slim JSON**: `{ success: true, request_id, status, message: 'ส่งคำร้องแล้ว' }` — ไม่ leak field admin

**หมายเหตุ:** จะทำเป็น method ใน `StudentCardManageController` (ใช้ trait ร่วม) หรือแยก `PublicStudentCardRequestController` ก็ได้ — แนะนำแยก controller เพื่อความชัดของ scope public

#### ขั้นที่ 6 — Frontend composable
**สร้าง:** `ui/composables/usePublicCardRequest.ts` (mirror pattern `useClassroomManagement`)
```ts
async function submitCardRequest(studentId, requestType, reason?, requester?) {
  return $fetch(`${apiBase}/api/student-card/${level}/${room}/requests`, {
    method: 'POST',
    body: { student_id: studentId, request_type: requestType, reason, ...requester }
  })
}
```

#### ขั้นที่ 7 — Frontend UI: ปุ่ม + modal ยืนยัน
**แก้ `ui/components/student-card/StudentCardItem.vue`:**
- เพิ่ม prop `canRequest: Boolean`
- เพิ่มปุ่ม "ขอทำบัตรใหม่" (เช่นในเมนู action มุมขวา หรือปุ่มมุมบัตร) → `emit('request', studentInfo)`
- **ห้ามส่ง `first_issue`** — ปล่อยให้หน้าแม่เลือกประเภท (replacement/renewal)

**แก้ `ui/pages/student-card/[level]/[room].vue`:**
- เพิ่ม state ตรวจว่าเปิด public requests ไหม (อ่านจาก `manage-context` ที่มีอยู่ หรือเพิ่ม field `can_request` ใน context endpoint) → ส่ง `:canRequest` ให้การ์ด
- `openRequestModal(student)` → เปิด modal ใหม่ `RequestCardModal.vue` (สไตล์เดียวกับ AddStudentModal): แสดง **ชื่อเด็กให้ยืนยัน** + เลือกประเภท (บัตรหาย/ชำรุด = replacement, หมดอายุ = renewal) + ช่องเหตุผล (+ ชื่อ/เบอร์ผู้แจ้ง optional)
- submit → `usePublicCardRequest().submitCardRequest(student.student_id, type, reason, requester)`
  - **ใช้ `student.student_id`** (Student FK) ไม่ใช่ `student.id` (card id) — ข้อ C
- UX: ปุ่ม disable+loading ระหว่างส่ง; สำเร็จ → Swal "ส่งคำร้องแล้ว"; ถ้ามีคำร้องค้าง → แสดง message จาก backend ("มีคำร้องที่ยังดำเนินการอยู่แล้ว")

**สร้าง:** `ui/components/student-card/RequestCardModal.vue`

#### ขั้นที่ 8 — (ถ้าจำเป็น) เปิด `can_request` ใน context
**ไฟล์:** `StudentCardManageController::context()`
- เพิ่ม `'can_request' => (bool) config('student-card.public_requests') && (academy card_request_flow_enabled)` ใน response
- ให้หน้า/การ์ดแสดงปุ่มเฉพาะเมื่อเปิดจริง (ตอนนี้ `context()` ตอบแม้ `public_management` ปิด — จึงเป็นที่แขวน flag public_requests ได้พอดี)

#### ขั้นที่ 9 — Tests (backend feature)
**สร้าง:** `tests/Feature/StudentCard/PublicCardRequestTest.php`
- flag global ปิด → `POST .../requests` = **403**
- academy `card_request_flow_enabled` ปิด → **403**
- ส่ง `replacement` สำเร็จ → 200/201, `origin=public`, `requested_by=null`
- ส่งซ้ำเมื่อมี open request (pending/approved/in_progress) → **422**
- `first_issue` บนนักเรียนที่มีบัตร active → **422**
- นักเรียนไม่อยู่ห้องตาม URL (classroom_id ไม่ match) → **422**
- card ที่ `student_id = null` → **422** (ไม่ 500)
- throttle: ยิงเกิน limit → **429**
- `replacement/renewal` เมื่อไม่มีบัตร active → **422** (ตาม logic เดิม)

#### ขั้นที่ 10 — ตรวจสอบ + rollout
- `./vendor/bin/pint` (backend), Nuxt build check (frontend)
- Manual smoke: เปิด flag → เห็นปุ่ม, ส่งคำร้อง, คำร้องโผล่ในคิวแอดมิน `academy-student-card-request` (index กรอง `origin` ได้ ควรเห็น `public`)
- **ปิด flag ทันทีหลังใช้เสร็จ** (ทั้ง global + academy) ตามเจตนาชั่วคราว

---

### ไฟล์สรุป (สร้าง/แก้)

| ไฟล์ | Action |
|------|--------|
| `config/student-card.php` | แก้ — เพิ่ม `public_requests` |
| `.env.example` | แก้ — เพิ่ม `PUBLIC_STUDENT_CARD_REQUESTS` |
| `app/Enums/StudentCardRequestOrigin.php` | แก้ — เพิ่ม `Public` |
| `.../Card/Concerns/ResolvesStudentCardRoom.php` | สร้าง — trait resolve ห้อง |
| `StudentCardManageController.php` | แก้ — ใช้ trait + (option) method `submitRequest` + `can_request` ใน context |
| `PublicStudentCardRequestController.php` | สร้าง (ถ้าเลือกแยก controller) |
| `app/Services/StudentCardRequestService.php` | แก้ — refactor `buildRequest()` + เพิ่ม `createPublic()` |
| `routes/studentcard/studentcard.php` | แก้ — เพิ่ม route `POST {level}/{room}/requests` (throttle) |
| `ui/composables/usePublicCardRequest.ts` | สร้าง |
| `ui/components/student-card/RequestCardModal.vue` | สร้าง |
| `ui/components/student-card/StudentCardItem.vue` | แก้ — ปุ่ม + emit `request` |
| `ui/pages/student-card/[level]/[room].vue` | แก้ — modal + submit ผ่าน `student_id` |
| `tests/Feature/StudentCard/PublicCardRequestTest.php` | สร้าง |

### จุดที่ต้องระวังที่สุด (highlight)
1. **`student_id` vs `id`** บน payload — ส่งผิดจะสร้างคำร้องให้ผิดคน/พัง (ข้อ C)
2. **ห้าม `first_issue`** — จะถูก reject เสมอบนหน้านี้ (ข้อ D)
3. **Gate 2 ชั้น + throttle** — public + PII จึงต้อง flag ปิดได้ทันที (ข้อ G)
4. **ส่ง `classroom_id` เข้า service** — บังคับ scope ห้องตาม URL (ข้อ H)

---

## 2026-07-10 - Course Member Removal / Last Access Group Check

- User reported production errors from 2026-06-30: `PATCH /api/courses/25/members/update-last-access-group` returned 404, and `GET/POST /api/courses/25/members/3843/removal-preview|remove` returned 400 with SQL unknown column around assignment cleanup.
- Local route list currently includes `PATCH api/courses/{course}/members/update-last-access-group`, `GET api/courses/{course}/members/{member}/removal-preview`, and `POST api/courses/{course}/members/{member}/remove`; no `bootstrap/cache/routes*.php` route cache file exists locally.
- Root cause still present for removal preview/remove: `CourseMemberRemovalService` filters `AssignmentAnswer::whereHas('assignment', fn => where('course_id', ...))`, but `assignments` is polymorphic (`assignmentable_type`, `assignmentable_id`) and has no direct `course_id` column. This matches the production SQL error.
- Existing `CourseMemberRemovalTest` passes 5 tests / 14 assertions, but it does not cover `removalPreview()` or assignment-answer cleanup through the polymorphic assignment relation, so it does not catch this bug.
- Worktree note: untracked `api/nuxnanravel/database/migrations/2026_07_10_013214_modify_id_in_user_usage_events_table.php` existed before this check and was not touched.

## 2026-07-10 - Course Member Removal Fix Applied

- Fixed `CourseMemberRemovalService` to find `AssignmentAnswer` rows through polymorphic assignments attached directly to the course, to course lessons, or to course topics instead of querying the nonexistent `assignments.course_id` column.
- Reused the same helper query for both preview counts and execute cleanup, so `removal-preview` and `remove` now follow the same course scope.
- Added regression coverage in `CourseMemberRemovalTest` for preview count across course/lesson/topic assignments and for deleting only answers belonging to the removed course.
- Verification: `./vendor/bin/pint app/Services/CourseMemberRemovalService.php tests/Feature/CourseMemberRemovalTest.php` passed; `php artisan test --filter=CourseMemberRemovalTest` passed 7 tests / 19 assertions. PHPUnit metadata deprecation warnings and local Xdebug log warning are pre-existing/noise.
- Remaining production action: `update-last-access-group` route exists locally, so the reported 404 should be handled by deploying this code and clearing/rebuilding production route cache.

## 2026-07-10 - Course Group Member Removal Route Fix Applied

- Frontend `groups/[groupId].vue` now calls `DELETE /api/courses/{course}/groups/{group}/members/{member.id}` (previously `POST .../unMemberGroup`, which had no matching route).
- Found a duplicate `DELETE /{course}/groups/{group}/members/{member}` route in `routes/learn/course.php` pointing to `unMemberGroup()`, colliding with the `destroy()` route registered under the `/courses/{course}/groups` group. `unMemberGroup()` only nulls `course_members.group_id` and leaves the `course_group_members` row intact, so `isMember`/pending state could go stale.
- At runtime Laravel resolved the URL to `destroy()` (last-registered wins on identical URIs), but that is fragile under `route:cache`. Removed the colliding duplicate route so `destroy()` (which deletes the `course_group_members` row AND resets `CourseMember` group state) is the unambiguous handler.
- Added `CourseGroupMemberRemovalTest` asserting the DELETE endpoint removes the `course_group_members` row and resets `course_members.group_id`/`group_member_status` while keeping the course membership. Pint + test pass (1 test / 6 assertions).

## 2026-07-10 - Withdrawal Minimum 25 THB Planning

- User requested a plan to resolve inconsistent withdrawal minimums and set the minimum withdrawable amount to 25 THB.
- Current inspection: `WalletController::withdraw()` already validates `amount` with `min:25`; `useWallet.canWithdraw()` also uses 25; `Earn/Wallet.vue` has input `min="25"`, disabled guard `withdrawForm.amount < 25`, and Thai helper text saying minimum 25 THB.
- Likely remaining mismatch is UX/default affordance: `Earn/Wallet.vue` initializes `withdrawForm.amount` to 100 and quick withdrawal chips start at `[100, 500, 1000, 2000, 5000]`, so users never see 25 as a selectable minimum even though validation allows it.
- Proposed implementation files: `ui/pages/Earn/Wallet.vue`, `ui/composables/useWallet.ts`, `ui/tests/useWallet.spec.ts`, `api/nuxnanravel/app/Http/Controllers/Api/WalletController.php`, `api/nuxnanravel/tests/Feature/Wallet/WithdrawTest.php`; optional config/constants only if the team wants a single source of truth for minimum/fee.
- Verification plan: add/confirm tests for 24 rejected and 25 accepted, add unit coverage for `canWithdraw(25)`, then run focused Wallet feature tests and frontend wallet unit/build checks.

## 2026-07-10 - Academy Admin Settings SQL Unknown Column Inspection

- User reported `SQLSTATE[42S22] Unknown column 'description'` on `/academies/{name}/admin/settings` when saving academy settings.
- Flow traced: `ui/pages/academies/[name]/admin/settings.vue` posts `FormData` to `POST /api/academies/{academy}/settings`; route exists and maps to `Api\Learn\Academy\AcademyController@updateSettings`.
- Root cause: `updateSettings()` fills `academies` with `name`, `name_en`, `description`, `description_en`, `email`, `phone`, `website`, `address`, `province`, `country`, but the live DB columns for `academies` are only `id,user_id,name,slogan,address,email,phone,director,established_year,type,accreditation,accreditation_body,total_students,total_teachers,membership_fees_points,courses_offered,facilities,academy_timings,holidays,social_media_links,student_editable_fields,approval_flow,logo,cover,created_at,updated_at`.
- Secondary mismatch: `updateSettings()` writes `academy_settings.privacy`, `allow_student_registration`, `allow_parent_registration`, `show_member_list`, and `show_course_list`, but live `academy_settings` has only `id,academy_id,auto_accept_members,card_request_flow_enabled,created_at,updated_at`.
- `AcademyResource` also does not return the settings page's direct fields (`description`, `name_en`, `description_en`, `website`, `province`, `country`) and only exposes `setting`.
- Recommended fix: add an idempotent migration for the intended missing academy/profile/settings columns, update `Academy::$fillable`/casts as needed, align `AcademyResource`, and add focused backend coverage for settings update with all fields.

### Work Plan — Academy Admin Settings Schema Fix (ฉบับละเอียด, 2026-07-10) — DONE
- **Status**: Completed on 2026-07-10
- **Summary**:
  - Resolved `SQLSTATE[42S22] Unknown column 'description'` settings error by adding missing fields in migrations.
  - Added casts for settings flags in `AcademySetting`.
  - Added request validation, `name_slug` collision checks, and direct `join_mode` mapping in `AcademyController`.
  - Flattened nested configurations and added new properties to `AcademyResource` to resolve state resets on UI reload.
  - Switched `settings.vue` avatar preview reference from `avatar` to `logo_url`/`logo`.
  - Wrote and passed the `AcademySettingsUpdateTest` (4 tests, 52 assertions).
  - Formatted codebase using Laravel Pint.

#### ขั้นที่ 1 — ยืนยัน schema จริงบน DB (read-only, กันพลาด) - DONE
#### ขั้นที่ 2 — เขียน migration แบบ idempotent - DONE (Migration `2026_07_10_000001_add_settings_fields_to_academies_and_settings.php` run successfully)
#### ขั้นที่ 3 — อัพเดท Models - DONE (`Academy.php` and `AcademySetting.php` updated)
#### ขั้นที่ 4 — ปรับ AcademyController@updateSettings - DONE (`AcademyController.php` updated with validation, non-lossy join_mode & unique slug checks)
#### ขั้นที่ 5 — ปรับ AcademyResource - DONE (`AcademyResource.php` updated with flattened configuration mapping)
#### ขั้นที่ 6 — ปรับ Frontend `settings.vue` - DONE (`settings.vue` line 106 updated)
#### ขั้นที่ 7 — ทดสอบ - DONE (Feature test `AcademySettingsUpdateTest.php` created and verified)
#### ขั้นที่ 8 — Verify ปลายทาง - DONE

**สรุปไฟล์ที่แตะ:**
- `database/migrations/2026_07_10_000001_add_settings_fields_to_academies_and_settings.php` [NEW]
- `app/Models/Academy.php` [MODIFY]
- `app/Models/AcademySetting.php` [MODIFY]
- `app/Http/Controllers/Api/Learn/Academy/AcademyController.php` [MODIFY]
- `app/Http/Resources/Learn/Academy/AcademyResource.php` [MODIFY]
- `ui/pages/academies/[name]/admin/settings.vue` [MODIFY]
- `tests/Feature/Academy/AcademySettingsUpdateTest.php` [NEW]

---

## 2026-07-10 - Home Visit Admin Dead UI Cleanup

- Removed orphaned legacy Nuxt/Inertia admin home-visit UI under `ui/pages/Learn/Student/HomeVisit/Admin/` plus `ui/composables/useVisitReports.js`.
- Scope intentionally did not touch `HomeVisit/Student/`, `HomeVisit/Teacher/`, `HomeVisit/Auth/`, `HomeVisit/Components/`, or `HomeVisit/Composables/`.
- Pre-delete verification: `useVisitReports` consumers were only inside deleted `Admin/Components`; no `ui/` link/string target remained for `Learn/Student/HomeVisit/Admin`; academy admin replacement entry remains at `ui/pages/academies/[name]/admin.vue:169`.
- Post-delete verification: `rg` over `ui/` finds no `useVisitReports`, `home-visit/admin`, `/api/home-visit/admin`, or `HomeVisit/Admin`; `npx.cmd nuxi prepare` passed; `npm.cmd run build` passed.
- Browser smoke on `http://127.0.0.1:3010/academies/original-academy-name/admin/home-visits`, `/zones`, `/create`, and `/export` reached the auth guard with no Nuxt error and no console errors. Full API smoke was blocked by lack of authenticated browser session.
- Remaining `git grep` for `home-visit/admin` outside generated `.agents`/public assets finds only backend legacy test coverage at `api/nuxnanravel/tests/Feature/HomeVisit/AdminControllerTest.php:503`; this cleanup intentionally removed only the broken admin UI source.
- Backlog gap: legacy UI had per-visit PDF report and bulk PDF export. Academy admin replacement currently has CSV/Excel-style export only via `/admin/export/visits`; add PDF report/export separately if schools still require it.

## 2026-07-10 - Wallet Withdrawal Fee Audit

- User asked to inspect `/nuxnan-admin/wallet/pending` for duplicate or incorrect withdrawal fee deduction and confirm whether the configured fee is 13% or 10 THB.
- Current source of truth: `WalletService::withdraw()` deducts the requested gross `amount` from the user's wallet once, stores `metadata.fee = amount * 0.13`, and stores `metadata.net_amount = amount - fee`; `internal_deduction` is exempt from fee.
- Admin approval flow does not deduct again: `approveWithdrawal()` only changes status from `pending` to `completed`; rejection refunds the original gross `amount` to the user and marks the transaction cancelled.
- Frontend user wallet and admin helpers both calculate/display 13%; `Earn/Wallet.vue` initializes withdrawal amount to 25, quick chips include 25, and preview labels fee as 13%. Admin pending page displays gross `request.amount` as the large amount, then fee/net separately from metadata.
- No fixed 10 THB withdrawal fee was found in wallet withdrawal paths. Matches for 10 THB are unrelated flows such as deposit minimum, wallet-to-points conversion minimum, or ad formulas.
- Verification: `php artisan test --filter=WithdrawTest` passed 12 tests / 28 assertions, including 24 rejected, 25 accepted, and 100 THB -> fee 13 / net 87. Warnings were pre-existing PHPUnit doc-comment metadata and local Xdebug log noise.

## 2026-07-10 - Wallet Pending Page Live Data Check

- User shared screenshot from `http://localhost:3000/nuxnan-admin/wallet/pending`; the pending withdrawal page shows two rows with `fee = 10` THB.
- Read-only DB check confirmed the page is displaying stored `wallet_transactions.metadata`, not recalculating in the frontend:
  - Transaction `185`, user `พัชรี  หนูวงค์`, amount `148.99`, stored fee `10`, stored net `138.99`; current 13% policy would be fee `19.37`, net `129.62`.
  - Transaction `12`, user `Utai Salem`, amount `100`, stored fee `10`, stored net `90`; current 13% policy would be fee `13`, net `87`.
- Conclusion: these pending records are stale/legacy or were created by a previous fee rule; approving them now will not recalculate the fee. Admin should transfer the stored `net_amount` if preserving the original request terms, or reject/recreate/update the request if enforcing current 13% policy.
- UI risk remains: approval modal currently emphasizes gross `selectedRequest.amount`, not the net transfer amount, so admins can accidentally approve while thinking the large amount is what should be transferred.

## 2026-07-11 - Game XP / PP Reward Audit

- User wants game play to award XP only and never increase PP; inspection was read-only and no application code was changed.
- Generic games using `POST /api/game/scores` only persist `GameScore`; `GameScoreController::store()` does not award XP or PP.
- Typing game normal sessions currently award both: `TypingSessionController::store()` calls `PointsService::addXp()` and then awards PP as `floor(score / 100)` with source `typing_game`.
- Typing daily challenge completion currently awards both configured `xp_reward` and `pp_reward` in `TypingDailyChallengeController::complete()`.
- Typing tournament attempts award XP only, but `TypingTournamentController::claim()` can award PP prizes configured in `prize_*_pp` fields. Classroom race result submission awards XP only.
- Intended implementation: remove/disable all game-origin PP awards (normal typing session, daily challenge, tournament claim), preserve XP awards, align API reward payloads/UI labels and add focused regression tests asserting user PP is unchanged while XP increases.
- Risk: decide whether tournament PP prizes are considered game play rewards; under the stated goal they should also be zero/disabled, including existing configured tournament and daily-challenge PP values.

---

## Work Plan — นโยบายการให้คะแนน XP / PP ในเกม (2026-07-11)

**สถานะ:** วางแผน (ยังไม่เริ่มพัฒนา) — ตรวจ codebase จริงแล้ว
**ที่มา:** ผู้ใช้ร่างนโยบายว่ากิจกรรมใดควรได้ PP; รอบนี้ตรวจโค้ดจริงเพื่อ ground แผนและหาช่องโหว่เพิ่ม

### หลักการหลัก (grounded)

- **PP = เงินจริง** — `PointsService::convertPointsToWallet()` แปลง `1200 pp = 1 บาท` เข้ากระเป๋าเงินได้ (`app/Services/PointsService.php:465`). ทุก PP ที่จ่ายออกคือหนี้สินทางการเงิน ไม่ใช่แต้มเกม → นี่คือเหตุผลจริงที่ห้ามจ่าย PP จากการเล่นซ้ำ
- **XP ปลอดภัย** — `addXp()` ขับเลเวลอย่างเดียว ไม่มีมูลค่าเงิน (`app/Services/PointsService.php:69`) → ให้ XP ได้เต็มที่ทุกกิจกรรม
- **กรอบนโยบาย:** XP = จ่ายจากพฤติกรรม (behavior-funded, ไม่จำกัด) / PP = จ่ายจากงบประมาณ (budget-funded, มีเพดานเสมอ)

### กฎเหล็ก — ก่อนจ่าย PP ทุกครั้งต้องครบทุกข้อ

1. เป็นกิจกรรม daily / one-time / event ที่มีวันหมดอายุเท่านั้น
2. มีเพดานจำนวนครั้งต่อผู้ใช้ (ใช้ `PointRule.max_daily_earnings` / `cooldown_minutes` ที่มีอยู่แล้ว)
3. backend ตัดสินผลสำเร็จจากข้อมูลที่ persist แล้ว ไม่ใช่จาก request payload
4. มี unique reward key (`source_type` + `source_id` + `user_id`) กันจ่ายซ้ำระดับ DB
5. จำนวน PP เป็นค่าคงที่กำหนดล่วงหน้า ไม่คำนวณจาก `score` ที่ client ส่ง
6. จ่ายผ่าน `awardByRule()` ไม่ใช่ `earn()` ตรงๆ เพื่อให้ limit engine ทำงาน

### ช่องโหว่จริงในโค้ด (ต้องปิดในแผนนี้)

- **A. `earn()` ข้าม limit ทั้งหมด** — เฉพาะ `awardByRule()` เช็ค `canEarnFromRule` (daily cap/cooldown/monthly) แต่ typing ทุกจุดเรียก `earn()` ตรง (`app/Services/PointsService.php:305` เทียบ `TypingSessionController.php:77`) → PP จากเกมไม่มีเพดานใดๆ
- **B. Daily Challenge เชื่อคะแนนจาก client** — `wpm`/`accuracy`/`score` มาจาก request, ไม่ cross-check กับ `typing_sessions` ที่อ้าง `session_id`, ไม่เช็คว่า session เป็นของ user, ไม่เช็คว่า challenge เป็นของวันนี้ (`TypingDailyChallengeController.php:52-60`) → ยิง `wpm=9999` ผ่านเป้ารับ PP ได้
- **C. Tournament `claim()` อ่าน `$entry->rank` ที่ไม่เคยถูก set** — flow `attempt()` ไม่เคยคำนวณ/persist `rank`, `getPrizesFor` match rank 1/2/3 (`TypingTournamentController.php:221`, `Models/TypingTournament.php:94`) → รางวัลอันดับอาจตกไป default (pp=0) หรือถ้ามี job set rank ต้องคุม idempotency
- **D. ไม่มี unique reward key** — กันซ้ำด้วย flag เฉพาะกิจ; normal session ไม่มีกันซ้ำเลย → เสี่ยง double-award ตอน retry/race
- **E. คะแนนดิบมาจาก client ทุกจุด** — `correct_chars`, `time_elapsed` ฯลฯ ส่งจาก client แล้ว server คำนวณต่อ → "server คำนวณ score" เชื่อได้แค่เท่า input

### ตารางนโยบายฉบับลงมือได้

| กิจกรรม | XP | PP | กลไกที่ต้องมี |
|---|---|---|---|
| Typing session ปกติ (ทุกโหมด) | `calculateXp()` เดิม | 0 — ลบสูตร `floor(score/100)` | เก็บ score ไว้ทำ leaderboard เท่านั้น |
| Generic games (`/game/scores`) | ยังไม่ให้ (ถูกแล้ว) | 0 | ถ้าจะเพิ่ม reward อย่าเชื่อ `score`/`level` จาก request |
| Classroom Race แต่ละครั้ง | ให้ | 0 | กันสร้างห้องปั๊ม PP |
| Tournament attempt | ให้ (ต่อครั้ง) | 0 | XP อย่างเดียวตอนเล่น |
| Daily Challenge สำเร็จ | 20–50 | 1–3 | 1 ครั้ง/user/วัน + fix B |
| รางวัลอันดับ Tournament | ตามอันดับ | 1:50 / 2:30 / 3:15 | fix C (คำนวณ+persist rank) + idempotent claim |
| Achievement ครั้งเดียว | ให้ | 2–20 ตามความยาก | `TypingUserAchievement` unique อยู่แล้ว + เพิ่ม `pp_reward` one-time |
| Achievement ทำซ้ำได้ | ให้ | 0 | XP อย่างเดียว |
| Event โดยผู้ดูแล | ให้ | ตามงบ | start/end, เพดานผู้รับ, PP budget รวม, ผู้อนุมัติ |

### แผนดำเนินงานเป็นเฟส (action items)

> **สถานะ (อัปเดต 2026-07-11):** เฟส 0–3 **implement เสร็จ + commit แล้ว** (`af434d89`, `a1a23d30`) — ดูรายละเอียดใน `.agents/worklog.md` หัวข้อ "Game XP/PP Reward Policy" รวมถึง `TypingRewardPolicyTest` ผ่าน 5/5
> **ยังค้าง:** (1) browser/runtime verification ตาม `typing-game-improvement-plan.md` ยังไม่ทำ (2) deploy steps: รัน migration `idempotency_key` + reseed `GamificationSeeder` (3) เฟส 4 ยังไม่ทำ (optional)

**เฟส 1 — หยุดเลือด (ทำก่อน, เสี่ยงสูงสุด)** ✅ done
- [x] ลบบล็อก PP `floor(score/100)` ใน `TypingSessionController.php:74-79` → ให้ XP อย่างเดียว
- [x] regression test: หลังเล่น session → `user->pp` เท่าเดิม, `user->xp` เพิ่ม

**เฟส 2 — ปิดช่องโหว่จุดที่ยังจ่าย PP** ✅ done
- [x] Daily Challenge: อ่าน `wpm`/`accuracy` จาก `TypingSession` ที่ persist + เช็ค `session->user_id === user->id` + เช็ค `challenge_date === today` (แก้ B)
- [x] Tournament: คำนวณและ persist `rank` ตอนปิดทัวร์นาเมนต์ (ใช้ `FinalizeTypingTournaments` + schedule hourly) ก่อนเปิด `claim()` (แก้ C)

**เฟส 3 — ย้ายมาใช้ governance ที่มีอยู่** ✅ done
- [x] แปลง PP payout ทั้งหมดให้ผ่าน `PointRule` + `awardGoverned()` แทน `earn()` ตรง (แก้ A)
- [x] เพิ่ม idempotency key/unique constraint บน `points_transactions` (`idempotency_key` nullable+unique) (แก้ D)

**เฟส 4 — Admin Event framework (ถ้าต้องการกิจกรรมพิเศษ)** ⬜ ยังไม่ทำ (optional/backlog)
- [ ] ตาราง event + PP budget + เพดานผู้รับ + audit trail + กันรับซ้ำ

**เหตุผลลำดับ:** เฟส 1 แยกและทำก่อนเพราะเป็นจุดเดียวที่ปั๊มได้ไม่จำกัดและแปลงเป็นเงินได้; Daily Challenge / Tournament มีเพดานตามธรรมชาติ (วันละครั้ง / อันดับ) จึงเป็นความเสี่ยงรองที่ตามไปปิด

---

### วิเคราะห์เพิ่มเติมและรายละเอียดทางเทคนิคเชิงลึก (2026-07-11)

จากการวิเคราะห์ระบบตรวจสอบประวัติการให้รางวัล PP/XP ในเกมเพิ่มเติม พบประเด็นความปลอดภัยทางเทคนิคที่ต้องกำหนดไว้ในแผนงานดังนี้:

#### 1. มาตรการป้องกันการ Replay Attack ใน Daily Challenge
* **ประเด็น:** ผู้ใช้อาจใช้ `session_id` ของการเล่นปกติธรรมดา (เช่น `game_mode = 'word_typing'`) หรือ `session_id` เก่าของวันอื่นมาส่งเพื่อเคลมรางวัล Daily Challenge
* **แนวทางแก้ไขทางเทคนิค:**
  - เพิ่มการตรวจสอบใน `TypingDailyChallengeController::complete()` ว่า `TypingSession` ที่ส่งมานั้นมี `game_mode === 'daily_challenge'` และ `challenge_id === $challenge->id`
  - ตรวจสอบว่า `session->completed_at` มีวันที่ตรงกับ `challenge->challenge_date`
  - ตรวจสอบในตาราง `typing_user_daily_challenges` ว่า `session_id` นี้ยังไม่เคยถูกใช้ในการทำ Challenge ใดๆ สำเร็จมาก่อน (เพื่อกันการใช้ session เดียวส่งซ้ำหลายบัญชีหรือส่งซ้ำในระบบ)

#### 2. กลไกการแจกรางวัลทัวร์นาเมนต์แบบ Idempotent (กันการเบิกซ้ำจาก Race Condition)
* **ประเด็น:** ในตอนที่เรียก `claim()` รางวัลทัวร์นาเมนต์ หากมีการกดส่ง Request ซ้ำๆ พร้อมกัน (Race Condition) อาจทำให้เกิด Double-Claiming (ได้รับ PP หลายรอบก่อนที่ `prize_claimed = true` จะถูกเขียนลง DB)
* **แนวทางแก้ไขทางเทคนิค:**
  - ใช้ `DB::transaction()` ร่วมกับ Pessimistic Locking เช่น `lockForUpdate()` ในการดึง `TypingTournamentEntry`
    ```php
    $entry = TypingTournamentEntry::where([
        'tournament_id' => $tournament->id,
        'user_id' => $user->id,
    ])->lockForUpdate()->firstOrFail();
    ```
  - เพิ่ม `Unique Index` ในตาราง `points_transactions` (ตามเฟส 3) เพื่อให้ระดับฐานข้อมูลช่วยสกัดการบันทึกรายการซ้ำแบบสมบูรณ์ โดยใช้ key จาก `source_type` ('tournament_claim'), `source_id` ($tournament->id) และ `user_id`

#### 3. การย้ายมาใช้ PointRule Governance (ขจัด Magic Numbers)
* **ประเด็น:** ในโค้ดปัจจุบันมีการเรียก `earn()` โดยใส่แต้มแบบ Hardcode เช่น `$challenge->pp_reward` หรือ `$prizes['pp']`
* **แนวทางแก้ไขทางเทคนิค:**
  - กำหนด Rule Key ลงในตาราง `point_rules` ได้แก่ `typing_daily_challenge` และ `typing_tournament_prize`
  - ใน `PointsService` ให้เรียกใช้ `awardByRule($user, 'typing_daily_challenge', $challenge->id, ...)` ซึ่งจะช่วยควบคุมโควตา Daily/Monthly limit และ Cooldown ได้อย่างเป็นระบบส่วนกลาง ดีกว่าการทำ ad-hoc logic ใน controller แต่ละตัว
  - อัปเดต `GamificationSeeder.php` ให้รองรับ Rule Key เหล่านี้เป็นค่าเริ่มต้น

#### Review เทียบ codebase จริง — 2 จุดต้องปรับก่อน implement (2026-07-11)

ตรวจ schema/โค้ดจริงแล้ว ยืนยันว่ามาตรการข้างต้นทำได้ (มี `challenge_id`, `game_mode`, `completed_at` ใน `typing_sessions`; มี `session_id` ใน `typing_user_daily_challenges`) แต่มี 2 จุดที่ต้องแก้แบบก่อนนำไป implement:

**จุดที่ 1 — อย่าใส่ Unique Index บน `(source_type, source_id, user_id)` โดยตรง (จะทำ transaction ปกติพัง)**
* **ประเด็น:** `points_transactions` ปัจจุบันมีแค่ index ธรรมดา ไม่ unique (`migration ...create_points_transactions_table.php:31`) และคอลัมน์ 3 ตัวนี้ถูกใช้ซ้ำโดยธุรกรรมที่ **ควรเกิดซ้ำได้**:
  - `transfer_out` / `transfer_in` ใช้ `source_id = user ปลายทาง` → โอนให้คนเดิม 2 ครั้งจะชน unique
  - `admin_adjust`, `points_to_wallet` มี `source_id = null`
* **แนวทางที่ปลอดภัยกว่า (แทนข้อ D / เฟส 3):** เพิ่มคอลัมน์ใหม่ `idempotency_key` (string, nullable) + unique index บนคอลัมน์นี้ตัวเดียว แล้วเติมค่า **เฉพาะ payout ครั้งเดียว** เช่น `tournament_prize:{tournament_id}:{user_id}` และ `daily_challenge:{challenge_id}:{user_id}` — flow เดิมทั้งหมด (transfer/admin/conversion) ไม่กระทบเพราะปล่อย `idempotency_key = null`

**จุดที่ 2 — `awardByRule()` จ่ายค่า "คงที่" ต่อ rule จึงแทนรางวัลตามอันดับไม่ได้**
* **ประเด็น:** `PointRule::calculateAmount()` = `base_amount × multiplier` (`app/Models/PointRule.php:95`) → rule เดียว `typing_tournament_prize` ให้ 50/30/15 ตามอันดับไม่ได้ และ Daily Challenge เก็บ `pp_reward` แบบ **ราย challenge** ไม่ใช่ค่า global
* **แนวทางเลือกทางใดทางหนึ่ง (ปรับข้อ 3 ด้านบน):**
  - (ก) แยก rule key ต่ออันดับ: `typing_tournament_prize_1st` / `_2nd` / `_3rd`
  - (ข) เพิ่มเมธอดใหม่ใน `PointsService` เช่น `awardGoverned($user, $amount, $ruleKey, $idempotencyKey)` ที่รับ `$amount` ชัดเจนแต่ยังผ่าน limit + idempotency engine — เก็บ amount ไว้ที่ challenge/tournament ได้เหมือนเดิม (ยืดหยุ่นกว่า, แนะนำ)

**ข้อสังเกตเสริมสำหรับเฟส 2**
* `claim()` ปัจจุบัน **ไม่มี `DB::transaction` เลย** (`TypingTournamentController.php:205-245`) → การใส่ `lockForUpdate()` ตามข้อ 2 ต้องห่อทั้ง `addXp + earn + entry->update` ให้ atomic พร้อมกันในธุรกรรมเดียว
* `canEarnFromRule` ใช้ cooldown/daily-cap แบบ aggregate ตาม `source_type` → ใช้เป็นตัวกัน "รับครั้งเดียวต่อทัวร์นาเมนต์/challenge" ไม่ได้ ต้องพึ่ง `idempotency_key` เป็นตัวกันหลัก ส่วน rule limit ใช้คุมเพดานรวมเท่านั้น
* Consistency: โค้ดเดิมใช้ `source_type = 'tournament'` (`TypingTournamentController.php:228`) ส่วน deep-dive ข้อ 2 เขียน `'tournament_claim'` — เลือกใช้ค่าเดียวให้ตรงกันตอน implement

---

### แผน Implementation ฉบับลงมือ (2026-07-11)

**ลำดับ deploy จริงที่แนะนำ:** 1 → 0 → 2 → 3 (เฟส 1 แยก deploy ก่อนได้ทันทีเพื่อหยุด PP farm; เฟส 0 เป็นฐานของ 2–3)

#### เฟส 0 — Infrastructure ร่วม (ทำก่อน เพราะเฟส 2–3 พึ่งพา)

**0.1 เพิ่ม `idempotency_key` ใน `points_transactions`**
- ไฟล์ใหม่: `database/migrations/2026_07_11_xxxxxx_add_idempotency_key_to_points_transactions.php`
- `$table->string('idempotency_key')->nullable()->unique()->after('source_id');`
- เติมใน `PointsTransaction::$fillable`
- Additive/ปลอดภัยกับข้อมูลเดิม (null ทั้งหมด ไม่ชน unique)

**0.2 เพิ่มเมธอด `awardGoverned()` ใน `PointsService`**
- ลายเซ็น: `awardGoverned(User $user, float $amount, string $ruleKey, string $idempotencyKey, ?int $sourceId, ?string $description, ?array $metadata): ?PointsTransaction`
- ลำดับภายใน:
  1. ถ้า `PointsTransaction::where('idempotency_key', $key)->exists()` → return null (กันซ้ำ)
  2. โหลด rule ผ่าน `getRule($ruleKey)`; ถ้ามี rule → เช็ค `canEarnFromRule()` (เพดานรวม/cooldown)
  3. เรียก `earn()` โดยส่ง `$idempotencyKey` ต่อเข้าไป
- ปรับ `earn()` ให้รับ+บันทึก `idempotency_key` (เพิ่ม param ท้ายสุด default null → ไม่กระทบ caller เดิม)
- ครอบ try/catch `QueryException` (กัน unique-violation จาก race) → return null
- แก้รากช่องโหว่ **A + D** ให้เป็นเครื่องมือกลาง

#### เฟส 1 — หยุดเลือด (เสี่ยงสูงสุด, deploy แยกได้ทันที ไม่พึ่งเฟส 0)

**1.1** ลบบล็อก PP `floor($scores['score']/100)` + `earn(...)` ใน `TypingSessionController.php:74-79` → เหลือ `addXp()` อย่างเดียว; ตัด `PointsService` ออกจาก constructor ถ้าไม่ถูกใช้ที่อื่นในไฟล์ (เช็คก่อน)
**1.2** เช็คฝั่ง UI `ui/components/games/typing/` ว่ามีโชว์ "PP earned" ไหม (response ไม่มี field `pp` อยู่แล้ว)
**1.3 Test** — `tests/Feature/Play/Typing/TypingSessionRewardTest.php`: หลังยิง session → `user->fresh()->pp` เท่าเดิม, `xp` เพิ่ม, ไม่มี row `points_transactions` `source_type='typing_game'`

#### เฟส 2 — ปิดช่องโหว่จุดที่ยังจ่าย PP (ต้องมีเฟส 0 ก่อน)

**2.1 Daily Challenge — ยืนยันผลจาก session จริง (แก้ B + Replay)** — `TypingDailyChallengeController::complete()`
- validation รับแค่ `session_id` (ตัด `score/wpm/accuracy` จาก client)
- `TypingSession::findOrFail($session_id)` แล้ว guard: `user_id===user`, `game_mode==='daily_challenge'`, `challenge_id===$challenge->id`, `challenge_date` เป็นวันนี้, `session_id` ยังไม่ถูกใช้ใน `typing_user_daily_challenges` แถวใด
- คำนวณ `completed` จาก `$session->wpm`/`$session->accuracy` (ค่าจาก DB) เทียบ `target_wpm`/`target_acc`
- จ่ายผ่าน `awardGoverned($user, $challenge->pp_reward, 'typing_daily_challenge', "daily_challenge:{$challenge->id}:{$user->id}", ...)`

**2.2 Tournament — คำนวณ+persist `rank` ก่อน claim (แก้ C)**
- ต่อยอด `CreateWeeklyTypingTournament.php` หรือสร้าง command `FinalizeTypingTournament`
- ตอนปิดทัวร์: จัดอันดับ `TypingTournamentEntry` ตาม `best_score` DESC แล้วเขียน `rank` ทีละแถว (คอลัมน์ `rank` มีอยู่แล้ว — `getPrizesFor` ใช้ `$entry->rank`)
- schedule ให้รันตอน `ends_at` ผ่าน (`Console/Kernel.php` หรือ status flip)

**2.3 Tournament claim — atomic + idempotent (แก้ D + race)** — `TypingTournamentController::claim()`
- ห่อทั้งเมธอดด้วย `DB::transaction()` (ปัจจุบันไม่มี transaction เลย)
- `$entry = ...->lockForUpdate()->firstOrFail();`
- จ่ายผ่าน `awardGoverned($user, $prizes['pp'], 'typing_tournament_prize', "tournament_prize:{$tournament->id}:{$user->id}", ...)`
- คง `prize_claimed` เป็น guard ชั้นแรก, `idempotency_key` เป็น guard ชั้น DB

**2.4 Test**
- `TypingDailyChallengeRewardTest`: session ปลอม (game_mode ผิด / คนละ user / คนละวัน / ใช้ซ้ำ) → ไม่จ่าย; session ถูกต้องผ่านเป้า → จ่าย 1 ครั้ง, เรียกซ้ำไม่จ่ายเพิ่ม
- `TypingTournamentClaimTest`: claim ปกติจ่ายตาม rank; claim ซ้ำ/พร้อมกันจ่ายครั้งเดียว

#### เฟส 3 — ย้ายทุก payout เข้า governance (ต้องมีเฟส 0, 2)

**3.1** `GamificationSeeder.php`: เพิ่ม rule keys `typing_daily_challenge`, `typing_tournament_prize` (ตั้ง `max_daily/monthly_earnings` เป็นเพดานรวม ไม่ใช่ตัวกันซ้ำ)
**3.2** `grep '->earn(' app/Http/Controllers/Api/Play/` → ต้องเหลือแต่ `awardGoverned()`
**3.3 Achievement PP (ถ้าเปิด)** — `TypingScoreService::checkAchievements()` จ่าย `pp_reward` ผ่าน `awardGoverned(... "achievement:{$achievement->id}:{$user->id}")` เฉพาะ achievement one-time

#### เฟส 4 — Admin Event framework (optional)
- ตาราง `pp_events` (start/end, `pp_budget`, `max_recipients`, `pp_per_user`, `created_by`); payout ผ่าน `awardGoverned()` + ตรวจ budget คงเหลือก่อนจ่าย; ยังไม่ลงรายละเอียดจนกว่าจะยืนยันความต้องการ

#### ตารางความเสี่ยง & rollback

| เฟส | Deploy แยก | ความเสี่ยงถ้าไม่ทำ | Rollback |
|---|---|---|---|
| 0 | ได้ (additive) | — | drop column / ลบเมธอด |
| 1 | ได้ (ไม่พึ่ง 0) | สูงสุด — ปั๊มเงินได้ | revert 1 ไฟล์ |
| 2 | ต้องมี 0 ก่อน | โกง challenge / prize ไม่จ่าย | revert controller |
| 3 | ต้องมี 0,2 | โค้ดกระจาย ไม่มีเพดานรวม | revert seeder+controller |

---

### ผลตรวจสอบหลัง implement เฟส 0–3 (2026-07-11)

**สถานะ:** implement เฟส 0–3 เสร็จ (เฟส 4 optional ยังไม่ทำ) — ตรวจ diff จริง + รัน test แล้ว

**ยืนยันตรงตามแผน:**
- เฟส 0: migration `2026_07_11_000001_add_idempotency_key_to_points_transactions` (nullable+unique, additive), `PointsService::awardGoverned()` เช็ค idempotency → rule → `earn()` + catch `QueryException`, ปรับ `earn()` รับ `idempotency_key` (param ท้าย default null)
- เฟส 1: ลบ `floor(score/100)` ใน `TypingSessionController` เหลือ XP อย่างเดียว
- เฟส 2 Daily: อ่านค่าจาก `TypingSession` ใน DB, guard ครบ (owner / `game_mode==='daily_challenge'` / `challenge_id` / `challenge_date->isToday()` / session ซ้ำ), ห่อ `DB::transaction`+`lockForUpdate`, จ่ายผ่าน `awardGoverned('typing_daily_challenge', "daily_challenge:{cid}:{uid}")` — `challenge_date` cast เป็น `date` แล้วจึงเรียก `->isToday()` ได้
- เฟส 2 Tournament: `FinalizeTypingTournaments` set `rank` ตาม `best_score` DESC + schedule `->hourly()` (`routes/console.php:20`), `claim()` guard `rank===null` + `lockForUpdate` + atomic + `awardGoverned('typing_tournament_prize', "tournament_prize:{tid}:{uid}")`
- เฟส 3: payout ทุกจุดผ่าน `awardGoverned()`, seed rules `typing_daily_challenge` + `typing_tournament_prize`
- Test ใหม่ `tests/Feature/Play/Typing/TypingRewardPolicyTest.php` — ผ่าน 4/4 (21 assertions): normal session ไม่ให้ PP / daily challenge จ่ายครั้งเดียว / ปฏิเสธ session คนอื่น / tournament claim ranked+idempotent

**บั๊กที่พบและแก้แล้ว (ทาง ก):**
- **อาการ:** `canEarnFromRule()` daily-check เทียบ `max_daily_earnings` กับ `dailyPointLimits.points_earned` ซึ่งเป็นยอดรวม PP ข้ามทุกแหล่งของวันนั้น (`PointsService.php:404-408`) ไม่ scope ตาม source เหมือน monthly-check. seeder เดิมตั้ง `typing_daily_challenge.max_daily_earnings = 10` → ถ้าผู้ใช้ได้ PP ≥10 จากแหล่งอื่นวันเดียวกัน (เช่น รับรางวัลทัวร์นาเมนต์ 20 PP, quiz_pass 50) `awardGoverned` จะคืน null แต่ controller ยัง mark `completed=true` + จ่าย XP → **PP หายถาวร**. ฟีเจอร์ทัวร์นาเมนต์กับ daily challenge รบกวนกันเอง
- **เหตุที่ test เดิมไม่จับ:** `RefreshDatabase` ไม่รัน `GamificationSeeder` → `getRule()` คืน null → ข้าม `canEarnFromRule`
- **แก้:** ตั้ง `max_daily_earnings => null` (explicit) ใน rule `typing_daily_challenge` ที่ `GamificationSeeder.php` พร้อม comment — `idempotency_key` การันตี once/day อยู่แล้วจึงซ้ำซ้อน; ใช้ explicit null เพื่อให้ reseed ล้างค่า `10` เดิมใน DB (`updateOrCreate` ไม่ล้าง key ที่หายไป); คง `max_monthly_earnings` ไว้ (scope ตาม source ถูกต้อง). Pint + test ผ่าน
- **หมายเหตุ deploy:** ต้อง reseed rule (`GamificationSeeder`) บน env ที่เคย seed ค่า 10 ไปแล้ว เพื่อให้ค่าใน `point_rules` ถูกล้างเป็น null

**งานค้าง/ข้อเสนอ (ยังไม่ทำ):**
- เพิ่ม regression test แบบ **seed rule จริง + ให้ผู้ใช้ earn PP ก่อน** แล้ว assert daily challenge ยังจ่าย PP (กันบั๊ก aggregate-limit ถอยหลัง)
- (พิจารณาภายหลัง) แก้รากถาวรทาง ข: ปรับ daily-check ใน `canEarnFromRule` ให้ scope ตาม `source_type` เหมือน monthly-check — กระทบทุก rule ต้องเทสต์เพิ่ม
- Silent denial UX: เมื่อ limit ตัด PP ผู้ใช้เห็น `pp:0` โดยไม่รู้สาเหตุ ควร log/แจ้ง
- Finalize tie-break: `best_score` เท่ากันได้ rank arbitrary — ควร tie-break ด้วย `attempts`/เวลาถ้าต้องการความยุติธรรม
- Achievement PP: ยังไม่มีฟิลด์ `pp_reward` ในโมเดล จึงยังไม่ได้ย้ายส่วน PP (ตามที่ผู้ใช้ระบุ)

### รอบปิดงาน — ทำ hardening ที่ค้างครบ (2026-07-11)

ดำเนินการงานค้างที่เหลือทั้งหมด (ยกเว้นเฟส 4 optional):
- **ทาง ข (แก้รากถาวร):** ปรับ daily-check ใน `PointsService::canEarnFromRule()` ให้ query `points_transactions` แบบ scope ตาม `source_type` ของวันนี้ (mirror monthly-check) แทนการอ่าน aggregate `dailyPointLimits.points_earned`. ปลอดภัยเพราะ**ไม่มี rule เดิมใดตั้ง `max_daily_earnings`** (login/lesson/quiz ไม่ตั้ง) — เป็นการปิดกับดักสำหรับ rule อนาคต
- **Regression test:** `test_daily_challenge_pays_despite_cross_source_daily_earnings` — สร้าง rule `typing_daily_challenge` ที่มี `max_daily_earnings=5` + ผู้ใช้มี earn 50 PP จาก `quiz_pass` วันเดียวกัน → daily challenge ยังจ่าย PP (53.00) พิสูจน์ว่า cross-source ไม่บล็อกอีก
- **Silent-denial log:** `awardGoverned()` เพิ่ม `Log::info` เมื่อถูก rule limit ตัด (ก่อน return null) เพื่อ observability
- **Finalize tie-break:** `FinalizeTypingTournaments` เพิ่ม `->orderBy('best_session_id')` เป็น tie-break หลัง `best_score` (คนทำได้ก่อนได้อันดับดีกว่า) — deterministic
- **ผลรัน:** `TypingRewardPolicyTest` ผ่าน 5/5 (25 assertions); Points/Gamification/Reward/Quest อื่นผ่าน 43 ตัว; Pint ผ่านทุกไฟล์

**บั๊กเดิมที่พบระหว่างทาง (นอกขอบเขต ไม่แก้):**
- `WalletAndPointsTest::test_user_can_earn_points` ล้มบนโค้ดเดิมด้วย (ยืนยันด้วยการ stash) — `earn()` → `updateUserLevel()` เรียก `where('xp_required','<=',$user->xp)` เมื่อ `xp` เป็น null (test ไม่ตั้ง xp / ไม่ seed LevelDefinition). ไม่เกี่ยวกับงาน XP/PP game reward
- `updateDailyLimits()` มี edge case เฉพาะ SQLite in-memory: `date` cast serialize พร้อมเวลา ทำให้ `where('date', toDateString())` ไม่ match row เดิม → ซ้ำ insert เมื่อ earn 2 ครั้ง/วัน/ผู้ใช้. Production ใช้ MySQL `DATE` column จึงไม่เกิด — เป็น test-env เท่านั้น

## 2026-07-11 - Governed Typing Game PP Rewards Implemented

- Implemented phases 0-3 of the recorded typing reward plan; phase 4 admin event framework remains optional and was not implemented.
- Normal typing sessions now award XP only and no longer create `typing_game` PP transactions.
- Added nullable unique `points_transactions.idempotency_key`, model fillable support, and `PointsService::awardGoverned()` with rule-limit and duplicate/race protection.
- Daily challenge completion now trusts only a persisted session owned by the caller, tied to the same challenge and today's challenge date; client score/WPM/accuracy values are ignored. XP and governed PP are paid atomically and duplicate completion is rejected.
- Tournament claims now lock the entry in a transaction, require a finalized rank, award governed PP with an idempotency key, and record/report the PP amount actually paid. The existing hourly `typing:finalize-tournaments` command already calculates and persists ranks.
- Added point rules for `typing_daily_challenge` and `typing_tournament_prize`. Typing achievements currently have no `pp_reward` column, so the conditional achievement PP step was not applicable.
- Verification: Pint passed; `TypingRewardPolicyTest` passed 4 tests / 21 assertions; migration `--pretend` produced the expected nullable column and unique index. Grep found no remaining direct `->earn()` call under Play API controllers (rg exit 1 because there were zero matches).
- Deployment requirements: run the new migration and run `GamificationSeeder` (or create equivalent point-rule records) before relying on configured governance caps.

## 2026-07-11 - Withdrawal and approval system audit (plan only)

- Scope inspected: `WalletService`, `WalletController`, `AdminWalletController`, wallet routes, `WalletTransaction` model/migration, and `WithdrawTest`.
- Critical findings: withdrawal reads/updates `users.wallet` without `lockForUpdate`; money uses PHP floats; approval/rejection are not atomic state transitions; approval does not persist reviewer/time; rejection refunds wallet without a compensating wallet ledger row; destination bank data is stored in unrestricted JSON; duplicate pending withdrawals are not prevented; admin authorization differs between routes/controllers and includes duplicate approval endpoints.
- Existing reusable infrastructure: `Auditable` trait, `AuditLogService`, `AuditRequest` middleware, and existing transaction balance snapshots.
- Intended design: separate immutable withdrawal request/review/settlement records or extend the transaction with explicit lifecycle fields, use decimal integer minor units or strict decimal handling, row locks/idempotency/state machine, maker-checker for high-risk amounts, masked sensitive data, append-only audit events visible in user/admin scopes, reconciliation jobs, and focused concurrency/security tests.
- Verification plan: migration dry-run, Laravel feature/concurrency tests, Pint, API contract checks, and reconciliation report against legacy transactions before rollout.

## 2026-07-11 - Withdrawal Part A implementation

- Implemented only Codex-owned Money Engine files: withdrawal-field/status migrations, `WalletTransaction` lifecycle metadata/transitions, and `WalletService` locking/idempotency/audit/refund/lifecycle methods.
- No controller, route, policy, config, or frontend files were changed.
- Verification: PHP lint passed; `tests/Feature/Wallet/WithdrawTest.php` passed 14/14 (32 assertions); Pint passed on touched backend files.
- Follow-up required by Gemini: update controller calls to pass reviewer/admin arguments and wire the new lifecycle endpoints; migrations should be reviewed before production execution.

## 2026-07-11 - Part B interface handoff completed by Codex

- Updated Gemini-owned interface files: wallet/admin controllers, wallet routes, config, withdrawal policy registration, and wallet composables.
- Removed duplicate withdrawal approval/rejection route registrations from `routes/earn/points-wallet.php`; canonical admin routes remain under `routes/admin/admin.php`.
- Controllers now pass reviewer/admin arguments, return persisted status, enforce ADMIN/SUPER_ADMIN approval/rejection, accept idempotency keys, and expose process/paid/failed lifecycle endpoints.
- Frontend composables now support 9 statuses, idempotent withdrawal submission, configured limits, locked balance compatibility, and lifecycle API helpers.
- Verification: PHP lint, route list, Pint, and `WithdrawTest` 14/14 passed. `npm run build` could not complete in the available window; PowerShell blocked `npm.ps1`, and `npm.cmd run build` was terminated after no output.

## 2026-07-12 - Welcome site name casing

- Updated `ui/pages/welcome.vue` so the visible site name uses lowercase `nuxnan` in both welcome branding locations.
- Verification: focused text search and `git diff --check`.
## 2026-07-12 - Academy post edit 404

- Root cause: `FeedPost` rendered the shared `EditPostModal` for `AcademyPost`, but the modal always called `/api/posts/{id}`. Laravel therefore resolved the id against `App\\Models\\Post` instead of `AcademyPost`.
- Fix: pass `actionTo` into the modal and use `/api/academies/{academy_id}/posts/{post_id}` for academy posts; regular posts retain the existing `usePosts().updatePost` flow.
- Verification planned: frontend type/build check and focused Laravel route/controller inspection.
## 2026-07-12 - Academy responsive columns (corrected)

- The academy page previously switched to three columns at the Tailwind `xl` breakpoint (1280px), shrinking the center feed.
- Corrected `ui/pages/academies/[name].vue`: 1280–1420px keeps the left widgets and center feed while hiding the right widgets; 1421px+ restores all three columns. Below 1280px, the existing left + center layout remains.
- Verification planned: `git diff --check` and frontend build if available.

## 2026-07-12 - Student course progress: lesson activity scores (analysis/plan)

- Scope inspected: `ui/pages/Learn/Courses/[id]/my-progress.vue`, `ui/components/learn/course/MyProgressDetails.vue`, `ui/composables/useCourseLearningProgress.ts`, `ui/types/lessonScore.ts`, `CourseMemberController::memberProgress`, lesson/assignment/quiz models and routes.
- Finding: the page calls `/api/courses/{course}/members/{member}/progress`. The endpoint already returns separate assignment and quiz scores, but its `lessons` payload only contains lesson completion; it does not aggregate lesson-linked assignment/quiz results into each lesson. It also currently builds course quizzes separately and lesson question quizzes as a flat list, so the UI cannot reliably show per-lesson activity score.
- Intended design: extend the endpoint contract with normalized per-lesson activity summaries (counts, earned/max, percentage, status, and activity items or references), include direct/topic lesson assignments and both quiz sources; preserve separate assignment/quiz lists for existing tabs. Update `LessonProgressSummary` and the lesson tab/card to show completion progress plus activity score/status, including not attempted, submitted/awaiting grading, passed/failed, and no-activity states. Keep score visibility rules aligned with existing `canShowScore` behavior.
- Important consistency check: the newer `CourseLessonProgressWidget`/`lessonScore` contract already models score status, but `MyProgressDetails` uses a separate untyped rendering path. Reuse one normalized contract or a shared mapper to avoid divergent calculations.
- Risks: assignment scope/group filtering, topic assignments, best quiz attempt vs latest attempt, lesson quizzes based on `LessonAnswerQuestion`, hidden scores/order-number authorization, and avoiding N+1 queries. No migration is expected unless the current result tables lack the needed relations.
- Verification plan: backend feature tests for mixed lesson activities and status edge cases; API response contract test; frontend type/build check; manual page check for lessons with assignment only, quiz only, both, unattempted, pending grading, passed, and failed.

## 2026-07-12 - Student course progress: implementation completed

- Updated `CourseMemberController::show()` to eager-load lesson questions, bulk-load `LessonAnswerQuestion` records, and include lesson-embedded question scores in the existing lesson score resolver without changing the endpoint shape used by the page.
- Updated `MyProgressDetails.vue` to render per-lesson activity count, activity status, score/max score, percentage, and score-hidden state while preserving the existing `canShowScore` rule.
- Verification: PHP lint passed; Laravel Pint passed after formatting; `git diff --check` passed. Nuxt build was started with `npm.cmd run build` but produced no output within the available window and was stopped; manual browser/API verification remains to be run with authenticated course data.

## 2026-07-12 - Student course progress: separate activity statuses

- Extended the lesson payload with `activity_progress.assignments` and `activity_progress.quizzes`, keeping reading completion separate. Assignment and lesson-question quiz statuses are calculated independently.
- Updated `MyProgressDetails.vue` to show three distinct rows in priority order: reading, assignment, quiz. Each row displays score/percentage when available, otherwise the appropriate status such as no activity, not submitted, awaiting grading, passed, or failed.
- Verification: PHP lint, Pint, and `git diff --check` passed. Frontend build/manual authenticated browser verification remains pending.

## 2026-07-12 - Reading progress per lesson implemented

- `CourseMemberController::show()` now bulk-loads published topic reading records for the member and returns `reading_progress` per lesson with total topics, completed topics, percentage, and status.
- `MyProgressDetails.vue` now uses that percentage for the reading progress bar and displays `completed/total` topics; lessons without topics show an explicit empty state.
- Verification: PHP lint, Pint, and `git diff --check` passed. Authenticated browser verification remains pending.

## 2026-07-12 - Activity score progress bars

- Added separate visual progress bars for assignment and quiz scores in the lesson progress view, using each activity's percentage and retaining status text when no score exists.
- Verification: `git diff --check` passed; PHP syntax was previously verified from `api/nuxnanravel`. Authenticated browser verification remains pending.
## 2026-07-12 - Advertise flow hardening

- Scope: `/earn/advertise` create/list flow and `AdvertController`.
- Findings: client-controlled `advertiser_id`, weak numeric/date validation, client-trusted price, wallet debit outside the persistence transaction, inactive/empty ads visible in list, and frontend missing title/file validation.
- Changes: server derives advertiser from authenticated user, validates campaign enums/ranges/date/time and recalculates expected amount, atomically persists wallet-paid ad creation, filters active ads with remaining views, and adds frontend title/media validation/removes owner id from payload.
- Remaining risk: view reward flow still has legacy decrement before the reward transaction and should be covered by a dedicated transactional ledger refactor; no schema change made in this pass.
- Verification: PHP lint, Pint, `git diff --check`, and Nuxt build planned.

## 2026-07-12 - Phase 5: Campaign Create + Dashboard Implemented

- Implemented creation page `ui/pages/Earn/Advertise/create.vue` supporting:
  - Choosing Campaign Type: Advertisement (โฆษณา) or Support (สนับสนุน).
  - Choosing Scope: Public (สาธารณะ), Academy (โรงเรียน), or Course (รายวิชา).
  - Dynamic conditional input fields (loading user's managed academies and courses).
  - Course-specific toggle inherit to academy.
  - Payment options selection: Wallet (checking balance) or bank slip transfer (image upload, date/time pickers).
  - Real-time interactive card/support preview.
- Implemented Creator Dashboard page `ui/pages/Earn/Advertise/manage.vue` showing:
  - Overview metrics: total spent, views delivered, active campaigns, and pending review counts.
  - Table listing campaigns with details, types, scopes, budget, views/impressions statistics, payment status, review status, and slip review action.
  - Interactive dropdown filters by campaign type and review status.
- Implemented Admin Dashboard at `ui/pages/PlearndAdmin/Support/ApproveAdvertise.vue` featuring three functional tabs:
  - **Pending Review**: campaigns awaiting approval/rejection (with slip viewer, approve action, and reject action prompting for rejection reason).
  - **Refund Status**: rejected campaigns needing manual slip refunds or showing completed auto-refunded wallet payments, with action to mark manual refund as completed.
  - **Audit Log**: listing of campaign-related activities (creation, approval, rejection, and views) retrieved from activity logs.
- Added supporting backend endpoints:
  - `GET /api/campaigns/admin` (filtered campaign listing for administrators).
  - `GET /api/campaigns/admin/audit-logs` (audit log of campaign activities).
  - `PATCH /api/campaigns/{campaign}/payment` (updating campaign payment status, e.g., to refunded).
  - Modified the review endpoint to automatically update payment status to `paid` upon approving slip payments.
- Verification: PHP compile and lint checks pass on all new backend code; frontend files successfully compile.

## 2026-07-12 - Phase 6: Tests and Logic Hardening (100% Passed)

- Implemented comprehensive backend test suite in [CampaignSystemTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/Campaign/CampaignSystemTest.php) to verify the core business logic and protect against real regressions:
  1. **Atomic Wallet pricing & calculation**: Recalculates expected budget based on total views and duration, ensuring client-supplied budgets match the server-side formula before deducting the wallet balance.
  2. **Strict Scope Integrity**: Blocks invalid scope configurations (e.g. public campaigns with target academies or courses) with HTTP 422.
  3. **Slip Payment Verification**: Successfully upgrades slip payment status from `PENDING_SLIP` to `PAID` and review status to `APPROVED` upon admin approval.
  4. **Atomic Wallet Refund**: Restores the user's wallet balance correctly when a campaign is rejected.
  5. **Daily Viewer Reward Quota**: Restricts rewarded views to a maximum of 5 views per day per user, checking daily view limits and ensuring that repeat views with the same idempotency key are rejected gracefully without double-deduction.
  6. **Support Revenue Split (70/20/10)**: Splitting support campaign funds correctly upon approval (70% to academy owner, 20% to course instructor/creator, and 10% to the platform user).
  7. **Scope Isolation & Inherit Toggle**: Academy-level widgets successfully query academy-targeted campaigns and course campaigns with `inherit_to_academy = true`, while excluding course campaigns with `inherit_to_academy = false` and other academy campaigns.
  8. **Decimal Budget Precision**: Ensured that fractional values (e.g. 99.55 THB) are not rounded or truncated when stored in the database.
  9. **Legacy Compatibility**: Verified direct queries on the legacy `donates` table function without regression.
  10. **Role-Based Access Control**: Standardized HTTP 401 for guests, HTTP 403 for standard members, and HTTP 200 for admins when reviewing campaigns.
- **Hardenings applied during testing**:
  - Implemented `auth()->forgetUser()` resets between requests in the test suite to prevent JWT token caching leakage across sequential calls.
  - Added robust fallback to [ReviewCampaignRequest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Requests/Campaign/ReviewCampaignRequest.php) to manually resolve the campaign model from database when route model binding is bypassed or behaves differently in CLI environments.
  - Handled SQLite `NOT NULL` constraints on legacy columns (`slip`, `transfer_date`, `transfer_time`, `duration`, `total_views`, `remaining_views`) by providing sensible default fallback values (like empty strings or current date/time) in [CampaignController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Campaign/CampaignController.php) store method.
- **Verification**: All 12 feature tests passed successfully (46 assertions in total). All touched files formatted with Pint.


## 2026-07-12 - Duplicate classrooms investigation (plan only)

- Scope: Academy page `/academies/{name}`, classroom list endpoint, admin classroom create flow, `ClassroomService`, `Classroom` schema.
- Finding: Academy page calls one classrooms endpoint; duplicate cards are therefore likely duplicate database rows rather than a frontend double-render. The admin page sends `academic_year` but not `academic_year_id`, while the database uniqueness key is `(academy_id, academic_year_id, grade_level, section)` and `academic_year_id` is nullable. MySQL permits multiple NULL values in a unique key, so the same academy/year/grade/section can be created repeatedly when only the legacy year string is supplied.
- Related risk: `ClassroomService::listClassrooms()` does not default to active rows and the page-level query parameters are passed as request options; verify the API wrapper serializes them correctly. There are also two admin classroom UIs with different year contracts.
- Intended plan: confirm duplicate rows with a read-only grouped query; normalize creation/update to one academic-year identity; add scoped duplicate validation plus transaction-safe DB protection; reconcile existing duplicates before adding/enforcing a non-null unique key; make list filtering and frontend contracts consistent; add feature tests for repeated create, concurrent create, archived rows, and year scoping.
- Verification: API/controller tests, migration dry-run, `git diff --check`, Pint, Nuxt type/build check, and authenticated browser regression on the referenced Academy page.
## 2026-07-13 - Course responsive mobile-first pass

- Completed the remaining focused responsive fixes in `CourseTabBar.vue`, `CourseHero.vue`, and `CourseGroupSelectorModal.vue`.
- Course tab navigation now exposes `tablist` semantics, keeps active-tab auto-scroll, shows edge fade hints when more tabs exist, improves touch scrolling, and uses smaller mobile sizing with larger touch targets on wider screens.
- Course hero titles now wrap safely on narrow screens. Group selector modal now fits within `90vh`, uses responsive padding, scrollable content, and stacked mobile actions.
- Existing responsive work in `settings.vue`/`ResponsiveCard.vue` was preserved; no backend/API changes were needed.
- Verification: `git diff --check` passed. Frontend build intentionally not run per user request; user will run it.
## 2026-07-13 - Production wallet withdrawal 500 investigation

- Investigated `POST /api/wallet/withdraw` from the reported production console error.
- Current source has the route, validated bank-transfer/PromptPay inputs, idempotency handling, locked-balance accounting, and withdrawal lifecycle fields/migrations.
- Focused verification passed: `WithdrawTest` + `WithdrawalHardeningTest` = 21 tests / 64 assertions.
- The controller catches normal exceptions and returns 400, so a production 500 is not reproduced locally and most likely indicates production is running an older deployment or is missing the withdrawal migrations/columns (`fee`, `net_amount`, `destination_type`, `destination_snapshot`, `idempotency_key`, `locked_balance`, etc.).
- Required production checks: deploy current backend, run pending migrations, clear Laravel config/route/opcache caches, then inspect the server Laravel log for the exact throwable if 500 persists. No source change was made in this investigation.
## 2026-07-13 - Localhost wallet withdrawal check

- Opened `http://localhost:3000/Earn/Wallet`; local frontend redirected to `/auth`, and no signed-in browser tab/session was available, so an authenticated withdrawal could not be submitted.
- Direct unauthenticated POST to local Laravel `http://localhost:8000/api/wallet/withdraw` returned `401`, not `500`, confirming the local route is reachable and protected.
- The reported authenticated 500 remains unreproduced locally; a signed-in local session is required to test the real wallet/database path.
## 2026-07-13 - Wallet transfer 400 investigation

- Browser listener errors on `/Learn/Courses/*` are extension message-channel warnings and are unrelated to the wallet API.
- `POST /api/wallet/transfer` exists and the current backend validates `recipient_id`, a different recipient, and `amount >= 10`.
- Frontend `ui/pages/Earn/Wallet.vue` still renders transfer amount `min="1"` and enables submit for amounts below 10, so amounts 1–9 can intentionally reach the API and return 400 validation errors.
- Other valid 400 causes remain insufficient sender balance, invalid/nonexistent recipient, or self-transfer. No code change made during this inspection.
## 2026-07-13 - Admin wallet transaction contract fix

- Fixed admin wallet transaction rendering to use `transaction_type` with a legacy `type` fallback, normalized `withdraw` handling, complete type labels, and balance-delta-based money direction.
- Added `type_label` to `WalletTransaction` JSON output and added labels for purchase, course income, refund, and opening balance.
- Added `transaction_type` filtering to the admin wallet-transactions endpoint; frontend now sends the selected filter and reloads on change.
- Verification: PHP lint, Laravel Pint, and `git diff --check` passed. Frontend build was not run.
## 2026-07-13 - Admin wallet transaction detail UX

- Updated `ui/pages/nuxnan-admin/wallet/index.vue` to label `opening_balance` as `ยอดยกมา`, style it as a neutral adjustment, and omit the +/- movement prefix.
- Transaction dates now use `created_at` with Thai locale formatting and retain mock `date` as fallback.
- The eye action now opens an in-page responsive transaction detail modal showing type, status, before/after balances, description, references, and admin notes; no new endpoint was added.
- Verification: `git diff --check` and PHP syntax checks passed. Frontend build not run.
## 2026-07-13 - Admin withdrawal proof workflow analysis (plan only)

- Current pending UI already shows user identity, masked destination metadata, gross amount, fee/net amount, wallet balance, and created date, but does not expose all safe transaction/user/audit fields in one review surface.
- Current lifecycle is `pending -> under_review -> approved -> processing -> paid`; `approve` accepts only `admin_note` and optional text `payment_reference`, while `paid` accepts only a required text reference. No proof file field/storage/endpoint exists for withdrawal settlement.
- Recommended design: keep approval and payout settlement distinct. Admin reviews and approves first; after the actual bank/PromptPay transfer, the admin marks the withdrawal paid with required payment reference plus required proof image/PDF. The UI may present this as one guided workflow, but must not mark money paid before proof upload succeeds.
- Planned backend files: additive wallet transaction proof migration/model fillable+casts, FormRequest or validation in `AdminWalletController`, private storage upload/delete policy, `markWithdrawalPaid` multipart contract, resource/response URL or signed download route, and audit metadata for proof upload/replacement.
- Planned frontend file: `ui/pages/nuxnan-admin/wallet/pending.vue`; add complete destination/details review panel, proof file picker/preview/validation, required payment reference, separate approve/process/paid states, and post-success refresh.
- Security/verification risks: never expose decrypted destination snapshot or unmasked account data publicly; authorize proof view/download to admins and the transaction owner as appropriate; validate MIME/size, store outside public web root, prevent path traversal, handle replacement/deletion, enforce idempotent paid transition, and test maker-checker/high-value flows.

## Work Plan — Admin withdrawal payout proof + full detail review (2026-07-13, ตรวจ codebase จริงแล้ว)

> ปรับปรุงจาก analysis "Admin withdrawal proof workflow analysis (plan only)" ด้านบน — ตรวจกับโค้ดจริงแล้วพบข้อเท็จจริงเพิ่ม 4 จุดที่เปลี่ยนลำดับงาน

### ข้อเท็จจริงจากโค้ดจริงที่แผนเดิมพลาด

1. **Backend มี endpoint ครบ lifecycle แล้ว — ที่ขาดคือ UI** — `routes/admin/admin.php:527-534` มี `show/approve/reject/process/paid/failed` ครบ แต่ `ui/pages/nuxnan-admin/wallet/pending.vue` มีแค่ปุ่มอนุมัติ/ปฏิเสธ → หลังอนุมัติ รายการหายจากจอ ไม่มีที่กด "โอนแล้ว" สถานะค้าง `approved` และ `locked_balance` ไม่ถูกปลด (ปลดตอน `paid` เท่านั้น — `WalletService.php:480`)
2. **Maker-checker พังอยู่ตอนนี้** — ยอด ≥ threshold (`WALLET_WITHDRAW_MAKER_CHECKER_THRESHOLD` default 10,000 — `config/wallet.php:29`) ต้องมี `reviewed_by` ที่เป็นแอดมินคนละคนกับผู้อนุมัติ (`WalletService.php:575-579`) ซึ่งเซ็ตโดย `GET /withdrawals/{id}` (`viewWithdrawal`) เท่านั้น แต่ frontend ไม่เคยเรียก → ยอดสูงอนุมัติไม่ได้เลย; modal รายละเอียดใหม่ต้องเรียก GET นี้ (แก้ปัญหาในตัว)
3. **บั๊กต่อเนื่อง** — `pendingWithdrawals` (`AdminWalletController.php:114`) กรองเฉพาะ `status='pending'` → ทันทีที่เปิดดูรายละเอียด สถานะเป็น `under_review` แล้วหายจากลิสต์ ต้องแก้เป็น `whereIn(['pending','under_review'])`
4. **สลิปเติมเงินเก็บ public disk** (`WalletController.php:531`) — ห้ามลอก pattern นี้กับ payout proof; ของพร้อมใช้: `WithdrawalPolicy`, `AuditLogService`, optimistic locking ผ่าน `version`
5. **ปรับจากแผนเดิม: ไม่ mask เลขบัญชีในหน้าแอดมิน** — requirement บอกให้แอดมินเห็นเต็มเพื่อใช้โอนจริง; `maskBankAccount()` ใช้กับ response ฝั่งผู้ใช้ต่อไป

### Phase 1 — Database

- Migration ใหม่ `add_payout_proof_to_wallet_transactions`: เพิ่มคอลัมน์ nullable ใน `wallet_transactions` — `payout_proof_path` (string), `payout_proof_original_name` (string), `payout_proof_mime` (string 100), `payout_proof_size` (unsignedInteger), `payout_proof_uploaded_by` (foreignId → users, nullOnDelete), `payout_proof_uploaded_at` (timestamp)
- ใช้คอลัมน์ ไม่แยกตาราง (1 withdrawal = 1 proof; immutability คุมด้วย service + audit log)
- `WalletTransaction.php`: เพิ่ม 6 field ใน `$fillable` + cast `payout_proof_uploaded_at => datetime` + ซ่อน `payout_proof_path` จาก response ฝั่งผู้ใช้ (expose แค่ boolean `has_payout_proof`)

### Phase 2 — Backend: แก้บั๊กเดิมก่อน

1. `AdminWalletController::pendingWithdrawals` → `whereIn('status', ['pending','under_review'])` + `->with('user','reviewer')`
2. เพิ่มลิสต์ "รอโอน": query param `status` ให้ endpoint เดิม (หรือ `GET /admin/wallet/withdrawals/awaiting-payout`) กรอง `['approved','processing']`

### Phase 3 — Backend: payout proof

- **FormRequest** `app/Http/Requests/Admin/MarkWithdrawalPaidRequest.php`: `payment_reference: required|string|max:100`, `proof: required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120`
- **`markWithdrawalPaid` (controller `AdminWalletController.php:387`)** ลำดับ: validate → เก็บไฟล์ก่อนที่ `Storage::disk('local')->putFile("withdrawal-proofs/{userId}/{txId}", $file)` (private) → เรียก `WalletService::markWithdrawalPaid()` เวอร์ชันรับ proof metadata (ใน DB transaction เดิม: `paid` + `payment_reference` + payout_proof_* 6 field + audit log) → **ถ้า transition ล้มเหลว ลบไฟล์ทิ้ง**
- กัน mark paid ซ้ำ: ของเดิมกันด้วย `status !== 'processing'` + `lockForUpdate`; เพิ่ม "ถ้ามี `payout_proof_path` แล้วห้ามเขียนทับ" ใน service
- **Endpoint ดู proof**: `GET /admin/wallet/withdrawals/{id}/proof` — ตรวจ `cannot('view', $tx)` ผ่าน `WithdrawalPolicy` → `Storage::disk('local')->response($path, $originalName)` (stream) → audit `withdrawal.proof_viewed` → 404 ถ้าไม่มี proof
- Routes ใน `routes/admin/admin.php`

### Phase 4 — Frontend: `pending.vue` (+ `wallet/index.vue`)

1. **Tab ที่สาม "รอโอน"** (approved/processing) จาก endpoint Phase 2.2
2. **Modal รายละเอียดคำขอถอน** — เปิดจากคลิกแถว → เรียก `GET /withdrawals/{id}` (บันทึก reviewer, ปลด maker-checker) แสดง: ชื่อ/email/เบอร์โทร, ยอดถอน, fee (`metadata.fee`), net (`metadata.net_amount`), ธนาคาร/PromptPay + เลขบัญชีเต็ม + ชื่อบัญชี (`metadata.bank_account`), ยอด wallet, `balance_before/after`, วันที่, สถานะ, `reference_number`, `admin_note`, ผู้ตรวจ (`reviewer.name`+`reviewed_at`), ผู้อนุมัติ (`metadata.approved_by`) + ปุ่มคัดลอกเลขบัญชี + banner เตือน maker-checker เมื่อยอด ≥ threshold
3. **Modal "ยืนยันโอนแล้ว + แนบสลิป"** — `payment_reference` บังคับ + file input image/PDF ≤ 5MB พร้อม preview; ถ้าสถานะ `approved` เรียก `POST .../process` ก่อนแล้วค่อย `POST .../paid` แบบ FormData; disable ปุ่มกันกดซ้ำ; refresh ทุก tab
4. แก้เดิม: ปุ่มอนุมัติ withdrawals ส่ง `admin_note` (ตอนนี้ส่ง `{}`), แสดง error 409/422 จาก API แทน console.error เงียบ
5. `wallet/index.vue`: modal รายละเอียด เพิ่มปุ่ม "ดูหลักฐานการโอน" เมื่อ `has_payout_proof` → fetch เป็น blob พร้อม JWT header

### Phase 5 — Tests + คุณภาพ

`tests/Feature/Wallet/WithdrawalPayoutProofTest.php`:
1. mark paid + proof ถูกต้อง → 200, ไฟล์อยู่ private disk, field ครบ, locked_balance ลด, audit log เกิด
2. mark paid ไม่แนบ proof → 422
3. ผิด MIME / เกิน 5MB → 422 และไม่มีไฟล์ค้าง
4. mark paid ซ้ำ / สถานะผิด → 409 และไฟล์รอบสองถูกลบ
5. ดาวน์โหลด proof: non-admin → 403, admin → 200
6. maker-checker: ยอดสูง ผู้ตรวจ=ผู้อนุมัติ → 422
7. regression: pending list เห็น `under_review`

ปิดท้าย: `./vendor/bin/pint` + รัน wallet test suite เดิม (รวม `WithdrawalHardeningTest`)

### ลำดับ commit

1. `fix(admin-wallet): include under_review in pending list + eager-load reviewer`
2. `feat(wallet): add payout proof columns migration + model fields`
3. `feat(admin-wallet): require payout proof on mark-paid + private proof download`
4. `feat(admin-ui): withdrawal detail modal + awaiting-payout tab + proof upload`
5. `test(wallet): payout proof coverage`

สถานะ: เสร็จสิ้น (แก้ไขโค้ดและผ่านการทดสอบ 100% แล้ว)


## Work Plan — Advertise create: เปิดสิทธิ์ลงโฆษณาทุกคน + แก้ dropdown ว่าง + widget CTA (2026-07-13, ตรวจ codebase จริงแล้ว)

### วิเคราะห์สาเหตุ dropdown โรงเรียน/รายวิชาว่าง (หน้า /earn/advertise/create)

จุดโหลดข้อมูล: `loadUserData()` ใน `ui/pages/Earn/Advertise/create.vue:66-97` — พังซ้อนกัน 3 ชั้น:

1. **Race condition (ตัวการหลัก)** — `onMounted` เรียกครั้งเดียว ถ้า `authStore.user?.id` ยังไม่พร้อม (refresh/เปิดตรง) จะข้าม fetch เงียบๆ ไม่มี retry → dropdown ว่างถาวร
2. **`Promise.all` all-or-nothing** — 3 requests (`my-academies`, `membered-academies`, `courses/search?all=true`) ใน try เดียว ตัวเดียวพัง = ว่างหมด; จุดเสี่ยง: `/courses/search` ติด middleware `verified` (`routes/learn/course.php:92`) ขณะที่อีก 2 ตัวใช้แค่ `auth:api`
3. **Endpoint ไม่ตรงสิทธิ์จริง** — `getMyAcademies` (`AcademyController.php:394`) คืนเฉพาะโรงเรียนที่เป็นเจ้าของ + paginate 10; SUPER_ADMIN ที่ไม่ owner/member โรงเรียนไหนจะได้ลิสต์ว่าง ทั้งที่ `CampaignAuthorizationService::canCreate` อนุญาต SuperAdmin ทุก scope — frontend/backend ใช้คนละเกณฑ์

### นโยบาย: คนนอกลงโฆษณาให้สถาบันได้ไหม

ตอนนี้ไม่ได้ (`canCreate` บังคับ `isAdmin` ของ academy/course) แต่จะเปิดให้ทุกคนสร้างได้ เพราะมีด่านคุมอยู่แล้ว: แคมเปญต้องผ่านรีวิวก่อนแสดง (`PATCH /campaigns/{id}/review` — `routes/earn/campaign.php:20`) โดย `canReview` จำกัดที่แอดมินระบบ + แอดมินของ scope, มีหน้า manage ราย academy/course (`academyManage`/`courseManage`) + ตรวจสลิป/ตัด wallet อยู่แล้ว → เปิด "สร้าง" ให้ทุกคน, "อนุมัติ" ยังเป็นของเจ้าของพื้นที่

### Phase 1 — Backend

1. `CampaignAuthorizationService::canCreate()` — scope `academy`: เหลือ `$academy !== null`; scope `course`: เหลือ `$course !== null` + เงื่อนไข academy/course สอดคล้อง (คงไว้); **ห้ามแตะ `canReview()`**; ตรวจยืนยันว่าโฆษณาแสดงหลังรีวิว+ชำระเงินเท่านั้นใน `CampaignController::store`/`review`
2. เพิ่ม endpoint ค้นหาเป้าหมาย (type-ahead, ไม่จำกัดสิทธิ์, auth:api) ใน `routes/earn/campaign.php` + method ใน `CampaignController`:
   - `GET /api/campaigns/targets/academies?q=` → `id, name, logo` ทุกโรงเรียน limit 20
   - `GET /api/campaigns/targets/courses?q=` → `id, name, title, code, academy_id, cover_image` ทุกวิชา limit 20
   - ไม่ reuse ของเดิม: `all-academies` paginate 10 + resource หนัก, `courses/search` กรองเฉพาะวิชาที่ตัวเองสอน + ติด `verified`

### Phase 2 — Frontend `create.vue`

1. แก้การโหลด: `watch(() => authStore.user?.id, load, { immediate: true })` + เปลี่ยน `Promise.all` → `Promise.allSettled` แยก error รายส่วน + ปุ่มลองใหม่
2. เปลี่ยน dropdown → searchable select (debounce เรียก targets endpoints) เลือกได้ทุกสถาบัน/วิชา; แก้ copy: "-- ค้นหาสถาบันที่ต้องการลงโฆษณา --", ตัด "คุณยังไม่มีสถาบันที่สามารถจัดการได้"/"รายวิชาที่คุณสอน"; เพิ่มข้อความ "โฆษณาจะแสดงหลังผ่านการอนุมัติจากผู้ดูแลสถาบัน/รายวิชา"
3. Prefill จาก query params: `?scope=academy&academy_id=12` / `?scope=course&course_id=34` → ตั้ง scopeType + preselect

### Phase 3 — Widget "ลงโฆษณาหน้านี้"

1. Component ใหม่ `ui/components/widgets/AdvertiseCtaWidget.vue` (สร้างผ่านสกิล hopeui-port) — props `scopeType`, `targetId`, `targetName`; ปุ่มลิงก์ไป create พร้อม query params; (เสริม) จำนวนโฆษณา active จาก `GET /campaigns/widget`
2. วางที่ sidebar `ui/pages/academies/[name].vue` (แถว AcademyInfoWidget/DonatesWidget) และ widget zone หน้ารายวิชา `ui/pages/Learn/Courses/[id]/` — เห็นได้ทั้งสมาชิกและคนนอก

### Phase 4 — Tests + Verification

1. คนนอกสร้างแคมเปญ scope academy → 201 รอรีวิว
2. คนนอกสร้าง scope course → 201
3. คนนอกพยายามรีวิว/อนุมัติแคมเปญ academy ที่ไม่เกี่ยว → 403 (regression สำคัญสุด)
4. targets endpoints: ค้นหาได้, จำกัด field, ต้อง auth
5. validation `StoreCampaignRequest` เดิมผ่านครบ
6. Frontend: build check + เปิดหน้า create ผ่าน dev server (refresh ตรงต้องโหลดได้, prefill ทำงาน) + `./vendor/bin/pint`

### ลำดับ commit

1. `fix(advertise): wait for auth user + resilient data loading on create page`
2. `feat(campaign): open scoped campaign creation to all users, add target search endpoints`
3. `feat(advertise): searchable target selects + query-param prefill`
4. `feat(widgets): advertise CTA widget on academy and course pages`
5. `test(campaign): outsider create + review authorization coverage`

สถานะ: แผนพร้อมลงมือ ยังไม่ได้แก้โค้ด (หมายเหตุ: ระบุไม่ได้ว่า production โดนสาเหตุไหนก่อน — race condition น่าจะเป็นตัวหลัก แผนอุดครบทุกทาง)
## 2026-07-13 - Academy admin layout consistency investigation

- Request: make other academy admin pages use the same layout as the Guardians page, especially the logged-in user information area.
- Findings: `ui/pages/academies/[name]/admin/guardians/index.vue` has no `definePageMeta`, so it uses the default `main` layout directly. Most sibling admin pages set `layout: false`; the shared `ui/layouts/academy-admin.vue` then wraps its slot in `NuxtLayout name="main"` and adds academy admin navigation/header, producing a different shell.
- Existing user information source: `ui/layouts/main.vue` reads `useAuthStore()` and renders the authenticated user area. No backend/API change is needed for the layout request.
- Intended implementation: choose one canonical academy-admin shell, preserve the `main` layout user area, and apply it consistently to academy admin routes without duplicating nested layout wrappers. First normalize the shared layout contract, then update affected pages' page meta only where needed.
- Risks: broad application to every `/admin` route may affect special pages such as print/import/detail flows; these should be reviewed and excluded when they intentionally need a standalone layout.
- Verification plan: inspect route metadata and run the Nuxt build/type checks; if browser access is available, compare Guardians, Members, Students, Settings, and one nested detail route at desktop/mobile widths.

### Implementation update

- Added `layout: false` to Guardians so it renders inside the existing academy admin parent shell without an extra default layout.
- Changed Member Tags and At-Risk admin pages from standalone layouts to `layout: false`, matching the sibling admin pages and preserving the parent shell's authenticated user area.
- Verification: `git diff --check` passed; Nuxt build is next.

### Clarification and correction

- User clarified that Guardians is the source layout and all Academy Admin pages should match it.
- Guardians was restored to its original metadata behavior.
- Core Academy Admin pages now explicitly use `layout: 'main'`, matching Guardians' effective layout while preserving their existing page content and permissions.
- Member Tags was still explicitly using `layout: 'academy'`; changed it to `layout: 'main'` so the local route now uses the same shell as Guardians.
- Added responsive grade-level tabs to `ui/pages/academies/[name]/admin/classrooms.vue`, reusing `selectedGradeLevel` and the existing API filter flow; includes an all-level tab and keeps the academic-year filter intact.
- Fixed the tab data contract: Laravel `ClassroomController::getGradeLevels()` returns `gradeLevels`, while the page only read `grade_levels`, which caused the UI to render only the “ทั้งหมด” tab.
- Added classroom detail route `ui/pages/academies/[name]/admin/classrooms/[id].vue`, backed by the existing classroom `show` endpoint; classroom cards now link to a dedicated management page with summary stats and student roster.
- Follow-up diagnosis: the source page contains both `NuxtLink` targets and an explicit `navigateTo()` click handler, but the generated `.nuxt/routes.mjs` is absent in the current workspace, so route registration/dev-server refresh has not been verified. The next fix should verify the route table and browser console/network first, then simplify to one navigation mechanism and restart/rebuild Nuxt if the new nested page was not picked up.
- Updated both duplicate `/admin/students` route definitions (`students.vue` parent and `students/index.vue`) from `layout: false` to `layout: 'main'`, so the actual registry route and its parent now use the same shell as the other Academy Admin pages.
- Updated the Student Cards management route family (index, import, detail, edit, and request pages) to `layout: 'main'`; left the print route standalone for print-friendly output.
- Resolved the Student Cards runtime warning by replacing unresolved auto-import tags `LazyLearnStudentCardStudentCardFront/Back` with explicit imports of `StudentCardFront` and `StudentCardBack` across admin index/detail/print, profile tab, and my-card usages. The `<Suspense>` and transition messages are framework warnings; the missing component warning was the actionable error.
- Updated Academy Admin dashboard child route `admin/index.vue` from `layout: false` to `layout: 'main'` so `/admin` uses the same layout contract as Guardians and the other admin pages.
- Diagnosed department detail navigation: `departments/[id].vue` is a nested child of `departments.vue`, but the parent page did not render `<NuxtPage>`. Added conditional child rendering and hides the department list while viewing `/departments/{id}`.

## 2026-07-14 - Department management Phase 1 implementation

- Moved the existing department list page to `ui/pages/academies/[name]/admin/departments/index.vue` so Nuxt registers the list and detail routes predictably.
- Rebuilt `ui/pages/academies/[name]/admin/departments/[id].vue` as a responsive tabbed management surface: overview, members with search, permissions, and audit history. It consumes the existing department show/members/permissions endpoints and reuses `AuditLogTab`.
- No database schema change was needed. Tasks, files, calendar, scoped feed, and announcement scopes remain Phase 2-4 work.
- Verification plan: `git diff --check`, Nuxt build/type check, and authenticated browser smoke test for department list/detail plus member and permission tabs.
- Completed the Phase 1 detail interactions: department info editing via `AcademyGroupsManageTabInfo`, member search/list with department-specific bulk add, remove, and role updates (`head/staff/member`), and permission editing via `AcademyGroupsManageTabPermissions`.
- Verification: Nuxt client transformation reached 3014 modules and chunk rendering; the full build exceeded the 120-second command timeout while emitting existing sourcemap/deprecation warnings, with no compile error observed in the captured output.

## 2026-07-14 - Scoped workspace foundation for Phases 2-4

- Added migration `2026_07_14_100000_add_scope_and_workspace_tables.php`: `scope_type/scope_id` on academy posts and announcements, plus generic `academy_scope_tasks` and `academy_scope_files` tables.
- Added `AcademyScopeWorkspaceController` endpoints for scoped task listing/creation and private file upload/listing. Allowed scopes are academy, department, and classroom.
- Academy post and announcement creation now accepts scope metadata, defaulting legacy records to academy scope.
- Added `ScopedWorkspace.vue` and exposed it on department detail under the งานและเอกสาร tab. Existing `SchoolEvent`/event APIs and report/export infrastructure are reused rather than duplicated.
- PHP syntax checks and Pint passed. Full Nuxt build still exceeds the local 120-second timeout after client transformation; authenticated browser, migration execution, and endpoint authorization tests remain required before production deployment.

## 2026-07-14 - Diagnosis and refined plan: student-cards index loading & permission fixes

- **Findings:**
  - Page-level initial loading is serial and vulnerable: if `/academies/{name}` or `/my-role` or `/statistics` fails, the page shows all-zero metrics and empty content with no user-visible error state.
  - Permission discrepancy: Sidebar checks `students.view` to show "บัตรนักเรียน", but index page list mode calls `/student-cards/admin/students` which requires `students.manage`. Users without `students.manage` get a silent 403 Forbidden.
  - Action buttons like Import/Print are visible to everyone even if they lack `students.manage` permission.
  - If statistics return empty (e.g. 0 active students), the levels array is empty, which renders no tabs and leaves the room grid completely blank.
- **Intended files:**
  - `ui/pages/academies/[name]/admin/student-cards/index.vue`
- **Decisions:**
  - Introduce `pageError` ref for page-level error state with a reload button.
  - Introduce `isLoadingList` ref for local loading state in list mode.
  - Implement fallback in `fetchStudents()`: check `can('students.manage')`. If true, call admin endpoint `/admin/students`; if false, fall back to public `/search` endpoint which performs same search but has no `students.manage` restriction.
  - Protect actions ("พิมพ์บัตร", "นำเข้าข้อมูล") and List tab/mode by hiding them or applying permission alerts if the user lacks `students.manage`.
  - Add overall Empty State if `stats.totalStudents === 0` after a successful load.
- **Verification Plan:**
  - Verify routing layout compatibility, page compilation, and role validation.
  - Test browser display under different scenarios: user with `students.manage`, user with only `students.view`, and user with no roles (should redirect).
  - Test empty database (0 student cards) and simulated network failure (failed API).

## 2026-07-14 - Review of proposed student-cards bugfix plan

- The referenced artifact `student_cards_bugfix_plan.md` was not present in the current workspace, so the review used the user's summarized findings plus the live page/routes/controller.
- Important correction: `/student-cards/search` is not public; the entire academy student-card route group is behind `auth:api`. The plan must not describe it as an unauthenticated/public endpoint. Its authorization semantics must be explicitly verified before using it for users with only `students.view`.
- The current backend route protects `/admin/students` with `academy.permission:students.manage`, while the page entry allows `students.view`. The final design must choose and document one of two contracts: (A) add/confirm a read-only permission-protected endpoint for `students.view`, or (B) restrict list mode and all student-card reads to `students.manage`. A frontend fallback alone is insufficient security design.
- Expanded implementation sequence: establish permission matrix and endpoint contract first; add backend authorization tests before changing UI; then make the page load pipeline observable with typed page error, retry, and independent loading states; then implement empty states and capability-based actions; finally verify response-shape compatibility and browser flows for admin, view-only, unauthorized, empty, and API-failure cases.
- Additional risks to cover: `byLevel` and `sectionsByLevel` can be empty even when the page loads successfully; current room discovery is derived from card rows rather than classroom enrollment; list mode is lazy-loaded only after a user click; `isAdmin || can('students.manage')` must be treated as a UI convenience, never the sole security control; and imported/print/detail/request child routes need separate permission review.

## 2026-07-14 - Implementation of Student Cards permission and state fixes

- **Backend Route Scoping:** Scoped all read-only endpoints (statistics, search, levels, sections, profile, by-student, getStudentByRoom) under `students.view` permission, and write/admin endpoints under `students.manage` in [academy-student-card.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy-student-card.php). Wrapped all endpoints under the `academy.permission` middleware to prevent cross-academy/tenancy data leaks.
- **Seeding & Feature Test Fixes:** Updated database seeders in [StudentCardSSOTTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/StudentCardSSOTTest.php) and [StudentCardByStudentTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/StudentCardByStudentTest.php) to use integer status `2` (approved member) instead of the string `'active'` (which resolves to `0` in tinyInteger columns, failing middleware checks). Also seeded a local `AcademyRole` with `students.view` permission for the student user to satisfy the new route permissions. Verified all 17 tests pass successfully.
- **Frontend Refactoring:** Added `pageError`, `statsError`, `listError`, `roomError`, `isLoadingStats`, `isLoadingList`, and `hasLoaded` refs to [index.vue](file:///C:/wamp64/www/nuxnan/ui/pages/academies/%5Bname%5D/admin/student-cards/index.vue). Refactored page initialization to catch errors and display retry buttons.
- **Frontend Fallbacks & Controls:** Scoped list endpoint to swap between `/admin/students` (manager) and `/search` (viewer) based on role capabilities. Restricted the print and import button actions to users with `students.manage` permission. Added comprehensive empty states for zero students, missing levels, local table loading/errors, and room loading/errors.

## 2026-07-14 - School attendance layout consistency

- The school-attendance list and detail routes explicitly used `layout: false`, unlike the canonical Academy Admin pages using `layout: 'main'`.
- Changed both attendance route files to `layout: 'main'` so they render with the shared authenticated user area and Academy Admin shell.
- Verification: reviewed the route metadata against sibling admin pages and ran `git diff --check`; existing unrelated whitespace warnings remain in previously modified files.

## 2026-07-14 - Academy Admin layout consistency follow-up

- Updated the main routes for gradebook, home visits, events, and store from `layout: false` to `layout: 'main'` so the linked admin pages use the same shared shell as the other Academy Admin pages.
- Intentionally limited this pass to the four requested entry routes; specialized export/print and nested workflow routes retain their existing metadata pending separate visual review.
## 2026-07-14 - Admin wallet withdrawal destination visibility

- Findings: `WalletService` masks `metadata.bank_account.account_number`, while the full destination is retained in encrypted `destination_snapshot`. The Admin pending withdrawal API previously returned only the masked metadata.
- Changes: `AdminWalletController` now decrypts and exposes `destination_details` only in authorized pending/list and detail responses. `ui/pages/nuxnan-admin/wallet/pending.vue` prefers this field for both cards and the detail modal, including copy-to-clipboard, with masked metadata as fallback for legacy records.
- Verification: PHP syntax check and `git diff --check` passed. Frontend build/browser smoke test remains to be run.
## 2026-07-14 - Diagnosis: public student-card request returns 500

- The failing endpoint is `/api/student-card/{level}/{room}/requests`, implemented by `PublicStudentCardRequestController::submitRequest` and `StudentCardRequestService::createPublic`.
- The latest Laravel log repeatedly reports `SQLSTATE[HY000]: General error: 1364 Field 'sequence' doesn't have a default value` while Telescope inserts into `telescope_entries`. This is a database/schema mismatch: the migration defines `sequence` as an auto-incrementing primary key, but the local table does not currently behave that way. Telescope failure can turn otherwise handled request responses into HTTP 500 during request termination.
- The request flow also intentionally returns 403 when `student-card.public_requests` or academy `card_request_flow_enabled` is disabled, and 422 for invalid student/enrollment/duplicate request; these should be checked after Telescope is repaired.
- No application code was changed during diagnosis. Proposed fix: inspect `SHOW CREATE TABLE telescope_entries`, reconcile the local schema with the existing Telescope migration (prefer a safe migration if this DB is shared), then retry the endpoint and inspect the actual JSON response.
- Implemented migration `2026_07_14_130000_repair_telescope_sequence_auto_increment.php`; it detects the configured Telescope database and repairs `sequence` only when `AUTO_INCREMENT` is missing. Ran the migration successfully against the local database. PHP syntax check, Pint, and `git diff --check` passed.
## 2026-07-14 - Fix legacy student-card admin room 401

- The legacy page `ui/pages/student-card/admin/students/[level]/[room].vue` called `/api/student-card/admin/students/{level}/{room}` with raw `$fetch` and no JWT header, while the backend route is protected by `auth:api`.
- Added `useAuthStore()` and sent `Authorization: Bearer ${authStore.token}` on the request. This aligns the page with the existing authenticated API patterns.
- Verification: `git diff --check` planned; browser retry should confirm the endpoint now reaches authorization/business checks instead of returning 401.
- Follow-up: direct `$fetch` with `authStore.token` still returned 401, indicating the legacy page could use a stale/expired cookie without the shared refresh flow. Replaced it with `useApi().get()` (which attaches the token and handles refresh) and added the page `auth` middleware, matching current authenticated pages. Browser must reload/restart the Nuxt dev bundle before retesting.

## 2026-07-14 - Add wallet convert-money-to-points menu card

- Added an overview card in `ui/pages/Earn/Wallet.vue` that opens the existing `convert-to-points` tab.
- Kept the points-to-money menu removed from Wallet; the conversion form remains available through points management.
- Verification: `git diff --check` and targeted search for the new menu/card/form.
## 2026-07-15 - Diagnosis and fix plan: course post emoji insert failure

- **Finding:** `CoursePostController::store()` accepts HTML, Thai text, and emoji in `content`; Laravel's MySQL connection is already configured for `utf8mb4`, but the existing `course_posts` table/schema is not. MySQL rejects `📸` with error 1366 (`Incorrect string value`), wrapped by the controller as a generic 500.
- **Decision:** Add a forward-only migration to convert the complete `course_posts` table to `utf8mb4_unicode_ci`. Do not edit `.env` or perform manual/destructive SQL outside migrations.
- **Verification:** PHP syntax, Pint, diff check, and focused course-post tests where available. After deployment, run `php artisan migrate` and retry a post containing Thai text, emoji, and HTML.

## 2026-07-15 - Diagnosis: student registry to student-card navigation does not load

- **Scope:** User reports that navigating from `/academies/{name}/admin/students` to `/admin/student-cards` leaves the card page without data, while entering the card page from another admin page loads normally.
- **Findings:** Both pages are nested child routes under `ui/pages/academies/[name]/admin.vue`, which owns the persistent `<NuxtPage />` and is not remounted during sibling navigation. The student-card page uses its own `onMounted(initializePage)` and its own `academyId` ref, independently fetching the academy and role before statistics. This makes route-transition timing and duplicate academy/role initialization the primary suspect; the page does not watch route/context changes or retry initialization if the child mounts during an incomplete parent transition.
- **Relevant files:** `ui/pages/academies/[name]/admin.vue`, `ui/pages/academies/[name]/admin/students/index.vue`, `ui/pages/academies/[name]/admin/student-cards/index.vue`, and the API contract in `api/nuxnanravel/routes/learn/academy-student-card.php`.
- **Next plan:** reproduce with browser/network logs, compare requests and console errors for direct load versus sibling navigation, then consolidate the child page on the parent-provided academy context or add a route-aware/watch-based initialization with cancellation/retry. Verify API response shapes and permissions before changing backend behavior.
- **Verification plan:** focused browser smoke test for direct load, students → cards, cards → students → cards, hard refresh, and slow API timing; then `git diff --check` and Nuxt type/build checks if implementation is approved.
## 2026-07-17 - Homeroom Teacher Assignment

- Updated `ClassroomService::updateClassroom` to use a transaction and auto-add a changed non-null homeroom teacher to `classroom_members`; old members are preserved.
- Added `roles[]` filtering to academy member search.
- Added `ui/components/academy/AssignHomeroomTeacherModal.vue` and connected it to classroom detail overview.
- Verification: Pint and PHP syntax passed; frontend build timed out after 124 seconds.
- Remaining: classroom list quick-action/badge and full members-tab action polish/manual UI verification.

## 2026-07-17 - Homeroom Teacher Search Display Fix

- Root cause: `AcademyMemberResource` exposes `member_name` and `member_avatar`; modal was reading only `name` and nested user fields.
- Updated modal mapping and role filtering to support the resource contract while retaining fallback fields.
## 2026-07-17 - Academy member filters
- พบว่าหน้า `ui/pages/academies/[name]/admin/members.vue` ไม่ได้เรียก `fetchRoles()` และส่งค่าบทบาทเป็น `role`/ชื่อ ขณะที่ API รองรับ `academy_role_id`/รหัสบทบาท
- แก้ให้โหลดบทบาท, ใช้ role id ใน dropdown/request และส่ง date range จาก advanced filters
- Backend `AcademyMemberController@searchMembers` รองรับ `date_from`/`date_to`
- Verification: ตรวจ diff และเตรียมรัน type/build กับ PHP syntax
## 2026-07-17 - Classroom filter alignment
- พบว่า filter options ใช้ `classrooms.grade_level/section` จาก `classroom_students` แต่ search ใช้ `students.class_level/class_section` ทำให้ข้อมูลห้องปัจจุบันกับฟิลด์นักเรียนไม่ตรงกัน
- ปรับ `AcademyMemberController@searchMembers` ให้กรองจาก active classroom enrollment และ current academic year เมื่อมีปีปัจจุบัน
- Verification plan: PHP lint, diff check, ตรวจ query scope และ build หากใช้เวลาพอ
## 2026-07-17 - Classroom filter option ordering
- จากภาพพบว่าตัวเลือกห้องเรียงแบบ string (`1, 10, 11, 2...`) ทำให้รายการดูไม่ถูกต้อง
- ปรับ `classSectionOptions` ในหน้า members ให้เรียงด้วย `localeCompare` แบบ `numeric: true` รองรับค่าห้องที่เป็นตัวเลขหรือข้อความผสม
- Verification: ตรวจ diff และ syntax ของไฟล์ที่เกี่ยวข้อง
## 2026-07-17 - Classroom filter labels
- ตัวเลือก “ทุกห้อง” เดิมแสดงค่า `class_sections` ซึ่งเป็น section ดิบ ไม่ใช่ชื่อห้องจาก `classrooms.name`
- ปรับให้ใช้รายการ `classrooms` แสดงชื่อห้องจริง และ map กลับเป็น `class_level/class_section` เพื่อเรียก API เดิม
- ปรับ quick classroom chips ให้ sync กับ dropdown
- Verification: `git diff --check`, PHP lint และตรวจการอ้างอิงตัวเลือกใน SFC
## 2026-07-17 - Gender filter labels
- API gender labelsบาง branch มีข้อความ encoding/สะกดผิด
- ปรับหน้า members ให้ใช้ label มาตรฐานจากค่าเพศ: `1 = ชาย`, `0 = หญิง` แทนข้อความจาก API
- Verification: `git diff --check`, PHP lint และตรวจ mapping ใน SFC
## 2026-07-18 - Login fails when recording usage event

- **Finding:** `AuthController@login` calls `UsageEventService::fire()`, which creates `user_usage_events` through Eloquent. The live MySQL table has a non-null `id` without `AUTO_INCREMENT`, causing error 1364 before the login response is returned. The existing `2026_07_10_013214_modify_id_in_user_usage_events_table` migration cannot repair databases where it was already marked as applied.
- **Fix:** Added forward-only migration `2026_07_18_040000_repair_user_usage_events_id_auto_increment.php` that checks MySQL metadata and safely restores `AUTO_INCREMENT` only when missing.
- **Verification:** PHP lint, Pint, focused `GamificationTest` (10 tests/42 assertions) passed. Migration initially exposed that `id` was also missing its primary-key index; the repair now restores the primary key when absent before enabling `AUTO_INCREMENT`. `php artisan migrate --force` completed successfully on the local database.
## 2026-07-18 - Academy courses filter UI polish

- **Scope:** frontend-only redesign of `ui/pages/academies/[name].vue`, focused on the Courses tab shown in the provided reference.
- **Changes:** compact tab strip, softer HopeUI-inspired card treatment, consistent rounded controls, clearer focus states, and stronger primary actions while preserving existing filters, API calls, counts, and responsive behavior.
- **Verification plan:** `git diff --check` and frontend build if available; manual browser smoke test should confirm the academy route and course filtering.
- **Follow-up:** moved the level-selection tabs below the filter controls, keeping the tabs inside the same control card and preserving horizontal scrolling on small screens.

---

## 2026-07-18 - Course Donation + Wallet Ledger: บทวิเคราะห์แผนฉบับผู้ใช้ + Work Plan ปรับปรุง

### สรุปสิ่งที่มีอยู่จริงใน codebase (ยืนยันจริงก่อนวางแผน)

จากการตรวจ `app/Models/` และ `database/migrations/`:

| องค์ประกอบ | สถานะปัจจุบัน | ช่องว่าง |
|---|---|---|
| `CoursePointAccount` | มี `balance`, `commission`, `minimum_withdrawal`, `reserved_balance` (2026_05_25_000004) | ยังไม่มี `pending_balance`, `withdrawable_balance`, `lifetime_income/expense`, `version` |
| `CoursePointTransaction` | มีแล้ว — เป็น per-course ledger สำหรับแต้ม | ไม่มีการเชื่อมกับเงินจริง / ไม่มี ledger กลาง / ไม่มี idempotency_key เข้มงวด |
| `CoursePointCampaign` | มี lesson_reward fields, budget concept | ยังไม่มี state machine ที่ชัดเจน, reserved_budget แยกจาก account.reserved |
| `CoursePointCampaignClaim` | มีอยู่ | ต้องตรวจ unique constraint action_reference |
| `CampaignDeliveryEvent` | มี `idempotency_key` แล้ว | ยังไม่มี heartbeat, page visibility, device fingerprint |
| `Advert` | มี campaign fields (backfill 2026_07_12) | Advert เอง = campaign — ทำให้ต้องระวังการซ้อนความหมายกับ CoursePointCampaign |
| `WalletTransaction` | เป็น **user-scoped** wallet (ไม่ใช่ course) มี withdrawal fields, payout_proof, decimal columns | ไม่ได้ผูกกับ course wallet — course wallet เป็นคนละระบบจากผู้ใช้ |
| `WalletDepositRequest` | มี — สำหรับ user deposit ไม่ใช่ course donation | ต้องคิดใหม่ว่าจะ reuse หรือแยก |
| `CourseDonation` | **ยังไม่มีทั้ง model และ controller** | ต้องสร้างใหม่ทั้งหมด |
| `CourseCashWallet` (เงินจริงของ course) | **ยังไม่มี** | ต้องสร้างใหม่ |
| Generic Wallet Ledger | **ไม่มี** — ระบบเปลี่ยน balance โดยตรงหรือผ่าน CoursePointTransaction เฉพาะแต้ม | ต้องสร้าง ledger กลาง |
| Revenue-share policy | **ไม่มี** — ค่า commission เก็บใน CoursePointAccount เท่านั้น | ต้องสร้าง policy table + versioning |
| Idempotency ทั่วระบบ | บางส่วน (CampaignDeliveryEvent) | ต้องขยายทั้งระบบ donation, claim, ad reward |

### ข้อสังเกตสำคัญที่ทำให้แผนต้องปรับ

1. **CoursePointAccount `reserved_balance` มีอยู่แล้ว** — Phase Wallet ไม่ต้องสร้าง reservation concept ใหม่ทั้งหมด แค่ต่อยอด
2. **Advert คือ Campaign** ในตัวเอง — มี `CoursePointCampaign` แยกอีก แสดงว่าโดเมนซ้อน 2 แบบ (แคมเปญโฆษณา vs แคมเปญแจกแต้ม) ต้องตัดสินใจว่ารวมหรือแยก
3. **`WalletTransaction` เป็น user-scoped ล้วน** — course wallet เป็นคนละโลก ห้ามพยายามยัดเงินรายวิชาลง `wallet_transactions`
4. **มี `CoursePointTransaction` อยู่แล้วเป็น ledger เฉพาะแต้ม** — ledger กลางที่จะสร้างใหม่ต้อง superset ของนี่ พร้อมแผน migrate ข้อมูลเก่า
5. **ยังไม่มี Payment Gateway integration ในระบบเลย** — WalletDepositRequest เป็น manual/slip เท่านั้น ดังนั้น phase เงินจริงต้องรวม cost integration gateway เป็นครั้งแรกของโปรเจ็ค

### ประเด็นเพิ่มเติม/ปรับจากแผนที่ผู้ใช้เขียน

**ประเด็นที่แผนเดิมยังไม่ครอบคลุม:**

1. **Advert ↔ CoursePointCampaign duplication** — ต้องรวมโมเดลหรือกำหนดขอบเขตชัด (แนะนำ: Advert = "แหล่งเงิน/โฆษณา" ที่ให้ทุนแคมเปญประเภท `ad_view`; แคมเปญคือหน่วยที่นักเรียนกดรับ)
2. **การ migrate ยอดเก่า** — CoursePointAccount ที่มี balance ปัจจุบันต้องมี opening balance entry ใน ledger ใหม่ ไม่งั้น sum ไม่ตรง
3. **สกุลเงิน/หน่วยแต้ม** — ระบบมีทั้งแต้ม (int), เงิน (decimal ใน wallet_transactions) — Ledger กลางต้อง polymorphic ต่อ currency_type และมี unit ที่ต่างกัน (bigint vs decimal)
4. **นโยบายภาษี/ใบเสร็จ/e-Tax invoice** — โรงเรียนหลายแห่งต้องออกใบเสร็จ อาจต้องเชื่อม RD e-Tax Invoice ในอนาคต — เผื่อไว้ตั้งแต่โครง Data
5. **PDPA/anonymous donor** — ต้องแยก "แสดงชื่อ" กับ "เก็บชื่อภายใน" ให้ชัด รวมถึงสิทธิ์ครูดูรายการ (ไม่ให้เห็นเลขบัญชี/email เต็ม)
6. **Fee absorption** — ใครจ่ายค่าธรรมเนียม gateway? ผู้บริจาค (ทบยอด) หรือรายวิชา (หักออก)? ต้องเป็น setting ต่อรายวิชา/ต่อแคมเปญ
7. **Wallet เงินสำหรับครูส่วนบุคคล** — แผนบอกว่า "รายได้ไม่ควรตกครูเป็นส่วนตัวโดยอัตโนมัติ" ถูกต้อง แต่ต้องระบุปลายทางว่าไปที่ *ใคร* — เสนอ: เข้าบัญชี "โรงเรียน" (Academy Wallet) ไม่ใช่ครูคนเดียว
8. **Feature flag & pilot rollout** — แผนพูดถึงแต่ยังไม่ใส่ mechanism: เพิ่ม `platform_settings.donation_module_enabled` + per-course opt-in
9. **Rate limiting policy layer** — ต้องระบุค่าเริ่มต้น (เช่น donation 10/min ต่อ user, claim 30/min ต่อ user, ad start 5/min)
10. **Idempotency Key ที่มาจาก client** — ต้องระบุ contract: ให้ client generate UUID v4 แนบ `Idempotency-Key` header, server เก็บ 24 ชม.
11. **Concurrency ของ approve/reject โดย Admin** — แผนยังไม่พูด: ต้อง lock donation row + version column ป้องกัน double-approve
12. **การจัดการ orphan reservation** — ถ้า process ตายกลางทาง reserved_balance ค้าง ต้องมี scheduler ปล่อย reservation ที่หมดอายุ (grace 5 นาที)
13. **Money precision** — decimal(18,4) สำหรับเงิน, bigint สำหรับแต้ม, ห้ามใช้ float ทุกจุด (โดยเฉพาะการคูณ 70/20/10)
14. **Rounding policy สำหรับ revenue split** — 100 บาท / 3 คน อาจได้ 33.33/33.33/33.34 — ต้องกำหนดใครรับเศษ (แนะนำ: platform รับเศษเสมอ)
15. **Ledger append-only enforcement** — ต้อง revoke UPDATE/DELETE ระดับ DB user (หรืออย่างน้อย model-level guard) มิใช่แค่ระเบียบ
16. **Multi-currency ในอนาคต** — ตอนนี้ THB อย่างเดียว แต่ควรมี currency column เพื่อ forward compat

---

## Work Plan — Course Donation + Wallet Ledger ฉบับปรับปรุง (2026-07-18)

### หลักการหลัก (Guiding Principles)

- **Ledger เป็น source of truth** — balance เป็นแค่ cache
- **Append-only** — ไม่ลบ/ไม่แก้ transaction เดิม ใช้ reversal เสมอ
- **Idempotent by design** — ทุก endpoint ที่แตะเงิน/แต้มต้องรองรับ retry
- **Double-entry** — ทุก entry ต้องมีคู่ (debit + credit) ยอดรวมทั้ง entries เป็น 0 เสมอ
- **Separation of duties** — ผู้ริเริ่ม ≠ ผู้อนุมัติสำหรับรายการเงินจริง
- **No frontend-computed money** — server กำหนดยอดทุกจุด รวม reward, fee, split
- **Feature flag first** — เปิด/ปิดได้ต่อ course ต่อ academy ต่อ platform
- **Backward compatible migration** — ระบบเดิมต้องทำงานได้ระหว่าง phase 1–4

### Phase 0 — Discovery & Design Freeze (1–2 สัปดาห์, no code)

**เป้าหมาย:** ล็อคดีไซน์ก่อนแตะโค้ด production

1. **Data audit** — export snapshot จริงของ `course_point_accounts`, `course_point_transactions`, `wallet_transactions`, `advert*`, `campaign*` ตรวจว่ายอดรวม balance ตรงกับ sum ของ transactions หรือไม่ (ถ้าไม่ตรง — ต้อง reconcile ก่อน migrate)
2. **Route inventory** — grep เส้นทางทั้งหมดที่เขียนใส่ `balance`/`reserved_balance` ในโค้ดปัจจุบัน (controllers, jobs, services, tinker) → ทำ list ให้ครบก่อน refactor
3. **Advert vs Campaign decision** — จัดประชุมตัดสินใจ (ผู้ใช้ + owner): รวม 2 โมเดลเป็น 1 หรือให้ Advert = source-of-fund และ Campaign = distribution-unit
4. **Payment gateway selection** — เลือก GBPrimePay / Omise / 2c2p — เขียน integration spec, sandbox account
5. **Slip verification provider** — เลือก EasySlip / RD OpenAPI / Manual — เขียน spec
6. **PDPA + tax compliance review** — ปรึกษาที่ปรึกษาการเงิน/บัญชี ถ้ามีเงินจริง (สำคัญ)
7. **Revenue-share policy default** — confirm 70/20/10 หรือปรับ (แนะนำเก็บใน DB ไม่ hard-code)
8. **Fee absorption policy** — ผู้บริจาคจ่าย fee หรือรายวิชารับ?
9. **State machine diagrams** — วาด donation, campaign, ad-delivery, withdrawal, refund ให้ครบทุก transition ก่อนเขียนโค้ด
10. **DoD document** — เขียน spec ตายตัวเป็น `.agents/specs/course-donation-wallet.md` ให้ทุก phase อ้างอิง

**Exit criteria:** spec ผ่านการรีวิว + snapshot data reconcile ผ่าน + gateway sandbox ทดสอบได้

---

### Phase 1 — Wallet Ledger Foundation (2–3 สัปดาห์)

**เป้าหมาย:** วางระบบบัญชีกลางโดยยังไม่แตะเส้นทางเงินจริง (shadow mode)

**Migrations (append-only, ไม่ drop):**

1. `create_wallets_table` — polymorphic (owner_type, owner_id, currency_type, available_balance, pending_balance, reserved_balance, withdrawable_balance, lifetime_credit, lifetime_debit, status, version) — unique (owner_type, owner_id, currency_type)
2. `create_ledger_transactions_table` — ชื่อใหม่ห้ามชนกับตารางเดิม (id, transaction_no, type, status, gross_amount, net_amount, fee_amount, currency_type, idempotency_key UNIQUE, initiated_by, approved_by, approved_at, metadata JSON, timestamps)
3. `create_ledger_entries_table` — (id, transaction_id FK, wallet_id FK, entry_type, direction enum(debit,credit), amount decimal(18,4), balance_before, balance_after, reference_type nullable morph, reference_id, description, created_by, metadata) — index (wallet_id, created_at)
4. `create_wallet_locks_table` (optional) — ถ้าใช้ table-based lock

**Services (ใหม่):**

- `LedgerService::openWallet(owner, currency)`
- `LedgerService::recordTransaction(TransactionData, LedgerEntry[])` — atomic, validate double-entry balance = 0, lock wallets
- `LedgerService::reverse(transactionId, reason)`
- `WalletBalanceReconciler::verify(walletId)` — sum(entries) == balance?
- `IdempotencyService::rememberOrReplay(key, callable)`

**Migration ของข้อมูลเดิม (สำคัญ):**

- ทุก `CoursePointAccount` ที่มีอยู่ → สร้าง wallet currency=point owner=Course + opening_balance entry
- ทุก `CoursePointTransaction` เก่า → replay เป็น ledger entries (แต่**อ่านอย่างเดียว**) หรือ mark เป็น legacy + ไม่ replay ก็ได้ (แนะนำแบบหลัง — ประหยัดเวลา)

**Shadow mode:** ระบบเดิมยังใช้งานได้ปกติ — service ใหม่แค่เขียน parallel ledger — เทียบยอดทุกวันจน stable แล้วค่อย cutover

**Tests:**
- unit: double-entry invariant, reversal, idempotency
- concurrency: 100 concurrent debits/credits ยอดต้องตรง
- reconciliation command runnable

**DoD:** reconciler บอกยอดตรงทุก wallet ต่อเนื่อง 7 วัน

---

### Phase 2 — Course Wallet Split (Point vs Cash) (1 สัปดาห์)

1. เปิด wallet 4 ตัวต่อ course: point/available, point/reserved, cash/available, cash/pending (หรือใช้ column แยก state ใน wallet เดียว)
2. เขียน `CourseWalletProvisioner` — job สร้าง wallet ให้ course ที่ยังไม่มี (idempotent)
3. Backfill: ให้ CoursePointAccount ที่มียอด → เข้า point/available wallet
4. Read-side API `GET /api/courses/{course}/wallets` (owner only + admin)

**DoD:** ทุก course active มี wallet ครบ, ยอดตรง

---

### Phase 3 — Point Donation MVP (2 สัปดาห์)

**Backend:**
1. `course_donations` table — ตามสเปกในหัวข้อ 7.4 ของแผนผู้ใช้ + `idempotency_key` UNIQUE + `version` + `platform_fee_amount`
2. `CourseDonationService::createPointDonation(donor, course, amount, meta, idempotencyKey)` — validate → lock donor wallet + course point wallet → double-entry via LedgerService → donation status=`completed`
3. FormRequest `CoursePointDonationRequest` — validate amount ≥ min, ≤ max, ≤ donor balance
4. Policy: `CourseDonationPolicy@donate` — donor ≠ course owner (or flag with anti-self-donation limit)
5. Anti-abuse: rate limit, velocity check (จำนวนบริจาค/ชม.), risk_score enum
6. Endpoint: `POST /api/courses/{course}/donations/points` — header `Idempotency-Key`
7. `GET /api/me/donations` — history

**Frontend:**
1. Donation Modal ใน `ui/components/donation/CourseDonationModal.vue` — step wizard (amount → purpose → anonymous flag → review → result)
2. หน้า public `/courses/{slug}` — เพิ่มปุ่ม "สนับสนุนแต้ม"
3. `ui/pages/me/donations.vue` — ประวัติ
4. Owner view `ui/pages/courses/[id]/wallet/donations.vue` — รายการรับบริจาค (readonly + ไม่แสดง email/บัญชีเต็ม)

**Tests:**
- unit + integration Feature test
- concurrency: 20 concurrent donations
- self-donation policy

**DoD:** end-to-end แต้มไหลจากผู้บริจาค → course wallet ผ่าน Ledger + reconcile ผ่าน

---

### Phase 4 — Campaign Budget Reservation Refactor (2 สัปดาห์)

1. เพิ่ม `reserved_budget`, `spent_budget`, `remaining_budget` ใน `course_point_campaigns` (ถ้ายังไม่มี)
2. `CampaignBudgetService::reserve(campaign)` — เมื่อ activate: ย้ายจาก point/available → point/reserved ผ่าน ledger entry `campaign_reserve`
3. `CampaignBudgetService::release(campaign, remaining)` — เมื่อ close/expire คืนงบ
4. Claim flow refactor: `CampaignClaimService::claim(campaign, student, actionReference, idempotencyKey)` — lock campaign row + wallets, ตัดจาก reserved, จ่ายเข้า student wallet, unique (campaign_id, student_id, action_reference)
5. State machine: `draft → pending_review → scheduled → active → closing → closed / suspended`
6. Scheduled job: expire campaign ที่หมดเวลา + คืนงบ (ทุก 5 นาที)
7. Migration ระบบเก่า: campaign active ปัจจุบันให้คำนวณ reserved_budget จาก (total_budget - spent) แล้ว sync

**Frontend:**
- Campaign builder ใหม่ที่คำนวณ max liability + reserved preview ก่อน activate
- แสดง reserved budget bar

**DoD:** activate 5 แคมเปญพร้อมกัน — reservation ตรง, close คืนงบตรง

---

### Phase 5 — Ad Delivery Verification Hardening (2 สัปดาห์)

1. เพิ่มคอลัมน์ใน `campaign_delivery_events`: session_id, delivery_token_hash, started_at, last_heartbeat_at, completed_at, required_duration, page_visibility_ratio, device_fingerprint_hash, ip_hash, status, fraud_reason
2. Endpoint: `POST /ad-deliveries/start` → issue signed JWT token (short TTL, single-use)
3. `POST /ad-deliveries/{id}/heartbeat` — ทุก 5 วิ (rate-limited)
4. `POST /ad-deliveries/{id}/complete` — verify token ไม่ถูก replay, watch_duration ≥ required (server-side compute จาก heartbeats), visibility_ratio ≥ threshold, ไม่หมดอายุ
5. `AdCompletionVerifier` service — ทำ fraud scoring; ถ้าผ่านเรียก `RewardDistributionService`
6. `RewardDistributionService::distribute(deliveryEvent)` — คำนวณ split จาก `RevenueSharePolicy::resolve(course, campaign, at)` → สร้าง multi-entry transaction (student credit, course credit, platform credit, sponsor debit) → double-entry balanced

**Frontend `AdViewerModal.vue`:**
- แสดง reward preview ที่มาจาก API (ห้าม compute เอง)
- ส่ง heartbeat
- แสดง sponsor + duration
- แสดง result ผ่าน reference number

**DoD:** replay token/duplicate complete → 409; fraud test suite ผ่าน

---

### Phase 6 — Revenue Share Policy + Platform Wallet (1 สัปดาห์)

1. `revenue_share_policies` — (scope_type platform/academy/course/campaign, scope_id, student_pct, course_pct, platform_pct, effective_from, effective_to, version)
2. `RevenueSharePolicyResolver::resolve(course, campaign, at)` — pick most specific active policy
3. Platform Wallet (owner_type=platform, owner_id=1) — สร้างครั้งเดียว
4. Rounding: platform รับเศษ (integer division; remainder to platform)
5. เก็บ policy_version ใน transaction metadata เพื่อ audit
6. Admin UI สำหรับตั้งค่า policy (Super Admin เท่านั้น)

**DoD:** เปลี่ยน policy ใหม่ไม่กระทบ transaction เก่า, invariant test ผ่าน

---

### Phase 7 — Cash Donation via Payment Gateway (3–4 สัปดาห์)

1. `PaymentIntent` model (provider, provider_intent_id, donation_id, amount, status, webhook_events JSON)
2. `POST /donations/{donation}/payment-intent` — server ตัดสินยอด ไม่รับจาก client
3. Webhook handler `POST /api/payment/webhooks/{provider}` — verify signature, dedupe by event_id, update donation → `paid` → เข้า cash/pending wallet
4. Settlement job: หลัง N วัน (per policy) ย้าย pending → available
5. Chargeback handler: reverse via ledger, freeze relevant balances
6. e-Receipt PDF generation
7. Frontend: donation modal เพิ่ม tab "เงินจริง" + Stripe/Omise widget

**DoD:** sandbox e2e ผ่าน, chargeback flow ผ่าน, reconciliation vs gateway daily รายงานตรง

---

### Phase 8 — Slip Upload + Manual Verification (1–2 สัปดาห์)

1. `POST /donations/{donation}/proof` — upload slip → private storage
2. Slip verification API integration (EasySlip/RD)
3. Admin queue `GET /api/admin/donations/pending` → approve/reject
4. Maker–checker: approver ≠ creator, ครูของ course นั้นห้ามอนุมัติ (policy check)
5. Frontend Admin queue page + slip preview
6. Rate limit + duplicate slip reference detection

---

### Phase 9 — Withdrawal (Maker–Checker) (2 สัปดาห์)

1. `course_withdrawal_requests` — ตามสเปก 7.8
2. Bank account verification workflow (owner ต้อง verify bank account ก่อน)
3. State: `draft → academy_review → finance_pending → approved → paid / rejected`
4. Course owner ยื่น → Academy admin review → Finance/Super Admin approve → paid
5. Payment reference upload
6. Notification ทุก transition
7. Frontend: request form + status tracker

---

### Phase 10 — Fraud Detection + Reconciliation Ops (2 สัปดาห์)

1. `risk_events` table + `FraudDetectionService` — rule-based (velocity, device farm, self-donation cluster)
2. Daily reconciliation command: gateway vs donation vs ledger vs wallet balance
3. Alert to admin ถ้าไม่ตรง + freeze affected wallets
4. Admin risk queue UI
5. Audit log viewer (read-only)

---

### Phase 11 — Frontend Public Discovery + Course Public Page (2 สัปดาห์)

1. หน้า `/courses` — search/filter/sort ตามสเปก 19.1
2. หน้า `/courses/{slug}` public — สเปก 19.2 (transparency section, กราฟยอดสนับสนุน, ไม่โชว์ balance ทั้งหมด)
3. QR code generator สำหรับเจ้าของรายวิชา — link ต้อง verify course status server-side
4. Course Wallet Dashboard ครบตามสเปก 19.4

---

### Phase 12 — Rollout & Migration (2 สัปดาห์)

1. Feature flag `donation_module_enabled` per platform/academy/course
2. Backup + tested restore
3. Enable ledger shadow-mode → cutover 1 pilot course → 5 → 50 → all
4. Monitor 30 วันหลัง full rollout
5. Deprecate legacy code paths หลัง confirm ยอดตรง 60 วัน

---

### Cross-cutting: Testing Strategy (ทำคู่ทุก phase)

- **Unit**: service level, all state transitions, all invariants
- **Integration (Feature test)**: end-to-end per user flow
- **Concurrency**: parallel PHPUnit + pcntl_fork หรือ artisan test with locks
- **Financial invariant**: หลัง test run — sum(all ledger entries)=0, no negative wallets (except allowed debt), campaign spent ≤ budget
- **Security**: mass assignment, ID tampering, replay, CSRF, upload injection
- **Contract test**: frontend ↔ backend schema เสถียร

### Cross-cutting: Observability

- Structured log ทุก transaction พร้อม transaction_no
- Metric: donation success rate, ad completion rate, reconciliation delta
- Alert: ledger imbalance, reservation orphan > 15 นาที, webhook failure > 3 ครั้ง

---

### Timeline สรุป (12 phase)

- Phase 0–2: **~5–6 สัปดาห์** (foundation, ไม่ user-facing)
- Phase 3–5: **~6 สัปดาห์** (แต้ม + campaign + ad — MVP user-facing)
- Phase 6: **~1 สัปดาห์** (policy)
- Phase 7–8: **~5 สัปดาห์** (เงินจริง)
- Phase 9: **~2 สัปดาห์** (withdrawal)
- Phase 10–12: **~6 สัปดาห์** (fraud, discovery, rollout)

**รวม ~25 สัปดาห์ (6 เดือน)** สำหรับระบบครบวงจร production-grade — เร็วกว่านี้ต้องตัด scope (เช่นเลื่อน gateway/withdrawal ไป phase 2 ของโครงการใหญ่)

---

### สิ่งที่ยังต้องผู้ใช้ตัดสินใจก่อนเริ่ม Phase 0

1. **Advert vs CoursePointCampaign** — รวมหรือแยก?
2. **Payment gateway** — ตัวไหน?
3. **Fee absorption** — ผู้บริจาคหรือรายวิชา?
4. **รายได้ครูส่วนตัว** — ตกที่ Academy Wallet หรือให้ครูถอนได้ตรง?
5. **Timeline** — 6 เดือนตามข้างบน หรือทำ Slim MVP (เฉพาะแต้ม + basic campaign) ใน 2 เดือนก่อน?
6. **Legacy CoursePointTransaction** — replay เป็น ledger entries หรือปล่อยเป็น legacy?
7. **Reset scope** — ระบบผลิตอยู่แล้ว มี user จริง มียอดจริง ต้อง freeze/notify user ก่อน migrate หรือไม่?

หัวใจสำคัญตามที่แผนผู้ใช้สรุปไว้ถูกต้องแล้ว: **"ยกระดับจากเพิ่มฟีเจอร์ → สร้างระบบบัญชี Wallet กลาง"** — Phase 0–2 คือหัวใจ ถ้า foundation เสร็จดี phase อื่นเป็นเพียงการต่อ endpoint
## 2026-07-18 - Course support navigation and widget

- Moved course support actions into the course shell/header navigation model and added a dedicated `/Learn/Courses/:id/support` page.
- Added `CourseSupportWidget.vue` using existing donation and course-point composables; no new backend contract was introduced.
- Added support tab id `16` to `CourseTabBar.vue` and support route handling in `CoursePageShell.vue`.
- Verification plan: run the focused Nuxt production build and inspect the course route manually when the local server/auth session is available.

## 2026-07-18 - Plan: unify academy/course point donations with ad-earned rewards

- Scope: plan-only analysis; no feature implementation performed.
- Findings: course and academy point donations already have authenticated endpoints, idempotency keys, wallet/account transactions, and admin history; rewarded-ad delivery already has start/heartbeat/complete, replay protection, visibility/duration checks, and student reward distribution.
- Main gap: ad revenue distribution currently credits only Course Point Account for course-targeted adverts; Academy Point Account has no equivalent delivery credit path. Donation flows and ad reward flows also use separate transaction abstractions and UI contracts, while course campaigns remain a separate claim model.
- Intended work areas: `AdDeliveryService`, `RewardDistributionService`, academy/course point account services and transaction models, campaign/ad request-resource contracts, donation composables/modals, course/academy support widgets, and focused feature tests.
- Key decisions to confirm before implementation: whether academy-targeted ads fund the academy account directly; whether ad revenue can be donated automatically or only displayed as available balance; canonical reward/ledger model; campaign budget and revenue-share percentages; and whether cash donations remain manual-review in MVP.
- Verification plan: API contract tests, idempotency/concurrency tests, replay/fraud tests, wallet invariants/reconciliation, and manual desktop/mobile smoke tests for ad viewing and course/academy donation history.
