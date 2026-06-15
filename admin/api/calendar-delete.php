<?php
require_once __DIR__ . '/../../config/auth.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $p = json_decode(file_get_contents('php://input'), true);
  if (!$p) throw new Exception('Invalid payload');

  $type = $p['type'] ?? '';
  $id = (int)($p['id'] ?? 0);
  if ($id <= 0) throw new Exception('IDが不正です。');

  if ($type === 'availability') {
    $stmt = $pdo->prepare("UPDATE availability SET is_active=0, updated_at=NOW() WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(['ok'=>true]); exit;
  }

  if ($type === 'reservation') {
    $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM reservations")->fetchAll(PDO::FETCH_ASSOC));
    if (in_array('status', $cols, true)) {
      $stmt = $pdo->prepare("UPDATE reservations SET status='cancelled', updated_at=NOW() WHERE id=?");
      $stmt->execute([$id]);
    } else {
      $stmt = $pdo->prepare("DELETE FROM reservations WHERE id=?");
      $stmt->execute([$id]);
    }
    echo json_encode(['ok'=>true]); exit;
  }

  throw new Exception('typeが不正です。');
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
