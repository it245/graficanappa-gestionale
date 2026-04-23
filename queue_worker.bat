@echo off
cd /d C:\progetti\graficanappa-gestionale
C:\php-8.5.3\php.exe artisan queue:work --stop-when-empty --tries=2 --timeout=90 --queue=default
