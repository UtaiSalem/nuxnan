# Wallet System Quick Reference
# คู่มืออ้างอิงระบบวอลเลตแบบรวบรัด

## สรุปหลักการใช้งาน (Quick Summary)

### 🎯 วิธีการสะสมแต้ม (How to Earn Points)

| กิจกรรม | แต้ม | Activity | Points |
|---------|------|----------|--------|
| โพสต์ทั่วไป | +180 | Regular post | +180 |
| โพสต์พร้อมรูป | +240 | Post with images | +240 |
| รับไลค์ | +12/ครั้ง | Receive like | +12/like |
| รับแชร์ | +18/ครั้ง | Receive share | +18/share |
| ทำแบบทดสอบ | +คะแนน | Quiz score | +score |
| ส่งงาน | +5-20 | Submit assignment | +5-20 |
| เข้าเรียน | +10-50 | Access lesson | +10-50 |
| ดูโฆษณา | +4%×เวลา | Watch ads | +4%×duration |
| Daily Login | +10-50 | Daily login | +10-50 |

### 💸 การใช้แต้ม (Spending Points)

| กิจกรรม | แต้ม | Activity | Points |
|---------|------|----------|--------|
| กดไลค์ | 24 | Like | 24 |
| กดดิสไลค์ | 12 | Dislike | 12 |
| แชร์โพสต์ | 36 | Share post | 36 |
| คอมเมนต์ | 12 | Comment | 12 |
| ตอบคอมเมนต์ | 12 | Reply comment | 12 |
| สร้างคอร์ส | 100 | Create course | 100 |
| สร้างโพล | 180+pool | Create poll | 180+pool |
| บริจาค | 270 | Donate | 270 |
| **แปลงเป็นเงิน** | **1,080 = 1 บาท** | **Convert to money** | **1,080 = 1 THB** |

### 💰 ระบบวอลเลต (Wallet System)

**อัตราแลกเปลี่ยน (Exchange Rate):**
- **1,080 แต้ม = 1 บาท** (1,080 points = 1 THB)

**เงื่อนไขการถอน (Withdrawal Conditions):**
- ขั้นต่ำ: **100 บาท** (Minimum: 100 THB)
- สูงสุด/ครั้ง: **50,000 บาท** (Max/transaction: 50,000 THB)
- สูงสุด/วัน: **200,000 บาท** (Max/day: 200,000 THB)
- สูงสุด/เดือน: **1,000,000 บาท** (Max/month: 1,000,000 THB)
- ค่าธรรมเนียม: **0.5%** (ขั้นต่ำ 10 บาท) (Fee: 0.5%, min 10 THB)

### 📊 Streak Bonus (โบนัสการเข้าต่อเนื่อง)

```
วันที่ 1-3:   10 แต้ม/วัน   | Day 1-3:   10 points/day
วันที่ 4-7:   20 แต้ม/วัน   | Day 4-7:   20 points/day
วันที่ 8-14:  30 แต้ม/วัน   | Day 8-14:  30 points/day
วันที่ 15-30: 50 แต้ม/วัน   | Day 15-30: 50 points/day
วันที่ 31+:   100 แต้ม/วัน  | Day 31+:   100 points/day
```

### 🎮 Level System (ระบบเลเวล)

```
Level = floor((Total Points / 100) ^ (2/3))
Level Up Bonus = 100 × Level แต้ม
```

**ตัวอย่าง (Examples):**
- 0-99 แต้ม: Level 0
- 100-299 แต้ม: Level 1
- 300-599 แต้ม: Level 2
- 600-999 แต้ม: Level 3
- 1,000+ แต้ม: Level 4+

---

## 📱 API Endpoints หลัก (Main API Endpoints)

### Points API
```javascript
GET    /api/points/balance          // ดูยอดแต้ม
POST   /api/points/earn            // รับแต้ม
POST   /api/points/spend           // ใช้แต้ม
POST   /api/points/convert         // แปลงแต้มเป็นเงิน
GET    /api/points/transactions    // ประวัติธุรกรรมแต้ม
```

### Wallet API
```javascript
GET    /api/wallet/balance         // ดูยอดเงิน
POST   /api/wallet/deposit         // ฝากเงิน
POST   /api/wallet/withdraw        // ถอนเงิน
POST   /api/wallet/transfer        // โอนเงิน
GET    /api/wallet/transactions    // ประวัติธุรกรรมเงิน
```

### Gamification API
```javascript
GET    /api/gamification/streak           // ข้อมูล streak
GET    /api/gamification/achievements     // ความสำเร็จทั้งหมด
GET    /api/gamification/leaderboard/points    // อันดับแต้ม
GET    /api/gamification/leaderboard/streak    // อันดับ streak
```

