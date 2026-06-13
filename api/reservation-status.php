<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed = ['reserved', 'confirmed', 'completed', 'cancelled'];
if (!$id || !in_array($status, $allowed, true)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => '入力内容を確認してください'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare("UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?");
  $stmt->execute([$status, $id]);
  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
