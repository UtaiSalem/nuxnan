# หน้าโปรไฟล์ผู้ใช้ — วิเคราะห์ · ออกแบบใหม่ · แผนงาน

> สถานะ:
> - ✅ **เฟส 0** = PR #54 squash-merge เข้า `main` แล้ว 2026-11-21 (`1a9e3f3b`) (ดูข้อ 10)
> - 🟡 **เฟส 1** = PR #55 (branch `fix/profile-phase1-friendship` rebase ลง `main` แล้ว) รอ review (ดูข้อ 11)
> - เฟส 2 ขึ้นไปต้องรอเจ้าของเคาะ D1–D8 (ข้อ 8) · และ **D9 (ข้อ 10.3) ก่อนแก้ `UserResource` ทั้งระบบ**
> วิธีตรวจ: อ่านโค้ด + **เรียก API จริงบน DB dev ในฐานะผู้ใช้จริง 2 คน (คู่ที่เป็นเพื่อนกัน และคนแปลกหน้า) ใน transaction แล้ว rollback**
> สคริปต์อยู่ที่ scratchpad (`probe_profile_api.php`) ถ้าจะรันซ้ำให้เขียนใหม่เป็นเทสต์ในเฟส 0–1
> 🔴 **ยังไม่ได้เปิดหน้าจอจริง** เพราะต้องสวมสิทธิ์บัญชีจริงใน DB dev · ข้อที่บอกว่า "อ่านจากโค้ด" ยังต้องยืนยันบนจอตอนทำเฟส 3

---

## 1. สรุปสั้น

หน้าโปรไฟล์หลัก `/profile/{id}` (`ui/pages/profile/[id].vue` **2,288 บรรทัดในไฟล์เดียว**) มี **14 แท็บ + การ์ดข้าง 12 ใบ**
- แท็บที่ใช้ได้จริงมี **4 แท็บ** (Timeline, About, Friends, Photos) และแต่ละแท็บก็ยังมีจุดผิดอยู่
- อีก **10 แท็บ** ได้ 404 หรือว่างเปล่าตลอด
- การ์ดข้างครึ่งหนึ่งเป็น**ตัวเลขปลอมหรือปุ่มที่กดแล้วไม่ทำอะไร**

ปัญหาที่ใหญ่กว่าเรื่องหน้าตามี 2 เรื่อง:
1. **ข้อมูลส่วนตัวรั่ว** — ใครก็ได้ที่ล็อกอินแล้วเปิดโปรไฟล์คนอื่น จะได้อีเมล เบอร์โทร ยอดเงินใน wallet และรหัสผู้แนะนำของเจ้าของโปรไฟล์ ไปใน network response
   นอกจากนี้ `GET /api/posts` ยังคืนโพสต์ "เฉพาะฉัน" และ "เฉพาะเพื่อน" ของคนอื่นด้วย
2. **ระบบเพื่อนอ่านคนละตาราง** — เพื่อนจริงอยู่ในตาราง `friendships` (ยอมรับแล้ว 3,483 คู่) แต่ controller ของโปรไฟล์ไปอ่านตาราง `friends` ซึ่งว่างเปล่า (0 แถว)
   ผลคือทุกคนเป็น "คนแปลกหน้า" ของทุกคน ปุ่มเพิ่มเพื่อนแสดงสถานะผิด จำนวนเพื่อนขึ้น 0 ทุกคน และเพื่อนกันจริงก็มองไม่เห็นโพสต์ที่ตั้งไว้ให้เฉพาะเพื่อน

แผนนี้จึงทำ 3 อย่าง:
1. ปิดช่องรั่วก่อน (เฟส 0)
2. แก้แหล่งความจริงของเพื่อนและวิธีระบุตัวผู้ใช้ (เฟส 1)
3. ลดแท็บจาก 14 เหลือ **7 แท็บที่ทุกแท็บมีข้อมูลจริงรองรับ** แล้วสร้างหน้าใหม่แบบ mobile-first (เฟส 2–4)

---

## 2. ผลตรวจ — สภาพปัจจุบัน

### 2.1 แท็บของหน้า `/profile/{id}` (ยิง API จริงด้วย `reference_code` แบบเดียวกับที่หน้าเว็บส่ง)

| แท็บ | หน้าเว็บเรียก | ผลจริง | ต้นเหตุ |
|---|---|---|---|
| Timeline | `/api/users/{ref}/activities` | ✅ 200 แต่**ไม่ครบ** | เช็คความเป็นเพื่อนจากตาราง `friends` ที่ว่าง → เพื่อนจริงไม่เห็นโพสต์ระดับ "เพื่อน" |
| About | ข้อมูลจาก `show` | ⚠️ แสดงได้ | ชั้น "ม.X" **ถูกแต่งขึ้นจากแต้ม** ถ้าไม่มีระเบียนนักเรียน (`UserProfileResource.php:349`) → ครูก็ขึ้นเป็น ม.1 ได้ |
| Friends | `/api/users/{ref}/friends` | ✅ 200 | ไม่เช็ค privacy · ไม่แบ่งหน้า · "ออนไลน์" = `updated_at` < 5 นาที (ไม่ใช่สถานะออนไลน์จริง) · เลเวลคิดจาก `pp` คนละสูตรกับหัวโปรไฟล์ |
| Photos | `/api/users/{ref}/photos` | ✅ 200 | ใช้งานได้ แต่ทั้งระบบมีรูปอยู่ **1 รูป** · อัลบั้ม 0 |
| Videos | `/api/users/{user}/videos` | ❌ ว่างตลอด | ตาราง `videos` = 0 · **ไม่มี UI อัปโหลด** · route อยู่นอก `auth:api` (guard เริ่มต้น = web) คนล็อกอินจึงถูกนับเป็น guest · ถ้าระบบรู้ว่าผู้ชมล็อกอิน (ยิงในโปรเซสเดียวหลัง route ที่มี `auth:api`) จะได้ **500** `Unknown column users.id` จาก `$viewer->friends()` (`VideoController.php:30` และซ้ำที่ `:226`) |
| Courses | `/api/users/{ref}/membered-courses` | ❌ **404** | route จริงอยู่ที่ `/api/courses/users/{user}/membered-courses` (`routes/learn/course.php:135`) และ bind ด้วย id |
| Certificates | ตัวเดียวกับ Courses (`status=completed`) | ❌ **404** | เหมือนข้างบน และกรองด้วย `completion_date` ที่**ไม่มีโค้ดไหนเขียนเลย** · ใบประกาศจริงอยู่ในตาราง `course_certificates` |
| Badges | `/api/users/{ref}/badges` | ❌ **404** | `{user}` bind ด้วย id แต่หน้าเว็บส่ง reference_code · ถ้าส่ง id จะได้ 200 แต่ `badges` = 0 แถว |
| Groups | `/api/users/{ref}/groups` | ❌ **404** | binding เดียวกัน · `academy_group_members` มีแค่ 1 แถว · ถ้าใช้ได้จะเปิดเผยกลุ่มในโรงเรียนให้คนนอกเห็น |
| Events | `/api/users/{ref}/events` | ❌ **404** | binding เดียวกัน · `events` = 0 · ไม่มีทางสร้างอีเวนต์ (หน้า `/events` เป็น "coming soon") |
| Blog / Forum / Marketplace | `GET /api/posts?post_type=…&user_id={ref}` | ❌ ว่างตลอด | API คืนคีย์ `posts` แต่หน้าเว็บอ่าน `data` · ส่ง `user_id` เป็น reference_code (string เทียบกับ int) · โพสต์ทั้ง 68 อันมี `post_type = null` · ไม่มี UI สร้างโพสต์ประเภทนี้ |
| Cart | `/api/cart` | ❌ ผิดแนวคิด | แสดง**ตะกร้าของคนที่เปิดดู**บนโปรไฟล์ของทุกคน · `cart_items` = 0 |

### 2.2 การ์ด/ปุ่มบนหน้า (อ่านจากโค้ด — ยังไม่ได้ดูบนจอ)

