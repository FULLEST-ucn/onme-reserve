<?php
require_once __DIR__ . '/../../config/auth.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $p = json_decode(file_get_contents('php://input'), true);
  if (!$p) throw new Exception('Invalid payload');

  $type = $p['type'] ?? 'availability';
  if ($type !== 'availability') throw new Exception('作成できるのは空き枠のみです。');

  $staffId = (int)($p['staff_id'] ?? 0);
  $date = $p['date'] ?? date('Y-m-d');
  $startTime = $p['start_time'] ?? '';
  $endTime = $p['end_time'] ?? '';
  $capacity = max(1, (int)($p['capacity'] ?? 1));
  $note = trim($p['note'] ?? '');

  if ($staffId <= 0) throw new Exception('スタッフが不正です。');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new Exception('日付が不正です。');
  if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) throw new Exception('時間が不正です。');

  $startDatetime = $date . ' ' . $startTime . ':00';
  $endDatetime = $date . ' ' . $endTime . ':00';
  if (strtotime($endDatetime) <= strtotime($startDatetime)) throw new Exception('終了時間は開始時間より後にしてください。');

  $stmt = $pdo->prepare("
    INSERT INTO availability
      (staff_id, start_datetime, end_datetime, note, store_id, date, start_time, end_time, capacity, is_active, created_at, updated_at)
    VALUES
      (?, ?, ?, ?, 1, ?, ?, ?, ?, 1, NOW(), NOW())
  ");
  $stmt->execute([
    $staffId,
    $startDatetime,
    $endDatetime,
    $note,
    $date,
    $startTime . ':00',
    $endTime . ':00',
    $capacity
  ]);

  echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
