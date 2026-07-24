<?php
/**
 * AKO 官网 - 线索数据查看后台
 * 
 * 数据源：
 *   - Contact 页面表单 → data/inquiries.json（12 字段项目询盘）
 *   - Widget 留资 / XM 线索登记表 → MySQL leads 表
 * 
 * 访问：https://akobuild.cloud/admin.php
 * 安全：建议上线前加上 .htpasswd 或 IP 白名单
 */

// ========== 读取 inquiries.json ==========
$inquiries = [];
$inqFile = __DIR__ . '/data/inquiries.json';
if (file_exists($inqFile)) {
    $raw = file_get_contents($inqFile);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $inquiries = $decoded;
    }
}

// ========== 读取 MySQL leads 表 ==========
$leads = [];
$dbError = '';
require_once __DIR__ . '/config.local.php';
try {
    $pdo = new PDO(
        'mysql:host=' . AKO_DB_HOST . ';dbname=' . AKO_DB_NAME . ';charset=utf8mb4',
        AKO_DB_USER, AKO_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->query('SELECT id, name, phone, market, message, source, created_at FROM leads ORDER BY created_at DESC LIMIT 200');
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbError = '数据库连接失败: ' . $e->getMessage();
}

// ========== CSV 导出 ==========
$download = $_GET['download'] ?? '';
if ($download === 'inquiries' || $download === 'leads') {
    $filename = 'AKO_' . $download . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // UTF-8 BOM（Excel 正确识别中文）
    echo "\xEF\xBB\xBF";

    $fp = fopen('php://output', 'w');

    if ($download === 'inquiries') {
        fputcsv($fp, ['编号', '项目名称', '项目地点', '建筑面积(m²)', '建筑类型', '墙板类型', '墙板厚度(mm)', '层数', '联系人', '电话', '预计造价(万元)', '开工时间', '备注', '提交时间', '来源IP']);
        foreach (array_reverse($inquiries) as $row) {
            fputcsv($fp, [
                $row['id'] ?? '', $row['projectName'] ?? '', $row['projectLocation'] ?? '',
                $row['buildingArea'] ?? '', $row['buildingType'] ?? '', $row['panelType'] ?? '',
                $row['panelThickness'] ?? '', $row['floors'] ?? '',
                $row['contactName'] ?? '', $row['contactPhone'] ?? '',
                $row['estimatedCost'] ?? '', $row['startDate'] ?? '',
                $row['remarks'] ?? '', $row['created_at'] ?? '', $row['ip'] ?? ''
            ]);
        }
    } else {
        fputcsv($fp, ['ID', '姓名', '电话', '意向市场', '留言', '来源', '提交时间']);
        foreach ($leads as $row) {
            fputcsv($fp, [
                $row['id'] ?? '', $row['name'] ?? '', $row['phone'] ?? '',
                $row['market'] ?? '', $row['message'] ?? '',
                $row['source'] ?? '', $row['created_at'] ?? ''
            ]);
        }
    }
    fclose($fp);
    exit;
}

// ========== 统计 ==========
$totalInquiries = count($inquiries);
$totalLeads = count($leads);
$totalAll = $totalInquiries + $totalLeads;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>线索数据后台 - AKO 阿格建筑</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f5f5f5;color:#333;padding:20px;}
.header{background:#fff;border-radius:8px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.08);display:flex;justify-content:space-between;align-items:center;}
.header h1{font-size:20px;color:#231E1C;}
.header .stats{display:flex;gap:20px;}
.stat-card{background:#EBDAB9;color:#231E1C;padding:12px 20px;border-radius:8px;text-align:center;}
.stat-card .num{font-size:28px;font-weight:900;}
.stat-card .lbl{font-size:12px;opacity:.7;}
.section{background:#fff;border-radius:8px;margin-bottom:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);}
.section-title{background:#231E1C;color:#B99B5F;padding:12px 20px;font-size:15px;font-weight:700;display:flex;justify-content:space-between;align-items:center;}
.section-title .count{font-size:12px;color:#EBDAB9;font-weight:400;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:#faf6ef;color:#A08C64;padding:10px 12px;text-align:left;font-weight:600;border-bottom:2px solid #EBDAB9;white-space:nowrap;}
td{padding:8px 12px;border-bottom:1px solid #f0f0f0;vertical-align:top;max-width:200px;overflow:hidden;text-overflow:ellipsis;}
tr:hover{background:#fdf9f2;}
.empty{padding:40px;text-align:center;color:#999;font-size:14px;}
.source-tag{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;}
.source-web{background:#d4edda;color:#155724;}
.source-widget{background:#cce5ff;color:#004085;}
.source-xm{background:#fff3cd;color:#856404;}
.db-error{background:#fff3cd;border:1px solid #ffc107;color:#856404;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:13px;}
.refresh{font-size:12px;color:#A08C64;}
.footer{padding:20px 0;text-align:center;font-size:12px;color:#999;}
@media(max-width:768px){
  .header{flex-direction:column;gap:12px;}
  .stats{flex-wrap:wrap;justify-content:center;}
  th,td{font-size:12px;padding:6px 8px;}
  table{display:block;overflow-x:auto;}
}
</style>
</head>
<body>

<div class="header">
  <div>
    <h1>AKO 线索数据后台</h1>
    <div class="refresh">页面刷新时间: <?php echo date('Y-m-d H:i:s'); ?></div>
  </div>
  <div class="stats">
    <div class="stat-card"><div class="num"><?php echo $totalAll; ?></div><div class="lbl">线索总数</div></div>
    <div class="stat-card"><div class="num"><?php echo $totalInquiries; ?></div><div class="lbl">询盘表单</div></div>
    <div class="stat-card"><div class="num"><?php echo $totalLeads; ?></div><div class="lbl">留资/线索</div></div>
  </div>
</div>

<?php if ($dbError): ?>
<div class="db-error">⚠ <?php echo htmlspecialchars($dbError); ?></div>
<?php endif; ?>

<!-- ====== 一、Contact 页面询盘 ====== -->
<div class="section">
  <div class="section-title">
    一、项目询盘（Contact 页面 · 12 字段表单）
    <span class="count">共 <?php echo $totalInquiries; ?> 条 · 存储于 data/inquiries.json</span>
  </div>
  <?php if (empty($inquiries)): ?>
  <div class="empty">暂无询盘记录</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table>
  <thead>
  <tr>
    <th>ID</th>
    <th>项目名称</th>
    <th>项目地点</th>
    <th>面积(m²)</th>
    <th>建筑类型</th>
    <th>墙板类型</th>
    <th>厚度(mm)</th>
    <th>层数</th>
    <th>联系人</th>
    <th>电话</th>
    <th>造价(万元)</th>
    <th>开工时间</th>
    <th>备注</th>
    <th>提交时间</th>
    <th>IP</th>
  </tr>
  </thead>
  <tbody>
  <?php foreach (array_reverse($inquiries) as $inq): ?>
  <tr>
    <td title="<?php echo htmlspecialchars($inq['id'] ?? ''); ?>"><?php echo htmlspecialchars(substr($inq['id'] ?? '', 0, 12)); ?>…</td>
    <td><?php echo htmlspecialchars($inq['projectName'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['projectLocation'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['buildingArea'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['buildingType'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['panelType'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['panelThickness'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['floors'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['contactName'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['contactPhone'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['estimatedCost'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['startDate'] ?? ''); ?></td>
    <td title="<?php echo htmlspecialchars($inq['remarks'] ?? ''); ?>"><?php echo htmlspecialchars(mb_substr($inq['remarks'] ?? '', 0, 20)); ?><?php echo mb_strlen($inq['remarks'] ?? '') > 20 ? '…' : ''; ?></td>
    <td><?php echo htmlspecialchars($inq['created_at'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($inq['ip'] ?? ''); ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- ====== 二、Widget 留资 / XM 线索 ====== -->
<div class="section">
  <div class="section-title">
    二、留资线索（Widget 留资 + XM 线索登记表）
    <span class="count">共 <?php echo $totalLeads; ?> 条 · 存储于 MySQL leads 表</span>
  </div>
  <?php if (empty($leads)): ?>
  <div class="empty">暂无留资记录 <?php echo $dbError ? '(数据库未连接)' : ''; ?></div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table>
  <thead>
  <tr>
    <th>ID</th>
    <th>姓名</th>
    <th>电话</th>
    <th>意向市场</th>
    <th>留言</th>
    <th>来源</th>
    <th>提交时间</th>
  </tr>
  </thead>
  <tbody>
  <?php foreach ($leads as $lead): 
    $srcClass = 'source-web';
    if ($lead['source'] === 'widget') $srcClass = 'source-widget';
    elseif ($lead['source'] === 'xm') $srcClass = 'source-xm';
  ?>
  <tr>
    <td><?php echo (int)$lead['id']; ?></td>
    <td><?php echo htmlspecialchars($lead['name'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($lead['phone'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($lead['market'] ?? ''); ?></td>
    <td title="<?php echo htmlspecialchars($lead['message'] ?? ''); ?>"><?php echo htmlspecialchars(mb_substr($lead['message'] ?? '', 0, 30)); ?><?php echo mb_strlen($lead['message'] ?? '') > 30 ? '…' : ''; ?></td>
    <td><span class="source-tag <?php echo $srcClass; ?>"><?php echo htmlspecialchars($lead['source'] ?? 'web'); ?></span></td>
    <td><?php echo htmlspecialchars($lead['created_at'] ?? ''); ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- ====== 三、手工取数据说明 ====== -->
<div class="section">
  <div class="section-title">三、手工取数据方式</div>
  <div style="padding:16px 20px;font-size:13px;line-height:2;">
    <p><strong>方式 1：JSON 文件下载</strong></p>
    <p>Contact 页面询盘数据直接保存在服务器：<code>data/inquiries.json</code></p>
    <p>可通过 FTP/SFTP 下载，或浏览器访问 <code>/data/inquiries.json</code>（如需公开访问需配置 web server）</p>
    <p style="margin-top:8px;"><strong>方式 2：MySQL 数据库直连</strong></p>
    <p>留资线索存储在 MySQL 表 <code>leads</code>，连接信息：</p>
    <pre style="background:#faf6ef;padding:12px;border-radius:6px;margin:8px 0;">Host: cdm823310131.my3w.com
Database: cdm823310131_db
User: cdm823310131
Table: leads
查询: SELECT * FROM leads ORDER BY created_at DESC;</pre>
    <p>可通过虚拟主机管理后台（DMS / phpMyAdmin）登录查看</p>
    <p style="margin-top:8px;"><strong>方式 3：本后台页面</strong></p>
    <p>直接访问 <code>https://akobuild.cloud/admin.php</code> 查看所有数据（建议设置访问密码）</p>
  </div>
</div>

<div class="footer">
  AKO 阿格建筑 · 线索管理后台 · 请勿分享此页面链接
</div>

</body>
</html>