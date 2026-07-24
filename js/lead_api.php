<?php
// ============================================================================
// lead_api.php — 官网留资接口 v1.0（仅 INSERT 入 leads 表）
// 配套: wall_api.php / widget v3 / AGE-TECH-AKO-DB-003
// 建表（DMS SQL 窗口执行一次）:
//   CREATE TABLE IF NOT EXISTS `leads` (
//     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
//     `name` VARCHAR(50) NOT NULL COMMENT '姓名',
//     `phone` VARCHAR(30) NOT NULL COMMENT '电话',
//     `market` VARCHAR(20) DEFAULT NULL COMMENT '意向市场',
//     `message` VARCHAR(500) DEFAULT NULL COMMENT '留言',
//     `source` VARCHAR(20) NOT NULL DEFAULT 'web' COMMENT '来源',
//     `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
//     PRIMARY KEY (`id`), KEY `idx_created` (`created_at`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='留资线索表';
// ============================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.local.php';
$DB_HOST = AKO_DB_HOST;
$DB_NAME = AKO_DB_NAME;
$DB_USER = AKO_DB_USER;
$DB_PASS = AKO_DB_PASS;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('{"ok":false,"error":"method"}'); }

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = $_POST;

$name    = trim($in['name'] ?? '');
$phone   = trim($in['phone'] ?? '');
$market  = trim($in['market'] ?? '');
$message = trim($in['message'] ?? '');

// 校验
if ($name === '' || mb_strlen($name) > 50)               exit('{"ok":false,"error":"name"}');
if (!preg_match('/^[0-9+\-\s]{5,20}$/', $phone))         exit('{"ok":false,"error":"phone"}');
$markets = ['城市更新','文旅民宿','乡村民居',''];
if (!in_array($market, $markets, true)) $market = '';
$message = mb_substr($message, 0, 500);

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    http_response_code(500);
    exit('{"ok":false,"error":"db connect fail"}');
}

// 防重复：同号码 5 分钟内只收一条
$dup = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE phone=? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$dup->execute([$phone]);
if ($dup->fetchColumn() > 0) exit('{"ok":true,"dup":1}');

$st = $pdo->prepare("INSERT INTO leads (name, phone, market, message, source) VALUES (?,?,?,?,'web')");
$st->execute([$name, $phone, $market ?: null, $message ?: null]);

echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
