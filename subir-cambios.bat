@echo off
setlocal
cd /d "%~dp0"

echo ============================================================
echo    SERVIFARMACIA RK - Subir cambios a GitHub
echo ============================================================
echo.

REM ---- 1. config/config.php DEBE estar ignorado por Git ----
git check-ignore -q config/config.php
if errorlevel 1 (
    echo [ALERTA] config/config.php NO esta ignorado por Git.
    echo          Tus credenciales reales podrian subir a GitHub.
    echo          Revisa el archivo .gitignore antes de continuar.
    echo.
    echo Se cancelo la subida. No se envio nada.
    pause
    exit /b 1
)

REM ---- 2. Preparar los cambios ----
git add -A

REM ---- 3. Doble chequeo: config/config.php no puede estar en la lista ----
git diff --cached --name-only | findstr /i /x "config/config.php" >nul
if not errorlevel 1 (
    echo [ALERTA] config/config.php aparece entre los archivos por subir.
    echo          Se cancelo la subida. No se envio nada.
    git reset -q
    pause
    exit /b 1
)

REM ---- 4. Buscar credenciales reales en lo que se va a subir ----
git diff --cached -U0 | findstr /c:"GOCSPX-" /c:"hf_" >nul
if not errorlevel 1 (
    echo [ALERTA] Se encontro algo que parece una credencial real
    echo          en los archivos por subir. Revisa antes de continuar.
    echo          Se cancelo la subida. No se envio nada.
    git reset -q
    pause
    exit /b 1
)

REM ---- 5. Hay algo que subir? ----
git diff --cached --quiet
if not errorlevel 1 (
    echo No hay cambios. Todo esta al dia con GitHub.
    echo.
    pause
    exit /b 0
)

echo Estos son los archivos que van a subir:
echo ------------------------------------------------------------
git diff --cached --name-status
echo ------------------------------------------------------------
echo.
echo Escribe el mensaje del commit. No uses comillas dobles.
echo Ejemplo: agrego validacion de stock en el carrito
echo.
set /p MSG="Mensaje: "
if "%MSG%"=="" (
    echo No escribiste mensaje. Se cancelo la subida.
    git reset -q
    pause
    exit /b 1
)

REM ---- 6. Commit ----
git commit -m "%MSG%"
if errorlevel 1 (
    echo.
    echo El commit fallo. Revisa el mensaje de arriba.
    echo Los cambios siguen guardados localmente, no se perdio nada.
    pause
    exit /b 1
)

REM ---- 7. Push ----
echo.
echo Enviando a GitHub...
git push
if errorlevel 1 (
    echo.
    echo El push fallo. El commit quedo guardado en tu carpeta.
    echo Vuelve a ejecutar este script para reintentar el envio.
    pause
    exit /b 1
)

echo.
echo ============================================================
echo    LISTO. Los cambios ya estan en GitHub.
echo ============================================================
echo.
pause
endlocal
