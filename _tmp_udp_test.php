<?php
// 测试1: sockets 扩展是否可用
echo "sockets 扩展: " . (extension_loaded('sockets') ? '已加载' : '未加载') . "\n";

// 测试2: 用 sockets 扩展发送 SNMP GET 到模拟服务器 (127.0.0.1:11161)
// 复用正式文件的编码函数
function encLen($l) { if ($l < 0x80) return chr($l); $b=''; while($l>0){$b=chr($l&0xFF).$b;$l>>=8;} return chr(0x80|strlen($b)).$b; }
function encOid($oid) { $p=array_map('intval',explode('.',$oid)); $b=chr(40*$p[0]+$p[1]); for($i=2;$i<count($p);$i++){$v=$p[$i];$x=chr($v&0x7F);$v>>=7;while($v>0){$x=chr(($v&0x7F)|0x80).$x;$v>>=7;}$b.=$x;} return $b; }

$community = 'LAyy@123';
$oidBytes = encOid('1.3.6.1.2.1.1.1.0');
$oidTlv = chr(0x06) . encLen(strlen($oidBytes)) . $oidBytes;
$nullTlv = chr(0x05) . chr(0x00);
$vb = chr(0x30) . encLen(strlen($oidTlv)+strlen($nullTlv)) . $oidTlv . $nullTlv;
$vbs = chr(0x30) . encLen(strlen($vb)) . $vb;
$rid = mt_rand(1, 0x7FFFFFFF);
$pdu = chr(0xA0) . encLen(4+1+1+1+1+1+1+strlen($vbs)) . chr(0x02) . chr(4) . pack('N',$rid) . chr(0x02).chr(1).chr(0) . chr(0x02).chr(1).chr(0) . $vbs;
$msg = chr(0x30) . encLen(1+1+1+strlen($community)+1+strlen($community)+strlen($pdu)) . chr(0x02).chr(1).chr(0) . chr(0x04).chr(strlen($community)).$community . $pdu;

echo "请求长度: " . strlen($msg) . "\n";

// 方式A: socket 扩展
if (extension_loaded('sockets')) {
    $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 4, 'usec' => 0]);
    $start = microtime(true);
    $ok = @socket_sendto($sock, $msg, strlen($msg), 0, '127.0.0.1', 11161);
    echo "socket_sendto: " . var_export($ok, true) . "\n";
    $buf = '';
    $from = '';
    $port = 0;
    $recv = @socket_recvfrom($sock, $buf, 8192, 0, $from, $port);
    $elapsed = round(microtime(true) - $start, 3);
    echo "socket_recvfrom: " . var_export($recv, true) . " 耗时{$elapsed}s\n";
    if ($recv !== false && $recv > 0) {
        echo "收到响应: " . bin2hex($buf) . "\n";
        // 检查首字节是否为 SEQUENCE 且 pduType=0xA2
        $type = ord($buf[0]);
        echo "首字节类型: 0x" . dechex($type) . "\n";
    } else {
        echo "socket_recvfrom 失败: " . socket_strerror(socket_last_error($sock)) . "\n";
    }
    socket_close($sock);
} else {
    echo "sockets 扩展不可用，跳过方式A\n";
}

// 方式B: stream 方式（对比）
$sock2 = @stream_socket_client('udp://127.0.0.1:11161', $e1, $e2, 4);
stream_set_timeout($sock2, 4);
$start = microtime(true);
@fwrite($sock2, $msg);
$resp = '';
$readErr = '';
while (!feof($sock2)) {
    $chunk = @fread($sock2, 8192);
    if ($chunk === false) { $readErr = 'fread返回false'; break; }
    if ($chunk === '') { $readErr = 'fread返回空字符串'; break; }
    $resp .= $chunk;
}
$elapsed = round(microtime(true) - $start, 3);
echo "stream方式: 读取字节数=" . strlen($resp) . " 耗时{$elapsed}s " . ($readErr ? "($readErr)" : '') . "\n";
if ($resp !== '') {
    echo "stream收到响应: " . bin2hex($resp) . "\n";
}
fclose($sock2);
