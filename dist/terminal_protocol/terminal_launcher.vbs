'============================================================
' terminal_launcher.vbs
' 远程终端协议助手：解析 crt:// / putty:// URL 并在本机启动终端软件
'
' URL 格式：
'   crt://ssh|IP|端口|用户名      （SecureCRT）
'   putty://telnet|IP|端口        （PuTTY）
'
' 配置：与脚本同目录的 terminal_config.ini
'============================================================
Option Explicit

Dim fso, shell
Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

' --- 1. 拼接命令行参数（浏览器可能分多段传递） ---
Dim rawUrl, i
rawUrl = ""
For i = 0 To WScript.Arguments.Count - 1
    rawUrl = rawUrl & WScript.Arguments(i)
Next
If rawUrl = "" Then
    MsgBox "未收到远程连接参数，请从系统页面点击"远程"使用。", vbExclamation, "远程终端助手"
    WScript.Quit 1
End If

' --- 2. 解析协议名与连接参数 ---
Dim scheme, body, parts, protocol, ip, port, username
If InStr(rawUrl, "://") > 0 Then
    scheme = LCase(Left(rawUrl, InStr(rawUrl, "://") - 1))
    body = Mid(rawUrl, InStr(rawUrl, "://") + 3)
Else
    scheme = "crt"
    body = rawUrl
End If
' 去掉多余的前导斜杠
Do While Left(body, 1) = "/"
    body = Mid(body, 2)
Loop
body = URLDecode(body)

parts = Split(body, "|")
protocol = "ssh"
ip = ""
port = ""
username = ""
If UBound(parts) >= 0 Then protocol = LCase(Trim(parts(0)))
If UBound(parts) >= 1 Then ip = Trim(parts(1))
If UBound(parts) >= 2 Then port = Trim(parts(2))
If UBound(parts) >= 3 Then username = Trim(parts(3))

If ip = "" Then
    MsgBox "缺少设备IP地址。", vbExclamation, "远程终端助手"
    WScript.Quit 1
End If
If protocol <> "ssh" And protocol <> "telnet" Then protocol = "ssh"

' --- 3. 读取本机配置文件（terminal_config.ini） ---
Dim dir, iniFile, software, crtPath, puttyPath, exePath
dir = fso.GetParentFolderName(WScript.ScriptFullName)
iniFile = dir & "\terminal_config.ini"

software = "crt"
crtPath = ""
puttyPath = ""
If fso.FileExists(iniFile) Then
    Dim ts, line
    Set ts = fso.OpenTextFile(iniFile, 1, False, 0)   ' 0 = 按 ANSI(GBK) 读取
    Do While Not ts.AtEndOfStream
        line = Trim(ts.ReadLine)
        If InStr(1, line, "=", 1) > 0 Then
            Dim key, val
            key = LCase(Trim(Left(line, InStr(line, "=") - 1)))
            val = Trim(Mid(line, InStr(line, "=") + 1))
            If key = "software" Then software = LCase(val)
            If key = "crt_path" Then crtPath = val
            If key = "putty_path" Then puttyPath = val
        End If
    Loop
    ts.Close
End If

If scheme = "putty" Then
    exePath = puttyPath
Else
    exePath = crtPath
End If

If exePath = "" Or (Not fso.FileExists(exePath)) Then
    MsgBox "未找到终端软件：" & exePath & vbCrLf & vbCrLf & _
           "请编辑配置文件：" & iniFile & vbCrLf & _
           "填写本机 SecureCRT / PuTTY 的完整路径后重试。", _
           vbExclamation, "远程终端助手"
    WScript.Quit 1
End If

' --- 4. 构造启动参数（与 SecureCRT / PuTTY 命令行一致） ---
Dim args
If scheme = "putty" Then
    If protocol = "ssh" Then
        args = "-ssh"
        If port <> "" Then args = args & " -P """ & port & """"
        If username <> "" Then args = args & " -l """ & username & """"
        args = args & " """ & ip & """"
    Else
        args = "-telnet"
        If port <> "" Then args = args & " -P """ & port & """"
        args = args & " """ & ip & """"
    End If
Else
    If protocol = "ssh" Then
        args = "/T /SSH2"
        If username <> "" Then args = args & " /L """ & username & """"
        If port <> "" Then args = args & " /P """ & port & """"
        args = args & " """ & ip & """"
    Else
        args = "/T /TELNET """ & ip & """"
        If port <> "" Then args = args & " """ & port & """"
    End If
End If

' --- 5. 启动终端软件 ---
shell.Run """" & exePath & """ " & args, 1, False
WScript.Quit 0

'============================================================
' URL 解码函数：%20 -> 空格、+ -> 空格
'============================================================
Function URLDecode(s)
    Dim r, idx, ch
    r = ""
    idx = 1
    Do While idx <= Len(s)
        ch = Mid(s, idx, 1)
        If ch = "%" And idx + 2 <= Len(s) Then
            r = r & Chr(CInt("&H" & Mid(s, idx + 1, 2)))
            idx = idx + 3
        ElseIf ch = "+" Then
            r = r & " "
            idx = idx + 1
        Else
            r = r & ch
            idx = idx + 1
        End If
    Loop
    URLDecode = r
End Function
