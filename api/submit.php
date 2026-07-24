<?php
/**
 * AKO 官网 - 询盘表单提交接口
 * 接收前端 12 字段表单数据，验证后存储并发送邮件通知
 *
 * v2: 12 字段采集 + 写入前 JSON 格式校验
 */

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '仅支持 POST 请求']);
    exit;
}

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 读取 JSON 请求体
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => '请求数据格式错误']);
    exit;
}

// --- 字段提取与清洗（12 字段） ---
$projectName     = trim($input['projectName'] ?? '');
$projectLocation = trim($input['projectLocation'] ?? '');
$buildingArea    = trim($input['buildingArea'] ?? '');
$buildingType    = trim($input['buildingType'] ?? '');
$panelType       = trim($input['panelType'] ?? '');
$panelThickness  = trim($input['panelThickness'] ?? '');
$floors          = trim($input['floors'] ?? '');
$contactName     = trim($input['contactName'] ?? '');
$contactPhone    = trim($input['contactPhone'] ?? '');
$estimatedCost   = trim($input['estimatedCost'] ?? '');
$startDate       = trim($input['startDate'] ?? '');
$remarks         = trim($input['remarks'] ?? '');

// --- 服务端验证 ---
$errors = [];

if (empty($contactName)) {
    $errors[] = '请填写联系人';
}

if (empty($contactPhone)) {
    $errors[] = '请填写联系电话';
} elseif (!preg_match('/^[\d\-\+\s]{7,20}$/', $contactPhone)) {
    $errors[] = '电话号码格式不正确';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('；', $errors)]);
    exit;
}

// --- 构造询盘记录 ---
$inquiry = [
    'id'              => date('YmdHis') . '_' . substr(md5(uniqid()), 0, 6),
    'projectName'     => htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'),
    'projectLocation' => htmlspecialchars($projectLocation, ENT_QUOTES, 'UTF-8'),
    'buildingArea'    => htmlspecialchars($buildingArea, ENT_QUOTES, 'UTF-8'),
    'buildingType'    => htmlspecialchars($buildingType, ENT_QUOTES, 'UTF-8'),
    'panelType'       => htmlspecialchars($panelType, ENT_QUOTES, 'UTF-8'),
    'panelThickness'  => htmlspecialchars($panelThickness, ENT_QUOTES, 'UTF-8'),
    'floors'          => htmlspecialchars($floors, ENT_QUOTES, 'UTF-8'),
    'contactName'     => htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8'),
    'contactPhone'    => htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8'),
    'estimatedCost'   => htmlspecialchars($estimatedCost, ENT_QUOTES, 'UTF-8'),
    'startDate'       => htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'),
    'remarks'         => htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8'),
    'ip'              => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'created_at'      => date('Y-m-d H:i:s'),
];

// --- 存储到 JSON 文件 ---
$dataDir  = __DIR__ . '/../data';
$dataFile = $dataDir . '/inquiries.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$existing = [];
if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $existing = $decoded;
        }
        // 解析失败则视为空数组，不会覆盖损坏文件
    }
}

$existing[] = $inquiry;

$jsonOut = json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($jsonOut === false) {
    echo json_encode(['success' => false, 'message' => '数据序列化失败，请稍后重试']);
    exit;
}

// 写入前备份当前文件（如存在且不为空）
if (file_exists($dataFile) && filesize($dataFile) > 0) {
    copy($dataFile, $dataFile . '.bak');
}

if (file_put_contents($dataFile, $jsonOut, LOCK_EX) === false) {
    echo json_encode(['success' => false, 'message' => '数据存储失败，请稍后重试']);
    exit;
}

// --- 生成 XM 编号（与线索登记表共用计数器） ---
$counterFile = __DIR__ . '/../data/xm_counter.txt';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}
$counter = 1;
if (file_exists($counterFile)) {
    $counter = (int)file_get_contents($counterFile);
    $counter++;
}
file_put_contents($counterFile, $counter, LOCK_EX);
$xmNumber = 'AKO_xm_2026' . str_pad($counter, 3, '0', STR_PAD_LEFT);

// --- 发送邮件通知（SMTP） ---
require_once __DIR__ . '/../mail_helper.php';
$notify_list = [
    'contact@akobuild.cloud',
    '583748052@qq.com',
    '376972621@qq.com',
    '806853039@qq.com',
];
$subject = "【AKO官网】新询盘 - {$contactName}";
$body    = "您有一条新的询盘：\n\n"
         . "项目名称：{$projectName}\n"
         . "项目地点：{$projectLocation}\n"
         . "建筑面积：{$buildingArea} m²\n"
         . "建筑类型：{$buildingType}\n"
         . "墙板类型：{$panelType}\n"
         . "墙板厚度：{$panelThickness} mm\n"
         . "预计层数：{$floors}\n"
         . "联系人：{$contactName}\n"
         . "联系电话：{$contactPhone}\n"
         . "预计造价：{$estimatedCost} 万元\n"
         . "开工时间：{$startDate}\n"
         . "备注：{$remarks}\n\n"
         . "提交时间：{$inquiry['created_at']}\n"
         . "来源IP：{$inquiry['ip']}\n"
         . "\n—— 查看后台：https://akobuild.cloud/admin.php\n";
ako_send_mail($notify_list, $subject, $body);

// --- 返回成功 ---
echo json_encode([
    'success'  => true,
    'message'  => '提交成功，我们将尽快联系您！',
    'id'       => $inquiry['id'],
    'xmNumber' => $xmNumber,
]);
