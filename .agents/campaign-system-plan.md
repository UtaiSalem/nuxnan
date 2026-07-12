# แผนระบบ Campaign (โฆษณา + สนับสนุน) — ฉบับล็อกการตัดสินใจ

**วันที่:** 2026-07-12
**สถานะ:** Phase 1-3 ✅ เสร็จ + review/fix (14/14 tests) | Phase 4 ✅ CampaignWidget verified (Nuxt build ผ่าน) | Phase 5 (create/dashboard) ระหว่างทำ
**ขอบเขต:** รวมระบบโฆษณา (`adverts`) + สนับสนุน (Support Plearnd) + บริจาค (`donates`) ให้เป็น Campaign Engine กลาง ที่รองรับ scope public / academy / course

---

## บทวิเคราะห์สถานะระบบปัจจุบัน (อิงโค้ดจริง)

ปัจจุบัน **ไม่ได้มี 2 ระบบ แต่มี 3 ระบบที่ทับซ้อนกัน**:

| ระบบ | ที่เก็บ | หมายเหตุ |
|---|---|---|
| โฆษณา | `adverts` (`AdvertController`) | `duration>0`, คิดราคา `total_views × duration × 0.10` |
| สนับสนุนระบบ (Support Plearnd) | `adverts` เดิม (`SupportController`) | ยัด `duration=0, total_views=0`, ให้แต้ม `amounts × 684` |
| บริจาค (Donate) | ตารางแยก `donates` + `donate_recipients` | ให้แต้ม `amounts × 1080`, มี `donor_id`, recipients, soft-deletes |

### บั๊ก/หนี้เทคนิคที่พบในโค้ดจริง (ต้องแก้ตอน refactor)

1. **`amounts` เป็น integer** (`create_supports_table` migration) → เงินที่มีเศษสตางค์ถูกปัดทิ้ง → `budget_amount` ต้องเป็น `decimal`
2. **Race/refund bug ใน `view()`** (`AdvertController.php:209-251`) — `decrement('remaining_views')` เกิด **นอก** transaction จ่ายรางวัล; เช็ค `todayViews<5` + attach ไม่ atomic → ยิงพร้อมกันเกินโควตา/หัก view ซ้ำได้
3. **ค่าคงที่ราคา/รางวัลกระจาย 3 ที่ไม่ตรงกัน** — คิดราคา `×0.10`, จ่ายจริง `duration×0.06 + points/1200`, widget แสดง `duration×0.07`
4. **Widget อ่าน field ที่ถูก rename ไปแล้ว** — `advert.supporter` (`AdvertisesWidget.vue:98`) แต่คอลัมน์เป็น `advertiser_id` → ตกไป fallback เสมอ (การ rename `supports→adverts` ทำ frontend พังเงียบ)
5. **`status` เป็น magic tinyint** ปน 2 ความหมาย (จ่ายเงินแล้ว + รีวิวผ่าน) → ต้องแยก `payment_status`/`review_status`
6. **`approve()`/`reject()` ไม่มี refund + ไม่มี authorization ตาม scope** (`AdvertController.php:268-302`) — มีแค่ middleware `plearnd_admin` global; wallet-paid support ให้แต้มทันทีที่ status=1 โดยไม่ผ่านรีวิว

### Helper ที่มีอยู่แล้วใช้เป็นฐาน authorization ได้
- `Academy::canManage()` (`Academy.php:95`) — owner/academyAdmins
- Course owner + role-4 admin (`Course.php:429`) — `user_id`, `instructor_id`, `academy_id`, `creator_id`

---

## การตัดสินใจที่ล็อกแล้ว (Phase 0 ✅)