| ส่วน | ตำแหน่ง | ปัญหา |
|---|---|---|
| ปุ่ม "เพิ่มเพื่อน" | `[id].vue:444` | สถานะมาจาก `friendship_status` ที่ผิดเสมอ (ดู 2.4) → เพื่อนกันแล้วก็ยังขึ้น "เพิ่มเพื่อน" |
| ปุ่ม "ส่งข้อความ" | `[id].vue:465` | พาไปหน้า `/chat?userId=` **ซึ่งไม่มีอยู่จริง** — ระบบยังไม่มีแชต (`stores/chat.ts` 21 บรรทัด, `Play/Messages.vue` = coming soon, backend ไม่มี route แชต) |
| แถบสถิติ: เพื่อน / เข้าชม | `[id].vue:818,829` | เพื่อนอ่านจากคอลัมน์ `user_profiles.friends` ที่**เป็น 0 ทุกแถว** · ยอดเข้าชมบวกทุกครั้งที่เปิด (ไม่กันซ้ำ) และโชว์ให้ทุกคนเห็น |
| การ์ด "แต้ม & กระเป๋าเงิน" | `[id].vue:967` | 🔴 โชว์ยอดของ**คนอื่น** · ปุ่ม "เติมเงิน"/"ประวัติ" ไม่มี handler (หน้า `/Earn/Wallet`, `/Earn/Points` มีอยู่แล้ว) |
| Level & XP | `[id].vue:1017` | เลเวลจริง แต่ "Next Level Reward: +500 points & special badge" เป็น**ข้อความแต่ง** |
| About Me | `[id].vue:1143` | ปุ่ม "⋯" ไม่ทำอะไร · การ์ด "ประเทศ" ซ้ำกับ location และเติม "Thailand" ให้เอง |
| Badges / ความสำเร็จ | `[id].vue:1235,1656` | ระบบ badge ว่าง (0) · ปุ่ม "View All" / "ดูความสำเร็จทั้งหมด" ไม่ทำอะไร |
| Friends preview | `[id].vue:1347` | Mutual / Online / New = 0 ตลอด (backend ไม่ส่งค่าเหล่านี้) |
| สถิติกิจกรรม | `[id].vue:1558` | ถูกใจ / คอมเมนต์ / แชร์ ไม่มีใน resource → 0 ตลอด · **Streak `profile.streak \|\| 7` = 7 วันตายตัว** (`point_streaks` มี 1,108 แถวแต่ `current_streak` = 0 ทุกแถว) |
| Stream Box + ปุ่ม Go Live | `[id].vue:1721` | ของปลอมทั้งการ์ด |
| Photos widget | `[id].vue:1783` | ช่องเทา 12 ช่องตายตัว ไม่เคยดึงรูปจริง |
| Groups widget | `[id].vue:1808` | ขึ้น "ยังไม่ได้เข้าร่วมกลุ่ม" เสมอ · ปุ่ม "สำรวจกลุ่ม" ไม่ทำอะไร |
| ProfileCompletionWidget | `components/organisms/ProfileCompletionWidget.vue:10,166` | ภารกิจ "0/30" กับ badge "x/4" เป็นค่า default ตายตัว · dropdown "All Updates" + แท็บ Status/Mentions/Groups/Blog **กดแล้วไม่มีผล** |

**มือถือ (375px):** แถบแท็บของ desktop เป็น `hidden md:block` (`[id].vue:843`) ส่วนแถบล่างของมือถือมีแค่ 5 แท็บ (`[id].vue:619`)
→ **9 จาก 14 แท็บเปิดไม่ได้เลยบนมือถือ** · แถบล่างเป็น `fixed bottom-16` ซ้อนอยู่บนแถบนำทางของ layout อีกชั้น
· การ์ดข้างซ้าย ~7 ใบเรียงซ้อนอยู่**ก่อน**เนื้อหาหลัก ต้องเลื่อนผ่านทั้งหมดก่อนจะเห็นโพสต์

### 2.3 🔴 ข้อมูลรั่ว (ยืนยันด้วยการยิง API จริง)

| # | Endpoint | ผู้เรียก | สิ่งที่หลุด |
|---|---|---|---|
| L1 | `GET /api/users/{id}/activities` (`UserProfileController.php:559`) และ `/users/{ref}/profile` (`:85`) | ใครก็ได้ที่ล็อกอิน | `'user' => $user` ทั้งก้อน: **email, phone_number, personal_code, pp, wallet, locked_balance, suggester_code, freeze_reason, google_id, facebook_id, two_factor_confirmed_at, deletion_reason** (User `$hidden` ซ่อนแค่รหัสผ่าน/2FA secret) |
| L2 | `GET /api/users/{id}/show` (`UserProfileResource.php:124-125,140`) | ใครก็ได้ | `points`, `wallet`, `last_login` ของทุกคน + `first_name/last_name/gender` ไม่สน privacy |
| L3 | `GET /api/users/{id}/stats` (`UserProfileController.php:573`) | ใครก็ได้ | `points`, `wallet` |
| L4 | `GET /api/posts` (`PostController.php:51`) | ใครก็ได้ | ไม่กรอง `privacy_settings` เลย — ยิงจริงได้โพสต์ "เฉพาะฉัน/เพื่อน" ของคนอื่น 3 โพสต์ · **consumer ฝั่งหน้าเว็บมีแค่ 3 แท็บที่จะลบทิ้ง** แก้ได้โดยไม่กระทบหน้าอื่น |
| L5 | `GET /api/users/{id}/friends` (`FriendController.php:188`) | ใครก็ได้ | ไม่สน `privacy_settings` · ส่งชื่อจริง-นามสกุลของเพื่อนทุกคน |

### 2.4 ตรรกะผิดที่เจอระหว่างทาง

- **ตารางเพื่อน 2 ชุด:** `FriendController` (เพิ่ม/ยอมรับ/เลิก/รายชื่อ) ใช้ trait `Friendable` ของ `multicaret/laravel-acquaintances` → ตาราง `friendships`
  · `UserProfileController` (`getFriendshipStatus`, `canViewFullProfile`, `activities`, `index`) ใช้ `App\Models\Friend` → ตาราง `friends` ที่**ไม่มีโค้ดไหนเขียนลงเลย**
  · ยิงจริงกับคู่ที่เป็นเพื่อนกัน: `friendship_status="none"` แต่ `data.is_friend=true` (resource ใช้ acquaintances), `friends_count=0` ทั้งที่มีเพื่อนจริง 14 คน
- `canViewFullProfile` (`:671-675`) — `where(A)->orWhere(B)->where('status',1)` = `A OR (B AND status=1)` แค่ส่งคำขอค้างไว้ก็นับเป็นเพื่อน (ตอนนี้ไม่ออกผลเพราะตารางว่าง แต่จะออกผลทันทีถ้ามีคนไป "แก้" ด้วยการเขียนลงตารางนั้น)
- `findUserByProfileIdentifier` (`:44`) จับคู่ด้วย `name` (ชื่อแสดง ซ้ำกันได้) → เสี่ยงได้ผู้ใช้ผิดคน · `reference_code` เป็นสตริง 10 ตัวทุกบัญชี (ไม่มีตัวที่เป็นตัวเลขล้วน จึงยังไม่ชนกับ id)
- `UserProfile` cast `privacy_settings` เป็น `'array'` ทั้งที่คอลัมน์เก็บสตริงเดี่ยว → ข้อมูลปนกัน: `public` 3,309 แถว · `"public"` 2 แถว · `"private"` 1 แถว
- ตัวเลือกในหน้าตั้งค่า **`allow_friend_requests`, `allow_messages`, `show_online_status` ไม่มีโค้ดไหนบังคับใช้** (มีแค่สะท้อนค่ากลับใน `UserResource`) = เมนูที่กดแล้วไม่มีผล

### 2.5 ข้อมูลจริงที่มีให้ใช้ (DB dev 2026-11-20)

| ข้อมูล | จำนวน | ใช้ทำอะไรได้ |
|---|---|---|
| users (ไม่ถูกลบ) / user_profiles | 3,281 / 3,312 | — · กรอก bio 34 คน · ชื่อจริง 133 · รูปปก 113 · รูปโปรไฟล์ 440 → **การ์ดชวนกรอกโปรไฟล์ยังมีประโยชน์** |
| friendships accepted / pending / denied | 3,483 / 3,611 / 230 | เพื่อน · คำขอ · เพื่อนร่วมกัน |
| course_members | 4,917 (ทุกแถว `completion_status=in_progress`, ไม่มีแถวไหนกรอก `completed_at/final_grade`) | แท็บการเรียน — ตอนนี้ "เรียนจบ" จะว่างจนกว่าจะมีการปิดเกรดจริง (D8) |
| courses / เจ้าของคอร์ส | 29 / 3 คน | แท็บการสอน |
| course_certificates | 0 (แต่ระบบออกใบประกาศมีครบ: `/api/certificates/my`, `/verify/{code}`) | แท็บใบประกาศ — **หน้าเว็บยังไม่มีหน้า "ใบประกาศของฉัน" และหน้าตรวจสอบสาธารณะ** |
| users มี XP / เลเวล > 1 · level_definitions | 363 / 66 · 50 ขั้น (มี `name_th`, `icon`, `color`) | เลเวล + ชื่อขั้น |
| typing_user_achievements / typing_achievements | 460 / 8 | **ความสำเร็จจริงชุดเดียวที่มีข้อมูล** |
| point_streaks | 1,108 แถว (current_streak > 0 = 0) | โชว์ streak เฉพาะเมื่อ > 0 |
| badges · user_badges · achievements · events · videos · albums · cart_items · follows | 0 ทั้งหมด | **ไม่มีข้อมูล → ไม่ทำแท็บ** |
| activities ตามชนิด | DonateRecipient 30,724 · SupportViewer 6,130 · CoursePost 1,389 · Donate 194 · Post 68 | Timeline แสดงแค่ Post + CoursePost (CoursePost ทุกแถว privacy=3) |

