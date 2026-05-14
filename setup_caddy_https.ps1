# Setup Caddy + mkcert per HTTPS interno MES Grafica Nappa
# Esegui come Administrator sul server .60

$ErrorActionPreference = 'Stop'
$caddyDir = 'C:\caddy'
$mkcertDir = 'C:\mkcert'

# 1. Crea directory
New-Item -ItemType Directory -Force -Path $caddyDir | Out-Null
New-Item -ItemType Directory -Force -Path $mkcertDir | Out-Null

# 2. Download Caddy
Write-Host "Downloading Caddy..."
$caddyUrl = 'https://caddyserver.com/api/download?os=windows&arch=amd64'
Invoke-WebRequest -Uri $caddyUrl -OutFile "$caddyDir\caddy.exe"

# 3. Download mkcert
Write-Host "Downloading mkcert..."
$mkcertUrl = 'https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-windows-amd64.exe'
Invoke-WebRequest -Uri $mkcertUrl -OutFile "$mkcertDir\mkcert.exe"

# 4. Genera CA locale
Write-Host "Generating local CA..."
& "$mkcertDir\mkcert.exe" -install

# 5. Genera cert per 192.168.1.60 + mes.local
Write-Host "Generating cert for 192.168.1.60..."
Set-Location $caddyDir
& "$mkcertDir\mkcert.exe" 192.168.1.60 mes.local localhost

# 6. Crea Caddyfile reverse proxy
$caddyfile = @'
{
    auto_https off
}

192.168.1.60:443 {
    tls 192.168.1.60+2.pem 192.168.1.60+2-key.pem
    reverse_proxy 127.0.0.1:80 {
        header_up Host {host}
        header_up X-Real-IP {remote_host}
        header_up X-Forwarded-For {remote_host}
        header_up X-Forwarded-Proto https
    }
}

192.168.1.60:8443 {
    tls 192.168.1.60+2.pem 192.168.1.60+2-key.pem
    reverse_proxy 127.0.0.1:8080 {
        header_up Host {host}
        header_up X-Real-IP {remote_host}
        header_up X-Forwarded-For {remote_host}
        header_up X-Forwarded-Proto https
    }
}
'@
Set-Content -Path "$caddyDir\Caddyfile" -Value $caddyfile -Encoding utf8

Write-Host ""
Write-Host "Setup complete. Next:"
Write-Host "  1. Test: cd $caddyDir; .\caddy.exe run"
Write-Host "  2. Apri https://192.168.1.60 dal browser server (deve essere verde)"
Write-Host "  3. Esporta CA: copy $env:LOCALAPPDATA\mkcert\rootCA.pem su tablet/telefoni"
Write-Host "  4. Su Android: Impostazioni > Sicurezza > Installa certificato CA"
Write-Host "  5. Se OK, installa come servizio Windows con nssm"
