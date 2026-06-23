param(
    [string]$EmailQueue = "emails"
)

$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot
Set-Location ..

$workerArgs = @(
    "artisan",
    "queue:work",
    "--queue=$EmailQueue",
    "--sleep=1",
    "--tries=3",
    "--timeout=90"
)

$schedulerArgs = @(
    "artisan",
    "schedule:work"
)

Write-Host "Starting queue worker..." -ForegroundColor Cyan
Start-Process -FilePath "php" -ArgumentList $workerArgs -WindowStyle Hidden

Write-Host "Starting scheduler..." -ForegroundColor Cyan
Start-Process -FilePath "php" -ArgumentList $schedulerArgs -WindowStyle Hidden

Write-Host "Automation services started. Keep this window open if needed." -ForegroundColor Green
