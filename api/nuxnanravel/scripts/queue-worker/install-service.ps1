<#
.SYNOPSIS
    ติดตั้ง Laravel queue worker ของ nuxnan เป็น Windows service ด้วย NSSM

.DESCRIPTION
    โปรเจคนี้ใช้ QUEUE_CONNECTION=database และมีหลายฟีเจอร์โยนงานเข้าคิว
    (แต้ม/gamification, นำเข้านักเรียน, โคลนคอร์สหลังซื้อ)
    ถ้าไม่มี worker รัน ฟีเจอร์พวกนี้จะค้างเงียบ ๆ ไม่ error ไม่ล้ม แค่ไม่เกิดอะไรขึ้น
    (เคยค้างสะสมมาแล้ว 16,404 งาน ตั้งแต่ 2026-05-25 ถึง 2026-09-02)

.NOTES
    ต้องรันด้วยสิทธิ์ Administrator
    ต้องมี nssm.exe อยู่ใน PATH ก่อน  ->  winget install NSSM.NSSM
                                          หรือโหลดจาก https://nssm.cc/download
                                          แล้ววาง nssm.exe ไว้ในโฟลเดอร์ที่อยู่ใน PATH

    ถอนการติดตั้ง: .\uninstall-service.ps1
#>

[CmdletBinding()]
param(
    [string] $ServiceName = 'nuxnan-queue',
    [string] $PhpExe      = 'C:\wamp64\bin\php\php8.4.15\php.exe',
    [string] $AppRoot     = 'C:\wamp64\www\nuxnan\api\nuxnanravel',
    [string] $Queue       = 'default',
    [int]    $Tries       = 3,
    [int]    $JobTimeout  = 60,      # วินาที ต่อ 1 job — ต้องน้อยกว่า retry_after (90) เสมอ
    [int]    $MaxTime     = 3600,    # worker จบตัวเองทุก 1 ชม. แล้ว NSSM เปิดใหม่
    [string] $MemoryLimit = '512M'
)

$ErrorActionPreference = 'Stop'

# ---------------------------------------------------------------- ตรวจก่อนเริ่ม

if (-not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()
        ).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'ต้องรันสคริปต์นี้ใน PowerShell ที่เปิดแบบ Run as Administrator'
}

$nssm = (Get-Command nssm -ErrorAction SilentlyContinue).Source
if (-not $nssm) {
    throw 'ไม่พบ nssm.exe ใน PATH — ติดตั้งก่อนด้วย `winget install NSSM.NSSM` หรือโหลดจาก https://nssm.cc/download'
}

if (-not (Test-Path $PhpExe))               { throw "ไม่พบ php.exe ที่ $PhpExe" }
if (-not (Test-Path "$AppRoot\artisan"))    { throw "ไม่พบ artisan ที่ $AppRoot" }

if (Get-Service -Name $ServiceName -ErrorAction SilentlyContinue) {
    throw "มี service ชื่อ $ServiceName อยู่แล้ว — ถอนก่อนด้วย .\uninstall-service.ps1"
}

$logDir = Join-Path $AppRoot 'storage\logs'
if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Path $logDir -Force | Out-Null }

$stdout = Join-Path $logDir 'queue-worker.out.log'
$stderr = Join-Path $logDir 'queue-worker.err.log'

# queue.connections.database.retry_after = 90 (ค่าจาก config/queue.php)
# Laravel จะถือว่า job ที่ค้างเกิน retry_after เป็นงานตายแล้วปล่อยกลับเข้าคิว
# ถ้า --timeout มากกว่าหรือเท่ากับ retry_after จะเกิดการทำงานซ้ำซ้อน job เดียวกัน 2 ตัวพร้อมกัน
$retryAfter = 90
if ($JobTimeout -ge $retryAfter) {
    throw "--timeout ($JobTimeout) ต้องน้อยกว่า retry_after ($retryAfter) ของ config/queue.php " +
          "ไม่งั้น job เดียวกันจะถูกหยิบไปทำซ้ำ — ลด -JobTimeout ลง หรือขึ้น DB_QUEUE_RETRY_AFTER ใน .env ก่อน"
}

# php.ini ของ CLI เครื่องนี้ตั้ง memory_limit=128M ซึ่งน้อยไปสำหรับ worker
# และเปิด Xdebug ไว้ ซึ่งทำให้ worker ที่รันยาวช้าลงมาก -> ปิดผ่าน env XDEBUG_MODE=off
$phpArgs = @(
    "-d", "memory_limit=$MemoryLimit"
    "artisan", "queue:work"
    "--queue=$Queue"
    "--tries=$Tries"
    "--timeout=$JobTimeout"
    "--max-time=$MaxTime"
    "--sleep=3"
) -join ' '

