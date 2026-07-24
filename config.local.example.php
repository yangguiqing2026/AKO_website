<?php
/**
 * AKO 官网 - 本地配置文件（示例）
 * 
 * 使用方法：
 *   1. 复制此文件为 config.local.php
 *   2. 填入真实的数据库和 SMTP 凭据
 *   3. config.local.php 已被 .gitignore 忽略，不会提交到 Git
 */

// === MySQL 数据库连接 ===
define('AKO_DB_HOST', 'your-db-host.example.com');
define('AKO_DB_NAME', 'your_database_name');
define('AKO_DB_USER', 'your_db_user');
define('AKO_DB_PASS', 'your_db_password');

// === SMTP 邮件配置 ===
define('AKO_SMTP_HOST', 'smtp.example.com');
define('AKO_SMTP_PORT', 465);
define('AKO_SMTP_USER', 'your-email@example.com');
define('AKO_SMTP_PASS', 'your_smtp_password');
define('AKO_SMTP_FROM', 'your-email@example.com');
define('AKO_SMTP_FROM_NAME', 'AKO 阿格建筑');