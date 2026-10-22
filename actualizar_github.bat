@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title MatiGuara - Actualizar GitHub

REM ============================================================
REM  Actualiza el repositorio de GitHub con los cambios locales.
REM  Colocar este archivo en la carpeta del proyecto
REM  (C:\xampp\htdocs\app-educativa) y hacer doble clic.
REM ============================================================

cd /d "%~dp0"

where git >nul 2>nul
if errorlevel 1 (
    echo [ERROR] Git no esta instalado o no esta en el PATH.
    echo Instalalo desde https://git-scm.com/download/win
    goto :fin
)

if not exist ".git" goto :inicializar
:continuar

for /f "delims=" %%b in ('git symbolic-ref --short HEAD') do set RAMA=%%b
echo ============================================================
echo  Proyecto : %cd%
echo  Rama     : %RAMA%
echo ============================================================
echo.

REM --- Seguridad: .env y vendor no deben estar versionados ---
git ls-files --error-unmatch .env >nul 2>nul
if not errorlevel 1 (
    echo [ATENCION] El archivo .env esta siendo versionado por Git.
    echo Contiene claves y contrasenas. Para dejar de subirlo ejecuta:
    echo     git rm --cached .env
    echo y verifica que ".env" figure en .gitignore.
    echo.
    choice /c SN /m "Continuar de todos modos"
    if errorlevel 2 goto :fin
)

REM --- Cambios pendientes ---
echo Cambios detectados:
git status --short
echo.

git status --porcelain > "%temp%\mg_status.txt"
for %%A in ("%temp%\mg_status.txt") do set TAM=%%~zA
if "!TAM!"=="0" (
    echo No hay cambios locales para subir.
    echo Se comprueba igualmente si hay commits pendientes de enviar...
    goto :push
)

REM --- Pruebas (opcional) ---
choice /c SN /m "Ejecutar las pruebas (php artisan test) antes de subir"
if not errorlevel 2 (
    echo.
    php artisan test
    if errorlevel 1 (
        echo.
        echo [ERROR] Hay pruebas que fallan. No se sube nada.
        goto :fin
    )
    echo.
    echo Pruebas OK.
    echo.
)

REM --- Mensaje del commit ---
set "MSG="
set /p MSG=Mensaje del commit (Enter = fecha y hora):
if "!MSG!"=="" set "MSG=Actualizacion %date% %time:~0,5%"

git add -A
git commit -m "!MSG!"
if errorlevel 1 (
    echo [ERROR] No se pudo crear el commit.
    goto :fin
)

:push
echo.
echo Trayendo cambios remotos, si los hay...
git pull --rebase origin %RAMA%
if errorlevel 1 (
    echo.
    echo [ERROR] Conflicto al sincronizar con GitHub.
    echo Resolvelo y ejecuta: git rebase --continue
    echo O cancela con:      git rebase --abort
    goto :fin
)

echo.
echo Subiendo a GitHub...
git push origin %RAMA%
if errorlevel 1 (
    echo.
    echo [ERROR] No se pudo subir. Causas comunes:
    echo  - Sin conexion a internet.
    echo  - Credenciales: usa un token personal de GitHub en lugar de la contrasena.
    goto :fin
)

echo.
echo ============================================================
echo  LISTO: el repositorio de GitHub quedo actualizado.
echo ============================================================
goto :fin

:inicializar
echo Esta carpeta todavia no esta conectada con GitHub: no tiene .git.
echo Carpeta actual: %cd%
echo.
if not exist "artisan" (
    echo [ERROR] No parece la carpeta del proyecto Laravel: falta el archivo artisan.
    echo Copia este archivo dentro de C:\xampp\htdocs\app-educativa
    goto :fin
)
if not exist ".gitignore" (
    echo [ATENCION] No hay archivo .gitignore: se subirian vendor, .env y otros.
    echo Crea el .gitignore antes de continuar.
    goto :fin
)
echo Pega la URL del repositorio de GitHub, por ejemplo:
echo     https://github.com/TU_USUARIO/TU_REPO.git
set "URL="
set /p URL=URL: 
if "!URL!"=="" (
    echo No se ingreso ninguna URL.
    goto :fin
)
git init
git remote add origin "!URL!"
echo.
echo Descargando el estado actual de GitHub...
git fetch origin
if errorlevel 1 (
    echo [ERROR] No se pudo conectar con GitHub. Revisa la URL y tus credenciales.
    rmdir /s /q .git
    goto :fin
)
set "RAMAREMOTA="
git rev-parse --verify origin/main >nul 2>nul
if not errorlevel 1 set RAMAREMOTA=main
if not defined RAMAREMOTA git rev-parse --verify origin/master >nul 2>nul
if not defined RAMAREMOTA if not errorlevel 1 set RAMAREMOTA=master
if not defined RAMAREMOTA (
    echo El repositorio remoto esta vacio: se creara la rama main.
    git branch -M main
    goto :continuar
)
git branch -M !RAMAREMOTA!
REM Apunta al historial remoto SIN tocar tus archivos locales:
REM los archivos distintos aparecen luego como cambios a subir.
git reset origin/!RAMAREMOTA!
echo.
echo Repositorio conectado, rama !RAMAREMOTA!. Se revisan las diferencias...
echo.
goto :continuar

:fin
echo.
pause
endlocal
