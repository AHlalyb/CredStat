<?php
/**
 * 临时 UDP 模拟 SNMP 服务器（用于本地验证 SNMP 报文构造/解析）
 * 监听 127.0.0.1:11161，收到 GetRequest 后返回 GetResponse(sysDescr)
 */
error_reporting(E_ALL);

// 复用正式文件的 BER 编码/解析函数（require 前先保护主逻辑不执行）
define('SNMP_SIMULATOR', true);

$socket = @stream_socket_server('udp://127.0.0.1:11161', $errno, $errstr, STREAM_SERVER_BIND);
if (!$socket) {
    echo "无法监听: $errstr\n";
    exit(1);
}
echo "SNMP 模拟服务器已启动，监听 127.0.0.1:11161\n";

while (true) {
    $data = stream_socket_recvfrom($socket, 8192, 0, $peer);
    if ($data === false || $data === '') {
        continue;
    }
    echo "收到请求: " . bin2hex($data) . "\n";

    // 解析 version / community / request-id
    $offset = 0;
    list($t, $len) = snmpReadHeader($data, $offset);
    if ($t !== 0x30 || $len === null) { echo "报文格式错误(无SEQUENCE)\n"; continue; }
    list($t, $l) = snmpReadHeader($data, $offset); // version
    $version = snmpReadInteger($data, $offset, $l);
    list($t, $l) = snmpReadHeader($data, $offset); // community
    $community = substr($data, $offset, $l);
    $offset += $l;
    if ($offset >= strlen($data)) { echo "报文格式错误(无PDU)\n"; continue; }
    $pduType = ord($data[$offset]);
    list($t, $pduLen) = snmpReadHeader($data, $offset);
    list($t, $l) = snmpReadHeader($data, $offset); // request-id
    $requestId = snmpReadInteger($data, $offset, $l);

    echo "version=$version community='$community' pduType=0x" . dechex($pduType) . " requestId=$requestId\n";

    // 构造 GetResponse：
    // SEQUENCE { INTEGER version, OCTET community, PDU(0xA2){ INTEGER reqId, INTEGER 0, INTEGER 0, SEQUENCE{ SEQUENCE{ OID, OCTET "TestDevice-SNMP-Simulator" } } } }
    $oidBytes = snmpEncodeOid('1.3.6.1.2.1.1.1.0');
    $oidTlv = chr(0x06) . snmpEncodeLength(strlen($oidBytes)) . $oidBytes;
    $sysDescr = 'Huawei Simulator Router / SNMP v1 OK';
    $valTlv = chr(0x04) . snmpEncodeLength(strlen($sysDescr)) . $sysDescr;
    $varbind = chr(0x30) . snmpEncodeLength(strlen($oidTlv) + strlen($valTlv)) . $oidTlv . $valTlv;
    $varbinds = chr(0x30) . snmpEncodeLength(strlen($varbind)) . $varbind;
    $reqIdTlv = chr(0x02) . snmpEncodeLength(4) . pack('N', $requestId);
    $errTlv = chr(0x02) . chr(0x01) . chr(0x00);
    $pduContent = $reqIdTlv . $errTlv . $errTlv . $varbinds;
    $pdu = chr(0xA2) . snmpEncodeLength(strlen($pduContent)) . $pduContent;
    $verTlv = chr(0x02) . chr(0x01) . chr($version);
    $commTlv = chr(0x04) . snmpEncodeLength(strlen($community)) . $community;
    $msg = chr(0x30) . snmpEncodeLength(strlen($verTlv) + strlen($commTlv) + strlen($pdu)) . $verTlv . $commTlv . $pdu;

    stream_socket_sendto($socket, $msg, 0, $peer);
    echo "已发送响应: " . bin2hex($msg) . "\n";
}

function snmpEncodeLength($length) {
    if ($length < 0x80) return chr($length);
    $bytes = '';
    while ($length > 0) { $bytes = chr($length & 0xFF) . $bytes; $length >>= 8; }
    return chr(0x80 | strlen($bytes)) . $bytes;
}
function snmpEncodeOid($oid) {
    $parts = array_map('intval', explode('.', $oid));
    $bytes = chr(40 * $parts[0] + $parts[1]);
    for ($i = 2; $i < count($parts); $i++) $bytes .= snmpEncodeSubId($parts[$i]);
    return $bytes;
}
function snmpEncodeSubId($value) {
    $bytes = chr($value & 0x7F);
    $value >>= 7;
    while ($value > 0) { $bytes = chr(($value & 0x7F) | 0x80) . $bytes; $value >>= 7; }
    return $bytes;
}
function snmpReadHeader(&$data, &$offset) {
    if (!isset($data[$offset])) return [null, null];
    $type = ord($data[$offset]); $offset++;
    if (!isset($data[$offset])) return [null, null];
    $first = ord($data[$offset]); $offset++;
    if ($first < 0x80) return [$type, $first];
    $numBytes = $first & 0x7F; $length = 0;
    for ($i = 0; $i < $numBytes; $i++) {
        if (!isset($data[$offset])) return [null, null];
        $length = ($length << 8) | ord($data[$offset]); $offset++;
    }
    return [$type, $length];
}
function snmpReadInteger(&$data, &$offset, $length) {
    $raw = substr($data, $offset, $length); $offset += $length;
    $value = 0;
    for ($i = 0; $i < strlen($raw); $i++) $value = ($value << 8) | ord($raw[$i]);
    return $value;
}