| ประเด็น | คำตอบ |
|---|---|
| Donate | คงแยก **อ่านอย่างเดียว** — support ใหม่ทั้งหมดไป `campaigns`, `donates` เก็บประวัติเดิม |
| อัตราแต้ม | เก็บใน `config/campaign.php` **ปรับได้ต่อ scope** (platform/academy/course) |
| Revenue split | โรงเรียน 70 / ผู้สอน 20 / ระบบ 10 (config ปรับได้) |
| รางวัลโฆษณา academy/course | **จ่ายเหมือน public** |
| Migration | **Strangler/additive** — ไม่ big-bang rename |
| Impression vs View | **แยก 2 action** (นับเห็น ≠ ดูจบจ่ายรางวัล) |
| Course→Academy inherit | ได้ **ถ้าเปิด toggle** |
| Approval | **แยกตามเจ้าของพื้นที่** + system admin override |
| ช่องทางจ่าย | wallet + แนบสลิป (คงทั้งคู่) |
| Review flow | **review ทุกกรณี** — `payment_status` แยกจาก `review_status` |

---

## Phase 1 — Backend Schema (additive, ไม่ทำลายของเดิม)

**เป้าหมาย:** เพิ่มความสามารถให้ตาราง `adverts` เดิมโดยไม่ทำ frontend พัง

### 1.1 Migration `add_campaign_fields_to_adverts_table` — เพิ่มคอลัมน์ nullable
```
campaign_type   enum('advertisement','support')   default 'advertisement'
scope_type      enum('public','academy','course') default 'public'
academy_id      nullable, FK academies (nullOnDelete)
course_id       nullable, FK courses (nullOnDelete)
beneficiary_id  nullable, FK users
inherit_to_academy  boolean default false          // course→academy toggle
payment_status  enum('unpaid','pending_slip','paid','refunded') default 'unpaid'
review_status   enum('pending','approved','rejected')           default 'pending'
budget_amount   decimal(10,2) nullable             // แทน amounts (integer) ที่ปัดเศษ
active_from     timestamp nullable
active_until    timestamp nullable
idempotency_key string nullable
reviewed_by     nullable FK users
reviewed_at     timestamp nullable
refunded_at     timestamp nullable
```

### 1.2 Migration backfill ข้อมูลเดิม (แยกไฟล์ ไม่ปนกับ schema)
- `duration > 0` → `campaign_type='advertisement'`, `scope_type='public'`
- `duration = 0 && exchange_points > 0` → `campaign_type='support'`, `scope_type='public'` (platform)
- copy `amounts` → `budget_amount`
- map `status` เดิม → `review_status` (0→pending, 1→approved, 2→rejected) + `payment_status` (มี slip→pending_slip/ตามจริง, ไม่มี→paid)

### 1.3 Composite indexes
```
(scope_type, academy_id, review_status)
(scope_type, course_id, review_status)
(campaign_type, review_status, remaining_views)
unique (user_id, advert_id, idempotency_key)   // กัน view ซ้ำ
```

### 1.4 PHP enums
`App\Enums\CampaignType`, `ScopeType`, `PaymentStatus`, `ReviewStatus` — เลิกใช้ magic number

### 1.5 `config/campaign.php` — รวมค่าคงที่ทั้งหมด (แก้บั๊กค่ากระจาย 3 ที่)
```php
'ad_price_per_view_second'   => 0.10,
'viewer_reward_per_second'   => 0.06,
'referrer_reward_per_second' => 0.02,
'points_per_reward_baht'     => 1200,
'points_rate' => ['platform'=>1080, 'academy'=>1080, 'course'=>1080], // ต่อ scope
'revenue_split' => ['academy'=>0.70, 'instructor'=>0.20, 'platform'=>0.10],
'platform_account_code' => 99999999,
```

---

## Phase 2 — Service layer (ดึง logic ออกจาก controller)

### 2.1 `CampaignAuthorizationService` — ห้ามใช้ `auth()->id()` ตัดสินเดี่ยว
- `canCreate(user, scope, academy?, course?)` — public: any verified; academy: `Academy::canManage()`; course: owner/role-4 admin
- `canReview(user, campaign)` — public→plearnd_admin; academy→academy owner/admin; course→course owner/instructor; system admin override เสมอ
- **ตรวจ course อยู่ใน academy ที่อ้างจริง** (กัน scope ปลอม)

### 2.2 `CampaignPricingService`
คิดราคา server-side จาก config (ย้ายสูตร `AdvertController.php:119` มาไว้ที่เดียว)

