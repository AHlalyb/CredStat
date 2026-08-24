<?php
// 在 php -S 环境下执行与 API 完全相同的 UDP stream 读取逻辑
header('Content-Type: application/json; charset=UTF-8');

function encLen($l) { if ($l < 0x80) return chr($l); $b=''; while($l>0){$b=chr($l&0xFF).$b;$l>>=8;} return chr(0x80|strlen($b)).$b; }
function encOid($oid) { $p=array_map('intval',explode('.',$oid)); $b=chr(40*$p[0]+$p[1]); for($i=2;$i<count($p);$i++){$v=$p[$i];$x=chr($v&0x7F);$v>>=7;while($v>0){$x=chr(($v&0x7F)|0x80).$x;$v>>=7;}$b.=$x;} return $b; }

$community = 'LAyy@123';
$oidBytes = encOid('1.3.6.1.2.1.1.1.0');
$oidTlv = chr(0x06) . encLen(strlen($oidBytes)) . $oidBytes;
$nullTlv = chr(0x05) . chr(0x00);
$vb = chr(0x30) . encLen(strlen($oidTlv)+strlen($nullTlv)) . $oidTlv . $nullTlv;
$vbs = chr(0x30) . encLen(strlen($vb)) . $vb;
$rid = mt_rand(1, 0x7FFFFFFF);
$pduContent = chr(0x02).chr(4).pack('N',$rid) . chr(0x02).chr(1).chr(0) . chr(0x02).chr(1).chr(0) . $vbs;
$pdu = chr(0xA0) . encLen(strlen($pduContent)) . $pduContent;
$msg = chr(0x30) . encLen(3+strlen($community)+2+strlen($pduContent)) . chr(0x02).chr(1).chr(0) . chr(0x04).chr(strlen($community)).$community . $pdu;

$start = microtime(true);
$socket = @stream_socket_client('udp://127.0.0.1:11161', $errno, $errstr, 3);
if (!$socket) {
    echo json_encode(['ok' => false, 'error' => $errstr]);
    exit;
}
stream_set_timeout($socket, 3);
@fwrite($socket, $msg);
$response = '';
$log = [];
while (!feof($socket)) {
    $chunk = @fread($socket, 8192);
    if ($chunk === false) { $log[] = 'fread=false'; break; }
    if ($chunk === '') { $log[] = 'fread=empty'; break; }
    $log[] = 'fread=' . strlen($chunk) . 'B';
    $response .= $chunk;
}
fclose($socket);
$elapsed = round(microtime(true) - $start, 3);
echo json_encode([
    'ok' => true,
    'elapsed' => $elapsed,
    'log' => $log,
    'recv_len' => strlen($response),
    'recv_hex' => $response !== '' ? bin2hex($response) : null
]);
