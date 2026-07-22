# Plan: Course Points — Manual Claim UI + Quiz Reward Campaign

> Spec สำหรับ Codex ลงมือเขียน. Claude เป็นผู้ orchestrate + verify diffs/tests.
> เป้าหมาย: ให้ **นักเรียนกดรับแต้มเอง** จาก manual campaign (คล้ายเมนูสะสมแต้ม) และ
> ขยาย Reward Campaign ให้ครอบคลุม **"ทำแบบทดสอบเสร็จ"** เพิ่มจาก "อ่านบทเรียนจบ"
> กติกา: ถ้าแต้มในกระเป๋าวิชายังพอ → ให้รางวัล, ถ้าไม่พอ → ให้สิทธิ์คนที่ทำเสร็จก่อน (first-come-first-served)

---

## 0. บริบท / ไฟล์ที่เกี่ยวข้อง (อ่านก่อนแก้)

- Service หลัก: `api/nuxnanravel/app/Services/CoursePointAccountService.php`
- Lesson completion hook: `api/nuxnanravel/app/Services/LessonCompletionService.php`
- Controllers:
  - `app/Http/Controllers/Api/Learn/Course/points/CoursePointCampaignController.php` (manual campaign + claim)
  - `app/Http/Controllers/Api/Learn/Course/points/LessonRewardCampaignController.php` (lesson reward)
  - `app/Http/Controllers/Api/Learn/Course/quizzes/CourseQuizResultController.php` (quiz finalize — จุด hook quiz reward)
- Models: `CoursePointCampaign`, `CoursePointCampaignClaim`, `CoursePointAccount`, `CoursePointTransaction`, `CourseMember`, `CourseQuiz`
- Routes: `api/nuxnanravel/routes/learn/course.php` (บล็อก `/courses/{course}/points`)
- Frontend: `ui/composables/useCoursePoints.ts`, `ui/components/learn/course/points/`

**บั๊กปัจจุบันที่ต้องแก้:** `CoursePointAccountService::claimCampaign()` (บรรทัด ~470) route ผ่าน `->lesson`
ทำให้ manual campaign (`lesson_id = null`) หา campaign ไม่เจอ คืน `no_active_campaign` เสมอ → ปุ่ม claim ใช้ไม่ได้จริง

---

## PHASE 1 — Backend refactor (แกนกลาง)

### 1.1 Migration: เพิ่มชนิด quiz + คอลัมน์ quiz_id
สร้าง migration ใหม่ `..._add_quiz_reward_to_course_point_campaigns.php`:
- ขยาย enum `campaign_type` เป็น `['manual_claim', 'lesson_completion', 'quiz_completion']`
  (ใช้ raw `ALTER TABLE ... MODIFY` หรือ doctrine change; MySQL — ยืนยันวิธี modify enum ที่ repo ใช้อยู่)
- เพิ่ม `quiz_id` unsignedBigInteger nullable after `lesson_id`
- FK `quiz_id` → ตาราง quiz (ยืนยันชื่อตารางจริงจาก model `CourseQuiz`; คาดว่า `course_quizzes`) `nullOnDelete()`
- เพิ่ม index `['quiz_id', 'status', 'campaign_type']`
- เขียน `down()` ย้อนกลับให้ครบ (drop FK, index, column, revert enum)

### 1.2 Model `CoursePointCampaign`
- เพิ่มค่าคงที่ `const CAMPAIGN_TYPE_QUIZ = 'quiz_completion';`
- เพิ่ม relation `quiz(): BelongsTo` → `CourseQuiz`

### 1.3 Service: refactor ให้มี core claim เดียว (ลดโค้ดซ้ำ + คุม concurrency ที่เดียว)
ใน `CoursePointAccountService` แยก logic body ของ `grantLessonCompletionReward` (ตั้งแต่ lock campaign → สร้าง claim)
ออกเป็น method กลาง:

```php
protected function grantCampaignClaim(
    CoursePointCampaign $campaign,
    User $student,
    string $txSource,           // 'lesson_completion' | 'quiz_completion' | 'manual_claim'
    string $earnReason,         // ส่งเข้า PointsService::earn
    int $earnRefId,             // lesson_id / quiz_id / campaign_id
    string $earnDescription,
    ?string $idempotencyKey = null,
    array $extraCourseTxMeta = []
): array
```