# ---------------------------------------------------------------- ติดตั้ง

Write-Host "ติดตั้ง service '$ServiceName' ..." -ForegroundColor Cyan

& $nssm install $ServiceName $PhpExe | Out-Null

& $nssm set $ServiceName AppDirectory   $AppRoot            | Out-Null
& $nssm set $ServiceName AppParameters  $phpArgs            | Out-Null
& $nssm set $ServiceName DisplayName    'nuxnan Laravel queue worker' | Out-Null
& $nssm set $ServiceName Description    'ประมวลผลคิวงานของ nuxnan (gamification, นำเข้านักเรียน, โคลนคอร์ส) — คิว default เท่านั้น' | Out-Null

# เริ่มอัตโนมัติแบบหน่วงเวลา เพื่อให้ WAMP/MySQL ขึ้นก่อน
& $nssm set $ServiceName Start          SERVICE_DELAYED_AUTO_START | Out-Null

# WAMP ตั้ง service ของตัวเองเป็น Manual ผูก dependency ตรง ๆ จะทำให้ service นี้ไม่ยอมสตาร์ต
# จึงพึ่งการ restart ของ NSSM แทน: ถ้า DB ยังไม่ขึ้น worker จะตายแล้วถูกเปิดใหม่จนกว่าจะต่อได้
# ถ้าอยากผูกจริง ให้เปิดบรรทัดนี้แล้วเปลี่ยน wampmysqld64 เป็น service DB ที่ใช้จริง
# & $nssm set $ServiceName DependOnService wampmysqld64 | Out-Null

# ปิด Xdebug และบอก Laravel ว่าไม่ใช่โหมด interactive
& $nssm set $ServiceName AppEnvironmentExtra 'XDEBUG_MODE=off' | Out-Null

# ล้มแล้วเปิดใหม่เสมอ (รวมกรณีจบตัวเองตาม --max-time ซึ่งถือว่าปกติ)
& $nssm set $ServiceName AppExit         Default Restart | Out-Null
& $nssm set $ServiceName AppRestartDelay 5000            | Out-Null
& $nssm set $ServiceName AppThrottle     10000           | Out-Null

# หยุดแบบสุภาพ: ส่ง Ctrl+C ให้ queue:work ทำงานที่ค้างอยู่ให้จบก่อน
# ต้องรอนานกว่า --timeout ของ job ไม่งั้นงานที่กำลังทำจะถูกฆ่ากลางคัน
$stopWaitMs = ($JobTimeout + 30) * 1000
& $nssm set $ServiceName AppStopMethodConsole $stopWaitMs | Out-Null
& $nssm set $ServiceName AppStopMethodWindow  0           | Out-Null
& $nssm set $ServiceName AppStopMethodThreads 0           | Out-Null

# log + หมุนไฟล์ที่ 10 MB
& $nssm set $ServiceName AppStdout           $stdout   | Out-Null
& $nssm set $ServiceName AppStderr           $stderr   | Out-Null
& $nssm set $ServiceName AppRotateFiles      1         | Out-Null
& $nssm set $ServiceName AppRotateOnline     1         | Out-Null
& $nssm set $ServiceName AppRotateBytes      10485760  | Out-Null

Write-Host 'เริ่ม service ...' -ForegroundColor Cyan
Start-Service -Name $ServiceName

Start-Sleep -Seconds 3
$svc = Get-Service -Name $ServiceName

Write-Host ''
Write-Host "service : $ServiceName" -ForegroundColor Green
Write-Host "สถานะ   : $($svc.Status)" -ForegroundColor Green
Write-Host "คำสั่ง   : $PhpExe $phpArgs"
Write-Host "log     : $stdout"
Write-Host ''
Write-Host 'ทดสอบว่ากินงานจริง (รันใน ' -NoNewline; Write-Host $AppRoot -NoNewline; Write-Host '):'
Write-Host '  php artisan tinker --execute="\App\Jobs\UpdateActivitySummary::dispatch(\App\Models\User::first(), now()->toDateString());"'
Write-Host '  แล้วดูท้ายไฟล์ log ว่ามีบรรทัด DONE'
Write-Host ''
Write-Host 'สำคัญ: queue:work แคชโค้ดไว้ตอนบูต แก้โค้ด backend แล้วต้องสั่ง' -ForegroundColor Yellow
Write-Host '  php artisan queue:restart      (worker จะจบตัวเองแล้ว service เปิดใหม่ให้)' -ForegroundColor Yellow
Write-Host '  หรือ  Restart-Service ' -NoNewline -ForegroundColor Yellow; Write-Host $ServiceName -ForegroundColor Yellow
