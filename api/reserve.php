<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/line-notify.php';
header('Content-Type: application/json; charset=utf-8');

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
function fail($message, $code = 422) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
  exit;
}

$menuId = (int)($_POST['menu_id'] ?? 0);
$staffId = (int)($_POST['staff_id'] ?? 0);
$date = $_POST['date'] ?? '';
$startTime = $_POST['start_time'] ?? '';
$duration = (int)($_POST['duration'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'cash';
$lineUserId = trim($_POST['line_user_id'] ?? '');

if (!$menuId || !$staffId || !$date || !$startTime || $duration <= 0 || !$name || !$phone) {
  fail('入力内容に不足があります。');
}

$allowedPayments = ['cash', 'card', 'paypay'];
if (!in_array($paymentMethod, $allowedPayments, true)) {
  $paymentMethod = 'cash';
}

try {
  $pdo = db();
  $pdo->beginTransaction();

  $startMinute = to_minute($startTime);
  $endMinute = $startMinute + $duration;
  $endTime = to_time($endMinute);

  $startDateTime = "$date $startTime:00";
  $endDateTime = "$date $endTime:00";

  $menuStmt = $pdo->prepare("SELECT id, name, price FROM menus WHERE id = ? AND is_active = 1");
  $menuStmt->execute([$menuId]);
  $menu = $menuStmt->fetch(PDO::FETCH_ASSOC);
  if (!$menu) {
    $pdo->rollBack();
    fail('選択されたメニューが見つかりません。');
  }

  $staffStmt = $pdo->prepare("SELECT id, name FROM staffs WHERE id = ? AND is_active = 1");
  $staffStmt->execute([$staffId]);
  $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
  if (!$staff) {
    $pdo->rollBack();
    fail('選択されたスタッフが見つかりません。');
  }

  $avStmt = $pdo->prepare("
    SELECT DATE_FORMAT(start_datetime, '%H:%i') AS start_time,
           DATE_FORMAT(end_datetime, '%H:%i') AS end_time
    FROM availability
    WHERE staff_id = ?
      AND DATE(start_datetime) = ?
      AND status = 'available'
  ");
  $avStmt->execute([$staffId, $date]);
  $availability = $avStmt->fetchAll(PDO::FETCH_ASSOC);

  $insideAvailability = false;
  foreach ($availability as $av) {
    $avStart = to_minute($av['start_time']);
    $avEnd = to_minute($av['end_time']);
    if ($startMinute >= $avStart && $endMinute <= $avEnd) {
      $insideAvailability = true;
      break;
    }
  }

  if (!$insideAvailability) {
    $pdo->rollBack();
    fail('選択された時間は受付可能時間外です。');
  }

  $resStmt = $pdo->prepare("
    SELECT id,
           DATE_FORMAT(start_datetime, '%H:%i') AS start_time,
           DATE_FORMAT(end_datetime, '%H:%i') AS end_time
    FROM reservations
    WHERE staff_id = ?
      AND DATE(start_datetime) = ?
      AND status IN ('reserved','confirmed')
    FOR UPDATE
  ");
  $resStmt->execute([$staffId, $date]);
  $reservations = $resStmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($reservations as $r) {
    if (overlaps($startMinute, $endMinute, to_minute($r['start_time']), to_minute($r['end_time']))) {
      $pdo->rollBack();
      fail('申し訳ございません。直前に予約が入りました。別の時間をお選びください。');
    }
  }

  $customerId = null;

  if ($lineUserId !== '') {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE line_user_id = ? LIMIT 1");
    $stmt->execute([$lineUserId]);
    $customerId = $stmt->fetchColumn() ?: null;
  }

  if (!$customerId) {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $customerId = $stmt->fetchColumn() ?: null;
  }

  if ($customerId) {
    $stmt = $pdo->prepare("
      UPDATE customers
      SET name = ?, phone = ?, line_user_id = COALESCE(NULLIF(?, ''), line_user_id), updated_at = NOW()
      WHERE id = ?
    ");
    $stmt->execute([$name, $phone, $lineUserId, $customerId]);
  } else {
    $stmt = $pdo->prepare("
      INSERT INTO customers (line_user_id, name, phone, memo, created_at, updated_at)
      VALUES (NULLIF(?, ''), ?, ?, '', NOW(), NOW())
    ");
    $stmt->execute([$lineUserId, $name, $phone]);
    $customerId = $pdo->lastInsertId();
  }

  $stmt = $pdo->prepare("
    INSERT INTO reservations
      (customer_id, menu_id, staff_id, start_datetime, end_datetime, status, payment_method, created_at, updated_at)
    VALUES
      (?, ?, ?, ?, ?, 'reserved', ?, NOW(), NOW())
  ");
  $stmt->execute([$customerId, $menuId, $staffId, $startDateTime, $endDateTime, $paymentMethod]);

  $reservationId = $pdo->lastInsertId();
  $pdo->commit();

  $reservationPayload = [
    'date' => $date,
    'start_time' => $startTime,
    'end_time' => $endTime,
    'customer_name' => $name,
    'phone' => $phone,
    'staff_name' => $staff['name'],
    'menu_name' => $menu['name'],
    'payment_method' => $paymentMethod,
  ];

  if ($lineUserId !== '') {
    line_push_message($lineUserId, format_reservation_message($reservationPayload));
  }

  line_notify_admins(format_admin_reservation_message($reservationPayload));

  echo json_encode([
    'ok' => true,
    'reservation_id' => $reservationId,
    'message' => '予約が完了しました。',
    'start_time' => $startTime,
    'end_time' => $endTime
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
