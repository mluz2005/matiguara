@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion
rem ============================================================
rem  MatiGuara - subir los cambios a GitHub  (version 2)
rem  Ponelo DENTRO de la carpeta del proyecto (app-educativa) y
rem  hacele doble clic.
rem  - Si la carpeta YA es un repositorio Git: sube los cambios.
rem  - Si NO lo es: la conecta a tu repositorio de GitHub SIN borrar
rem    ni cambiar tus archivos, y despues sube los cambios.
rem  No usa force push: no borra nada del historial en GitHub.
rem ============================================================
cd /d "%~dp0"

where git >nul 2>nul
if errorlevel 1 (
  echo [ERROR] Git no esta instalado o no esta en el PATH.
  echo Instalalo desde https://git-scm.com/download/win y volve a ejecutar.
  goto fin
)

if not exist ".git" (
  echo.
  echo Esta carpeta todavia NO esta conectada a GitHub.
  echo Vamos a conectarla ^(tus archivos no se tocan^).
  echo Necesito la direccion de tu repositorio, por ejemplo:
  echo    https://github.com/TU_USUARIO/TU_REPOSITORIO.git
  echo.
  set "URL="
  set /p URL=Direccion del repositorio: 
  if "!URL!"=="" (
    echo Cancelado: no escribiste ninguna direccion.
    goto fin
  )
  set "RAMA="
  set /p RAMA=Rama principal ^(Enter = main^): 
  if "!RAMA!"=="" set "RAMA=main"

  git init
  git remote add origin "!URL!"
  git fetch origin
  if errorlevel 1 (
    echo [ERROR] No se pudo leer el repositorio. Revisa la direccion y tu usuario de GitHub.
    rmdir /s /q ".git"
    goto fin
  )
  git symbolic-ref HEAD refs/heads/!RAMA!
  rem Toma el historial de GitHub sin tocar tus archivos locales ^(si la rama existe^)
  git reset origin/!RAMA! >nul 2>nul
  git branch --set-upstream-to=origin/!RAMA! !RAMA! >nul 2>nul
  echo.
  echo Conectado. A continuacion se muestran las diferencias con lo que hay en GitHub.
)

echo.
echo === Rama y repositorio remoto ===
git branch --show-current
git remote -v

rem --- Seguridad: .env y vendor NUNCA deben subirse ---
git check-ignore -q .env
if errorlevel 1 (
  echo [ALTO] .env NO esta ignorado por Git. Se cancela para no subir claves.
  goto fin
)
git check-ignore -q vendor
if errorlevel 1 (
  echo [ALTO] vendor NO esta ignorado por Git. Se cancela.
  goto fin
)

echo.
echo === Cambios pendientes ===
echo   M = modificado   ^?^? = nuevo   D = BORRADO del repositorio
git status --short
echo.
set "HAYCAMBIOS="
for /f "delims=" %%i in ('git status --porcelain') do set "HAYCAMBIOS=1"
if not defined HAYCAMBIOS (
  echo No hay cambios para subir.
  goto fin
)

set "BORRADOS=0"
for /f "delims=" %%i in ('git status --porcelain ^| findstr /b /r /c:" D" /c:"D "') do set /a BORRADOS+=1
if not "!BORRADOS!"=="0" (
  echo [ATENCION] Hay !BORRADOS! archivo^(s^) marcados con D: existen en GitHub pero NO en tu carpeta.
  echo Si seguis, se borraran de GitHub ^(el historial los conserva^).
  set "OKD="
  set /p OKD=Queres continuar igual? ^(S/N^): 
  if /i not "!OKD!"=="S" (
    echo Cancelado. No se subio nada.
    goto fin
  )
)

set "MSG="
set /p MSG=Mensaje del commit (Enter = mensaje por defecto): 
if "!MSG!"=="" set "MSG=Actualizacion MatiGuara: Laravel 13, El camino a la escuela, limite de tiempo y musica"

echo.
set "OK="
set /p OK=Subir estos cambios a GitHub? ^(S/N^): 
if /i not "!OK!"=="S" (
  echo Cancelado. No se subio nada.
  goto fin
)

git add -A
git commit -m "!MSG!"
if errorlevel 1 (
  echo [ERROR] No se pudo crear el commit. Si dice "Please tell me who you are", ejecuta:
  echo    git config --global user.name "Tu Nombre"
  echo    git config --global user.email "tu_correo@ejemplo.com"
  goto fin
)
git push -u origin HEAD
if errorlevel 1 (
  echo.
  echo [ERROR] El push fallo. Si dice que hay cambios remotos, ejecuta "git pull --rebase" y volve a intentar.
  goto fin
)
echo.
echo Listo: cambios subidos a GitHub.

:fin
echo.
pause
endlocal