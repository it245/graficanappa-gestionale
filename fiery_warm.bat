@echo off
cd /d C:\progetti\graficanappa-gestionale
php artisan fiery:warm >> storage\logs\fiery_warm.log 2>&1
