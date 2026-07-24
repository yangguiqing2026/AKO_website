<?php
/**
 * SMTP 连接诊断脚本
 * 访问 https://akobuild.cloud/smtp_diag.php 查看详细信息
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config.local.php';
$host = AKO_SMTP_HOST;
$port = AKO_SMTP_PORT;
$user = AKO_SMTP_USER;
$pass = AKO_SMTP_PASS;

echo "<h2>AKO SMTP 诊断</h2>";
echo "<pre>";

// 1. 检查 cURL
echo "<b>1. cURL 扩展：</b>";
echo function_exists('curl_init') ? "✅ 可用\n\n" : "❌ 不可用\n\n";

// 2. 检查 cURL 版本
$cv = curl_version();
echo "<b>2. cURL 版本：</b>{$cv['version']}\n";
echo "   SSL 版本：" . $cv['ssl_version'] . "\n";
echo "   协议支持：" . implode(', ', $cv['protocols']) . "\n\n";

// 3. DNS 解析
echo "<b>3. DNS 解析 smtp.qq.com：</b>";
$ip = gethostbyname($host);
echo "{$ip}\n\n";

// 4. 尝试 socket 连接（不 SSL）
echo "<b>4. TCP 连接 smtp.qq.com:25：</b>";
$errno = 0; $errstr = '';
$sock = @fsockopen('smtp.qq.com', 25, $errno, $errstr, 5);
echo $sock ? "✅ 成功\n" : "❌ 失败: #{$errno} {$errstr}\n";
if ($sock) fclose($sock);
echo "\n";

// 5. 尝试 TCP 连接 465 端口
echo "<b>5. TCP 连接 smtp.qq.com:465：</b>";
$errno = 0; $errstr = '';
$sock = @fsockopen('smtp.qq.com', 465, $errno, $errstr, 5);
echo $sock ? "✅ 成功\n" : "❌ 失败: #{$errno} {$errstr}\n";
if ($sock) fclose($sock);
echo "\n";

// 6. 尝试 SSL 连接（stream_socket_client）
echo "<b>6. SSL 连接 smtp.qq.com:465 (stream)：</b>";
$errno = 0; $errstr = '';
$ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$sock = @stream_socket_client("ssl://smtp.qq.com:465", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ctx);
if ($sock) {
    echo "✅ 成功\n";
    $line = fgets($sock, 512);
    echo "   服务器欢迎: " . htmlspecialchars($line) . "\n";
    fclose($sock);
} else {
    echo "❌ 失败: #{$errno} {$errstr}\n";
}
echo "\n";

// 7. 尝试 cURL SMTP 最简单版本
echo "<b>7. cURL SMTP 快速测试：</b>\n";
$msg = "From: AKO <yangguiqing1970@qq.com>\r\n"
     . "To: <contact@akobuild.cloud>\r\n"
     . "Subject: =?UTF-8?B?" . base64_encode("诊断测试") . "?=\r\n"
     . "MIME-Version: 1.0\r\n"
     . "Content-Type: text/plain; charset=UTF-8\r\n"
     . "\r\n"
     . "诊断测试邮件\r\n"
     . "\r\n.\r\n";

$stream = fopen('php://temp', 'r+');
fwrite($stream, $msg);
rewind($stream);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL        => "smtps://{$host}:{$port}",
    CURLOPT_USERPWD    => "{$user}:{$pass}",
    CURLOPT_MAIL_FROM  => '<' . $user . '>',
    CURLOPT_MAIL_RCPT  => ['<contact@akobuild.cloud>'],
    CURLOPT_UPLOAD     => true,
    CURLOPT_INFILE     => $stream,
    CURLOPT_INFILESIZE => strlen($msg),
    CURLOPT_TIMEOUT    => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_VERBOSE    => false,
]);
$resp = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
curl_close($ch);
fclose($stream);

if ($errno === 0) {
    echo "   ✅ cURL SMTP 成功!\n";
} else {
    echo "   ❌ cURL 错误 #{$errno}: {$error}\n";
}

echo "</pre>";