### 2.3 `CampaignDeliveryService` — query widget ตาม scope/context
- public → ทุกที่สาธารณะ
- academy → เฉพาะ academy นั้น (+ course ที่ `inherit_to_academy=true`)
- course → เฉพาะ course นั้น
- filter: `review_status=approved`, `remaining_views>0`, ช่วง `active_from/until`

### 2.4 `CampaignImpressionService` vs `CampaignViewService` (แยก 2 action)
- **impression** — บันทึกการเห็นแบบถูก ไม่จ่ายรางวัล ไม่หัก remaining_views
- **rewarded view** — ย้าย `decrement(remaining_views)` **เข้า transaction + lockForUpdate** (แก้ race/refund bug); ใช้ `idempotency_key` กันกดซ้ำ; เช็คโควตา 5/วัน + attach + จ่ายรางวัลใน transaction เดียว

### 2.5 `SupportPaymentService`
ตัด wallet/รับสลิป, ให้แต้มตาม `points_rate[scope]`, แบ่งเงินตาม `revenue_split` (academy owner / instructor_id / platform account) แบบ atomic

### 2.6 `CampaignRefundService`
reject แล้วคืนเงิน/แต้ม (แก้บั๊ก `reject()` ที่ไม่คืนเลย): wallet-paid→คืน wallet; slip→mark refunded (คืน manual); support ที่ให้แต้มแล้ว→หักแต้มคืน

## Phase 1-2 — ✅ เสร็จแล้ว (2026-07-12)

### สิ่งที่สร้าง

| ไฟล์ | ประเภท | หมายเหตุ |
|------|--------|----------|
| `database/migrations/2026_07_12_120000_add_campaign_fields_to_adverts_table.php` | Migration | เพิ่ม campaign fields แบบ additive |
| `database/migrations/2026_07_12_120001_backfill_campaign_fields_on_adverts.php` | Migration | Backfill ข้อมูล legacy |
| `config/campaign.php` | Config | ราคา, reward, points rate, revenue split |
| `app/Enums/CampaignType.php` | Enum | advertisement, support |
| `app/Enums/CampaignScopeType.php` | Enum | public, academy, course |
| `app/Enums/CampaignPaymentStatus.php` | Enum | unpaid, pending_slip, paid, refunded |
| `app/Enums/CampaignReviewStatus.php` | Enum | pending, approved, rejected |
| `app/Services/Campaign/CampaignAuthorizationService.php` | Service | canCreate, assertCanCreate, canReview |
| `app/Services/Campaign/CampaignPricingService.php` | Service | advertisement pricing, pointsRate per scope |
| `app/Services/Campaign/CampaignDeliveryService.php` | Service | query builder ตาม scope/context |
| `app/Services/Campaign/CampaignViewService.php` | Service | impression (counter), rewardedView (atomic) |
| `app/Services/Campaign/SupportPaymentService.php` | Service | payWithWallet, markSlipPending |
| `app/Services/Campaign/CampaignRefundService.php` | Service | refundWallet (atomic) |
| `app/Models/Advert.php` | Model update | casts + relationships ใหม่ |

### ตรวจสอบแล้ว
- ✅ PHP syntax ผ่านทุกไฟล์
- ✅ Laravel Pint ผ่าน
- ✅ `php artisan migrate --pretend` ผ่าน
- ⚠️ ยังไม่ได้รัน migration จริง
- ✅ route/controller เดิมยังไม่ถูกเปลี่ยน (backward compatible)

---

## Review Findings & Fixes (2026-07-12) — ✅ แก้ครบแล้ว (14/14 tests ผ่าน)

ตรวจสอบ Phase 1-3 ทั้งหมด (syntax, pint, migrate --pretend, route:list, test suite) แล้วพบและแก้ finding ดังนี้:

