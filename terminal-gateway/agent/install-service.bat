@echo off
title CredStat Jump Agent Service Manager
setlocal

:: ============================================================
:: CredStat Jump Agent Service Manager
:: Right-click -> Run as administrator
:: ============================================================

set "SERVICE_NAME=CredStatAgent"

:: Use the 8.3 short path form (e.g. C:\AGENT~1\) so sc binPath always works
:: even if the folder name actually contains (invisible) spaces.
set "SCRIPT_DIR=%~sdp0"

:: Check admin privilege
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Please run as Administrator.
    pause
    exit /b 1
)

:: Check agent.exe exists
if not exist "%SCRIPT_DIR%agent.exe" (
    echo [ERROR] agent.exe not found in: %SCRIPT_DIR%
    echo         Please ensure this script is in the same folder as agent.exe
    pause
    exit /b 1
)

:: SCRIPT_DIR is the 8.3 short path (no spaces), so sc binPath always works.
:: Keep a defensive check in case short-path generation is disabled.
set "SCRIPT_DIR_CHECK=%SCRIPT_DIR: =%"
if not "%SCRIPT_DIR_CHECK%"=="%SCRIPT_DIR%" (
    echo [ERROR] The folder path contains spaces and short-path generation is disabled:
    echo         [%SCRIPT_DIR%]
    echo         Please move agent.exe and this script to a path WITHOUT spaces,
    echo         for example: C:\agent
    pause
    exit /b 1
)

:menu
cls
echo ============================================================
echo   CredStat Jump Agent Service Manager
echo ============================================================
echo   Agent Dir : %SCRIPT_DIR%
echo   Service   : %SERVICE_NAME%
echo ------------------------------------------------------------
echo   1. Install and Start Service (Recommended)
echo   2. Install Only (Do Not Start)
echo   3. Start Service
echo   4. Stop Service
echo   5. Uninstall Service
echo   6. View Service Status
echo   0. Exit
echo ------------------------------------------------------------
set /p choice=Enter option number:

if "%choice%"=="1" goto install_and_start
if "%choice%"=="2" goto install_only
if "%choice%"=="3" goto start_service
if "%choice%"=="4" goto stop_service
if "%choice%"=="5" goto uninstall
if "%choice%"=="6" goto status
if "%choice%"=="0" exit /b 0
echo Invalid input, please try again
timeout /t 2 >nul
goto menu

:: ------------------------------------------------------------
:install_and_start
call :install
if %errorlevel% neq 0 goto end
call :start
goto end

:install_only
call :install
goto end

:start_service
call :start
goto end

:stop_service
call :stop
goto end

:uninstall
call :stop >nul 2>&1
echo Uninstalling service %SERVICE_NAME% ...
sc delete %SERVICE_NAME%
if %errorlevel% equ 0 (
    echo [OK] Service uninstalled
) else (
    echo [INFO] Service may not exist, or stop it first
)
goto end

:status
sc query %SERVICE_NAME%
echo ------------------------------------------------------------
sc qc %SERVICE_NAME%
goto end

:: ------------------------------------------------------------
:install
echo ------------------------------------------------------------
echo Configure Agent Service Parameters
echo Leave blank to use default (press Enter)
echo ------------------------------------------------------------

:: Listen address
set "LISTEN=:19878"
set /p LISTEN_INPUT=Listen address (default %LISTEN%):
if not "%LISTEN_INPUT%"=="" set "LISTEN=%LISTEN_INPUT%"

:: Shared token
set "TOKEN="
set /p TOKEN_INPUT=Shared token (blank = no auth, strongly recommended):
if not "%TOKEN_INPUT%"=="" set "TOKEN=%TOKEN_INPUT%"

:: Build binPath value.
:: sc stores the whole string after "binPath= " verbatim.
:: The path C:\agent\agent.exe has no spaces, so no inner quotes are needed.
:: The whole value is wrapped in one pair of quotes so sc sees it as a single argument.
set "BIN_PATH=%SCRIPT_DIR%agent.exe --listen %LISTEN%"
if not "%TOKEN%"=="" set "BIN_PATH=%BIN_PATH% --token %TOKEN%"

:: Remove existing service if any
sc query %SERVICE_NAME% >nul 2>&1
if %errorlevel% equ 0 (
    echo [INFO] Service %SERVICE_NAME% exists, recreating...
    net stop %SERVICE_NAME% >nul 2>&1
    sc delete %SERVICE_NAME% >nul 2>&1
    timeout /t 1 >nul
)

echo ------------------------------------------------------------
echo Creating service %SERVICE_NAME% ...
echo   Binary: %BIN_PATH%
echo ------------------------------------------------------------

sc create %SERVICE_NAME% binPath= "%BIN_PATH%" start= auto DisplayName= "CredStat Jump Agent"
if %errorlevel% neq 0 (
    echo [ERROR] Failed to create service. Exit code: %errorlevel%
    exit /b 1
)

:: Set description via a separate command (sc create does NOT accept Description=)
sc description %SERVICE_NAME% "CredStat Jump Agent: TCP tunnel for gateway to target devices" >nul 2>&1

:: Auto-restart on failure (restart after 5s, up to 3 times)
sc failure %SERVICE_NAME% reset= 86400 actions= restart/5000/restart/5000/restart/5000 >nul 2>&1

echo [OK] Service created, start type: Auto (start on boot)
echo [INFO] Use option 3 or Services manager to start
exit /b 0

:: ------------------------------------------------------------
:start
echo Starting service %SERVICE_NAME% ...
net start %SERVICE_NAME%
if %errorlevel% equ 0 (
    echo [OK] Service started
) else (
    echo [ERROR] Failed to start. Check Windows Event Viewer for details.
)
exit /b 0

:stop
echo Stopping service %SERVICE_NAME% ...
net stop %SERVICE_NAME%
exit /b 0

:: ------------------------------------------------------------
:end
echo ------------------------------------------------------------
pause
goto menu