---

## 3. ใครต้องใช้หน้าโปรไฟล์ เพื่อทำอะไร

| ผู้ใช้ | ต้องการ | หน้าที่ตอบโจทย์ |
|---|---|---|
| **เจ้าของบัญชี (ทุกบทบาท)** | ดูภาพรวมตัวเอง: เลเวล, คอร์สที่เรียนต่อ, ใบประกาศ, แต้ม/เงิน, คำขอเป็นเพื่อน, กรอกโปรไฟล์ให้ครบ, เปลี่ยนรูป | `/profile/me` (โหมดเจ้าของ) |
| เจ้าของบัญชี | ควบคุมว่าใครเห็นอะไร โดยตั้งแล้ว**มีผลจริง** | `/profile/{ref}/settings` แท็บความเป็นส่วนตัว |
| นักเรียน/สมาชิก ดูเพื่อน-เพื่อนร่วมชั้น | รู้จักกัน, ดูโพสต์, เพื่อนร่วมกัน, ความสำเร็จ, กดเพิ่มเพื่อน | `/profile/{ref}` |
| **ผู้เรียนดูครูผู้สอน** (คลิกจาก `CourseInstructorWidget`, `CourseHero`, `CourseSidebarInstructor`) | ครูคนนี้สอนอะไร สังกัดที่ไหน มีผู้เรียนกี่คน — **ตอนนี้หน้าโปรไฟล์ไม่มีข้อมูลการสอนเลย** | `/profile/{ref}` แท็บ "การสอน" |
| คนทั่วไป | ตรวจว่าใบประกาศของคนนี้เป็นของจริง | หน้า verify สาธารณะ (ใหม่) + ลิงก์จากแท็บการเรียน |
| นักเรียน | ดู/แก้**ระเบียนนักเรียน**ของโรงเรียน (ที่อยู่ ผู้ปกครอง สุขภาพ บัตร เยี่ยมบ้าน) | `/academies/{name}/my-profile` (มีแล้ว ป้องกันดี) — โปรไฟล์หลักแค่**ลิงก์**ไป ห้ามดึงข้อมูลมาแสดง |
| ครู/ผู้บริหารโรงเรียน | ดูระเบียนนักเรียนในความดูแล | `/academies/{name}/students/{id}/profile` (มีแล้ว guard ครบตาม `StudentMasterProfilePolicy`) — นอกขอบเขตแผนนี้ |
| ทุกคน | ร้องเรียนบัญชีทุจริต | ปุ่ม "ร้องเรียน" → `ReportAccountModal` (มีแล้ว ไม่ได้ยิงทดสอบรอบนี้) |
| ผู้เล่นเกมพิมพ์ | ดูสถิติพิมพ์ของตัวเอง/คนอื่น | `/Play/Games/typing/profile/{userId}` — **บั๊ก:** ใส่ id คนอื่นแล้วสถิติ ความสำเร็จ และประวัติที่ขึ้นเป็นของคนที่เปิดดูเอง (`[userId].vue:75-78` ไม่ส่ง user_id) |

### 3.1 แผนที่หน้าที่เกี่ยวกับโปรไฟล์ทั้งระบบ

| หน้า | ไฟล์ | ตัดสินใจ |
|---|---|---|
| โปรไฟล์ (สาธารณะ + โหมดเจ้าของ) | `ui/pages/profile/[id].vue` | 🔨 **ทำใหม่** (หัวใจของแผนนี้) |
| ตั้งค่าโปรไฟล์ | `ui/pages/profile/[id]/settings.vue` + `components/settings/*` | 🔧 แก้ให้ทุกตัวเลือกมีผลจริง (เฟส 4) |
| ทางลัด `/profile`, `/profile/edit`, `/settings`, `/timeline` | redirect | ✅ คงไว้ · `/profile` เลิกใส่ `personal_code` (รหัสผู้แนะนำ) ลง URL ให้ใช้ `reference_code` |
| ระเบียนนักเรียนของฉัน / มุมมองครู | `academies/[name]/my-profile.vue`, `.../students/[id]/profile.vue` | ✅ ไม่แตะ — แค่ทำลิงก์จากโปรไฟล์หลัก |
| `/student/profile` | `ui/pages/student/profile.vue` | 🗑️ **หน้ากำพร้า** (ไม่มีลิงก์จากที่ไหน) ซ้ำกับ my-profile → redirect หรือลบ (รอยืนยัน) |
| โปรไฟล์เกมพิมพ์ | `ui/pages/Play/Games/typing/profile/[userId].vue` | 🔧 แก้ให้แสดงข้อมูลของ userId จริง (เฟส 4) |
| หน้า stub ระดับบน (`/photos`, `/videos`, `/events`, `/forum`, `/account`, `Play/Messages`) | หน้า "coming soon" | ⏸️ นอกขอบเขต — แต่**ห้ามลิงก์จากโปรไฟล์ไปหน้าเหล่านี้** |

---

## 4. ดีไซน์ใหม่ของ `/profile/{ref}`

### 4.1 หลักการ

1. **ทุกปุ่ม ทุกตัวเลข ทุกแท็บ ต้องมีข้อมูลจริงหรือ action จริงรองรับ** ถ้ายังไม่มีฟีเจอร์ก็**ไม่แสดง** (ห้ามใช้ placeholder หรือ default ปลอมอย่าง `|| 7`, `?? 30`)
2. **backend ตัดสินสิทธิ์ที่เดียว** — `show` คืน `visibility` + `counts` ให้หน้าเว็บรู้ว่าแท็บไหนเปิดได้และมีกี่รายการ หน้าเว็บไม่ต้องเดาเอง
3. **โปรไฟล์สังคม ≠ ระเบียนนักเรียน** — ห้ามดึงที่อยู่ ผู้ปกครอง สุขภาพ เลขบัตร หรือคะแนน/เกรดมาแสดงบนโปรไฟล์หลักเด็ดขาด
4. **ยึดเนื้อหาตามบทบาทจริง** — แท็บ "การสอน" ขึ้นเฉพาะคนที่มีคอร์สเผยแพร่ ส่วนป้ายบทบาทคิดจากข้อมูลจริง (สมาชิกโรงเรียน/เจ้าของคอร์ส) ไม่ใช่ให้ผู้ใช้ตั้งเอง
5. **Mobile-first ตาม CLAUDE.md** — ทุกแท็บต้องเข้าถึงได้ที่ 375px · ใช้แถบแท็บเดียวที่เลื่อนแนวนอนได้ ไม่ทำแถบล่างลอยซ้อนกับของ layout
6. **แท็บอยู่ใน URL** (`?tab=learning`) — แชร์ลิงก์ได้ ปุ่ม back ใช้ได้ ตัวเลขบนแถบสถิติกดแล้วพาไปแท็บนั้น
7. ต้นแบบดีไซน์: HopeUI `social-app/social-profile.html`, `friend-profile.html`, `profile-badges.html` ผ่านสกิล `hopeui-port` แล้ว**เขียน breakpoint ใหม่เป็น mobile-first** (ดู D4)

### 4.2 โครงหน้า

**มือถือ 375px**
```
┌─────────────────────────────────┐
│ [รูปปก  aspect 16:6]        [📷] │ ← ปุ่มเปลี่ยนรูปปก: เฉพาะเจ้าของ
│  (รูปโปรไฟล์ 88px)[📷]            │
│ ชื่อแสดง ✓                        │ ← min-w-0 + break-words (ชื่อไทยยาว)
│ @username · Lv.12 นักสำรวจ         │ ← ชื่อขั้นจาก level_definitions.name_th
│ [นักเรียน] [ครูผู้สอน]              │ ← ป้ายบทบาทจากข้อมูลจริง
│ bio 2 บรรทัด… (แตะเพื่ออ่านต่อ)     │
│ [＋ เพิ่มเพื่อน ] [ ⋯ ]            │ ← เจ้าของ: [แก้ไขโปรไฟล์] [⚙ ตั้งค่า]
├─────────────────────────────────┤
│  24      14      6       9       │ ← แตะแล้วไปแท็บนั้น
│ โพสต์   เพื่อน  คอร์ส  ความสำเร็จ  │
├─────────────────────────────────┤
│ ภาพรวม│โพสต์│การเรียน│การสอน│ … → │ ← sticky top, overflow-x-auto, ปุ่มสูง ≥44px
├─────────────────────────────────┤
│ เนื้อหาแท็บ (คอลัมน์เดียว)          │
└─────────────────────────────────┘
```

