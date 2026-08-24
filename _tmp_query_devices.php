<?php
$dbConfig = require __DIR__ . '/app/config/database.php';
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
echo "=== credstat_user 结构 ===\n";
foreach ($pdo->query("SHOW COLUMNS FROM credstat_user")->fetchAll() as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "\n=== credstat_user 数据(仅关键字段) ===\n";
foreach ($pdo->query("SELECT * FROM credstat_user")->fetchAll() as $row) {
    $out = [];
    foreach ($row as $k => $v) {
        $out[$k] = strlen((string)$v) > 60 ? substr((string)$v, 0, 60) . '...' : $v;
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE) . "\n";
}