---

## 🎯 เคล็ดลับสะสมแต้ม (Tips for Earning Points)

### ✅ ทำทุกวัน (Do Daily)
1. เข้าสู่ระบบทุกวัน - รับ 10-100 แต้ม/วัน
2. โพสต์อย่างน้อย 1 ครั้ง - ได้ 180-240 แต้ม
3. โต้ตอบกับผู้อื่น - กดไลค์, คอมเมนต์

### 🚀 เพิ่มแต้มเร็ว (Earn Fast)
1. สร้างโพสต์พร้อมรูปภาพ - ได้ 240 แต้ม
2. สร้างเนื้อหาน่าสนใจ - รับไลค์และแชร์
3. เข้าเรียนและทำแบบทดสอบ - ได้แต้มและความรู้
4. ดูโฆษณา - แปลงเป็นเงิน wallet

### 💎 กลยุทธ์ขั้นสูง (Advanced Strategy)
1. สร้าง streak ยาวๆ - ได้ 100 แต้ม/วัน
2. สร้างคอร์ส - ได้ 100 แต้ม + รายได้
3. สร้างโพล - ได้ 180+ แต้ม
4. บริจาค - ได้ 270 แต้ม

---

## ❓ คำถามที่พบบ่อย (FAQ)

### แต้ม (Points)
**Q: แต้มหมดอายุไหม?**
A: ไม่หมดอายุ (No expiration)

**Q: แปลงแต้มเป็นเงินได้ไหม?**
A: ได้ 1,080 แต้ม = 1 บาท (Yes, 1,080 points = 1 THB)

**Q: แปลงกลับเป็นแต้มได้ไหม?**
A: ไม่ได้ (No, cannot convert back)

### วอลเลต (Wallet)
**Q: ถอนขั้นต่ำกี่บาท?**
A: 100 บาท (100 THB minimum)

**Q: ค่าธรรมเนียมถอนเท่าไหร่?**
A: 0.5% (ขั้นต่ำ 10 บาท) (0.5%, min 10 THB)

**Q: ถอนเงินใช้เวลานานแค่ไหน?**
A: 1-3 วันทำการ (1-3 business days)

**Q: ถอนเงินสูงสุดกี่บาท/วัน?**
A: 200,000 บาท/วัน (200,000 THB/day)

**Q: ถอนเงินสูงสุดกี่บาท/เดือน?**
A: 1,000,000 บาท/เดือน (1,000,000 THB/month)

### โอนเงิน (Transfer)
**Q: โอนเงินให้ใครก็ได้ไหม?**
A: โอนได้เฉพาะเพื่อนหรือผู้ใช้ในระบบ (Only to friends or system users)

**Q: ค่าธรรมเนียมโอนเงินเท่าไหร่?**
A: ฟรี (Free)

---

## 📋 ตัวอย่างการใช้งาน (Usage Examples)

### ตัวอย่างที่ 1: สะสมแต้มและแปลงเป็นเงิน
```javascript
// 1. โพสต์ (ได้ 180 แต้ม)
POST /api/points/earn { "activity_type": "post", "activity_id": 123 }

// 2. รับไลค์ 10 ครั้ง (ได้ 120 แต้ม)
// ระบบเพิ่มแต้มอัตโนมัติ

// 3. แปลง 1,080 แต้ม → 1 บาท
POST /api/points/convert { "points": 1080 }

// 4. ดูยอด wallet
GET /api/wallet/balance
// Response: { "cash_balance": 1.00 }
```

### ตัวอย่างที่ 2: ถอนเงิน
```javascript
// 1. ขอถอน 500 บาท
POST /api/wallet/withdraw {
  "amount": 500,
  "method": "bank_transfer",
  "bank_account": {
    "bank_name": "Krungthai Bank",
    "account_number": "123-4-56789-0",
    "account_name": "Somchai Jaidee"
  }
}

// 2. รอการอนุมัติ (1-3 วันทำการ)
// Status: pending → completed
```

### ตัวอย่างที่ 3: โอนเงิน
```javascript
// โอน 100 บาทให้เพื่อน
POST /api/wallet/transfer {
  "to_user_id": 123,
  "amount": 100,
  "message": "ขอบคุณครับ"
}
```

---

## 🎖️ ระบบความสำเร็จ (Achievement System)

### ประเภทความสำเร็จ (Achievement Types)

**Points Achievements:**
- 🏆 ได้แต้มครั้งแรก
- 🏆 สะสม 1,000 แต้ม
- 🏆 สะสม 10,000 แต้ม
- 🏆 สะสม 100,000 แต้ม

