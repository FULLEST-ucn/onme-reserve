<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$date = $_GET['date'] ?? date('Y-m-d');
$staffId = (int)($_GET['staff_id'] ?? 0);
$duration = (int)($_GET['duration'] ?? 0);
$step = 30;

if (!$staffId || $duration <= 0) {
  echo json_encode([], JSON_UNESCAPED_UNICODE);
  exit;
}

function to_minute($time) {
  [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));
  return $h * 60 + $m;
}
function to_time($minute) {
  return sprintf('%02d:%02d', floor($minute / 60), $minute % 60);
}
function overlaps($aStart, $aEnd, $bStart, $bEnd) {
  return $aStart < $bEnd && $aEnd > $bStart;
}

try {
  $pdo = db();

  $avStmt = $pdo->prepare("
    SELECT DATE_FORMAT(start_datetime, '%H:%i') AS start_time,
           DATE_FORMAT(end_datetime, '%H:%i') AS end_time
    FROM availability
    WHERE staff_id = ?
      AND DATE(start_datetime) = ?
      AND status = 'available'
    ORDER BY start_datetime
  ");
  $avStmt->execute([$staffId, $date]);
  $availability = $avStmt->fetchAll(PDO::FETCH_ASSOC);

  $resStmt = $pdo->prepare("
    SELECT DATE_FORMAT(start_datetime, '%H:%i') AS start_time,
           DATE_FORMAT(end_datetime, '%H:%i') AS end_time
    FROM reservations
    WHERE staff_id = ?
      AND DATE(start_datetime) = ?
      AND status IN ('reserved','confirmed')
    ORDER BY start_datetime
  ");
  $resStmt->execute([$staffId, $date]);
  $reservations = $resStmt->fetchAll(PDO::FETCH_ASSOC);

  $slots = [];

  foreach ($availability as $av) {
    $start = to_minute($av['start_time']);
    $end = to_minute($av['end_time']);

    for ($current = $start; $current + $duration <= $end; $current += $step) {
      $candidateEnd = $current + $duration;
      $blocked = false;

      foreach ($reservations as $r) {
        if (overlaps($current, $candidateEnd, to_minute($r['start_time']), to_minute($r['end_time']))) {
          $blocked = true;
          break;
        }
      }

      if (!$blocked) {
        $slots[] = [
          'start_time' => to_time($current),
          'end_time' => to_time($candidateEnd),
          'label' => to_time($current) . '〜' . to_time($candidateEnd),
        ];
      }
    }
  }

  echo json_encode($slots, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
