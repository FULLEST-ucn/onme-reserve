<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');
$method = $_SERVER['REQUEST_METHOD'];

function input_json() {
  return json_decode(file_get_contents('php://input'), true) ?: [];
}
function to_minute($time) {
  [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));
  return $h * 60 + $m;
}
function overlaps($aStart, $aEnd, $bStart, $bEnd) {
  return $aStart < $bEnd && $aEnd > $bStart;
}

try {
  $pdo = db();

  if ($method === 'GET') {
    $availabilityStmt = $pdo->prepare("
      SELECT id, staff_id,
             DATE_FORMAT(start_datetime, '%H:%i') AS start_time,
             DATE_FORMAT(end_datetime, '%H:%i') AS end_time,
             'availability' AS kind,
             '受付可能' AS label
      FROM availability
      WHERE DATE(start_datetime) = ?
      ORDER BY staff_id, start_datetime
    ");
    $availabilityStmt->execute([$date]);
    $availability = $availabilityStmt->fetchAll(PDO::FETCH_ASSOC);

    $reservationStmt = $pdo->prepare("
      SELECT r.id, r.staff_id,
             DATE_FORMAT(r.start_datetime, '%H:%i') AS start_time,
             DATE_FORMAT(r.end_datetime, '%H:%i') AS end_time,
             'reservation' AS kind,
             CONCAT(COALESCE(c.name, 'お客様'), ' / ', COALESCE(m.name, 'メニュー')) AS label,
             r.status
      FROM reservations r
      LEFT JOIN customers c ON c.id = r.customer_id
      LEFT JOIN menus m ON m.id = r.menu_id
      WHERE DATE(r.start_datetime) = ?
        AND r.status IN ('reserved','confirmed','completed')
      ORDER BY r.staff_id, r.start_datetime
    ");
    $reservationStmt->execute([$date]);
    $reservations = $reservationStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      'availability' => $availability,
      'reservations' => $reservations,
      'stats' => [
        'availability_count' => count($availability),
        'reservation_count' => count($reservations),
      ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'PATCH') {
    $data = input_json();
    $kind = $data['kind'] ?? '';
    $id = (int)($data['id'] ?? 0);
    $staffId = (int)($data['staff_id'] ?? 0);
    $date = $data['date'] ?? date('Y-m-d');
    $start = $data['start_time'] ?? '';
    $end = $data['end_time'] ?? '';

    if (!$id || !$staffId || !$start || !$end || $start >= $end) {
      http_response_code(422);
      echo json_encode(['ok' => false, 'error' => '入力内容を確認してください'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    if ($kind === 'reservation') {
      $targetStart = to_minute($start);
      $targetEnd = to_minute($end);

      $check = $pdo->prepare("
        SELECT id, DATE_FORMAT(start_datetime, '%H:%i') AS start_time,
                  DATE_FORMAT(end_datetime, '%H:%i') AS end_time
        FROM reservations
        WHERE staff_id = ?
          AND DATE(start_datetime) = ?
          AND id <> ?
          AND status IN ('reserved','confirmed')
      ");
      $check->execute([$staffId, $date, $id]);
      foreach ($check->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (overlaps($targetStart, $targetEnd, to_minute($r['start_time']), to_minute($r['end_time']))) {
          http_response_code(409);
          echo json_encode(['ok' => false, 'error' => '他の予約と重複しています。'], JSON_UNESCAPED_UNICODE);
          exit;
        }
      }

      $stmt = $pdo->prepare("
        UPDATE reservations
        SET staff_id = ?, start_datetime = ?, end_datetime = ?, updated_at = NOW()
        WHERE id = ?
      ");
      $stmt->execute([$staffId, "$date $start:00", "$date $end:00", $id]);
    } else {
      $stmt = $pdo->prepare("
        UPDATE availability
        SET staff_id = ?, start_datetime = ?, end_datetime = ?
        WHERE id = ?
      ");
      $stmt->execute([$staffId, "$date $start:00", "$date $end:00", $id]);
    }

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
  }

  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
