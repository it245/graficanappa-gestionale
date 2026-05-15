# Backup DB grafica_nappa
# Usa credenziali da .env
# Output: backup_grafica_nappa_YYYYMMDD_HHmmss.sql.gz

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "C:\backups\db"
$backupFile = "$backupDir\backup_grafica_nappa_$timestamp.sql"

# Crea cartella se manca
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    Write-Output "Cartella creata: $backupDir"
}

# Trova mysqldump
$mysqldumpPaths = @(
    "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe",
    "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqldump.exe",
    "C:\Program Files\MySQL\MySQL Server 9.0\bin\mysqldump.exe",
    "C:\xampp\mysql\bin\mysqldump.exe",
    "C:\wamp64\bin\mysql\mysql8.0.31\bin\mysqldump.exe"
)
$mysqldump = $null
foreach ($p in $mysqldumpPaths) {
    if (Test-Path $p) { $mysqldump = $p; break }
}
if (-not $mysqldump) {
    $mysqldump = (Get-Command mysqldump -ErrorAction SilentlyContinue).Source
}
if (-not $mysqldump) {
    Write-Error "mysqldump non trovato. Cerca manualmente:"
    Write-Output "  Get-ChildItem 'C:\Program Files' -Recurse -Filter mysqldump.exe -ErrorAction SilentlyContinue"
    exit 1
}
Write-Output "mysqldump: $mysqldump"

# Credenziali dal .env
$envPath = "C:\progetti\graficanappa-gestionale\.env"
$envContent = Get-Content $envPath
$dbHost = ($envContent | Where-Object { $_ -match "^DB_HOST=" }) -replace "^DB_HOST=", ""
$dbPort = ($envContent | Where-Object { $_ -match "^DB_PORT=" }) -replace "^DB_PORT=", ""
$dbName = ($envContent | Where-Object { $_ -match "^DB_DATABASE=" }) -replace "^DB_DATABASE=", ""
$dbUser = ($envContent | Where-Object { $_ -match "^DB_USERNAME=" }) -replace "^DB_USERNAME=", ""
$dbPass = ($envContent | Where-Object { $_ -match "^DB_PASSWORD=" }) -replace "^DB_PASSWORD=", ""

Write-Output "Backup DB: $dbName su ${dbHost}:${dbPort}"
Write-Output "Output: $backupFile"

# Esegui dump
$args = @(
    "--host=$dbHost",
    "--port=$dbPort",
    "--user=$dbUser",
    "--password=$dbPass",
    "--single-transaction",
    "--quick",
    "--lock-tables=false",
    "--routines",
    "--triggers",
    "--events",
    "--add-drop-table",
    "--result-file=$backupFile",
    $dbName
)

$start = Get-Date
& $mysqldump @args
$elapsed = (Get-Date) - $start

if (Test-Path $backupFile) {
    $sizeMB = [math]::Round((Get-Item $backupFile).Length / 1MB, 2)
    Write-Output "Dump completato: $sizeMB MB in $($elapsed.TotalSeconds)s"

    # Comprimi con .NET (no gzip nativo Windows)
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zipFile = "$backupFile.zip"
    if (Test-Path $zipFile) { Remove-Item $zipFile -Force }
    Compress-Archive -Path $backupFile -DestinationPath $zipFile -CompressionLevel Optimal
    Remove-Item $backupFile -Force
    $zipSizeMB = [math]::Round((Get-Item $zipFile).Length / 1MB, 2)
    Write-Output "Compresso: $zipFile ($zipSizeMB MB)"

    # Mostra ultimi 5 backup
    Write-Output "`nUltimi 5 backup in $backupDir :"
    Get-ChildItem $backupDir -Filter "backup_grafica_nappa_*.zip" |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 5 |
        ForEach-Object {
            $sz = [math]::Round($_.Length / 1MB, 2)
            Write-Output "  $($_.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss'))  ${sz}MB  $($_.Name)"
        }
} else {
    Write-Error "Backup fallito: file non creato"
    exit 1
}
