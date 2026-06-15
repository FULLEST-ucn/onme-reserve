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
  $date = $p['date'] ?? date('Y-m-d');
  $startTime = $p['start_time'] ?? '';
  $endTime = $p['end_time'] ?? '';
  $note = trim($p['note'] ?? '');

  if ($id <= 0) throw new Exception('IDが不正です。');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new Exception('日付が不正です。');
  if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) throw new Exception('時間が不正です。');

  $startDatetime = $date . ' ' . $startTime . ':00';
  $endDatetime = $date . ' ' . $endTime . ':00';
  if (strtotime($endDatetime) <= strtotime($startDatetime)) throw new Exception('終了時間は開始時間より後にしてください。');

  if ($type === 'availability') {
    $capacity = max(1, (int)($p['capacity'] ?? 1));
    $stmt = $pdo->prepare("
      UPDATE availability
      SET start_datetime=?, end_datetime=?, date=?, start_time=?, end_time=?, capacity=?, note=?, updated_at=NOW()
      WHERE id=?
    ");
    $stmt->execute([$startDatetime,$endDatetime,$date,$startTime.':00',$endTime.':00',$capacity,$note,$id]);
    echo json_encode(['ok'=>true]); exit;
  }

  if ($type === 'reservation') {
    $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM reservations")->fetchAll(PDO::FETCH_ASSOC));
    if (in_array('end_datetime', $cols, true)) {
      $stmt = $pdo->prepare("UPDATE reservations SET start_datetime=?, end_datetime=?, updated_at=NOW() WHERE id=?");
      $stmt->execute([$startDatetime,$endDatetime,$id]);
    } else {
      $stmt = $pdo->prepare("UPDATE reservations SET start_datetime=?, updated_at=NOW() WHERE id=?");
      $stmt->execute([$startDatetime,$id]);
    }
    echo json_encode(['ok'=>true]); exit;
  }

  throw new Exception('typeが不正です。');
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
