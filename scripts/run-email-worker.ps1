param(
    [string]$Queue = "emails"
)

$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot
Set-Location ..

Write-Host "Starting queue worker for queue: $Queue" -ForegroundColor Cyan

while ($true) {
    try {
        php artisan queue:work --queue=$Queue --sleep=1 --tries=3 --timeout=90
    }
    catch {
        Write-Host "Queue worker stopped unexpectedly. Restarting in 5 seconds..." -ForegroundColor Yellow
        Start-Sleep -Seconds 5
    }
}