Core logic (คงพฤติกรรมเดิมทั้งหมด — ดูของเดิมบรรทัด 319–409):
- `DB::transaction`
- idempotency short-circuit ผ่าน `CoursePointTransaction::where('idempotency_key', ...)`
- `lockForUpdate` ลำดับ **campaign → account** (กัน deadlock)
- เช็ค `isClaimable()`
- กันซ้ำ: `CoursePointCampaignClaim::where('campaign_id')->where('user_id')->exists()` → `already_claimed`
- **กติกา FCFS / งบ:**
  - ถ้า `max_claims` (มี reserve): ต้อง `balance >= amount && reserved_balance >= amount` ไม่งั้น set `DEPLETED` + คืน `depleted`
  - ถ้าไม่มี `max_claims`: ต้อง `available_balance >= amount` ไม่งั้นคืน `insufficient_balance`
  - → นี่คือ first-come-first-served อยู่แล้ว: คนที่ทำเสร็จ/กดก่อนแล้ว balance ยังพอ = ได้; คนหลัง balance หมด = พลาด
- หัก `balance`, `+total_distributed`, ลด `reserved_balance` (เฉพาะ max_claims), `+version`
- สร้าง `CoursePointTransaction` type `TYPE_STUDENT_CLAIM` (แนบ `related_campaign_id`, metadata `source = $txSource`)
- `PointsService::earn($student, $amount, $earnReason, $earnRefId, $earnDescription, ['campaign_id' => ...])`
- สร้าง `CoursePointCampaignClaim`
- `increment('total_claimed')`, `increment('total_points_claimed', $amount)`
- ถ้า `max_claims` เต็ม → set `DEPLETED`
- return `['rewarded' => true, 'points_received' => $amount, 'campaign_title' => ...]`

จากนั้นให้ 3 entry points เรียก core นี้:

**(a) `grantLessonCompletionReward(Lesson $lesson, User $student, ?string $idempotencyKey = null)`** — ปรับให้ค้น campaign แบบเดิม (type `LESSON`, `lesson_id`, status ACTIVE) แล้วเรียก `grantCampaignClaim(... 'lesson_completion' ...)`

**(b) `grantQuizCompletionReward(CourseQuiz $quiz, User $student, ?string $idempotencyKey = null)`** — ใหม่ ค้น campaign type `QUIZ` ด้วย `quiz_id` แล้วเรียก core
- idempotency key แนะนำ: `"quiz_reward:{quiz_id}:{user_id}"` (กันแจกซ้ำแม้ทำแบบทดสอบหลายครั้ง)

**(c) `claimManualCampaign(int $campaignId, User $student)`** — ใหม่ แทนของพังเดิม
- โหลด campaign, `abort/return` ถ้า type != `MANUAL` หรือไม่ `isClaimable()`
- **เช็ค enrollment**: `CourseMember::where('user_id', $student->id)->where('course_id', $campaign->course_id)->exists()` (eligible_type = all_enrolled) ไม่ผ่าน → `['success' => false, 'message' => 'ต้องเข้าร่วมรายวิชาก่อน']`
- เรียก core `grantCampaignClaim(... 'manual_claim', earnReason 'course_manual_claim', earnRefId = campaign->id ...)`
- map ผลลัพธ์ core → `{ success: bool, message, points_received?, new_user_points? }`

**(d) ลบ/แทน `claimCampaign()` เดิม** (บรรทัด 470–474) — ให้ delegate ไป `claimManualCampaign()` เพื่อ backward-compat หรือแก้ controller ให้เรียกตัวใหม่ตรง ๆ

> หมายเหตุ concurrency: `grantQuizCompletionReward` ต้องเผื่อเคสเรียกจากหลาย request — core ใช้ lock+idempotency อยู่แล้ว ปลอดภัย

### 1.4 Controller `CoursePointCampaignController`
- `claim()` → เรียก `$this->service->claimManualCampaign($campaign->id, auth()->user())` (แทน `claimCampaign`)
- เพิ่ม method `available(Course $course)` — student-facing list (ดู 1.6 response shape) คืนเฉพาะ manual campaign ที่ `isClaimable()` + สถานะ per-user
- `store()` validation: เพิ่มรับ `campaign_type` ถ้าจะให้สร้าง manual ชัดเจน (ตอนนี้ default manual อยู่แล้ว — คงไว้ได้)

### 1.5 Quiz reward hook
ใน `CourseQuizResultController::update()` — ในบล็อก `finalize == true` (บรรทัด ~141–151) หลัง fire gamification events:
- inject `CoursePointAccountService` ผ่าน constructor
- เรียก `$reward = $this->coursePointService->grantQuizCompletionReward($quiz, $user, "quiz_reward:{$quiz->id}:{$user->id}")`
- แนบ `$reward` ใน response (`'reward' => $reward`)
- **ตัดสินใจกติกา:** ให้รางวัลเมื่อ finalize ครั้งแรกเท่านั้น (idempotency key จัดการซ้ำให้แล้ว). ยืนยันกับเจ้าของว่า
  ต้อง "สอบผ่าน" (`STATUS_PASSED`) หรือแค่ "ทำเสร็จ" — **ค่าเริ่มต้นในแผน: แค่ทำเสร็จ (finalize) ก็ได้แต้ม**
  ถ้าต้องการผูกกับผ่านเกณฑ์ ให้เพิ่มคอลัมน์ `require_pass` ใน campaign (optional, phase later)

