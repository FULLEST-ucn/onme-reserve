<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$date = $_GET['date'] ?? date('Y-m-d');

function input_json() {
  $raw = file_get_contents('php://input');
  return json_decode($raw, true) ?: [];
}

try {
  $pdo = db();

  if ($method === 'GET') {
    $stmt = $pdo->prepare("
      SELECT id, staff_id, DATE(start_datetime) AS date,
             DATE_FORMAT(start_datetime, '%H:%i') AS start_time,
             DATE_FORMAT(end_datetime, '%H:%i') AS end_time,
             '受付可能' AS label,
             'available' AS type
      FROM availability
      WHERE DATE(start_datetime) = ?
      ORDER BY staff_id, start_datetime
    ");
    $stmt->execute([$date]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'POST') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $start = $_POST['start_time'] ?? '10:00';
    $end = $_POST['end_time'] ?? '18:00';

    if (!$staffId || $start >= $end) {
      http_response_code(422);
      echo json_encode(['error' => '入力内容を確認してください'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $stmt = $pdo->prepare("
      INSERT INTO availability (staff_id, start_datetime, end_datetime, status, created_at)
      VALUES (?, ?, ?, 'available', NOW())
    ");
    $stmt->execute([$staffId, "$date $start:00", "$date $end:00"]);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'PATCH') {
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $date = $data['date'] ?? date('Y-m-d');
    $start = $data['start_time'] ?? null;
    $end = $data['end_time'] ?? null;

    if (!$id || !$start || !$end || $start >= $end) {
      http_response_code(422);
      echo json_encode(['error' => '更新内容を確認してください'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $stmt = $pdo->prepare("
      UPDATE availability
      SET start_datetime = ?, end_datetime = ?
      WHERE id = ?
    ");
    $stmt->execute(["$date $start:00", "$date $end:00", $id]);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'DELETE') {
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM availability WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
