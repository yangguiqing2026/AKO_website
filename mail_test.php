<?php
/**
 * AKO 邮件测试脚本
 * 访问一次即向所有预设邮箱发送一封测试邮件（SMTP）。
 * 用法：浏览器访问 https://akobuild.cloud/mail_test.php
 *       或命令行：php mail_test.php
 */

require_once __DIR__ . '/mail_helper.php';

$notify_list = [
    'contact@akobuild.cloud',
    '583748052@qq.com',
    '376972621@qq.com',
    '806853039@qq.com',
];

$testTime = date('Y-m-d H:i:s');
$subject = "【AKO 邮件测试】{$testTime}";
$body    = "这是一封自动测试邮件。\n\n"
         . "如果您收到此邮件，说明 AKO 官网表单邮件通知功能正常。\n\n"
         . "测试时间：{$testTime}\n"
         . "发件来源：yangguiqing1970@qq.com SMTP 发送\n"
         . "接收邮箱：" . implode(', ', $notify_list) . "\n\n"
         . "后续正式提交将从此邮箱地址发出。\n"
         . "—— 后台地址：https://akobuild.cloud/admin.php\n";

$smtpResults = ako_send_mail($notify_list, $subject, $body);

$results = [];
foreach ($smtpResults as $to => $sent) {
    $results[] = [$to, $sent ? '✅ 已发送' : '❌ 失败'];
}

// 输出结果
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><title>AKO 邮件测试</title>
<style>
body{font-family:sans-serif;padding:30px;background:#f5f5f5;color:#333;}
.card{background:#fff;border-radius:8px;padding:24px;max-width:500px;margin:0 auto;box-shadow:0 2px 8px rgba(0,0,0,.1);}
h2{color:#231E1C;margin:0 0 16px;}
table{width:100%;border-collapse:collapse;}
td{padding:10px 12px;border-bottom:1px solid #eee;}
.status{font-weight:600;}
.ok{color:#28a745;} .fail{color:#dc3545;}
.note{margin-top:16px;padding:12px;background:#fff3cd;border-radius:6px;font-size:13px;color:#856404;}
</style>
</head>
<body>
<div class="card">
<h2>📧 AKO 邮件发送测试</h2>
<p style="color:#666;">测试时间：<?php echo $testTime; ?></p>
<table>
<?php foreach ($results as $r): ?>
<tr><td><?php echo htmlspecialchars($r[0]); ?></td><td class="status <?php echo strpos($r[1], '✅') !== false ? 'ok' : 'fail'; ?>"><?php echo $r[1]; ?></td></tr>
<?php endforeach; ?>
</table>
<div class="note">
⚠️ <strong>注意：</strong><br>
• 如果显示"已发送"但未收到邮件，请检查垃圾箱。<br>
• QQ邮箱可能拦截虚拟主机发出的邮件，建议将 noreply@akobuild.cloud 加入白名单。<br>
• 多次发送失败需要改用 SMTP 方式发信。
</div>
</div>
</body>
</html>