### 1.6 LessonRewardCampaignController → generalize เป็น reward setting ของทั้ง lesson และ quiz (owner side)
- ทางเลือก A (เร็ว): คง controller lesson เดิม + เพิ่ม `QuizRewardCampaignController` คู่ขนาน (show/store/destroy) ที่ resolve ด้วย `quiz` แทน `lesson` เรียก service method ใหม่ `createQuizRewardCampaign(courseId, quizId, data, createdBy)` (clone จาก `createLessonRewardCampaign` เปลี่ยน type + field)
- ทางเลือก B (สะอาดกว่า แต่แตะเยอะ): รวมเป็น controller เดียว param-driven
- **แผนเลือก A** เพื่อลด risk. เพิ่ม service `createQuizRewardCampaign()` mirror ของ lesson (reserve logic เหมือนกัน, กันซ้ำ campaign active ต่อ quiz)

### 1.7 Routes (`routes/learn/course.php`)
เพิ่มในบล็อก `/courses/{course}/points`:
```php
Route::get('/campaigns/available', [CoursePointCampaignController::class, 'available'])
    ->name('course.points.campaigns.available');
```
เพิ่มบล็อก quiz reward (owner) คู่กับ lesson reward:
```php
Route::prefix('/courses/{course}/quizzes/{quiz}/reward')->group(function () {
    Route::get('/',    [QuizRewardCampaignController::class, 'show']);
    Route::post('/',   [QuizRewardCampaignController::class, 'store']);
    Route::delete('/', [QuizRewardCampaignController::class, 'destroy']);
});
```
> `claim` route มีอยู่แล้ว (บรรทัด 71) ไม่ต้องเพิ่ม

### Response shape — `available()` (สำคัญ ต้องตรงกับ frontend)
```json
{
  "data": [
    {
      "id": 12,
      "title": "รับแต้มต้อนรับ",
      "description": "กดรับได้เลยเมื่อเข้าร่วมรายวิชา",
      "points_per_claim": 50,
      "max_claims": 100,
      "remaining": 42,           // null = ไม่จำกัด
      "total_claimed": 58,
      "status": "active",
      "starts_at": null,
      "ends_at": null,
      "claimed_by_auth": false,  // ผู้ใช้ปัจจุบันเคยรับแล้วหรือยัง
      "can_claim": true          // enrolled && claimable && !claimed_by_auth && (remaining>0|null)
    }
  ]
}
```

---

## PHASE 2 — Frontend (นักเรียนกดรับ)

### 2.1 `ui/composables/useCoursePoints.ts`
เพิ่ม state + methods:
```ts
const campaigns = ref<any[]>([])
const isLoadingCampaigns = ref(false)
const isClaiming = ref<number | null>(null)   // campaign id ที่กำลัง claim

const fetchAvailableCampaigns = async () => { /* GET /api/courses/{id}/points/campaigns/available → campaigns.value */ }
const claimCampaign = async (campaignId: number) => {
  // POST /api/courses/{id}/points/campaigns/{campaignId}/claim
  // on success: refetch campaigns + fetchAccount(); return res
}
```
export เพิ่ม `campaigns, isLoadingCampaigns, isClaiming, fetchAvailableCampaigns, claimCampaign`

> ใช้ผ่าน `useApi` เท่านั้น (ตาม convention — ห้าม `$fetch` ตรง)

### 2.2 Component ใหม่ (ใช้ hopeui-port skill ดึง markup ต้นแบบก่อน)
- `ui/components/learn/course/points/CoursePointClaimCard.vue` — การ์ด 1 campaign: title, description, badge `+N แต้ม`, โควตาเหลือ, ปุ่ม **"รับแต้ม"**
  - state ปุ่ม: `รับแต้ม` (can_claim) / `รับแล้ว` (claimed_by_auth, disabled + เขียว) / `โควตาเต็ม` (disabled) / spinner ระหว่าง claim
  - reuse สไตล์ badge จาก `LessonRewardBadge.vue`
  - emit `claim` → parent เรียก composable
  - toast/แจ้งผลเมื่อสำเร็จ (`+50 แต้ม!`) — ใช้ระบบ toast ที่โปรเจคใช้อยู่ (ตรวจ pattern เดิม)
