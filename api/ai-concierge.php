<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_POST['message'] ?? $_GET['message'] ?? '');
$date = date('Y-m-d');

if (preg_match('/明日/u', $q)) $date = date('Y-m-d', strtotime('+1 day'));
if (preg_match('/土曜|土曜日/u', $q)) {
  $d = new DateTime();
  while ($d->format('w') != 6) $d->modify('+1 day');
  $date = $d->format('Y-m-d');
}
if (preg_match('/日曜|日曜日/u', $q)) {
  $d = new DateTime();
  while ($d->format('w') != 0) $d->modify('+1 day');
  $date = $d->format('Y-m-d');
}

$duration = 90;
if (preg_match('/120|2時間/u', $q)) $duration = 120;
if (preg_match('/150/u', $q)) $duration = 150;
if (preg_match('/180|3時間/u', $q)) $duration = 180;

try {
  $pdo = db();
  $staffs = $pdo->query("SELECT id, name FROM staffs WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

  $lines = ["お問い合わせありがとうございます✨", "", "{$date} の空き候補です。"];
  foreach($staffs as $s) {
    $url = __DIR__ . '/slots.php';
    $_GET['date'] = $date;
    $_GET['staff_id'] = $s['id'];
    $_GET['duration'] = $duration;
    ob_start();
    include $url;
    $json = ob_get_clean();
    $slots = json_decode($json, true) ?: [];
    $take = array_slice($slots, 0, 3);
    $lines[] = "";
    $lines[] = "【{$s['name']}】";
    if (!$take) {
      $lines[] = "空きなし";
    } else {
      foreach($take as $slot) $lines[] = "{$slot['start_time']}〜{$slot['end_time']}";
    }
  }
  $lines[] = "";
  $lines[] = "ご希望の時間をお知らせください💅";

  echo json_encode(['ok' => true, 'reply' => implode("\n", $lines)], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
