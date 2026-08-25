@echo off
setlocal
title Terminal Protocol Installer
color 0A
echo ============================================================
echo   Terminal Protocol Installer
echo   Registers crt:// and putty:// protocols
echo   so the web page can launch local terminal software
echo ============================================================
echo.

set "DIR=%~dp0"
if "%DIR:~-1%"=="\" set "DIR=%DIR:~0,-1%"
set "VBS=%DIR%\terminal_launcher.vbs"
set "INI=%DIR%\terminal_config.ini"

if not exist "%VBS%" (
    echo [ERROR] terminal_launcher.vbs not found.
    echo Please place install.bat and the .vbs file in the same folder.
    pause
    exit /b 1
)

:: 1. Create config if not exists
if not exist "%INI%" (
    echo [1/3] Creating terminal_config.ini ...
    > "%INI%" echo software=crt
    >> "%INI%" echo crt_path=C:\Program Files\VanDyke Software\SecureCRT\SecureCRT.exe
    >> "%INI%" echo putty_path=C:\tools\putty.exe
) else (
    echo [1/3] Config file already exists, skip creation
)

:: 2. Register crt://
echo [2/3] Registering crt:// protocol ...
reg add "HKCU\Software\Classes\crt" /ve /d "URL:SecureCRT Protocol" /f >nul
reg add "HKCU\Software\Classes\crt" /v "URL Protocol" /d "" /f >nul
reg add "HKCU\Software\Classes\crt\shell\open\command" /ve /d "wscript.exe \"%VBS%\" \"%%1\"" /f >nul

:: 3. Register putty://
echo [3/3] Registering putty:// protocol ...
reg add "HKCU\Software\Classes\putty" /ve /d "URL:PuTTY Protocol" /f >nul
reg add "HKCU\Software\Classes\putty" /v "URL Protocol" /d "" /f >nul
reg add "HKCU\Software\Classes\putty\shell\open\command" /ve /d "wscript.exe \"%VBS%\" \"%%1\"" /f >nul

echo.
echo ============================================================
echo   Installation complete!
echo.
echo   Next steps:
echo   1. Edit %INI% and set your local SecureCRT/PuTTY path
echo   2. Go to System Settings - Remote Terminal Settings
echo   3. Select software and enter the same path, then Save
echo ============================================================
echo.
pause
