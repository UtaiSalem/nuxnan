# รายงานสรุป — Autonomous Session (Monetization Hardening)

> เริ่ม 2026-07-24 · โหมด autonomous ~3 ชม. · pipeline: **Codex implement → agy review → Claude verify → commit**
> Branch: **`feat/monetization-hardening`** (6 commits, ไม่ push, ไม่รัน migrate/config, ไม่รัน npm build)
> อ่านคู่: [`public-tier-monetization.md`](./public-tier-monetization.md) · [`school-tier-monetization.md`](./school-tier-monetization.md)

## ✅ สถานะ: เสร็จทุก batch ที่ตัดสินใจล็อคแล้ว

| commit | เนื้อหา | verify |
|---|---|---|
| `36e58a8c` | docs: บทวิเคราะห์ public+school + มติล็อค 15 ข้อ | - |
| `6afe4080` | **Phase 0** slip privacy / SVG / spoofing / dead endpoint / hide pending | Claude 6/6 |
| `ff0d8d0b` | **Phase 0-fix** ปิด slip leak ที่ agy จับได้ (accessor, raw model, migration delete) | agy + Claude, test 34 |
| `ee66578c` | **SVG sweep** ตัด svg จาก upload ทั้งแอป (19 ไฟล์) | Claude spot-check |
| `a0094613` | **PP=money** เกม/gamification → XP เท่านั้น, ปิด /points/earn, streak→XP | Claude, test 13 |
| `fa1b80a0` | **Donation integrity** SSOT config, rate 100→1080, tx/lock, split 220/30/20 ledgered, caps 5/20 | agy 3 P1 fixed + Claude, test 83 |

## จุดที่ pipeline พิสูจน์คุณค่า (agy จับที่ Claude verify พลาด)
- **Phase 0:** model accessor `getSlipAttribute` คืน path public → สลิปยังรั่วแม้ตัดจาก resource (Claude ตรวจ 6 items ผ่านหมดแต่พลาดจุดนี้) → แก้แล้ว
- **Batch C (การเงิน):** F8 `try/catch` ใน `DB::transaction` → commit partial state (ลบล้าง atomicity ทั้งหมด) · F3 platform หาย → เผา 20 แต้ม · F6 store raw decrement ไม่ ledger → **แก้ครบทั้ง 3, พิสูจน์ด้วย PublicDonationHardeningTest 6 ผ่าน**

## ⚠️ เรื่องที่ต้องรู้ก่อน deploy
1. **ยังไม่รัน:** `slips:migrate-to-private --delete-public` (ย้ายสลิปเก่า 267 ไฟล์) · migration `make_gamification_rules_xp_only` · (config/economy.php ใช้ทันทีเมื่อ deploy code) — **ทั้งหมดตั้งใจไม่รัน** รอ deploy จริง
2. **Full test suite รวมทั้งหมด fatal (exit 255)** = Xdebug max-nesting/memory ตอนรันพร้อมกันทุก test (**environmental ไม่ใช่ regression** — ทุกโดเมนที่แก้รันแยกผ่านหมด: donation 83, gamification 34, wallet, post) → ตอน deploy ควรรัน suite แบบปิด Xdebug หรือแบ่ง chunk เพื่อ confirm เขียว
3. **ผลข้างเคียงที่ตั้งใจ (D11.2):** หลัง migration → quiz/lesson ได้แค่ XP; pp มาจากกองทุนวิชา (CoursePointCampaign) เท่านั้น — วิชาไม่มีแคมเปญ = ไม่ได้ pp
4. **behavior เปลี่ยน:** การกดรับบริจาค 240→220, เพิ่มแพลตฟอร์ม 20 + เพดานรวม 20/วัน (เดิม 10/รายการ อย่างเดียว)

## 🔜 รอ user ตัดสินก่อน implement ต่อ (ไม่ได้แตะรอบนี้)
1. **6 เรื่องโรงเรียน** (§4 school doc): guest ระดับ ร.ร. · สูตรโฆษณา 2 ชั้น · ยุบ campaign_type=support · ถอนรายวิชา pp/cash · academy withdrawal · split การกดรับรายวิชา
2. **guest advertiser** (D4/D7/D8 — ต้อง migrate `adverts.user_id`+`activities.user_id` เป็น nullable ก่อน)
3. **ad-pay-with-points** (โฆษณายังจ่ายด้วยแต้มไม่ได้ — เพิ่ม payment_method points ถ้าต้องการสมมาตรกับบริจาค)
4. **ปิด/ยุบ legacy ad path** (CampaignWidget ยังใช้ /campaigns/{id}/view นับเวลา client-side)
5. finding นอกสโคป: **สลิปเติมเงิน wallet (DepositApproval) ก็รั่ว public disk** — ย้าย private รอบหน้า
6. P0 #1 donor-card remaining_points = course tier → ไปกับสโคปโรงเรียน

## วิธี review งานนี้
```bash
git log --oneline main..feat/monetization-hardening
git diff main..feat/monetization-hardening
```
ยังไม่ merge/push — เปิดให้ตรวจก่อน ทุก commit มี Codex-implement + (บางส่วน) agy-review + Claude-verify
