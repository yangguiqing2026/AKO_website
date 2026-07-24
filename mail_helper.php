<?php
/**
 * AKO SMTP 邮件发送模块 (cURL 版 v2)
 * 零依赖，使用 PHP cURL 实现 SMTP SSL 发信
 *
 * SMTP 配置：从 config.local.php 加载
 */
require_once __DIR__ . '/config.local.php';
define('SMTP_HOST', AKO_SMTP_HOST);
define('SMTP_PORT', AKO_SMTP_PORT);
define('SMTP_USER', AKO_SMTP_USER);
define('SMTP_PASS', AKO_SMTP_PASS);
define('SMTP_FROM', AKO_SMTP_FROM);
define('SMTP_FROM_NAME', AKO_SMTP_FROM_NAME);

/**
 * 发送邮件（cURL SMTP 方式）
 */
function ako_send_mail($to, $subject, $body) {
    if (!is_array($to)) {
        $to = [$to];
    }

    $results = [];
    foreach ($to as $recipient) {
        $results[$recipient] = _curl_smtp_send($recipient, $subject, $body);
    }
    return $results;
}

/**
 * cURL SMTP 单封发送
 */
function _curl_smtp_send($to, $subject, $body) {
    if (!function_exists('curl_init')) {
        error_log("[AKO SMTP] cURL 扩展不可用");
        return false;
    }

    // 构造邮件内容
    $fromEncoded    = "=?UTF-8?B?" . base64_encode(SMTP_FROM_NAME) . "?=";
    $subjectEncoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";

    $message  = "From: {$fromEncoded} <" . SMTP_FROM . ">\r\n";
    $message .= "To: {$to}\r\n";
    $message .= "Subject: {$subjectEncoded}\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "X-Mailer: AKO cURL SMTP v2\r\n";
    $message .= "\r\n";
    $message .= chunk_split(base64_encode($body));
    $message .= "\r\n.\r\n";

    // 用 php://temp 流代替闭包
    $stream = fopen('php://temp', 'r+');
    if (!$stream) {
        error_log("[AKO SMTP] 无法创建临时流");
        return false;
    }
    fwrite($stream, $message);
    rewind($stream);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => 'smtps://' . SMTP_HOST . ':' . SMTP_PORT,
        CURLOPT_USERPWD        => SMTP_USER . ':' . SMTP_PASS,
        CURLOPT_MAIL_FROM      => '<' . SMTP_FROM . '>',
        CURLOPT_MAIL_RCPT      => ['<' . $to . '>'],
        CURLOPT_UPLOAD         => true,
        CURLOPT_INFILE         => $stream,
        CURLOPT_INFILESIZE     => strlen($message),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_VERBOSE        => false,
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    $error    = curl_error($ch);
    curl_close($ch);
    fclose($stream);

    if ($errno !== 0) {
        error_log("[AKO SMTP] cURL 错误 #{$errno}: {$error} (to: {$to})");
        return false;
    }

    return true;
}