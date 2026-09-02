<#
.SYNOPSIS
    ถอน Windows service ของ nuxnan queue worker ที่ติดตั้งไว้ด้วย NSSM

.NOTES
    ต้องรันด้วยสิทธิ์ Administrator
#>

[CmdletBinding()]
param(
    [string] $ServiceName = 'nuxnan-queue'
)

$ErrorActionPreference = 'Stop'

if (-not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()
        ).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'ต้องรันสคริปต์นี้ใน PowerShell ที่เปิดแบบ Run as Administrator'
}

$nssm = (Get-Command nssm -ErrorAction SilentlyContinue).Source
if (-not $nssm) { throw 'ไม่พบ nssm.exe ใน PATH' }

if (-not (Get-Service -Name $ServiceName -ErrorAction SilentlyContinue)) {
    Write-Host "ไม่มี service ชื่อ $ServiceName อยู่แล้ว — ไม่ต้องทำอะไร" -ForegroundColor Yellow
    return
}

Write-Host "หยุด service '$ServiceName' (รอให้งานที่ค้างอยู่จบก่อน) ..." -ForegroundColor Cyan
& $nssm stop $ServiceName | Out-Null

Write-Host "ถอน service ..." -ForegroundColor Cyan
& $nssm remove $ServiceName confirm | Out-Null

Write-Host "ถอนเรียบร้อย — ไฟล์ log ใน storage/logs ยังอยู่ ลบเองได้ถ้าไม่ต้องการ" -ForegroundColor Green
