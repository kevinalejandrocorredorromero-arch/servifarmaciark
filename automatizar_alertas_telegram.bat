@echo off
setlocal
cd /d "%~dp0"

set "URL=http://localhost/servifarmacia%%20rk/notificar_telegram.php"
set "LOG=%~dp0logs\telegram_scheduler.log"

if not exist "%~dp0logs" mkdir "%~dp0logs"
echo [%date% %time%] Ejecutando alertas >> "%LOG%"
C:\Windows\System32\curl.exe -sS --max-time 45 "%URL%" >> "%LOG%" 2>&1
echo. >> "%LOG%"

endlocal
