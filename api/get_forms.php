<?php
/**
 * AKO 表单数据只读 API
 * 供 AKO_form_extractor 通过 HTTP 拉取表单数据
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$API_KEY = 'AKO-FORM-EXTRACTOR-2026';
$provided_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['key'] ?? '';
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);

if (!$is_local && $provided_key !== $API_KEY) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'unauthorized']));
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'method not allowed']));
}

$filter_type  = $_GET['type'] ?? '';
$filter_since = $_GET['since'] ?? '';
$results = [];

// ============================================================
// 1. inquiries.json（Contact 页面询盘）
// ============================================================
if (!$filter_type || $filter_type === 'inquiry') {
    $file = __DIR__ . '/../data/inquiries.json';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if ($content !== false) {
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                foreach ($data as $inq) {
                    $created = $inq['created_at'] ?? '';
                    if ($filter_since && $created < $filter_since) continue;
                    $results[] = [
                        'id'               => $inq['id'] ?? '',
                        'form_type'        => 'inquiry',
                        'source'           => 'web_contact',
                        'name'             => $inq['contactName'] ?? $inq['name'] ?? '',
                        'phone'            => $inq['contactPhone'] ?? $inq['phone'] ?? '',
                        'projectName'      => $inq['projectName'] ?? '',
                        'projectLocation'  => $inq['projectLocation'] ?? '',
                        'buildingArea'     => $inq['buildingArea'] ?? '',
                        'buildingType'     => $inq['buildingType'] ?? '',
                        'panelType'        => $inq['panelType'] ?? '',
                        'panelThickness'   => $inq['panelThickness'] ?? '',
                        'floors'           => $inq['floors'] ?? '',
                        'estimatedCost'    => $inq['estimatedCost'] ?? '',
                        'startDate'        => $inq['startDate'] ?? '',
                        'remarks'          => $inq['remarks'] ?? '',
                        'ip'               => $inq['ip'] ?? '',
                        'created_at'       => $created,
                    ];
                }
            }
        }
    }
}

// ============================================================
// 2. leads 表（Widget 留资 + XM 线索）
// ============================================================
if (!$filter_type || $filter_type === 'lead') {
    try {
        require_once __DIR__ . '/../config.local.php';
        $pdo = new PDO(
            'mysql:host=' . AKO_DB_HOST . ';dbname=' . AKO_DB_NAME . ';charset=utf8mb4',
            AKO_DB_USER, AKO_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $sql  = "SELECT id, name, phone, market, message, source, created_at FROM leads";
        $where = [];
        $params = [];
        if ($filter_since) {
            $where[] = "created_at >= ?";
            $params[] = $filter_since . ' 00:00:00';
        }
        if ($where) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rec = [
                'id'         => 'LEAD-' . $row['id'],
                'form_type'  => ($row['source'] === 'xm') ? 'xm_lead' : 'lead',
                'source'     => ($row['source'] === 'xm') ? 'xm_page' : 'web_widget',
                'name'       => $row['name'],
                'phone'      => $row['phone'],
                'market'     => $row['market'],
                'message'    => $row['message'],
                'created_at' => $row['created_at'],
            ];
            // xm_data 列暂未添加，跳过
            $results[] = $rec;
        }
    } catch (Exception $e) {
        error_log('[get_forms] DB error: ' . $e->getMessage());
        $results[] = [
            'id'         => 'DB-ERROR',
            'form_type'  => 'system_error',
            'name'       => 'MySQL查询失败: ' . $e->getMessage(),
            'phone'      => '',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }
}

echo json_encode([
    'ok'         => true,
    'total'      => count($results),
    'updated_at' => date('Y-m-d H:i:s'),
    'data'       => $results,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
