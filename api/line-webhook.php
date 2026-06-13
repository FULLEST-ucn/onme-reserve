<?php
/**
 * LINE Webhook受信用の雛形です。
 * 今後「来週空いてる？」のようなAI予約へ拡張します。
 */
header('Content-Type: application/json; charset=utf-8');

$body = file_get_contents('php://input');
file_put_contents(__DIR__ . '/../storage/logs/line-webhook.log', date('c') . "\n" . $body . "\n\n", FILE_APPEND);

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
