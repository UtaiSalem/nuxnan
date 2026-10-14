# #23 รายได้ — แผนแตก shard ให้ agy (เคาะ Q1/Q2/Q3 แล้ว)

> agy รันบนเครื่อง WAMP เท่านั้น (เซสชันคลาวด์ไม่มี agy) — เปิดเซสชันบนเครื่องแล้วค่อยสั่ง
> อ้างอิง audit เต็ม: [`../23-revenue.md`](../23-revenue.md)

## วิธีรัน agy (ต่อ shard)
```
agy --print "Read <spec path เต็ม> and follow it exactly." \
  --add-dir "C:\wamp64\www\nuxnan" --model gemini-3.1-pro-high --mode accept-edits --print-timeout 20m
```
แล้ว Claude ตรวจเอง: `git diff --stat` + `git diff` + รันเกณฑ์ผ่านใน §5 ของแต่ละ spec ด้วยตัวเอง (ห้ามเชื่อรายงาน agy)

## Shard

| Shard | ขอบเขต | สเปค | สถานะ |
|---|---|---|---|
| **RV-S1** | Q1 แก้ authz backend (isAdmin → finance.view/manage · คง tenant) + AcademyRevenueAuthzTest | [`23-RV-S1-authz.spec.md`](23-RV-S1-authz.spec.md) | ✅ สเปคพร้อมรัน |
| RV-S2 | Q1 FE — useApi inject academyId + ยืนยัน tab/ปุ่มตรง authz ใหม่ (ส่วนใหญ่ FE ใช้ finance.* อยู่แล้ว เหลือ G4 inject) | _เขียนในเซสชันเครื่อง_ | ⏳ |
| RV-S3 | Q2 รวมการเงินจาก #8 — fee-structures/tuition-fees/payments/expenses/budgets เป็นแท็บใน revenue.vue (reuse component/composable เดิมของ #8) | _ต้องสแกน #8 ก่อน เขียนในเซสชันเครื่อง_ | ⏳ |
| RV-S4 | Q3 export (donations/revenue xlsx/csv) + filter (status/type/date) | _เขียนในเซสชันเครื่อง_ | ⏳ |
| RV-S5 | Q3 กราฟรายได้ (dataviz · mobile-first) บนแท็บภาพรวม | _เขียนในเซสชันเครื่อง_ | ⏳ |

## ทำไม RV-S2..S5 ยังไม่เขียนสเปค
- RV-S3 (Q2) ต้องสแกนโครงจริงของการเงิน #8 (fee/tuition/payments/expenses/budgets controller+component) ให้ครบก่อน จะได้สเปคที่ agy ทำตามได้แม่น — ทำในเซสชันบนเครื่อง (เข้าถึงไฟล์เดียวกัน + รัน agy + ตรวจผล + build ได้ครบในที่เดียว)
- RV-S1 เป็น shard ที่พิสูจน์ root cause ชัดและคุ้มสุด (ปลดล็อกให้ฝ่ายการเงินใช้งานได้) จึงเขียนให้พร้อมรันก่อน