**≥1024px:** ส่วนหัวเต็มความกว้าง แถบแท็บอยู่ใต้ส่วนหัว เนื้อหาเป็น `lg:grid-cols-3`
- ซ้าย 1/3 (sticky): การ์ด "เกี่ยวกับ" + การ์ดเจ้าของ (ความครบโปรไฟล์ / คำขอเป็นเพื่อน / แต้ม-เงิน)
- ขวา 2/3: เนื้อหาแท็บ
- บนมือถือ การ์ดซ้ายย้ายเข้าไปอยู่ในแท็บ "ภาพรวม" แทน ไม่ซ้อนอยู่หน้าเนื้อหาหลัก

### 4.3 แท็บ (7 แท็บ — ทุกแท็บมีข้อมูลจริง)

| แท็บ | `?tab=` | เนื้อหา | API | ใครเห็น | ซ่อนแท็บเมื่อ |
|---|---|---|---|---|---|
| ภาพรวม | `overview` (ค่าเริ่ม) | เกี่ยวกับ (bio, ความสนใจ, ทักษะ, เว็บไซต์, โซเชียล, สมัครเมื่อ) · คอร์สล่าสุด 3 รายการ · ความสำเร็จล่าสุด 4 อัน · เพื่อน 6 คน + จำนวนเพื่อนร่วมกัน · **เจ้าของเท่านั้น:** ความครบโปรไฟล์, คำขอเป็นเพื่อน, การ์ดแต้ม-เงิน (ลิงก์ `/Earn/Points`, `/Earn/Wallet`), ทางลัด "ระเบียนนักเรียนของฉัน" | `show` (ข้อมูลสรุป) | ทุกคน (ฟิลด์ตาม 4.5) | — |
| โพสต์ | `posts` | timeline + กล่องสร้างโพสต์ (เจ้าของ) + โหลดเพิ่ม | `/users/{ref}/activities` (แก้) | ตาม privacy ของแต่ละโพสต์ | — (ว่าง = empty state) |
| การเรียน | `learning` | กำลังเรียน / เรียนจบ / ใบประกาศ (ลิงก์ตรวจสอบได้) · ชั้นเรียนปัจจุบันจาก `classrooms` (ไม่ใช่ชั้นที่คำนวณจากแต้ม) · **ไม่มีคะแนน/เกรด** · % ความคืบหน้าเห็นเฉพาะเจ้าของ | `/users/{ref}/learning` + `/users/{ref}/certificates` (ใหม่) | ตาม `learning_visibility` (D1) | ผู้ชมไม่มีสิทธิ์ |
| การสอน | `teaching` | คอร์สที่สอนซึ่งเผยแพร่แล้ว (การ์ดคอร์สใช้ `Course::withCardData`) · โรงเรียนที่สังกัดในฐานะครู/ผู้บริหาร · จำนวนผู้เรียนรวม | `/users/{ref}/teaching` (ใหม่) | ทุกคน | ไม่มีคอร์สเผยแพร่และไม่ได้เป็นบุคลากร |
| ความสำเร็จ | `achievements` | เลเวล + ชื่อขั้น + แถบ XP · ความสำเร็จเกมพิมพ์ (ได้แล้ว/ยังไม่ได้) · badge (เฉพาะเมื่อตาราง `badges` มีข้อมูล) · streak (เฉพาะเมื่อ > 0) | `/users/{ref}/achievements` (ใหม่) | ทุกคน | — |
| เพื่อน | `friends` | รายชื่อแบบแบ่งหน้า · ป้าย "เพื่อนร่วมกัน" · ค้นหา + คำขอ (เจ้าของ) | `/users/{ref}/friends` (แก้) | ตาม `privacy_settings` | ผู้ชมไม่มีสิทธิ์ |
| รูปภาพ | `photos` | รูปจากโพสต์ที่ผู้ชมมีสิทธิ์เห็น (D3) | `/users/{ref}/photos` (แก้) | ตาม privacy ของโพสต์ | — |

**ตัดออก:** Videos, Groups, Events, Blog, Forum, Marketplace, Cart, Stream Box, Next Level Reward, Mutual/Online/New, Activity Streak ตัวเลขปลอม, Photos widget ช่องเทา, Groups widget
(เหตุผลอยู่ในตาราง 2.1–2.2 — ข้อมูลเป็น 0 / ไม่มี UI สร้าง / เป็นของปลอม / แสดงข้อมูลผิดคน) — **การลบไฟล์ต้องรอยืนยัน (D2)**

### 4.4 ปุ่มบนส่วนหัว

| สถานะ (`friendship_status` จาก `friendships`) | ปุ่มหลัก | หมายเหตุ |
|---|---|---|
| `self` | [แก้ไขโปรไฟล์] [⚙] | เปลี่ยนรูปปก/รูปโปรไฟล์ได้จากส่วนหัว |
| `none` | [＋ เพิ่มเพื่อน] | ถ้าเป้าหมายปิด `allow_friend_requests` → ไม่แสดงปุ่ม (backend ต้องตอบ 403 ด้วย) |
| `pending_sent` | [ยกเลิกคำขอ] | |
| `pending_received` | [ยอมรับ] [ปฏิเสธ] | ตอนนี้มีแค่ "ยอมรับ" |
| `friends` | [✓ เพื่อน ▾] → เลิกเป็นเพื่อน | ต้องยืนยันก่อนเลิก (มีแล้ว) |
| `denied` | ปฏิบัติแบบ `none` (acquaintances ยอมให้ส่งคำขอใหม่หลัง denied) | 🔴 **ห้ามเรียก `canBefriend()` เพื่อเช็คสถานะ** — มัน `unblockFriend()` ให้เป็น side effect · อ่านจาก `getFriendship()` แทน |

เมนู ⋯ : คัดลอกลิงก์โปรไฟล์ · ร้องเรียนบัญชี
**ปุ่ม "ส่งข้อความ" ซ่อนไว้จนกว่าจะมีระบบแชตจริง** (D7)

### 4.5 ใครเห็นข้อมูลอะไร

`privacy_settings`: public = สมาชิกที่ล็อกอินทุกคน · friends = เฉพาะเพื่อน · private = เฉพาะเจ้าของ

| ข้อมูล | เจ้าของ | เพื่อน | สมาชิกอื่น | ตอนนี้ |
|---|---|---|---|---|
| ชื่อแสดง, @username, รูปโปรไฟล์/ปก, bio, เลเวล/XP/ชื่อขั้น, เดือนที่สมัคร, ป้ายบทบาท, จำนวนเพื่อน, จำนวนโพสต์ที่ผู้ชมเห็นได้ | ✅ | ✅ | ✅ | |
| ชื่อจริง-นามสกุล, เพศ, ความสนใจ, ทักษะ, เว็บไซต์, ลิงก์โซเชียล | ✅ | ✅ ถ้าไม่ private | ✅ ถ้า public | ❗ ส่งให้ทุกคน |
| อายุ / location / อีเมล / เบอร์โทร | ✅ | ✅ ถ้าเปิด `show_*` | ❌ | คงกติกาเดิม |
| **แต้มคงเหลือ (pp), เงินใน wallet, `last_login`, `personal_code`** | ✅ | ❌ | ❌ | 🔴 รั่ว (L1–L3) |
| รายชื่อเพื่อน | ✅ | ✅ ถ้าไม่ private | ✅ ถ้า public | ❗ ไม่เช็ค |
| โพสต์ privacy 1 / 2 / 3 | ทั้งหมด | 2, 3 | 3 | ❗ เพื่อนเห็นแค่ 3 |
| การเรียน (คอร์ส, ใบประกาศ, ชั้นเรียน, โรงเรียน) | ✅ | ตาม D1 | ตาม D1 | ❌ 404 |
| คอร์สที่สอน (เผยแพร่แล้ว) | ✅ | ✅ | ✅ | ไม่มี |
| ยอดเข้าชม | ✅ (กันนับซ้ำ) | ❌ | ❌ | ❗ ทุกคนเห็น นับซ้ำ |
| ระเบียนนักเรียน (ที่อยู่ ผู้ปกครอง สุขภาพ เลขบัตร) และคะแนน/เกรด | ❌ ไม่อยู่ในหน้านี้ | ❌ | ❌ | ✅ ไม่มีอยู่แล้ว |

---

## 5. ออกแบบฝั่ง backend

### 5.1 ชิ้นส่วนกลาง (ใหม่)

- **`App\Services\Profile\ProfileAccess`** — รับ (viewer ?User, target User, profile UserProfile) แล้วคำนวณ:
  - `relation()`: `self | friend | member | guest` (ใช้ `isFriendWith` ของ acquaintances เท่านั้น)
  - `friendshipStatus()`: `self | friends | pending_sent | pending_received | none` จาก `getFriendship()` + sender
  - `canSee('about'|'friends'|'learning'|'contact.email'|…)` ตามตาราง 4.5
  - `postPrivacyLevels()`: `[1,2,3] | [2,3] | [3]`
  - memo ต่อ request เพราะ resource กับ controller เรียกซ้ำ
  - ใช้ทั้งใน `UserProfileController`, `UserProfileResource`, `FriendController@userFriends`, `PostController@index` และ endpoint ใหม่ทุกตัว
