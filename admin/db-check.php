<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = db();
    $version = $pdo->query('SELECT VERSION() AS version')->fetch();
    echo '<h1>DB接続OK</h1>';
    echo '<p>MySQL Version: ' . htmlspecialchars($version['version'], ENT_QUOTES, 'UTF-8') . '</p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>DB接続エラー</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
}
