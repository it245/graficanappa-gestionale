# Fix Laravel Scheduler Task Windows
# Ricreare il task con timeout + ignora duplicati hung
# Run come Amministratore su .60

$ErrorActionPreference = 'Stop'

Write-Host "1. Stop e rimuovi task esistenti..." -ForegroundColor Cyan
foreach ($name in @('Laravel Scheduler', 'LaravelScheduler')) {
    try {
        Stop-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
        Unregister-ScheduledTask -TaskName $name -Confirm:$false -ErrorAction SilentlyContinue
        Write-Host "   Rimosso: $name" -ForegroundColor Green
    } catch {
        Write-Host "   Skip $name (non esisteva)" -ForegroundColor Yellow
    }
}

Write-Host "2. Creo nuovo task 'Laravel Scheduler'..." -ForegroundColor Cyan

# Wrapper batch per redirigere output a log (cosi' vediamo errori)
$batchPath = 'C:\progetti\graficanappa-gestionale\laravel-scheduler.bat'
@'
@echo off
cd /d C:\progetti\graficanappa-gestionale
"C:\php-8.5.3\php.exe" artisan schedule:run >> "C:\progetti\graficanappa-gestionale\storage\logs\scheduler-task.log" 2>&1
'@ | Set-Content -Path $batchPath -Encoding ASCII

$action = New-ScheduledTaskAction -Execute $batchPath -WorkingDirectory 'C:\progetti\graficanappa-gestionale'

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date)
$trigger.Repetition = $(New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::FromDays(3650))).Repetition

$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 2) `
    -MultipleInstances IgnoreNew `
    -StartWhenAvailable `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries

# S4U = run only when user logged on, ma con priv elevati e no password
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask -TaskName 'Laravel Scheduler' `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal | Out-Null

Write-Host "   Creato OK" -ForegroundColor Green

Write-Host "3. Stato task:" -ForegroundColor Cyan
Get-ScheduledTask -TaskName 'Laravel Scheduler' | Get-ScheduledTaskInfo

Write-Host "`nFatto. Aspetta 2 min poi verifica con: php check_contatori.php" -ForegroundColor Green