**Action Achievements:**
- 📝 โพสต์ 10 ครั้ง
- 👍 ได้รับ 100 ไลค์
- 💬 คอมเมนต์ 50 ครั้ง

**Streak Achievements:**
- 🔥 เข้า 3 วันต่อเนื่อง
- 🔥 เข้า 7 วันต่อเนื่อง
- 🔥 เข้า 30 วันต่อเนื่อง
- 🔥 เข้า 100 วันต่อเนื่อง

**Social Achievements:**
- 👥 เพิ่มเพื่อน 10 คน
- 👥 เพิ่มเพื่อน 100 คน
- 👥 เพิ่มเพื่อน 1,000 คน

**Learning Achievements:**
- 📚 เข้าเรียนครั้งแรก
- 📝 ทำแบบทดสอบครั้งแรก
- 📤 ส่งงานครั้งแรก
- 🎓 จบคอร์สครั้งแรก

### ระดับความหายาก (Rarity Levels)
- **Common** (สามัญ): 10-50 แต้ม
- **Uncommon** (ไม่สามัญ): 51-100 แต้ม
- **Rare** (หายาก): 101-200 แต้ม
- **Epic** (มหาศาล): 201-500 แต้ม
- **Legendary** (ตำนาน): 501+ แต้ม

---

## 📊 ระบบอันดับ (Leaderboard System)

| ประเภท | รายละเอียด |
|-------|-----------|
| Points Leaderboard | อันดับตามแต้มสะสมทั้งหมด |
| Weekly Leaderboard | อันดับตามแต้มในสัปดาห์ |
| Monthly Leaderboard | อันดับตามแต้มในเดือน |
| Streak Leaderboard | อันดับตาม streak ยาวนานที่สุด |
| Achievement Leaderboard | อันดับตามจำนวนความสำเร็จ |

---

## 🎁 ระบบรางวัล (Rewards System)

### ประเภทรางวัล (Reward Types)
- 💳 **Wallet Rewards**: บัตรกำนัล, เงินสด
- 🏅 **Badge Rewards**: Badge พิเศษ
- ⭐ **Feature Rewards**: ฟีเจอร์พิเศษ, ธีม
- 🎉 **Discount Rewards**: ส่วนลด, โปรโมชัน

### วิธีแลกรางวัล (How to Redeem)
```javascript
// 1. ดูรางวัลที่แลกได้
GET /api/rewards

// 2. แลกรางวัล
POST /api/rewards/redeem {
  "reward_id": 1,
  "quantity": 1
}

// 3. ดูรางวัลที่แลกแล้ว
GET /api/rewards/my
```

---

## 📞 การติดต่อ (Contact)

หากมีปัญหาหรือข้อสงสัย:
- ติดต่อผ่านระบบ Support
- อีเมล: support@nuxnan.com

---

## 📝 สรุป (Summary)

### ขั้นตอนเริ่มต้น (Getting Started)
1. ✅ เข้าสู่ระบบทุกวัน - รับแต้ม daily login
2. ✅ โพสต์และโต้ตอบ - สะสมแต้ม
3. ✅ เรียนรู้ - ได้แต้มและความรู้
4. ✅ แปลงแต้มเป็นเงิน - 1,080 แต้ม = 1 บาท
5. ✅ ถอนเงิน - ขั้นต่ำ 100 บาท

### เป้าหมาย (Goals)
- 🎯 สะสม 1,080 แต้ม → แปลงเป็น 1 บาท
- 🎯 เข้า 7 วันต่อเนื่อง → ได้ 20 แต้ม/วัน
- 🎯 สร้างเนื้อหาน่าสนใจ → รับไลค์และแชร์
- 🎯 บรรลุความสำเร็จ → รับ badge พิเศษ
- 🎯 ขึ้นอันดับ leaderboard → แข่งขันกับเพื่อน

---

**เอกสารนี้อัปเดตล่าสุดเมื่อ: 14 มกราคม 2026**
**This document was last updated on: January 14, 2026**

---

## 📚 เอกสารเพิ่มเติม (Additional Documentation)

- [`wallet-user-guide-th.md`](wallet-user-guide-th.md) - คู่มือฉบับสมบูรณ์ (ภาษาไทย)
- [`wallet-user-guide-en.md`](wallet-user-guide-en.md) - Complete user guide (English)
- [`../plans/points-wallet-system-summary.md`](../plans/points-wallet-system-summary.md) - สรุประบบ
- [`../plans/points-wallet-system-architecture.md`](../plans/points-wallet-system-architecture.md) - สถาปัตยกรรมระบบ
