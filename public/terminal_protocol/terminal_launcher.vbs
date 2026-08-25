'============================================================
' terminal_launcher.vbs
' Protocol handler for crt:// and putty://
' URL: crt://ssh|IP|PORT|USER  or  putty://telnet|IP|PORT
' Config: terminal_config.ini (same folder, UTF-8 or ANSI)
'============================================================
Option Explicit
Dim fso, shell
Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

' --- 1. Parse arguments ---
Dim rawUrl, i
rawUrl = ""
For i = 0 To WScript.Arguments.Count - 1
    rawUrl = rawUrl & WScript.Arguments(i)
Next
If rawUrl = "" Then
    MsgBox "No remote parameters received. Please click Remote on the web page.", vbExclamation, "Terminal Helper"
    WScript.Quit 1
End If

' --- 2. Parse URL ---
Dim scheme, body, parts, protocol, ip, port, username
If InStr(rawUrl, "://") > 0 Then
    scheme = LCase(Left(rawUrl, InStr(rawUrl, "://") - 1))
    body = Mid(rawUrl, InStr(rawUrl, "://") + 3)
Else
    scheme = "crt"
    body = rawUrl
End If
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
    MsgBox "Missing device IP address.", vbExclamation, "Terminal Helper"
    WScript.Quit 1
End If
If protocol <> "ssh" And protocol <> "telnet" Then protocol = "ssh"

' --- 3. Read config (UTF-8 preferred, ANSI fallback) ---
Dim dir, iniFile, software, crtPath, puttyPath, exePath
dir = fso.GetParentFolderName(WScript.ScriptFullName)
iniFile = dir & "\terminal_config.ini"

software = "crt"
crtPath = ""
puttyPath = ""
If fso.FileExists(iniFile) Then
    Dim content
    content = ReadFileUtf8(iniFile)
    ParseIni content, software, crtPath, puttyPath
    If crtPath <> "" And (Not fso.FileExists(crtPath)) Then
        Dim ts
        Set ts = fso.OpenTextFile(iniFile, 1, False, 0)
        content = ts.ReadAll
        ts.Close
        ParseIni content, software, crtPath, puttyPath
    End If
End If

If scheme = "putty" Then
    exePath = puttyPath
Else
    exePath = crtPath
End If

If exePath = "" Or (Not fso.FileExists(exePath)) Then
    MsgBox "Terminal software not found: " & exePath & vbCrLf & vbCrLf & _
           "Please edit the config file: " & iniFile & vbCrLf & _
           "and set the correct crt_path or putty_path.", _
           vbExclamation, "Terminal Helper"
    WScript.Quit 1
End If

' --- 4. Build arguments ---
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

' --- 5. Launch ---
shell.Run """" & exePath & """ " & args, 1, False
WScript.Quit 0

'============================================================
' Functions
'============================================================
Function ReadFileUtf8(filePath)
    Dim stream, text
    Set stream = CreateObject("ADODB.Stream")
    stream.Type = 2
    stream.Charset = "utf-8"
    stream.Open
    stream.LoadFromFile filePath
    text = stream.ReadText
    stream.Close
    Set stream = Nothing
    ReadFileUtf8 = text
End Function

Sub ParseIni(content, sw, cp, pp)
    Dim lines, ln, pos, k, v
    content = Replace(content, vbCrLf, vbLf)
    lines = Split(content, vbLf)
    For Each ln In lines
        ln = Trim(ln)
        pos = InStr(ln, "=")
        If pos > 0 Then
            k = LCase(Trim(Left(ln, pos - 1)))
            v = Trim(Mid(ln, pos + 1))
            If k = "software" Then sw = LCase(v)
            If k = "crt_path" Then cp = v
            If k = "putty_path" Then pp = v
        End If
    Next
End Sub

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