- `ui/components/learn/course/points/CoursePointClaimList.vue` — โหลด `fetchAvailableCampaigns`, loop การ์ด, empty state ("ยังไม่มีแต้มให้รับตอนนี้"), skeleton ระหว่างโหลด

### 2.3 จุดวาง (mount point)
- วางใน course page ฝั่งนักเรียน: แผงด้านข้าง/แท็บของ `ui/pages/Learn/Courses/[id].vue` หรือใน `CoursePageShell.vue`
  (มี slot `#tabs-slot` + sidebar area — ยืนยันตำแหน่งที่เห็นชัดสำหรับนักเรียน)
- แสดงเฉพาะเมื่อ **มี campaign ที่ available > 0** เพื่อไม่รก UI
- **ต้องยืนยันตำแหน่งกับเจ้าของก่อน finalize UI** (Claude จะถามตอน review)

---

## PHASE 3 — ทดสอบ / verify (Claude ตรวจ)

Backend (PHPUnit):
- `claimManualCampaign`: happy path (ได้แต้ม, balance ลด, สร้าง claim + 2 ledger), กันซ้ำ (`already_claimed`),
  ไม่ enrolled (block), balance ไม่พอ (FCFS: คนแรกได้ คนสองพลาด), campaign paused/ended/expired (block)
- `grantQuizCompletionReward`: finalize ครั้งแรกได้แต้ม, finalize ซ้ำไม่ได้แต้มซ้ำ (idempotency), ไม่มี campaign → no-op
- concurrency: two claimants แข่งกันบน balance เหลือพอ 1 คน → มีคนเดียวได้ (ยืนยัน lock ทำงาน)
- รัน `./vendor/bin/pint` ก่อน commit

Frontend:
- build check (ผู้ใช้รันเอง — อย่ารัน `npm run build`), ตรวจ type ของ response ให้ตรง shape 1.6

Commit เป็นชุดเล็ก: (1) migration+model (2) service refactor (3) controllers+routes (4) quiz hook (5) frontend composable (6) frontend components+mount

---

## ✅ การตัดสินใจ (ยืนยันจากเจ้าของแล้ว 2026-07-23)
1. **Quiz reward = ให้แต้มเฉพาะ "สอบได้คะแนนเต็ม" เท่านั้น** (ไม่ใช่แค่ทำเสร็จ/ผ่านเกณฑ์)
   → guard ใน `CourseQuizResultController::update` finalize branch: เรียก `grantQuizCompletionReward` เฉพาะเมื่อ `$data['percentage'] >= 100`
2. **จุดวาง UI = widget แยก** วางบนหน้าหลัก course ที่นักเรียนเห็น (การ์ด "สะสมแต้ม")
   หมายเหตุ: หน้า `courses/[id]/wallet/withdraw.vue` เป็นฝั่งเจ้าของ (ถอนแต้มออก) — คนละ audience กับ claim จึงแยก widget
3. **Manual campaign รับครั้งเดียวถาวร** — คงพฤติกรรมปัจจุบัน (claim table กันซ้ำถาวร) ไม่ต้องแก้

## PHASE 1.5 — Backend amendment (quiz คะแนนเต็ม) + Tests
- แก้ `CourseQuizResultController::update`: ครอบ `grantQuizCompletionReward(...)` ด้วยเงื่อนไข `$data['percentage'] >= 100`
  (เดิม default เรียกทุกครั้งที่ finalize — ต้องเพิ่ม guard). ถ้าไม่ถึงคะแนนเต็ม `$reward = null`
- เพิ่ม PHPUnit tests ตาม Phase 3 (backend list) — รวมเคส quiz reward ให้เฉพาะ percentage 100

## PHASE 2 (อัปเดต) — Frontend widget
- ทำเป็น `ui/components/learn/course/points/CoursePointClaimWidget.vue` (self-contained: โหลด available campaigns เอง + ปุ่มรับแต้ม + toast)
  ประกอบด้วย `CoursePointClaimCard.vue` ต่อ campaign
- Mount บนหน้า course ที่นักเรียน landing — ยืนยัน anchor ใน `pages/Learn/Courses/[id]/index.vue` (แสดงเฉพาะเมื่อมี campaign available > 0)
- ใช้ `useCoursePoints` (เพิ่ม `fetchAvailableCampaigns`, `claimCampaign`) — ผ่าน `useApi` เท่านั้น
- ใช้ skill `hopeui-port` ดึง markup ต้นแบบก่อนสร้าง component