- **`App\Support\ProfileUserResolver::resolve($identifier)`** — ตัวเลขล้วน = id · นอกนั้น = `reference_code` แล้วค่อย `username` · **เลิกจับคู่ด้วย `name`** และเลิกรับ `personal_code` จากคนอื่น
  → ใช้แทน `{user}` binding และ `findUserByProfileIdentifier` ทุกจุด
- **`PublicUserResource`** (มินิ): `id, name, avatar, reference_code, xp_level` → ใช้แทน `'user' => $user` ทุกที่

### 5.2 Endpoint

| Endpoint | สถานะ | ต้องทำ |
|---|---|---|
| `GET /api/users/{ref}/show` | แก้ | `friendship_status` ถูก · `friends_count` = `getFriendsCount()` · `mutual_friends_count` · ฟิลด์ตาม 4.5 · เพิ่ม `visibility{about,friends,learning,posts}` + `counts{posts,friends,learning,teaching,achievements,certificates}` + `roles[]` + `level_name` · ลบ `grade` ที่แต่งขึ้น · นับเข้าชมแบบกันซ้ำ |
| `GET /api/profile/me` | แก้ | ใช้ resource เดียวกัน (เจ้าของได้ครบ) |
| `GET /api/users/{ref}/activities` | แก้ | `PublicUserResource` · privacy ผ่าน `ProfileAccess` · คง `per_page` ไว้ที่ 10 |
| `GET /api/users/{ref}/friends` | แก้ | เช็ค privacy · paginate · เลเวลจาก `xp_level` · ลบ `is_online`/`last_seen` ปลอม · ลบ N+1 ของ `mutual_friends_count` (คิดครั้งเดียวจากรายชื่อเพื่อนของผู้ชม) |
| `GET /api/users/{ref}/learning?status=` | **ใหม่** | จาก `course_members` (+ course + academy) · นิยาม "เรียนจบ" ตาม D8 · ไม่มีคะแนน |
| `GET /api/users/{ref}/certificates` | **ใหม่** | `course_certificates` สถานะ issued · ส่ง `verification_code` (เจ้าของใช้ `/api/certificates/my` เดิมได้) |
| `GET /api/users/{ref}/teaching` | **ใหม่** | คอร์ส status=1 (published) ที่เป็นเจ้าของ + โรงเรียนที่เป็นบุคลากร · ใช้ `Course::withCardData` กัน N+1 |
| `GET /api/users/{ref}/achievements` | **ใหม่** | level + `level_definitions` · typing achievements · badges (ถ้ามี) · streak |
| `GET /api/users/{ref}/photos` | แก้ | ตาม D3 |
| `GET /api/users/{ref}/stats`, `/api/profile/stats` | แก้ | ตัด `wallet/points` เมื่อไม่ใช่เจ้าของ (ไม่มีหน้าเว็บใช้ `fetchStats` — ถ้ายืนยันว่าไม่มี consumer ภายนอกด้วย ให้ลบทิ้งได้) |
| `GET /api/posts` | แก้ | กรอง privacy ด้วย `ProfileAccess` |
| `POST /api/friends/{recipient}` | แก้ | บังคับ `allow_friend_requests` → 403 `FRIEND_REQUESTS_DISABLED` |
| `/users/{user}/badges|groups|events`, `/profile/badges|groups|events` (closure ใน `routes/api.php:146-160`), `/users/{user}/videos`, `/profile/videos*` | 🗑️ | ลบหลังหน้าเว็บเลิกใช้ (D2) |

### 5.3 DB (migration ทุกตัว — ห้ามแก้ผ่าน phpMyAdmin · ไม่ hardcode id · มี `down()` จริง · กัน STRICT mode)

1. `user_profiles.learning_visibility` enum/string `public|friends|private` ค่าเริ่มตาม D1
2. แก้ cast `privacy_settings` จาก `array` เป็น string + migration ข้อมูลที่ถอดเครื่องหมาย `"` ออก (3 แถว)
   ให้ `up()` จดแถวที่แก้ลงตารางสำรองไว้ เพื่อให้ `down()` คืนค่าเดิมได้จริง
3. **ไม่**ลบตาราง `friends` / คอลัมน์ `user_profiles.friends|followers|following` ในแผนนี้ — แค่เลิกอ่าน (ค่อยลบในงานเก็บกวาดแยก)

---

## 6. แผนงานแบ่งเฟส (shard ละชิ้นเล็กให้ agy · Claude ตรวจ diff + รันเกณฑ์เองทุกชิ้น)

> ทุก shard ที่แตะ `ui/` ต้องแปะบล็อก mobile-first ของ CLAUDE.md · เทสต์ backend ต้องรันบน **MySQL** (`-c phpunit.mysql.xml`) ไม่ใช่แค่ sqlite

### เฟส 0 — ปิดข้อมูลรั่ว (เริ่มได้ทันที ไม่ต้องรอเคาะ)

| Shard | งาน | เกณฑ์ผ่าน |
|---|---|---|
| 0.1 | `activities` + `index`: `'user' => $user` → `PublicUserResource` | เทสต์: คนแปลกหน้าเรียก activities → ไม่มีคีย์ `email, phone_number, personal_code, pp, wallet, locked_balance, suggester_code, freeze_reason, google_id, facebook_id` |
| 0.2 | `UserProfileResource`: `points, wallet, last_login, personal_code` เฉพาะเจ้าของ · ชื่อจริง/เพศ/ความสนใจ/ทักษะ/เว็บ/โซเชียล ตาม `privacy_settings` | ตารางเทสต์ เจ้าของ/เพื่อน/คนแปลกหน้า × public/friends/private |
| 0.3 | `stats`: ตัด wallet/points เมื่อไม่ใช่เจ้าของ | เทสต์ 2 เคส |
| 0.4 | `PostController@index` กรอง privacy (ใช้ acquaintances ตรง ๆ ไปก่อน ถ้าเฟส 1 ยังไม่เสร็จ) | เทสต์: คนแปลกหน้าได้แค่ 3 · เพื่อนได้ 2, 3 · เจ้าของได้ทุกโพสต์ของตัวเอง |
| 0.5 | หน้าเว็บ: การ์ด "แต้ม & กระเป๋าเงิน" `v-if="isOwnProfile"` (`[id].vue:968`) | โปรไฟล์คนอื่นไม่ขึ้น ฿0 |

### เฟส 1 — เพื่อนและการระบุตัวผู้ใช้ให้ถูก

| Shard | งาน | เกณฑ์ผ่าน |
|---|---|---|
| 1.1 | `ProfileAccess` + เปลี่ยนทุกจุดใน `UserProfileController` ที่อ่าน `App\Models\Friend` ไปใช้ acquaintances · `friends_count` / `mutual_friends_count` จริง | เทสต์ `friendship_status` ครบ 5 สถานะ · เพื่อนเห็นโพสต์ privacy 2 · `grep "Models\\\\Friend" UserProfileController.php` = 0 |
| 1.2 | `ProfileUserResolver` ใช้กับทุก `/users/{…}/*` ของโปรไฟล์ | ทุก endpoint ตอบ 200 ทั้งตอนส่ง id และ reference_code · `name` ใช้ระบุตัวไม่ได้แล้ว |
| 1.3 | นับเข้าชมแบบกันซ้ำ (`Cache::add` ต่อ ผู้ชม×เป้าหมาย×วัน) + เห็นเฉพาะเจ้าของ | เปิดซ้ำ 3 ครั้งในวันเดียว ยอดขึ้น 1 |
| 1.4 | migration `privacy_settings` (5.3 ข้อ 2) | `migrate --path` เฉพาะไฟล์นี้ (dev DB มี migration อื่นค้างอยู่) · rollback แล้วได้ค่าเดิม |
| 1.5 | `FriendController`: บังคับ `allow_friend_requests` · `userFriends` เช็ค privacy + paginate + ลบ online/last_seen ปลอม + ตัด N+1 | เทสต์ 403 · รายชื่อของคน private ได้ 403/ว่างเมื่อไม่ใช่เจ้าของ |

### เฟส 2 — API ของแท็บใหม่ (หลังเคาะ D1, D3, D8)

| Shard | งาน |
|---|---|
| 2.1 | migration `learning_visibility` + `/users/{ref}/learning` |
| 2.2 | `/users/{ref}/certificates` + หน้าเว็บสาธารณะ `/certificates/verify/[code]` (ใช้ `/api/certificates/verify/{code}` ที่มีอยู่) |
| 2.3 | `/users/{ref}/teaching` |
| 2.4 | `/users/{ref}/achievements` |
| 2.5 | `/users/{ref}/photos` ตาม D3 |
| 2.6 | `show`: `visibility`, `counts`, `roles`, `level_name` · ลบ `grade` ที่แต่งขึ้น · ชั้นเรียนจริงจาก `classrooms` (อยู่ใต้ `learning_visibility`) |

