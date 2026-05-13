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
$action = New-ScheduledTaskAction `
    -Execute 'C:\php-8.5.3\php.exe' `
    -Argument 'artisan schedule:run' `
    -WorkingDirectory 'C:\progetti\graficanappa-gestionale'

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date)
$trigger.Repetition = $(New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::FromDays(3650))).Repetition

$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 2) `
    -MultipleInstances IgnoreNew `
    -StartWhenAvailable `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries

$principal = New-ScheduledTaskPrincipal -UserId 'NT AUTHORITY\SYSTEM' -RunLevel Highest

Register-ScheduledTask -TaskName 'Laravel Scheduler' `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal | Out-Null

Write-Host "   Creato OK" -ForegroundColor Green

Write-Host "3. Stato task:" -ForegroundColor Cyan
Get-ScheduledTask -TaskName 'Laravel Scheduler' | Get-ScheduledTaskInfo

Write-Host "`nFatto. Aspetta 2 min poi verifica con: php check_contatori.php" -ForegroundColor Green