### 🔴 HIGH — แก้แล้ว
1. **จ่ายเงินสนับสนุนซ้ำได้** — เพิ่มคอลัมน์ `distributed_at` + guard ใน `SupportPaymentService::distributeSupport()` (distribute ได้ครั้งเดียว idempotent) + backfill mark legacy approved support เป็น distributed
2. **reject หลัง approve = เสกเงิน** — เพิ่ม state-machine guard ใน `CampaignController::review()`: approve/reject ทำได้เฉพาะจาก `pending`, reject บล็อกถ้า `distributed_at` แล้ว, pause เฉพาะจาก `approved` → 422 ถ้าผิด state

### 🟡 MEDIUM — แก้แล้ว
3. **แต้มผู้สนับสนุนไม่เคยถูกให้** — `distributeSupport()` credit `pp` ให้ผู้สนับสนุนตาม `exchange_points` (atomic, ภายใต้ guard เดียวกัน กันซ้ำ)
4. **Support โผล่ widget ไม่ได้** — `CampaignDeliveryService` เพิ่ม filter `campaign_type='advertisement'` ให้ชัดว่า delivery = โฆษณาเท่านั้น (support เป็น action แยก)
5. **course campaign ไม่เก็บ academy_id** — `store()` derive `academy_id` จาก `course->academy_id` ฝั่ง server เมื่อ scope=course → inherit ทำงานจริง
6. **referrer reward + points-portion หาย** — `rewardedView()` wire config ครบ: viewer ได้ `duration×0.06 + pointsRequired/points_per_reward_baht`, เพิ่ม `rewardReferrer()` จ่าย `duration×0.02` ให้ผู้แนะนำ (fallback platform account)

### 🟢 LOW — แก้แล้ว
7. **ครบโควตา → HTTP 500** → สร้าง `DailyViewLimitException` render เป็น **429**
8. **nested transaction ซ้ำใน `store()`** → flatten เหลือ transaction เดียว
9. **คอมเมนต์ "คิดดัง ๆ" ค้างใน `distributeSupport()`** → ลบออก
10. **backfill payment_status CASE ผิดลำดับ** → เช็ค `status=1 → paid` ก่อน slip

### Test ที่เพิ่ม
- `it_does_not_double_distribute_support_when_paused_then_reapproved` — pause→approve ไม่จ่ายซ้ำ
- `it_blocks_reject_after_support_was_distributed` — reject หลัง distribute → 422 ยอดไม่เปลี่ยน
- อัปเดต `it_limits_rewarded_views...` (429 + reward ใหม่) และ `it_splits_support_revenue...` (assert แต้มผู้สนับสนุน + `distributed_at`)

### ไฟล์ที่แก้/เพิ่มในรอบ review
- `app/Exceptions/DailyViewLimitException.php` (ใหม่)
- `app/Services/Campaign/{CampaignViewService,SupportPaymentService,CampaignDeliveryService}.php`
- `app/Http/Controllers/Api/Campaign/CampaignController.php`
- `app/Models/Advert.php` (cast `distributed_at`)
- migrations `120000` (+`distributed_at`), `120001` (backfill fix + legacy distributed)
- `tests/Feature/Campaign/CampaignSystemTest.php`

---

## Frontend Verification (2026-07-12) — ✅ Nuxt build ผ่าน

- ✅ `npm run build` สำเร็จ (exit 0, "Build complete") — ไฟล์ที่แก้ทั้งหมด compile ได้ (มีแค่ warning sourcemap ของ nuxt:components-loader ซึ่ง benign)
- ✅ `CampaignWidget.vue` auto-import เป็นชื่อ `CampaignWidget` (Nuxt dedupe `campaign/Campaign*`) — resolve ได้จริง
- ✅ เดินสายเข้า 3 จุด: public ([`AdvertisesWidget.vue`](ui/components/widgets/AdvertisesWidget.vue) → wrap), course ([`CoursePageShell.vue:238`](ui/components/learn/course/v2/CoursePageShell.vue)), academy desktop+mobile ([`academies/[name].vue`](ui/pages/academies/[name].vue))

### แก้ contract mismatch frontend↔backend
- **reward_per_view แสดง≠จ่ายจริง** — `CampaignResource::rewardPerView()` คำนวณตรงกับ `CampaignViewService` (base 0.06 + points-portion)
- **route `impression` อยู่หลัง auth** — ย้ายออกเป็น public (RecordImpressionRequest อนุญาต guest, service รองรับ viewer=null) → widget นับ impression ให้ guest ได้

