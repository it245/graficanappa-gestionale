@echo off
cd /d C:\Apache24\bin
openssl.exe req -x509 -nodes -days 3650 -newkey rsa:2048 -keyout C:\Apache24\conf\gestionale.key -out C:\Apache24\conf\gestionale.crt -subj "/CN=gestionale"
echo.
echo === FILES CREATI ===
dir C:\Apache24\conf\gestionale.*
pause