เกณฑ์ทุก shard: เทสต์เจ้าของ/เพื่อน/คนแปลกหน้า · response ไม่มีคะแนน/เกรด/PII ของระเบียนนักเรียน · ไม่มี N+1 (นับ query ในเทสต์)

### เฟส 3 — หน้าเว็บใหม่ (สกิล `hopeui-port` + mobile-first)

| Shard | งาน |
|---|---|
| 3.1 | `pages/profile/[id].vue` เหลือแค่โครง (≤ ~250 บรรทัด): โหลด `show` → `ProfileHeader` + `ProfileStatsStrip` + `ProfileTabBar` + แท็บตาม map · sync `?tab=` · **คง `<NuxtPage>` ของหน้าตั้งค่าไว้** (กับดัก parent route ไม่มี NuxtPage) |
| 3.2 | `ProfileFriendButton.vue` (ตาราง 4.4) + เมนู ⋯ (คัดลอกลิงก์ / ร้องเรียน) |
| 3.3 | `tabs/ProfileOverviewTab.vue` + widget เจ้าของ: `ProfileCompletionWidget` (ลบภารกิจ/badge ปลอมและแท็บที่กดไม่ได้) · `FriendRequestsWidget` (เดิม) · `OwnerBalanceCard` (ลิงก์ `/Earn/Points`, `/Earn/Wallet`) · `MySchoolRecordCard` (ลิงก์ my-profile ของแต่ละโรงเรียน) |
| 3.4 | `tabs/ProfilePostsTab.vue` (ใช้ `FeedPost`, `CreatePostBox` เดิม) |
| 3.5 | `tabs/ProfileLearningTab.vue` + `tabs/ProfileTeachingTab.vue` (ใช้ `useApi` — เลิก `useFetch` + แนบ token เองแบบ `CoursesList.vue:27`) |
| 3.6 | `tabs/ProfileAchievementsTab.vue` + `tabs/ProfileFriendsTab.vue` + `tabs/ProfilePhotosTab.vue` |
| 3.7 | URL หลัก: ถ้าเข้าด้วย id ให้ `router.replace` เป็น reference_code · `/profile` ไม่ใส่ `personal_code` ลง URL |
| 3.8 | ลบ component ที่ไม่ใช้แล้ว (หลังยืนยัน D2): `BlogList, ForumList, MarketplaceList, CartList, EventsList, GroupsList, VideosList` (+ `CoursesList, CertificatesList, BadgesDisplay` ถ้าย้ายเข้าแท็บใหม่ครบแล้ว) |

### เฟส 4 — ตั้งค่า + หน้าที่เกี่ยวข้อง

| Shard | งาน |
|---|---|
| 4.1 | แท็บความเป็นส่วนตัว: เพิ่ม "ใครเห็นการเรียนของฉัน" · **ซ่อน `allow_messages` และ `show_online_status`** จนกว่าจะมีแชต/สถานะออนไลน์จริง (เมนูที่ไม่มีผลห้ามแสดง) |
| 4.2 | โปรไฟล์เกมพิมพ์: ให้ endpoint typing รับ user ที่ดู (ข้อมูลสาธารณะเท่านั้น) หรือจำกัดหน้านี้ให้ดูได้แค่ของตัวเอง |
| 4.3 | `/student/profile`: redirect ไป `/academies/{name}/my-profile` หรือลบ (ยืนยันก่อน) |
| 4.4 | ลิงก์ไปโปรไฟล์ที่ใช้ id (`FeedPost.vue`, `LessonCommentItem.vue`, `academies/[name].vue:1926`) — ไม่ต้องแก้ เพราะ resolver รองรับ id และข้อ 3.7 จะเปลี่ยน URL ให้เอง |

### เฟส 5 — ตรวจรับ

- เทสต์ทั้งหมดของแผนนี้ผ่านบน MySQL (สร้าง DB เทสต์ใหม่ก่อนด้วย `test:db:rebuild --force`)
- เปิดจอจริง 3 มุมมอง (เจ้าของ / เพื่อน / คนแปลกหน้า) × 3 ความกว้าง (375 → 768 → 1280) × โหมดมืด/สว่าง · กดทุกแท็บ ทุกปุ่ม
- grep ใน `components/profile/` และ `pages/profile/`:
  `\|\| 7`, `?? 30`, `?? 4`, `picsum`, `coming soon`, `$fetch(`, `useFetch(` ต้องเป็น 0
  และ `<button` ที่ไม่มี `@click`, `type="submit"` หรือ `:to` ต้องเป็น 0
- `git diff --stat` อ่านเลขบรรทัดที่ลบ (agy เคยรายงานงานที่ไม่ได้ทำ)

---

## 7. เกณฑ์ "เมนูใช้งานได้จริง" (ทุกแท็บ ทุกปุ่มต้องผ่านทุกข้อ)

1. ตอบ 200 ทั้งตอนเข้าด้วย id และ reference_code
2. ข้อมูลตรงกับ DB สำหรับเจ้าของ เพื่อน และคนแปลกหน้า (ไม่มีข้อมูลเกินสิทธิ์)
3. ไม่มีตัวเลขหรือข้อความแต่ง — ไม่มีข้อมูลก็แสดง empty state ที่บอกความจริง (บอกเจ้าของด้วยว่าจะเริ่มได้อย่างไร)
4. ทุกปุ่มทำงานจริงหรือพาไปหน้าที่มีอยู่จริง (ไม่มีลิงก์ไป 404 หรือหน้า coming soon)
5. เปิดได้ที่ 375px โดยไม่ต้องใช้แถบแท็บของ desktop และไม่มีการเลื่อนแนวนอนทั้งหน้า
6. ข้อผิดพลาดจาก API แสดงข้อความจริงให้ผู้ใช้เห็น (ระวัง error shape ต่างกันตาม HTTP client — `useApi` → `err.data`)

---

## 8. ข้อตัดสินใจที่รอเจ้าของเคาะ

| # | คำถาม | ตัวเลือก | แนะนำ |
|---|---|---|---|
| **D1** | ใครเห็น "การเรียน" (คอร์ส ใบประกาศ ชั้นเรียน โรงเรียน) โดยค่าเริ่ม | public / **friends** / private | **friends** — ผู้ใช้ส่วนใหญ่เป็นนักเรียนที่ยังไม่บรรลุนิติภาวะ คอร์สกับชั้นเรียนบอกได้ว่าเรียนโรงเรียนไหน ชั้นอะไร (เจ้าของเปิดเป็น public เองได้) |
| **D2** | ลบ 7 แท็บและการ์ดปลอมตามข้อ 4.3 รวมไฟล์ component | ลบ / ซ่อนไว้ก่อน | **ลบ** — ข้อมูลเป็น 0 และไม่มี UI สร้างข้อมูล ถ้าทำฟีเจอร์เหล่านั้นในอนาคตค่อยออกแบบใหม่ |
| **D3** | แท็บรูปภาพเอาข้อมูลจากไหน | รูปจากโพสต์ / ระบบอัปโหลดรูป+อัลบั้มเดิม (มี 1 รูป 0 อัลบั้ม) | **รูปจากโพสต์** — มีข้อมูลจริงและใช้ privacy ของโพสต์ได้เลย ส่วน `/api/profile/photos` คงไว้แต่ไม่ต้องมีปุ่มอัปโหลดบนโปรไฟล์ |
| **D4** | ภาษาดีไซน์ | HopeUI (ตาม CLAUDE.md) / คงสไตล์ Vikinger แบบหน้า feed | **HopeUI** — โปรไฟล์ไม่ใช่หน้าเกม (กติกา Vikinger ใช้กับ Play/Games) |
| **D5** | คนที่ไม่ล็อกอินเปิดโปรไฟล์ได้ไหม | ต้องล็อกอิน (เหมือนเดิม) / เปิดเฉพาะโปรไฟล์ครูแบบสาธารณะ | **ต้องล็อกอินไปก่อน** แล้วค่อยพิจารณาหน้าครูสาธารณะ (SEO) เป็นงานแยก |
| **D6** | ยอดเข้าชม | กันซ้ำ + เห็นเฉพาะเจ้าของ / ลบทิ้ง | **กันซ้ำ + เจ้าของเห็นคนเดียว** |
| **D7** | ปุ่ม "ส่งข้อความ" | ซ่อนจนกว่าจะมีแชต / ทำระบบแชตเป็นโปรเจกต์แยก | **ซ่อน** — ระบบแชตเป็นงานใหญ่ ไม่ควรผูกกับแผนนี้ |
| **D8** | "เรียนจบ" นับจากอะไร | `grade_accepted_at` / `completion_status='completed'` / `completed_at` | ต้องให้เจ้าของบอกว่าตอนปิดเกรด ระบบเขียนลงคอลัมน์ไหน — **ตอนนี้ทั้ง 4,917 แถวยังไม่มีค่าใดเลย** แท็บ "เรียนจบ" จะว่างจนกว่าจะปิดเกรดจริง |

