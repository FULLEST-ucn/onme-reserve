<?php
require_once __DIR__ . '/../../config/auth.php';
require_owner();

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!$payload) throw new Exception('Invalid payload');

  $customerId = (int)($payload['customer_id'] ?? 0);
  if ($customerId <= 0) throw new Exception('顧客IDが不正です。');

  $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_ASSOC));

  if (!in_array('is_active', $cols, true)) {
    $pdo->exec("ALTER TABLE customers ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
  }

  $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_ASSOC));

  $sql = "UPDATE customers SET is_active = 0";
  if (in_array('updated_at', $cols, true)) {
    $sql .= ", updated_at = NOW()";
  }
  $sql .= " WHERE id = ?";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([$customerId]);

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
