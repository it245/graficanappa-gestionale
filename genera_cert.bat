@echo off
cd /d C:\Apache24\bin

REM Genera config file con SAN per cert
(
echo [req]
echo distinguished_name = req_distinguished_name
echo x509_extensions = v3_req
echo prompt = no
echo.
echo [req_distinguished_name]
echo CN = gestionale
echo.
echo [v3_req]
echo keyUsage = critical, digitalSignature, keyEncipherment
echo extendedKeyUsage = serverAuth
echo subjectAltName = @alt_names
echo.
echo [alt_names]
echo DNS.1 = gestionale
echo DNS.2 = gestionale.local
echo DNS.3 = localhost
echo IP.1 = 192.168.1.60
echo IP.2 = 127.0.0.1
) > C:\Apache24\conf\openssl_san.cnf

REM Genera cert con SAN
openssl.exe req -x509 -nodes -days 3650 -newkey rsa:2048 ^
  -keyout C:\Apache24\conf\gestionale.key ^
  -out C:\Apache24\conf\gestionale.crt ^
  -config C:\Apache24\conf\openssl_san.cnf ^
  -extensions v3_req

echo.
echo === FILES CREATI ===
dir C:\Apache24\conf\gestionale.*

echo.
echo === VERIFICA SAN ===
openssl.exe x509 -in C:\Apache24\conf\gestionale.crt -noout -text | findstr "DNS IP Subject"

pause