---

## 9. กับดัก / ความเสี่ยง

- **sqlite โกหกได้** — ตาราง `friendships` และ cast ต่าง ๆ ต้องตรวจบน MySQL
- `UserProfileResource` มี consumer 2 ที่: หน้าโปรไฟล์ และโปรไฟล์เกมพิมพ์ (`/users/{id}/show`) — เปลี่ยนรูปแบบ response แล้วต้องเช็คทั้งสองหน้า
- `ProfileAccess` ถูกเรียกต่อแถวใน list (เช่น รายชื่อเพื่อน) → ระวัง N+1 ให้คิดสิทธิ์ครั้งเดียวต่อผู้ชม
- รูปโปรไฟล์มีสองชื่อคีย์ (`avatar` / `profile_photo_url`) และ select `avatar` ตรง ๆ ไม่ได้ (เป็น accessor) — ต้อง select `profile_photo_path`
- รูปจาก `/storage/...` — ใช้ URL ที่ resource ส่งมา ห้ามเขียน `src="/storage/..."` ตายตัว (ทำโมดูลหน้าพัง)
- agy รายงานงานที่ไม่ได้ทำ → ตรวจ `git diff` เองทุก shard · Codex อาจรัน migrate เอง → เช็ค `migrate:status` หลังทุก shard ที่มี migration
- ห้ามแตะคิว `backlog` · ห้าม `migrate` เปล่า ๆ (ใช้ `--path`)

---

## 10. ผลเฟส 0 (2026-11-20) — branch `fix/profile-privacy-phase0` จาก `origin/main` · = PR #54 (squash-merge แล้ว `1a9e3f3b`)

### 10.1 เจอเพิ่มระหว่างทำ (ร้ายแรงกว่าในแผน)
ต้นเหตุร่วมคือ `App\Http\Resources\UserResource` ซึ่งส่ง **email, phone, phone_number, points, wallet, personal_code** ของผู้ใช้**ทุกคนที่มันแปลง**
(ถ้าโหลด `profile` มาด้วย จะส่ง address, birthdate, postal_code และฟิลด์อื่นอีก) · ใช้อยู่ใน ~46 ไฟล์
- **สาธารณะ ไม่ต้องล็อกอิน (ยิงจริงยืนยันแล้ว):**
  - `GET /api/welcome` และ `GET /api/` คืนผู้ใช้ทีละ 12 คนพร้อมอีเมล เบอร์โทร wallet ไล่ `?page=` ได้ทุกคน
    + ผู้บริจาค 8 คนพร้อม `transaction_id` · ถ้า error จะคืน stack trace
  - `GET /api/register/{ref}` และ `/api/suggester/check/{code}` (หน้าลิงก์ชวนสมัคร)
  - `GET /api/courses/marketplace` คืนข้อมูลเจ้าของคอร์ส ผู้สร้างโรงเรียน และผู้อำนวยการ
- **ล็อกอินแล้ว:** timeline ของโปรไฟล์ฝังผู้ใช้แบบเต็มไว้ใน `action_by`, `target_resource.author` และในคอมเมนต์ทุกระดับ

### 10.2 สิ่งที่แก้แล้ว (4 shard)

| Shard | ผู้ทำ | ไฟล์ |
|---|---|---|
| A1 | agy | `PublicUserResource` + `PublicLeaderboardUserResource` (ใหม่) · `UserProfileController` (activities/index/stats) · `UserProfileResource` (แต้ม/wallet/last_login/ความครบโปรไฟล์ เฉพาะเจ้าของ + หมวดเกี่ยวกับตาม privacy) · `WelcomeController` (เลิกส่ง trace) · `SuggesterController` · `DonateResource` (ผู้ไม่ล็อกอินได้ข้อมูลผู้บริจาคแบบสาธารณะ ไม่มี transaction_id/notes) · resource ฟีด 8 ไฟล์ (Activity, Post, PostComment, PostMention, PostTaggedUser, Poll, CoursePost, CoursePostComment) |
| A2 | agy | `PostController` index กรอง privacy · show บล็อกระดับ "เพื่อน" ไม่ให้คนที่ไม่ใช่เพื่อนเห็น (ใช้ตาราง `friendships`) |
| A3 | **Codex** (agy หมดโควตา 429) | `MarketplaceCourseResource.user` + `AcademyResource.creater/director` → `PublicUserResource` |
| B | agy | `profile/[id].vue` การ์ดแต้ม-เงินแสดงเฉพาะเจ้าของ · `ProfileAboutSection` ช่องแต้มแสดงเฉพาะเจ้าของ · `useProfile.ts` เปลี่ยน type เป็น optional |

Claude แก้เอง 1 จุด: รัน `pint` กับ `PostController.php` เพราะ agy รายงานว่า pint ผ่าน แต่ที่จริงไม่ผ่าน
ผลคือได้ `use App\Models\User;` และ `use Illuminate\Database\Eloquent\Builder;` เพิ่ม → **จะชนกับ PR #53 ตรงบรรทัด import ตอน merge** (แก้ง่าย)

### 10.3 🔴 D9 — ยังรั่วอยู่ (ต้องเคาะก่อนแก้) : เปลี่ยนค่าเริ่มต้นของ `UserResource`
ผู้ใช้ที่ล็อกอินแล้วยังเห็นอีเมล เบอร์โทร และ wallet ของคนอื่นได้ผ่าน:
- `CourseResource.user` (เจ้าของคอร์ส) · `LessonResource.creater` · `LessonCommentResource.user` · `TopicResource.author` · `QuestionResource.creator`
- `AcademyPost*Resource` (โพสต์และคอมเมนต์ในโรงเรียน) · `DonateRecipientResource` (กิจกรรมบริจาคบน newsfeed ~30,000 แถว)
- `CourseMemberResource.user` · `CourseMemberGradeProgressResource` · `CourseQuizResultResource` · `AssignmentAnswerResource.student`
- `FriendshipResource.sender` · `ShareCommentController` · `NewsfeedController.peopleMayKnow`
  (`PeopleMayKnowWidget.vue` **แสดงอีเมลของคนแปลกหน้าบนจอด้วย**)

ข้อเสนอ:
- `UserResource` ส่ง email/phone/wallet เฉพาะเมื่อ (ก) ผู้ชมคือเจ้าของบัญชี หรือ (ข) ผู้ชมเป็นแอดมินแพลตฟอร์ม
- resource ของหน้าจัดการสมาชิกเรียก `->withContactDetails()` เอง
- ตอน login/register ให้หาผู้ชมจาก `auth('api')->user()` เพราะ route นั้นไม่มี `auth:api`

**ต้องเคาะ:** ครูประจำรายวิชา / แอดมินโรงเรียน เห็นอีเมล-เบอร์ของสมาชิกได้ไหม
(ตอนนี้ `MemberList`, `MemberListView`, `ProgressList` ใช้อีเมลเป็นตัวระบุคน)
และ `personal_code` + แต้ม เป็นข้อมูลสาธารณะไหม (หน้าแรกกับการ์ดผู้บริจาคโชว์อยู่)

### 10.4 ตรวจแล้ว (Claude รันเอง ไม่ได้เชื่อรายงาน)
- `pint --test` ผ่านทุกไฟล์ PHP ที่แก้ (A1/A2/A3) · SFC compile ผ่าน 2 ไฟล์ (`[id].vue`, `ProfileAboutSection.vue`)
- **MySQL** (`-c phpunit.mysql.xml`):
  - เทสต์ใหม่ `ProfilePrivacyLeakTest` (8) · `PublicUserDataLeakTest` (5) · `PostPrivacyVisibilityTest` (7) + `UserProfilePrivacyTest` เดิม (5) = **25 ผ่าน / 200 assertions**
  - `MarketplacePublicDataTest` (2) + `CoursePurchaseFlowTest|AcademyCoursePurchaseTest` (18) = **20 ผ่าน**
- **revert-check:** ใช้โค้ดเดิม เทสต์ใหม่ fail 15/20 (5 เคสที่ผ่านคือเทสต์กันถอยหลัง เช่น เจ้าของยังเห็นของตัวเอง) · marketplace fail 2/2
- **ชุดกันถอยหลัง MySQL** (Post/Feed/Activity/Comment/Donat/Marketplace/Profile/Friend/Welcome/Newsfeed/Poll/Share/Suggester/Referral/Academy/Course/Lesson/Support):
  1,244 ผ่าน · 4 fail ซึ่ง fail บนโค้ดเดิมด้วย (stash แล้วรันซ้ำยืนยัน) ได้แก่
  `AcademySettingsAuditLogTest`, `StaffDepartmentValidationTest`, `StudentLifecycleControllerTest`, `ClassScheduleTodayTest`
