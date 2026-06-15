<?php
require_once __DIR__ . '/../../config/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();

  $payload = json_decode(file_get_contents('php://input'), true);
  if (!$payload) {
    throw new Exception('Invalid payload');
  }

  $type = $payload['type'] ?? '';
  $id = (int)($payload['id'] ?? 0);
  $staffId = (int)($payload['staff_id'] ?? 0);
  $date = $payload['date'] ?? date('Y-m-d');
  $time = $payload['time'] ?? '10:00';

  if ($id <= 0 || $staffId <= 0) {
    throw new Exception('IDまたはスタッフが不正です。');
  }

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    throw new Exception('日付が不正です。');
  }

  if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    throw new Exception('時間が不正です。');
  }

  $newStart = $date . ' ' . $time . ':00';

  if ($type === 'availability') {
    $stmt = $pdo->prepare("SELECT * FROM availability WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('空き枠が見つかりません。');

    $duration = max(30, (strtotime($row['end_datetime']) - strtotime($row['start_datetime'])) / 60);
    $newEnd = date('Y-m-d H:i:s', strtotime($newStart . " +{$duration} minutes"));

    $stmt = $pdo->prepare("
      UPDATE availability
      SET staff_id = ?,
          start_datetime = ?,
          end_datetime = ?,
          date = ?,
          start_time = ?,
          end_time = ?,
          updated_at = NOW()
      WHERE id = ?
    ");
    $stmt->execute([
      $staffId,
      $newStart,
      $newEnd,
      $date,
      $time . ':00',
      date('H:i:s', strtotime($newEnd)),
      $id
    ]);

    echo json_encode(['ok' => true]);
    exit;
  }

  if ($type === 'reservation') {
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('予約が見つかりません。');

    $start = strtotime($row['start_datetime']);
    $end = !empty($row['end_datetime']) ? strtotime($row['end_datetime']) : strtotime($row['start_datetime'] . ' +90 minutes');
    $duration = max(30, ($end - $start) / 60);
    $newEnd = date('Y-m-d H:i:s', strtotime($newStart . " +{$duration} minutes"));

    $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM reservations")->fetchAll(PDO::FETCH_ASSOC));

    if (in_array('end_datetime', $cols, true)) {
      $stmt = $pdo->prepare("
        UPDATE reservations
        SET staff_id = ?,
            start_datetime = ?,
            end_datetime = ?,
            updated_at = NOW()
        WHERE id = ?
      ");
      $stmt->execute([$staffId, $newStart, $newEnd, $id]);
    } else {
      $stmt = $pdo->prepare("
        UPDATE reservations
        SET staff_id = ?,
            start_datetime = ?,
            updated_at = NOW()
        WHERE id = ?
      ");
      $stmt->execute([$staffId, $newStart, $id]);
    }

    echo json_encode(['ok' => true]);
    exit;
  }

  throw new Exception('typeが不正です。');
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
