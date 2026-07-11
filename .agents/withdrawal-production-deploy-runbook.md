# Production Deploy Runbook — Withdrawal/Wallet Hardening (PR #3–#8)

> รันบน **Plesk server** ผ่าน SSH terminal (ไม่ใช่เครื่อง dev)
> Path: `/var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel`
> ⚠️ งานนี้แตะ **schema เงิน + เขียน ledger เงินจริง** — ทำช่วง low-traffic + ใน maintenance mode + มี backup ก่อนเสมอ
> ทุกขั้นที่เขียน ledger มี **dry-run ให้ดูก่อน** — อย่าข้าม gate ✅

ตั้งตัวแปร path + อ่าน DB creds จาก `.env` ของ server (ไม่ hardcode — ตรงกับ production เสมอ):
```bash
API=/var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel
UI=/var/www/vhosts/nuxnan.com/httpdocs/ui
cd "$API"

# ดึงชื่อ DB / user จาก .env (password จะถูกถามตอนรัน mysqldump/mysql ด้วย -p)
DB_NAME=$(sed -n 's/^DB_DATABASE=//p'  "$API/.env" | tr -d '"' | head -1)
DB_USER=$(sed -n 's/^DB_USERNAME=//p' "$API/.env" | tr -d '"' | head -1)
echo "DB_NAME=$DB_NAME  DB_USER=$DB_USER"
# คาดว่า (production):  DB_NAME=nuxnan_nuxnan_db   DB_USER=nuxnan_nuxnan_admin
```
✅ ตรวจว่า `DB_NAME`/`DB_USER` ตรงกับ production ก่อนไปต่อ

---

## 0) ตรวจ prerequisites
```bash
php -m | grep -i bcmath || echo "!!! bcmath MISSING — เปิดใน Plesk PHP Settings ก่อน หยุด"
php artisan --version
git rev-parse --abbrev-ref HEAD    # ควรเป็น main
```
✅ **Gate 0:** bcmath ต้องมี (โค้ดใหม่พึ่ง bcadd/bcsub/bccomp)

## 1) Backup ฐานข้อมูล (ห้ามข้าม)
```bash
BK=/var/www/vhosts/nuxnan.com/backups/db
mkdir -p "$BK"
# ใช้ $DB_USER/$DB_NAME จาก setup ด้านบน (จะถาม password ด้วย -p)
mysqldump -u "$DB_USER" -p "$DB_NAME" | gzip > "$BK/pre_wallet_deploy_$(date +%Y%m%d_%H%M%S).sql.gz"
ls -lh "$BK" | tail -3
```
✅ **Gate 1:** ไฟล์ backup มีขนาด > 0

## 2) เข้า maintenance mode (กัน write ระหว่างแปลง schema + baseline)
```bash
php artisan down --render="errors::503" --retry=120
```

## 3) Pull โค้ดใหม่ (main มี PR #3–#8)
- ถ้าใช้ **Plesk Git extension**: กด **Pull Updates** บน `api.nuxnan.com` (branch `main`)
- หรือผ่าน SSH ถ้า clone เอง:
```bash
cd "$API/../.."   # ไปที่ git root (httpdocs)
git fetch origin && git reset --hard origin/main
cd "$API"
```

## 4) Dependencies + autoload (สำคัญ: โหลด BcMathHelper)
```bash
composer install --optimize-autoloader --no-dev
php -r 'require "vendor/autoload.php"; echo function_exists("bcround")?"bcround OK\n":"bcround MISSING\n";'
```
✅ **Gate 4:** ต้องขึ้น `bcround OK` (autoload.files ถูกโหลด)

## 5) รัน migration (000001–000005)
```bash
php artisan migrate --force
```
- 000001 withdrawal fields, 000002 status enum (9), 000003 opening_balance type,
  000004 locked_balance (+backfill), **000005 แปลง `users.wallet` double→decimal(15,2)** + fee/net_amount → (15,2)
- ⚠️ 000005 ALTER TABLE users lock ตารางชั่วคราว — ปกติเร็ว (users ~ไม่กี่พันแถว)

