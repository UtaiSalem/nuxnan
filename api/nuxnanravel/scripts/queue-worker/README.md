# nuxnan queue worker เป็น Windows service (NSSM)

โปรเจคนี้ใช้ `QUEUE_CONNECTION=database` และมีหลายฟีเจอร์โยนงานเข้าคิว —
แต้ม/gamification (`ProcessUsageEvent`, `UpdateActivitySummary`), นำเข้านักเรียน
(`ValidateStudentImportBatchJob`, `ProcessStudentImportBatchJob`), โคลนคอร์สหลังซื้อ
(`CloneCourseJob`) และ job ตามเวลาใน `routes/console.php`

**ถ้าไม่มี worker รัน ฟีเจอร์พวกนี้จะค้างเงียบ ๆ ไม่ error ไม่ล้ม แค่ไม่เกิดอะไรขึ้น**
เคยค้างสะสมมาแล้ว **16,404 งาน ตั้งแต่ 2026-05-25 ถึง 2026-09-02** เพราะไม่มีใครรัน worker เลย

## ติดตั้ง

1. ติดตั้ง NSSM ก่อน (ครั้งเดียว)

   ```powershell
   winget install NSSM.NSSM
   ```

   หรือโหลดจาก <https://nssm.cc/download> แล้ววาง `nssm.exe` ไว้ในโฟลเดอร์ที่อยู่ใน `PATH`

2. เปิด **PowerShell แบบ Run as Administrator** แล้วรัน

   ```powershell
   cd C:\wamp64\www\nuxnan\api\nuxnanravel\scripts\queue-worker
   .\install-service.ps1
   ```

   ถ้าโดนบล็อกเรื่อง execution policy ให้รันแบบนี้แทน

   ```powershell
   powershell -ExecutionPolicy Bypass -File .\install-service.ps1
   ```

## ถอน

```powershell
.\uninstall-service.ps1
```

## ค่าที่ตั้งไว้และเหตุผล

| ค่า | ตั้งเป็น | ทำไม |
|---|---|---|
| `--queue=default` | บังคับเสมอ | ห้ามรัน `queue:work` เปล่า ๆ — ดู "คิว backlog" ข้างล่าง |
| `--tries=3` | ลอง 3 ครั้ง | ล้มครบแล้วเข้า `failed_jobs` ให้ตามดูย้อนหลังได้ |
| `--timeout=60` | 60 วิ/job | **ต้องน้อยกว่า `retry_after` = 90** ไม่งั้น Laravel จะปล่อย job กลับเข้าคิวทั้งที่ยังทำอยู่ ⇒ ทำงานซ้ำซ้อน |
| `--max-time=3600` | จบตัวเองทุก 1 ชม. | กัน memory รั่วสะสม และทำให้โค้ดใหม่ถูกโหลดอย่างน้อยชั่วโมงละครั้ง |
| `-d memory_limit=512M` | 512M | `php.ini` ของ CLI เครื่องนี้ตั้งไว้แค่ **128M** ซึ่งน้อยไปสำหรับงานนำเข้านักเรียน |
| `XDEBUG_MODE=off` | ปิด Xdebug | CLI ของเครื่องนี้โหลด Xdebug ไว้ ซึ่งทำให้ worker ที่รันยาวช้าลงมาก |
| `Start = DELAYED_AUTO` | หน่วงตอนบูต | ให้ WAMP/MySQL ขึ้นก่อน |
| `AppExit Default Restart` | เปิดใหม่เสมอ | ครอบทั้งกรณีล้มและกรณีจบเองตาม `--max-time` |
| `AppRestartDelay 5000` + `AppThrottle 10000` | หน่วง 5 วิ | กันวนรีสตาร์ตรัวถ้า DB ยังไม่ขึ้น |
| `AppStopMethodConsole` | รอ `timeout`+30 วิ | ส่ง Ctrl+C ให้ `queue:work` ทำงานที่ค้างอยู่จบก่อน (ต้องมากกว่า `--timeout`) |
| `AppRotate*` | หมุนที่ 10 MB | log ไม่บวมจนเต็มดิสก์ |

> **เรื่อง dependency กับ MySQL:** WAMP ตั้ง `wampmysqld64` / `wampmariadb64` เป็น **Manual**
> (ตัว tray ของ WAMP เป็นคนสั่งเปิด) ถ้าไปผูก `DependOnService` ตรง ๆ service นี้จะไม่ยอมสตาร์ตตอนบูต
> สคริปต์จึงพึ่ง `AppExit Restart` แทน — ถ้า DB ยังไม่ขึ้น worker จะตายแล้วถูกเปิดใหม่จนกว่าจะต่อได้
> บรรทัด `DependOnService` คอมเมนต์ไว้ในสคริปต์แล้ว เปิดใช้ได้ถ้าเปลี่ยน WAMP เป็น Automatic

## 🔴 คิว `backlog` — ห้ามรัน `queue:work` เปล่า ๆ

เคยมี job ค้างสะสม 16,404 งานถูกพักไว้บนคิวชื่อ `backlog`
(migration `2026_09_02_120000_park_legacy_jobs_on_backlog_queue`) แล้วทิ้งไปในภายหลัง
(`2026_09_03_100000_discard_gamification_backlog`) — ตอนนี้คิวนั้นว่างแล้ว

ถึงอย่างนั้นก็ยังควรระบุ `--queue=default` เสมอ เพื่อไม่ให้ worker เผลอไประบายคิวชื่ออื่น
ที่อาจถูกพักไว้ในอนาคตด้วยเหตุผลเดียวกัน

## ⚠️ แก้โค้ด backend แล้วต้องรีสตาร์ต worker

`queue:work` **แคชโค้ดไว้ตอนบูต** — ถ้าไม่รีสตาร์ตจะไล่บั๊กผีที่โค้ดใหม่แล้วแต่ worker ยังรันของเก่า

```powershell
php artisan queue:restart        # worker จบตัวเองรอบถัดไป แล้ว service เปิดใหม่ให้
Restart-Service nuxnan-queue     # หรือสั่งตรง ๆ
```

## ตรวจสถานะ

```powershell
Get-Service nuxnan-queue
Get-Content C:\wamp64\www\nuxnan\api\nuxnanravel\storage\logs\queue-worker.out.log -Tail 20
```

ทดสอบว่ากินงานจริง (รันใน `api/nuxnanravel/`) — งานนี้คำนวณสรุปรายวันใหม่จากข้อมูลต้นทาง
เป็น idempotent ไม่แจกแต้ม/XP ให้ใคร:

```powershell
php artisan tinker --execute="\App\Jobs\UpdateActivitySummary::dispatch(\App\Models\User::first(), now()->toDateString());"
```

แล้วดูท้าย log ว่ามีบรรทัด `DONE`

## Scheduler ยังต้องแยกอีกตัว

`routes/console.php` มี `RefreshLeaderboardCache` (03:00) และ `ResetDailyQuests` (00:00)
ซึ่ง **โยนเข้าคิว** ⇒ ต้องมีทั้ง scheduler และ queue worker ถึงจะทำงาน
ถ้าต้องการ ให้ทำ service อีกตัวแบบเดียวกันโดยเปลี่ยน `AppParameters` เป็น
`artisan schedule:work` (หรือใช้ Task Scheduler เรียก `artisan schedule:run` ทุกนาที)
