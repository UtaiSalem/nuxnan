# คู่มือการทดสอบการแปลงเงินเป็นแต้ม (Wallet to Points Conversion Test Guide)

## ภาพรวม (Overview)

ระบบรองรับฟีเจอร์การแปลงเงินเป็นแต้มสำหรับการสนับสนุนโฆษณา แล้ว คุณสามารถทดสอบได้ทันที

## อัตราแลกเปลี่ยน (Exchange Rates)

- **แปลงแต้มเป็นเงิน (Points → Money)**: 1,200 แต้ม = 1 บาท
- **แปลงเงินเป็นแต้ม (Money → Points)**: 1 บาท = 1,080 แต้ม

## วิธีทดสอบ (Testing Methods)

### วิธีที่ 1: ผ่านหน้าเว็บ (Web UI)

#### ขั้นตอน (Steps):

1. **เข้าสู่ระบบ**
   - เข้าสู่ระบบด้วยบัญชีผู้ใช้งาน
   - ไปที่หน้า [`/earn/wallet`](/earn/wallet)

2. **ตรวจสอบยอดเงิน**
   - ดูยอดเงินคงเหลือที่หน้ากระเป๋าเงิน
   - ตรวจสอบว่ามีเงินเพียงพอสำหรับการทดสอบ

3. **เลือกแท็บ "แปลงเงินเป็นแต้ม"**
   - คลิกที่แท็บ "แปลงเงินเป็นแต้ม" (Convert to Points)
   - ไอคอน: `mdi:swap-horizontal-circle`

4. **ระบุจำนวนเงิน**
   - ป้อนจำนวนเงินที่ต้องการแปลง
   - ขั้นต่ำ: 10 บาท
   - สามารถเลือกจำนวนด่วน: 10, 50, 100, 500, 1,000 บาท

5. **ตรวจสอบการแปลง**
   - ระบบจะแสดงการคำนวณ:
     - เงินที่ใช้: จำนวนที่ระบุ
     - แต้มที่จะได้: จำนวน x 1,080
     - อัตราแลกเปลี่ยน: 1 บาท = 1,080 แต้ม

6. **ยืนยันการแปลง**
   - คลิกปุ่ม "แปลงเงินเป็นแต้ม"
   - รอให้ระบบดำเนินการ

7. **ตรวจสอบผลลัพธ์**
   - ดูข้อความสำเร็จ/ล้มเหตุ
   - ตรวจสอบยอดเงินและแต้มที่อัปเดตแล้ว

### วิธีที่ 2: ผ่าน API (Direct API Testing)

#### ขั้นตอน (Steps):

1. **รับ Authentication Token**
   ```bash
   # Login และรับ token
   curl -X POST http://localhost:8000/api/login \
     -H "Content-Type: application/json" \
     -d '{"login": "your_email@example.com", "password": "your_password"}'
   ```

2. **ทดสอบแปลงเงินเป็นแต้ม**
   ```bash
   # แปลง 10 บาท เป็นแต้ม
   curl -X POST http://localhost:8000/api/wallet/convert-to-points \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -d '{"amount": 10}'
   ```

3. **ตรวจสอบ Response**
   ```json
   {
     "success": true,
     "message": "Wallet converted to points successfully",
     "data": {
       "wallet_amount": 10,
       "points_received": 10800,
       "new_wallet_balance": 90,
       "new_points_balance": 10800,
       "exchange_rate": "1 THB = 1080 points"
     }
   }
   ```

## ตัวอย่าง Test Cases (Test Cases)

### Test Case 1: แปลงขั้นต่ำสุด (Minimum Amount)
```bash
curl -X POST http://localhost:8000/api/wallet/convert-to-points \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"amount": 10}'
```
**ผลลัพธ์ที่คาดหวัง (Expected Result):**
- ✅ Success: แปลง 10 บาท → 10,800 แต้ม
- ✅ ยอดเงินลด 10 บาท
- ✅ แต้มเพิ่มขึ้น 10,800 แต้ม

### Test Case 2: แปลงจำนวนปานกลาง (Medium Amount)
```bash
curl -X POST http://localhost:8000/api/wallet/convert-to-points \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"amount": 100}'
```
**ผลลัพธ์ที่คาดหวัง (Expected Result):**
- ✅ Success: แปลง 100 บาท → 108,000 แต้ม
- ✅ ยอดเงินลด 100 บาท
- ✅ แต้มเพิ่มขึ้น 108,000 แต้ม

### Test Case 3: แปลงจำนวนเกินยอด (Exceed Balance)
```bash
curl -X POST http://localhost:8000/api/wallet/convert-to-points \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"amount": 999999}'
```
**ผลลัพธ์ที่คาดหวัง (Expected Result):**
- ❌ Error: "ยอดเงินของคุณไม่เพียงพอ"
- ✅ ไม่มีการเปลี่ยนยอดเงินหรือแต้ม

### Test Case 4: แปลงจำนวนติดลบ (Negative Amount)
```bash
curl -X POST http://localhost:8000/api/wallet/convert-to-points \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"amount": -10}'
```
**ผลลัพธ์ที่คาดหวัง (Expected Result):**
- ❌ Error: Validation error (amount must be >= 10)