## 6) รีเฟรช cache
```bash
php artisan config:clear && php artisan route:clear && php artisan view:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 7) ตรวจสถานะ ledger (คาดว่ายัง "ไม่ healthy" ก่อน baseline)
```bash
php artisan wallet:reconcile
```
- คาดว่า `Wallets == ledger: MISMATCH` + mismatched > 0 (เพราะ wallet prod ยังไม่มี ledger กำกับ เหมือน dev ก่อน baseline)
- **ต้องเห็น `Negative balances: 0`** — ถ้าติดลบ = มีปัญหา หยุดตรวจก่อน

## 8) DRY RUN งานเขียน ledger (ดูก่อน — ยังไม่เขียนอะไร)
```bash
php artisan wallet:flag-legacy-withdrawals          # ดูรายการ returned เก่าที่ไม่มี refund
php artisan wallet:baseline                          # ดูจำนวน user ที่ต้อง baseline + ยอดรวม
```
✅ **Gate 8:** ทบทวนตัวเลข — จำนวน user, ยอด positive/negative diff สมเหตุผล
> ตัวเลข positive diff = wallet เก่าที่ไม่มี ledger (ปกติ), negative diff ควรเป็น 0 หรือน้อยมาก ถ้าเยอะผิดปกติ **หยุด** สอบก่อน

## 9) COMMIT งานเขียน ledger (หลังทบทวน dry-run แล้วเท่านั้น)
```bash
php artisan wallet:flag-legacy-withdrawals --commit
php artisan wallet:baseline --commit --force
```

## 10) ยืนยัน HEALTHY
```bash
php artisan wallet:reconcile
```
✅ **Gate 10:** ต้องขึ้น **`Ledger is HEALTHY`** + `Money out ≤ money in: OK` + `Wallets == ledger: OK` + `Mismatched: 0` + `Negative: 0`
> ถ้าไม่ healthy → **อย่าออกจาก maintenance** เก็บ output แล้วตรวจ (ดู §rollback)

## 11) ออกจาก maintenance
```bash
php artisan up
```

## 12) Frontend (Nuxt) — มี useWallet/useAdminWallet เปลี่ยน
```bash
cd "$UI"
npm install && npm run build
# แล้ว Restart Node.js application ใน Plesk (Websites & Domains → Node.js → Restart)
```

## 13) ตั้ง/ยืนยัน Cron (สำหรับ daily reconcile 03:30)
Plesk → **Tools & Settings → Scheduled Tasks** ต้องมี:
```
* * * * *   cd /var/www/vhosts/nuxnan.com/httpdocs/api/nuxnanravel && php artisan schedule:run >> /dev/null 2>&1
```
ยืนยัน:
```bash
cd "$API" && php artisan schedule:list | grep wallet:reconcile
```

---

## Rollback (ถ้า Gate ใด fail)
- **ยังไม่ commit baseline (ก่อน §9):** แค่ `php artisan up` — schema เปลี่ยนแต่ไม่กระทบเงิน (locked_balance backfill + double→decimal เป็น non-lossy); ตรวจ mismatch แล้วค่อยลองใหม่
- **หลัง commit baseline แต่ไม่ healthy:** baseline สร้าง `opening_balance` rows (reference_number='OPENING_BALANCE') — ลบได้:
  ```sql
  DELETE FROM wallet_transactions WHERE reference_number='OPENING_BALANCE';
  UPDATE wallet_transactions SET metadata=JSON_REMOVE(metadata,'$.legacy_no_refund_ledger')
    WHERE JSON_EXTRACT(metadata,'$.legacy_no_refund_ledger') IS NOT NULL;
  ```
- **พังหนัก:** restore จาก backup §1 (ใช้ `$DB_USER`/`$DB_NAME` จาก setup; ถ้าเปิด shell ใหม่ derive ซ้ำจาก §setup):
  ```bash
  gunzip < "$BK/pre_wallet_deploy_"*.sql.gz | mysql -u "$DB_USER" -p "$DB_NAME"
  ```
- migration ย้อน: `php artisan migrate:rollback --step=5` (000005 down คืน wallet เป็น double — แต่ค่าเป็น decimal สะอาดแล้ว ปลอดภัยกว่าเดิม)

## หมายเหตุ
- baseline + flag เป็น **idempotent** — รันซ้ำไม่ทำให้เงินเกิน (skip user ที่ baseline แล้ว)
- หลัง deploy ควร monitor `storage/logs/wallet-reconcile.log` (daily job) 2–3 วันก่อนเปิดถอนเงินจริงเต็มรูปแบบ
- **ก่อนเปิดให้ผู้ใช้ถอนเงินจริง** ต้องมั่นใจว่า reconcile HEALTHY ต่อเนื่อง