### ยังเหลือ (Phase 4-5 ที่ยังไม่ครบ)
- Plan เดิมแยก `AdvertisementCard/SupportCard/CampaignViewerModal` — ปัจจุบันรวม modal ไว้ใน `CampaignWidget` (ใช้งานได้ แต่ยังไม่แตกเป็น component ย่อย)
- **ปุ่ม "สนับสนุน" ในพื้นที่ยังไม่มีจุดเริ่ม** — widget เป็น delivery ของโฆษณาเท่านั้น (support ไม่โผล่) การสร้าง support ทำผ่าน `create.vue` เท่านั้น ยังไม่มีปุ่ม support บนหน้า academy/course
- create.vue (+1150) / manage.vue — compile ผ่าน แต่ยังไม่ได้ทดสอบ flow จริง (ต้องรัน server ทั้งคู่)

---

## Phase 3 — API Contract (🔧 กำลังดำเนินการ)

**เป้าหมาย:** เชื่อม FormRequests, CampaignController, CampaignResource และ routes ใหม่เข้ากับ service layer จาก Phase 2 — พร้อมเพิ่ม `campaign_delivery_events` table สำหรับเก็บ impression รายรายการ

**Strangler strategy**: routes เดิม `/api/advertises/*` ยังทำงานเหมือนเดิม 100% — routes ใหม่ `/api/campaigns/*` เพิ่มเข้ามาขนานกัน

### 3.0 Migration — Delivery Events Table

**[NEW]** `database/migrations/2026_07_12_130000_create_campaign_delivery_events_table.php`

```
campaign_delivery_events
├── id (bigIncrements)
├── advert_id (FK → adverts, cascadeOnDelete)
├── user_id (FK → users, nullable, nullOnDelete)
├── event_type enum('impression','rewarded_view') default 'impression'
├── ip_hash string(64) nullable
├── user_agent string(255) nullable
├── placement string(50) nullable
├── idempotency_key string nullable
├── metadata json nullable
├── created_at timestamp (index)
└── indexes: (advert_id, event_type), (user_id, created_at)
└── unique nullable: (advert_id, user_id, idempotency_key)
```

**เหตุผล**: `impressions_count` column = denormalized counter (เร็ว) | events table = รายละเอียด (audit, analytics)

### 3.1 FormRequests (4 ไฟล์)

ตำแหน่ง: `app/Http/Requests/Campaign/`

#### StoreCampaignRequest
- `authorize()` → delegate to `CampaignAuthorizationService::assertCanCreate()`
- **Scope integrity validation**:
  - `public` → `academy_id`,`course_id` ต้อง NULL
  - `academy` → ต้องมี `academy_id` + ผ่าน authorization
  - `course` → ต้องมี `course_id` + course อยู่ใน academy ที่อ้างจริง
- **Advertisement rules**: `duration` in:5,10,15,30,60; `total_views` min:100,max:100000; `budget_amount` numeric + server-side price recheck
- **Support rules**: `budget_amount` numeric,min:1
- `payment_method` in: wallet,slip; slip requires: image, transfer_date, transfer_time

#### RecordImpressionRequest
- `authorize()` → true (รวม guest)
- Rules: `placement` nullable|max:50, `idempotency_key` nullable|max:100

#### RecordViewRequest
- `authorize()` → auth check
- Rules: `idempotency_key` required|max:100

#### ReviewCampaignRequest
- `authorize()` → `CampaignAuthorizationService::canReview()`
- Rules: `action` in:approve,reject,pause; `rejection_reason` required_if:action,reject

### 3.2 CampaignResource

**[NEW]** `app/Http/Resources/Campaign/CampaignResource.php`

ส่ง campaign fields ใหม่ครบ + backward-compatible legacy fields (amounts, status, supporter)

**ไม่แก้** `Shared/AdvertResource` เดิม (frontend เก่ายังอ้างอยู่)

