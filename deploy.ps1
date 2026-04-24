# Deploy rapido: pull + clear cache + restart bot
# Uso (admin PS): .\deploy.ps1

Write-Host "=== DEPLOY START ===" -ForegroundColor Cyan

Write-Host "`n[1/4] git pull" -ForegroundColor Yellow
git pull origin def2.0

Write-Host "`n[2/4] config:clear" -ForegroundColor Yellow
php artisan config:clear

Write-Host "`n[3/4] restart Telegram bot" -ForegroundColor Yellow
$nssm = "C:\tools\nssm-2.24\win64\nssm.exe"
& $nssm restart GraficaTelegramBot

Write-Host "`n[4/4] status" -ForegroundColor Yellow
& $nssm status GraficaTelegramBot

Write-Host "`n=== DEPLOY DONE ===" -ForegroundColor Green
