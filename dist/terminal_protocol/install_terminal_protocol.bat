@echo off
setlocal
title 远程终端协议助手 - 一键安装
color 0A
echo ============================================================
echo   远程终端协议助手 一键安装
echo.
echo   作用：注册 crt:// 与 putty:// 协议，
echo         让网页上的"远程"按钮可直接调起
echo         本机 SecureCRT / PuTTY 连接设备
echo ============================================================
echo.

set "DIR=%~dp0"
if "%DIR:~-1%"=="\" set "DIR=%DIR:~0,-1%"
set "VBS=%DIR%\terminal_launcher.vbs"
set "INI=%DIR%\terminal_config.ini"

:: 检查脚本是否存在
if not exist "%VBS%" (
    echo [错误] 未找到 terminal_launcher.vbs，请将脚本与本安装包放在同一目录！
    pause
    exit /b 1
)

:: 1. 生成配置文件（如不存在）
if not exist "%INI%" (
    echo [1/3] 生成配置文件 terminal_config.ini ...
    > "%INI%" echo software=crt
    >> "%INI%" echo crt_path=C:\Program Files\VanDyke Software\SecureCRT\SecureCRT.exe
    >> "%INI%" echo putty_path=C:\tools\putty.exe
) else (
    echo [1/3] 配置文件已存在，跳过生成
)

:: 2. 注册 crt:// 协议
echo [2/3] 注册 crt:// 协议 ...
reg add "HKCU\Software\Classes\crt" /ve /d "URL:SecureCRT Protocol" /f >nul
reg add "HKCU\Software\Classes\crt" /v "URL Protocol" /d "" /f >nul
reg add "HKCU\Software\Classes\crt\shell\open\command" /ve /d "wscript.exe \"%VBS%\" \"%%1\"" /f >nul

:: 3. 注册 putty:// 协议
echo [3/3] 注册 putty:// 协议 ...
reg add "HKCU\Software\Classes\putty" /ve /d "URL:PuTTY Protocol" /f >nul
reg add "HKCU\Software\Classes\putty" /v "URL Protocol" /d "" /f >nul
reg add "HKCU\Software\Classes\putty\shell\open\command" /ve /d "wscript.exe \"%VBS%\" \"%%1\"" /f >nul

echo.
echo ============================================================
echo   安装成功！
echo.
echo   下一步：
echo   1. 编辑配置文件  %INI%
echo      将 crt_path / putty_path 修改为本机软件实际路径
echo   2. 到系统「远程终端设置」页面，选择软件并填写
echo      相同路径后点"保存设置"（保存到本机浏览器）
echo   3. 回到信息查询页，点击"远程"即可调起终端软件
echo ============================================================
echo.
pause
