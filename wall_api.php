<?php
/**
 * wall_api.php — 墙板数据库只读代理
 *
 * 本文件部署于 akobuild.cloud 网站根目录，widget v3 前端通过同域
 * /wall_api.php 请求结构化数据（规格/参数/报价/FAQ/meta）。
 * 实际数据源为虚拟主机 wh-nc6lcdplh894m2oe8v0.my3w.com 上的 wall_api.php，
 * 本代理做透明转发 + 响应缓存（60s），避免每次请求都打到虚拟主机。
 *
 * 依据：AGE-TECH-AKO-DB-003（2026-07-18）
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// === 缓存目录 ===
$cacheDir = __DIR__ . '/data';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// === 只接受 GET ===
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method not allowed']);
    exit;
}

$type = $_GET['type'] ?? '';
$thickness = isset($_GET['thickness']) ? intval($_GET['thickness']) : 0;

$allowedTypes = ['panels', 'specs', 'pricing', 'faq', 'projects', 'meta'];
if (!in_array($type, $allowedTypes, true)) {
    echo json_encode(['ok' => false, 'error' => 'invalid type', 'allowed' => $allowedTypes]);
    exit;
}

// === 缓存键 ===
$cacheKey = $type . ($thickness > 0 ? '_' . $thickness : '');
$cacheFile = $cacheDir . '/wall_' . md5($cacheKey) . '.json';
$cacheTTL = 60; // 秒

// 命中缓存且未过期 → 直接返回
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    $cached = file_get_contents($cacheFile);
    if ($cached !== false) {
        echo $cached;
        exit;
    }
}

// === 转发到虚拟主机 wall_api.php ===
$upstream = 'http://wh-nc6lcdplh894m2oe8v0.my3w.com/wall_api.php';
$url = $upstream . '?type=' . urlencode($type);
if ($thickness > 0) {
    $url .= '&thickness=' . $thickness;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 2,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 上游不可达 → 返回降级响应
if ($response === false || $httpCode >= 500) {
    error_log('[wall_api proxy] upstream unreachable: ' . ($curlError ?: "HTTP $httpCode"));
    echo json_encode(['ok' => false, 'error' => 'upstream unavailable'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证返回 JSON 有效性
$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('[wall_api proxy] invalid JSON from upstream');
    echo json_encode(['ok' => false, 'error' => 'invalid upstream response'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 成功 → 写入缓存
if ($decoded && ($decoded['ok'] ?? false)) {
    file_put_contents($cacheFile, $response, LOCK_EX);
}

echo $response;