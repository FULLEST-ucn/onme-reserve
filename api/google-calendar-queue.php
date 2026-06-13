<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/store.php';
header('Content-Type: application/json; charset=utf-8');

$reservationId = (int)($_POST['reservation_id'] ?? 0);

try {
  $pdo = db();
  $stmt = $pdo->prepare("
    INSERT INTO google_calendar_sync (store_id, reservation_id, status, created_at)
    VALUES (?, ?, 'pending', NOW())
  ");
  $stmt->execute([current_store_id(), $reservationId]);

  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
