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
  if ($type !== 'availability') throw new Exception('複製できるのは空き枠のみです。');

  $stmt = $pdo->prepare("SELECT * FROM availability WHERE id=?");
  $stmt->execute([$id]);
  $a = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$a) throw new Exception('空き枠が見つかりません。');

  $stmt = $pdo->prepare("
    INSERT INTO availability
      (staff_id, start_datetime, end_datetime, note, store_id, date, start_time, end_time, capacity, is_active, created_at, updated_at)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
  ");
  $stmt->execute([
    $a['staff_id'],
    $a['start_datetime'],
    $a['end_datetime'],
    ($a['note'] ?? '') . ' コピー',
    $a['store_id'] ?? 1,
    $a['date'] ?? date('Y-m-d', strtotime($a['start_datetime'])),
    $a['start_time'] ?? date('H:i:s', strtotime($a['start_datetime'])),
    $a['end_time'] ?? date('H:i:s', strtotime($a['end_datetime'])),
    $a['capacity'] ?? 1
  ]);

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