### Test Case 5: แปลงจำนวนเป็น 0 (Zero Amount)
```bash
curl -X POST http://localhost:8000/api/wallet/convert-to-points \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"amount": 0}'
```
**ผลลัพธ์ที่คาดหวัง (Expected Result):**
- ❌ Error: Validation error (amount must be >= 10)

## ตรวจสอบธุรกรรม (Transaction Verification)

หลังจากการทดสอบ คุณสามารถตรวจสอบธุรกรรมได้:

### 1. ตรวจสอบในหน้า Wallet
- ไปที่หน้า [`/earn/wallet`](/earn/wallet)
- เลือกแท็บ "ประวัติ" (History)
- ดูรายการล่าสุด

### 2. ตรวจสอบในหน้า Points
- ไปที่หน้า [`/earn/points`](/earn/points)
- ดูประวัติแต้มล่าสุด
- ตรวจสอบว่ามีรายการ "แปลงจากเงิน" (conversion from wallet)

### 3. ตรวจสอบใน Database (ถ้าจำเป็นต้อง)
```sql
-- ตรวจสอบ wallet transactions
SELECT * FROM wallet_transactions 
WHERE user_id = YOUR_USER_ID 
AND transaction_type = 'conversion' 
AND metadata->>'$.conversion_type' = 'money_to_points'
ORDER BY created_at DESC 
LIMIT 10;

-- ตรวจสอบ points transactions
SELECT * FROM points_transactions 
WHERE user_id = YOUR_USER_ID 
AND transaction_type = 'conversion' 
AND source_type = 'wallet_to_points'
ORDER BY created_at DESC 
LIMIT 10;
```

## ข้อจำกัด (Important Notes)

### 1. ข้อจำกัดเกี่ยวกับอัตราแลกเปลี่ยน
- อัตราแลกเปลี่ยนสำหรับการแปลงเงินเป็นแต้ม (1:1080) แตกต่างจากการแปลงแต้มเป็นเงิน (1200:1)
- เหตุผลเพราะ:
  - การแปลงเงินเป็นแต้มมีค่าธรรมเนียม (สำหรับการสนับสนุนโฆษณา)
  - การแปลงแต้มเป็นเงินมีค่าธรรมต่ำกว่า (สำหรับการถอนเงิน)

### 2. การใช้งานจริง
- การแปลงเงินเป็นแต้มควรใช้สำหรับการสนับสนุนโฆษณาเท่านั้น
- ห้ามใช้เพื่อหลบเลี่ยนอัตราแลกเปลี่ยน

### 3. การตรวจสอบ
- หลังจากการทดสอบ ควรตรวจสอบว่า:
  - ยอดเงินถูกหักถูกต้อง
  - แต้มถูกเพิ่มถูกต้อง
  - ประวัติถูกบันทึกถูกต้อง
  - อัตราแลกเปลี่ยนถูกต้อง

## การแก้ไขปัญหา (Troubleshooting)

### ปัญหา: แปลงไม่สำเร็จ
**สาเหตุที่เป็นไปได้:**
- Token หมดอายุ
- ยอดเงินไม่เพียงพอ
- Server error

**วิธีแก้ไข:**
1. ตรวจสอบ token ว่ายังใช้งานได้
2. เติมเงินเข้ากระเป๋าเงินก่อน
3. ลองอีกครั้ง

### ปัญหา: ประวัติไม่แสดง
**สาเหตุที่เป็นไปได้:**
- Cache ของ browser
- ไม่ได้ refresh ข้อมูลหลังจากการแปลง

**วิธีแก้ไข:**
1. Refresh หน้า (F5 หรือ Ctrl+R)
2. Clear cache ของ browser
3. ตรวจสอบ Network tab ใน Developer Tools

### ปัญหา: อัตราแลกเปลี่ยนไม่ถูกต้อง
**สาเหตุที่เป็นไปได้:**
- Bug ในระบบ
- Exchange rate ไม่ได้ถูกอัปเดต

**วิธีแก้ไข:**
1. ตรวจสอบ log ของ server
2. ตรวจสอบ code ใน [`WalletService.php`](api/nuxnanravel/app/Services/WalletService.php)
3. รายงาน bug ให้ทีมพัฒนา

## สรุป (Summary)

ระบบแปลงเงินเป็นแต้มได้ถูกสร้างเสร็จแล้วและพร้อมสำหรับการทดสอบ:

✅ **Backend:**
- [`WalletService::convertWalletToPoints()`](api/nuxnanravel/app/Services/WalletService.php) - บริการแปลงเงินเป็นแต้ม
- [`WalletController::convertToPoints()`](api/nuxnanravel/app/Http/Controllers/Api/WalletController.php) - API endpoint
- Route: `POST /api/wallet/convert-to-points`

✅ **Frontend:**
- [`Wallet.vue`](ui/pages/Earn/Wallet.vue) - หน้ากระเป๋าเงินพร้อมแท็บ "แปลงเงินเป็นแต้ม"
- [`useWallet::convertToPoints()`](ui/composables/useWallet.ts) - Composable สำหรับการแปลง
- [`authStore`](ui/stores/auth.ts) - รองรับฟังก์ชันจัดการ wallet

✅ **อัตราแลกเปลี่ยน:**
- 1 บาท = 1,080 แต้ม (สำหรับการสนับสนุนโฆษณา)

คุณสามารถเริ่มทดสอบได้ทันที!
