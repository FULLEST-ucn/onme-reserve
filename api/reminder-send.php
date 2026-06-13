<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/line-notify.php';

header('Content-Type: application/json; charset=utf-8');

$date = $_POST['date'] ?? date('Y-m-d', strtotime('+1 day'));
$dryRun = ($_POST['dry_run'] ?? '1') === '1';

try {
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT r.id, r.start_datetime, r.end_datetime,
           c.name AS customer_name, c.line_user_id,
           s.name AS staff_name,
           m.name AS menu_name
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) = ?
      AND r.status IN ('reserved','confirmed')
      AND c.line_user_id IS NOT NULL
      AND c.line_user_id <> ''
    ORDER BY r.start_datetime ASC
  ");
  $stmt->execute([$date]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $results = [];

  foreach ($rows as $r) {
    $text = "明日のご予約のお知らせです✨\n\n"
      . "【日時】" . date('Y/m/d H:i', strtotime($r['start_datetime'])) . "〜" . date('H:i', strtotime($r['end_datetime'])) . "\n"
      . "【担当】" . ($r['staff_name'] ?? '-') . "\n"
      . "【メニュー】" . ($r['menu_name'] ?? '-') . "\n\n"
      . "ご来店をお待ちしております。";

    $ok = $dryRun ? true : line_push_message($r['line_user_id'], $text);

    $results[] = [
      'reservation_id' => $r['id'],
      'customer_name' => $r['customer_name'],
      'sent' => $ok,
      'dry_run' => $dryRun,
    ];
  }

  echo json_encode(['ok' => true, 'count' => count($results), 'results' => $results], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
