<?php
/**
 * lead_submit.php — AKO_xm_线索登记表提交接口
 *
 * 接收 AKO_xm_线索登记表.html 的全部字段，存入 MySQL leads 表。
 * leads 表需扩展 xm_data 字段存放 JSON 序列化的多字段数据。
 *
 * 建表更新（在 DMS SQL 窗口执行）:
 *   ALTER TABLE leads ADD COLUMN `xm_data` TEXT COMMENT 'XM线索登记表完整JSON' AFTER `message`;
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ---- 生成 XM 编号函数 ----
function getNextXmNumber() {
    $counterFile = __DIR__ . '/data/xm_counter.txt';
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    $counter = 1;
    if (file_exists($counterFile)) {
        $counter = (int)file_get_contents($counterFile);
    }
    return 'AKO_xm_2026' . str_pad($counter + 1, 3, '0', STR_PAD_LEFT);
}

function allocateXmNumber() {
    $counterFile = __DIR__ . '/data/xm_counter.txt';
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    $counter = 1;
    if (file_exists($counterFile)) {
        $counter = (int)file_get_contents($counterFile);
        $counter++;
    }
    file_put_contents($counterFile, $counter, LOCK_EX);
    return 'AKO_xm_2026' . str_pad($counter, 3, '0', STR_PAD_LEFT);
}

// GET 请求：预取下一个编号（不锁定，仅预览）
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'ok'         => true,
        'nextNumber' => getNextXmNumber(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('{"ok":false,"error":"method"}');
}

require_once __DIR__ . '/config.local.php';
$DB_HOST = AKO_DB_HOST;
$DB_NAME = AKO_DB_NAME;
$DB_USER = AKO_DB_USER;
$DB_PASS = AKO_DB_PASS;

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = $_POST;

// 提取联系人信息（必需字段）
$name    = trim($in['name'] ?? $in['联系人'] ?? '');
$phone   = trim($in['phone'] ?? $in['电话'] ?? '');

// 校验
if ($name === '' || mb_strlen($name) > 50)  exit('{"ok":false,"error":"name"}');
if (!preg_match('/^[0-9+\-\s]{5,20}$/', $phone)) exit('{"ok":false,"error":"phone"}');

// 构建 message 摘要
$market  = trim($in['market'] ?? $in['意向市场'] ?? '');
$projectName = trim($in['项目名称'] ?? '');
$summary = [];
if ($projectName) $summary[] = "项目: $projectName";
if ($market) $summary[] = "市场: $market";
$message = mb_substr(implode('; ', $summary), 0, 500);

// 完整 XM 数据序列化
$xmData = json_encode($in, JSON_UNESCAPED_UNICODE);
if ($xmData === false) $xmData = '{}';

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
if ($dup->fetchColumn() > 0) exit('{"ok":true,"dup":1,"id":0}');

// 尝试插入（xm_data 列可能不存在，降级为仅插入基础字段）
try {
    $st = $pdo->prepare("INSERT INTO leads (name, phone, market, message, source, xm_data) VALUES (?,?,?,?,'xm',?)");
    $st->execute([$name, $phone, $market ?: null, $message ?: null, $xmData]);
} catch (Exception $e) {
    // xm_data 列不存在时降级
    error_log('[lead_submit] xm_data column missing, falling back: ' . $e->getMessage());
    $st = $pdo->prepare("INSERT INTO leads (name, phone, market, message, source) VALUES (?,?,?,?,'xm')");
    $st->execute([$name, $phone, $market ?: null, $message ?: null]);
}

// ---- 分配 XM 编号 ----
$xmNumber = allocateXmNumber();

// ---- 邮件通知（SMTP）----
require_once __DIR__ . '/mail_helper.php';
$notify_list = [
    'contact@akobuild.cloud',
    '583748052@qq.com',
    '376972621@qq.com',
    '806853039@qq.com',
];
$subject = "【AKO-XM线索】{$name} - {$phone}";
$body = "您有一条新的XM线索登记：\n\n"
    . "姓名：{$name}\n"
    . "电话：{$phone}\n"
    . "项目名称：{$projectName}\n"
    . "意向市场：{$market}\n"
    . "留言摘要：{$message}\n"
    . "来源：XM 线索登记表\n"
    . "提交时间：" . date('Y-m-d H:i:s') . "\n\n"
    . "完整数据已存入 MySQL leads 表（source='xm'）。\n"
    . "—— 查看后台：https://akobuild.cloud/admin.php";
ako_send_mail($notify_list, $subject, $body);

echo json_encode([
    'ok'       => true,
    'id'       => (int)$pdo->lastInsertId(),
    'xmNumber' => $xmNumber,
], JSON_UNESCAPED_UNICODE);
