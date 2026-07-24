<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$dir = __DIR__ . '/../downloads';
$files = [];

if (is_dir($dir)) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_file($path)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $files[] = [
                'name' => $item,
                'size' => filesize($path),
                'url'  => 'downloads/' . rawurlencode($item),
                'ext'  => $ext
            ];
        }
    }
}

echo json_encode(['success' => true, 'files' => $files], JSON_UNESCAPED_UNICODE);