Fields:
- campaign_type, scope_type
- advertiser object {id, name, display_name, avatar, profile_photo_url}
- academy {id, name, logo}, course {id, title}, beneficiary {id, name}
- inherit_to_academy
- title, description, media_link, media_image
- budget_amount, duration, total_views, remaining_views, impressions_count
- active_from, active_until
- payment_status, review_status (enum label)
- reviewer {id, name}, reviewed_at (conditional)
- reward_per_view (computed จาก config)
- Legacy compat: amounts, status, supporter

### 3.3 CampaignController

**[NEW]** `app/Http/Controllers/Api/Campaign/CampaignController.php`

**หลักการ**: thin layer — เรียก services, รับ FormRequests, return Resources

| Method | Route | Service |
|--------|-------|---------|
| `widget(Request)` | `GET /campaigns/widget` | `CampaignDeliveryService::query()` |
| `index(Request)` | `GET /campaigns` | `CampaignDeliveryService::query()` |
| `store(StoreCampaignRequest)` | `POST /campaigns` | Pricing + SupportPayment |
| `impression(RecordImpressionRequest, Advert)` | `POST /campaigns/{c}/impression` | `CampaignViewService::impression()` |
| `view(RecordViewRequest, Advert)` | `POST /campaigns/{c}/view` | `CampaignViewService::rewardedView()` |
| `manage(Request)` | `GET /campaigns/manage` | Own campaigns |
| `academyManage(Request, Academy)` | `GET /academies/{a}/campaigns/manage` | Academy campaigns |
| `courseManage(Request, Course)` | `GET /courses/{c}/campaigns/manage` | Course campaigns |
| `review(ReviewCampaignRequest, Advert)` | `PATCH /campaigns/{c}/review` | AuthZ + RefundService |

**`store()` flow**:
1. FormRequest validates + authorizes
2. Upload files (slip, media_image)
3. Advertisement → server recalculate price via `CampaignPricingService`
4. Support → `budget_amount` ตามที่ส่งมา, คำนวณ `exchange_points = budget_amount × points_rate[scope]`
5. สร้าง Advert record (เซ็ตทั้ง legacy fields + campaign fields)
6. Payment: wallet → `SupportPaymentService::payWithWallet()` + review_status=approved; slip → `markSlipPending()` + review_status=pending
7. Log Activity

**`review()` flow**:
1. FormRequest validates + authorizes (canReview)
2. approve → set review_status + reviewed_by + reviewed_at + sync legacy status=1
3. reject → set review_status + reviewed_by + reviewed_at + sync legacy status=2 → trigger `CampaignRefundService::refundWallet()` ถ้า paid
4. pause → review_status=pending (เฉพาะ approved→pending)

### 3.4 CampaignDeliveryEvent Model

**[NEW]** `app/Models/CampaignDeliveryEvent.php`

- fillable: advert_id, user_id, event_type, ip_hash, user_agent, placement, idempotency_key, metadata
- casts: metadata→array, created_at→datetime
- No updated_at
- Relationships: advert() BelongsTo, user() BelongsTo

### 3.5 Routes

**[NEW]** `routes/earn/campaign.php`

```
// Public (no auth)
GET  /campaigns/widget                    → widget
GET  /campaigns                           → index

// Authenticated
POST /campaigns                           → store
POST /campaigns/{campaign}/impression     → impression
POST /campaigns/{campaign}/view           → view
GET  /campaigns/manage                    → manage

// Scoped management
GET  /academies/{academy}/campaigns/manage  → academyManage
GET  /courses/{course}/campaigns/manage     → courseManage

// Review (auth + authorization via FormRequest)
PATCH /campaigns/{campaign}/review          → review
```

**ไม่แก้** `routes/earn/advert.php` — legacy routes ทำงานเหมือนเดิม

### 3.6 ไฟล์ที่ต้องแก้ไข

| ไฟล์ | การเปลี่ยนแปลง |
|------|----------------|
| `app/Services/Campaign/CampaignViewService.php` | เพิ่ม insert CampaignDeliveryEvent + referrer reward |
| `app/Models/Advert.php` | เพิ่ม `deliveryEvents()` relationship |
| `routes/api.php` | เพิ่ม `require __DIR__.'/earn/campaign.php';` |

