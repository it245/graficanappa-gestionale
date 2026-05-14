# Install bot come Windows service via nssm. Esegui come Administrator.
$botPath = 'C:\progetti\gestionale-v2\telegram-bot-mes-query'
$python = "$botPath\.venv\Scripts\python.exe"
$script = "$botPath\bot.py"

# Verifica nssm
$nssm = (Get-Command nssm -ErrorAction SilentlyContinue)?.Source
if (-not $nssm) {
    Write-Host "nssm non trovato. Scarica da https://nssm.cc/download e metti in PATH, oppure path manuale."
    Write-Host "Esempio: C:\nssm\nssm.exe"
    exit 1
}

# Rimuovi servizio esistente se presente
nssm stop MesQueryBot confirm 2>$null
nssm remove MesQueryBot confirm 2>$null

# Installa
nssm install MesQueryBot $python $script
nssm set MesQueryBot AppDirectory $botPath
nssm set MesQueryBot AppStdout "$botPath\logs\stdout.log"
nssm set MesQueryBot AppStderr "$botPath\logs\stderr.log"
nssm set MesQueryBot AppRotateFiles 1
nssm set MesQueryBot AppRotateBytes 10485760
nssm set MesQueryBot Start SERVICE_AUTO_START
nssm set MesQueryBot DisplayName "MES Query Bot Telegram"
nssm set MesQueryBot Description "Bot Telegram interroga MES Grafica Nappa"

# Crea cartella log
New-Item -ItemType Directory -Force -Path "$botPath\logs" | Out-Null

# Start
nssm start MesQueryBot
Get-Service MesQueryBot
Write-Host "Service MesQueryBot installato. Gira sempre, anche dopo reboot."