- **ยิง API จริงบน DB dev ใน transaction แล้ว rollback:**
  - โปรไฟล์คนอื่น show/activities/stats ไม่มีข้อมูลส่วนตัวหลุด
  - โปรไฟล์ตัวเองยังได้ครบ
  - `/api/posts` ไม่มีโพสต์ที่ไม่ใช่สาธารณะของคนอื่น (เดิม 3)
  - สแกน route สาธารณะ 22 ตัว ไม่มีข้อมูลส่วนตัวหลุดเลย
    (ตอบ 200 = 19 ตัว · `typing/leaderboard` 422 เพราะต้องมีพารามิเตอร์ · `donates/donor/{id}` 404 · `donates/create` 500 เป็นปัญหาเดิม)
- **เจอข้างทาง (ไม่ได้แก้):** `GET /api/supports/donates/create` (สาธารณะ) ตอบ 500 เพราะไม่มีเมธอด `DonateController::create()` · คอลัมน์ `donates.donor_address` ไม่มีอยู่จริง
- 🔴 **ยังไม่ได้เปิดจอจริง** (การ์ดแต้ม-เงินในโปรไฟล์คนอื่นต้องหายไป · ของตัวเองยังอยู่ · ตรวจที่ 375px)

---

## 11. ผลเฟส 1 (2026-11-21) — branch `fix/profile-phase1-friendship` · = PR #55 (rebase ลง `main` หลัง #54 merge)

### 11.1 สิ่งที่แก้

| Shard | ผู้ทำ | ไฟล์ |
|---|---|---|
| A | agy | ใหม่: `App\Support\ProfileUserResolver` (ตัวเลข → id แล้ว personal_code · ข้อความ → reference_code แล้ว username · **เลิกจับคู่ด้วย name**) · `App\Services\Profile\ProfileAccess` (สถานะเพื่อน สิทธิ์ ระดับ privacy ของโพสต์ คำขอเป็นเพื่อน เพื่อนร่วม — อ่านจาก `friendships` ที่เดียว) · `UserProfileController` (เลิกใช้ `App\Models\Friend` ทั้งไฟล์ · นับผู้เข้าชมวันละครั้งต่อคน · `stats` ใช้จำนวนเพื่อนจริง และ visits เห็นเฉพาะเจ้าของ · `update` เลิก `json_encode` ซ้ำ) · `UserProfileResource` (friends_count จริง · `mutual_friends` · `can_send_friend_request` · posts_count ตามสิทธิ์จริง · visits เฉพาะเจ้าของ · ลิงก์โซเชียลไม่ทำให้ 500) · `UserProfile` (privacy_settings เป็นสตริงผ่าน accessor/mutator) |
| B | agy (โควตาหมดหลังแก้ไฟล์เสร็จ — Claude รัน pint/เทสต์ต่อเอง) | `FriendController` (คำขอเป็นเพื่อน: ส่งหาตัวเอง 422 · ผู้รับปิดรับ 403 · ซ้ำหรือเป็นเพื่อนแล้ว 409 · response แบบสาธารณะ / รายชื่อเพื่อน: ทำตาม privacy (403) · แบ่งหน้าละ 20 · ลบ is_online/last_seen ปลอม · ไม่ส่งชื่อจริง · เลเวลจาก xp_level · นับเพื่อนร่วมด้วย query จำนวนคงที่ / suggestions + pending ไม่ส่งอีเมล) · `PhotoController` / `VideoController` (ใช้ resolver + แก้ 500 จาก `friends()`) |
| D | agy | `[id].vue` (ช่องเข้าชมแสดงเฉพาะเจ้าของ · ซ่อนปุ่มเพิ่มเพื่อนเมื่อเจ้าของปิดรับ) · `ProfileAboutSection` · `FriendsList` (ลบ mock · ตัวกรองออนไลน์ · dropdown ที่พึ่ง DaisyUI ซึ่งไม่ได้ติดตั้ง · ปุ่มแชตที่ไปหน้า `/messages` ซึ่งไม่มีอยู่ · ลิงก์ `/members` ที่เป็น coming soon / ค้นหาบนโปรไฟล์คนอื่นใช้กรองในรายชื่อที่โหลดแล้ว / จำนวนรวมจาก `meta.total`) · `PeopleMayKnowWidget` (เลิกแสดงอีเมล) · type ใน `useFriends` / `useProfile` |

**Claude แก้เอง:** นับจำนวนเพื่อนเป็น "จำนวนคน" แทน "จำนวนแถว"
- เหตุผล: DB มี **21 คู่ที่ยอมรับซ้ำ 2 แถว (เกิน 22 แถว)** ทำให้จำนวนเพื่อนของ 41 คนเกินจริง
- แก้ที่ resource, `stats` และการนับเพื่อนร่วม + เพิ่มเทสต์ 2 เคส

### 11.2 ต่างจากแผนเดิม
- **1.4 `privacy_settings`:** ใช้ accessor/mutator ใน model แทน data migration จึงไม่ต้องแก้ข้อมูลจริง (3 แถวเก่าที่มี `"..."` อ่านได้ถูกทันที)
- **เพิ่มเข้ามา:** แก้โปรไฟล์ที่ตอบ 500 ของ 4 คนที่บันทึกลิงก์โซเชียล · แก้ 500 ของ `VideoController`
- **1.3 ยอดเข้าชม:** ใช้ทางเลือกที่แนะนำของ D6 (กันนับซ้ำ + เจ้าของเห็นคนเดียว) ถ้าเจ้าของเลือก "ลบทิ้ง" ค่อยลบทีหลัง
- **`data.can_view_full_profile`:** คงความหมายเดิมไว้ (= เจ้าของหรือเพื่อน) เพราะเทสต์เดิมยึดอยู่ หน้าเว็บใช้ `can_view_full_profile` ระดับบนของ `show`

### 11.3 ตรวจแล้ว (Claude รันเอง)
- pint ผ่านทุกไฟล์ · SFC compile ผ่าน 4 ไฟล์
- **MySQL:** `ProfileFriendshipAccessTest` (13) · `FriendListPrivacyTest` (12) + เทสต์เฟส 0 ทั้งหมด + `UserProfilePrivacyTest` = **52 ผ่าน / 346 assertions**
- **revert-check:** ใช้โค้ดเดิม เทสต์ใหม่ fail 21/23 (2 เคสที่ผ่านคือเทสต์กันถอยหลัง) · เคสแถวซ้ำ fail 2/2 เมื่อใช้วิธีนับแบบเดิม
- **ชุดกันถอยหลัง MySQL** (เพิ่ม Photo/Video/Settings จากรอบเฟส 0): 1,285 ผ่าน · 4 fail ชุดเดิมที่ fail บน main อยู่แล้ว · ไม่มีอะไรพังเพิ่ม
- **ข้อมูลจริงบน DB dev (rollback + cache แบบ array):**
  - คู่เพื่อนจริง 50 คู่ได้สถานะ `friends` ครบทุกคู่ (เดิมเป็น `none` ทุกคู่)
  - จำนวนเพื่อนเป็น 13 ตรงกับรายชื่อ (เดิม 0)
  - เปิดโปรไฟล์ซ้ำไม่นับยอดเข้าชมเพิ่ม · ผู้ชมไม่เห็นยอดเข้าชม
  - 4 คนที่เคยตอบ 500 ตอนนี้ได้ 200
  - โปรไฟล์ private ตอบ 403 เมื่อขอรายชื่อเพื่อน · suggestions ไม่มีอีเมล
- 🔴 **ยังไม่ได้เปิดจอจริง** — แท็บเพื่อน (ไม่มีออนไลน์/แชตแล้ว) · ปุ่มเพิ่มเพื่อน · ช่องเข้าชมของคนอื่นต้องหายไป · ตรวจที่ 375px

### 11.4 ค้าง / ต้องเคาะ
- **agy หมดโควตาประมาณ 6 วัน** (รีเซ็ตราว 2026-11-27) → งานถัดไปใช้ Codex
- **แถวซ้ำใน `friendships` (21 คู่):** ยังไม่ลบ ถ้าจะลบต้องทำเป็น migration ตามกติกา — รอเจ้าของเคาะ
  สงสัยว่ามาจาก `befriend()` ของแพ็กเกจที่สร้างแถวใหม่ได้หลัง denied (ยังไม่ได้พิสูจน์)
- **PR ซ้อน (จบแล้ว):** #54 squash-merge แล้ว → rebase เฉพาะ 4 commit ของเฟส 1 ด้วย `git rebase --onto main fdcf7726`
  tree เหมือนก่อน rebase ทุกไบต์ · เทสต์โปรไฟล์ MySQL 52 ผ่านซ้ำ → PR #55
- **#53 ชนกับ `main` แล้ว:** `PostController` ไฟล์เดียว (บรรทัด `use` ที่ #54 เพิ่ม) ต้องแก้ก่อน merge #53