### 3.7 Verification Plan

```bash
php -l app/Http/Controllers/Api/Campaign/CampaignController.php
php -l app/Http/Requests/Campaign/StoreCampaignRequest.php
php -l app/Http/Requests/Campaign/RecordImpressionRequest.php
php -l app/Http/Requests/Campaign/RecordViewRequest.php
php -l app/Http/Requests/Campaign/ReviewCampaignRequest.php
php -l app/Http/Resources/Campaign/CampaignResource.php
php -l app/Models/CampaignDeliveryEvent.php
php artisan migrate --pretend
php artisan route:list --path=campaigns
./vendor/bin/pint --dirty
```

---

## Phase 4 — Widget กลาง (frontend)

- **4.1** `CampaignWidget.vue` — รับ `scope/academyId/courseId/placement`; states loading/empty/error; badge scope (Public/School/Course); dark mode; กันยิง API ซ้ำ (debounce + guard); เรียก `impression` ตอนเห็น, `view` ตอนคลิก/ดูจบ
- **4.2** `AdvertisementCard.vue` / `SupportCard.vue` / `CampaignViewerModal.vue`
- **4.3** **แก้บั๊กตอนสร้างใหม่:** แสดงรางวัลจากค่าจริง backend (ไม่ hardcode `0.07`)
- **4.4** วาง widget: public (earn/feed/home) → academy pages → course pages; แทน `AdvertisesWidget.vue` เดิม

---

## Phase 5 — หน้า Create + Dashboard
- Create: เลือกประเภท (โฆษณา/สนับสนุน) → พื้นที่ (สาธารณะ/โรงเรียน/รายวิชา) → field ตามที่เลือก + toggle inherit (เฉพาะ course); เลือก wallet/สลิป
- Dashboard ผู้สร้าง: campaign/ยอดวิว/งบ/สถานะ review
- Dashboard admin: pending review, refund, audit log

## Phase 6 — Tests (ครอบบั๊กจริง)
- ยิง `/view` พร้อมกัน → ไม่หัก remaining_views เกิน / ไม่เกิน 5/วัน
- reject → คืนเงิน/แต้มถูกต้อง
- budget เศษสตางค์ไม่ถูกปัด
- academy campaign ไม่หลุดข้ามโรงเรียน; course inherit ทำงานเฉพาะเมื่อเปิด toggle
- support แบ่ง 70/20/10 ถูกบัญชี
- `donates` เดิมยังอ่านได้ (ไม่ regress)
- สิทธิ์: guest / member / instructor / academy-admin / system-admin

## Phase 7 — เก็บกวาด (ขั้นสุดท้าย)
เมื่อ frontend เลิกอ้าง field เก่าครบ → พิจารณา rename ตาราง/route จริง, ลบ alias, ลบคอลัมน์ `amounts`/`duration=0` hack, drop `status` เก่า

---

## ลำดับลงมือ
**Phase 1-2 ✅ เสร็จ** → **Phase 3 🔧 กำลังทำ** → Phase 4 → 5 → 6 → 7

---

## ไฟล์อ้างอิงหลัก (โค้ดจริง)
- `api/nuxnanravel/app/Http/Controllers/AdvertController.php` — โฆษณา (store/view/approve/reject)
- `api/nuxnanravel/app/Http/Controllers/Api/Shared/SupportController.php` — support (ยัดลง adverts)
- `api/nuxnanravel/app/Models/Advert.php`, `AdvertViewer.php`, `Donate.php`
- `api/nuxnanravel/routes/earn/advert.php`, `routes/earn/donate.php`
- `api/nuxnanravel/database/migrations/2025_10_26_070433_create_supports_table.php` (+ rename migrations ม.ค. 2026)
- `ui/components/widgets/AdvertisesWidget.vue`, `ui/components/widgets/advertises/AdvertiseItemCard.vue`
- `ui/pages/Earn/Advertise/{index,create}.vue`, `ui/pages/PlearndAdmin/Support/ApproveAdvertise.vue`

