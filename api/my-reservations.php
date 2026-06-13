<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$lineUserId = $_GET['line_user_id'] ?? '';
$phone = $_GET['phone'] ?? '';

try {
  $pdo = db();

  $customer = null;
  if ($lineUserId !== '') {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE line_user_id = ? LIMIT 1");
    $stmt->execute([$lineUserId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if (!$customer && $phone !== '') {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if (!$customer) {
    echo json_encode(['customer' => null, 'reservations' => []], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT r.*, s.name AS staff_name, m.name AS menu_name
    FROM reservations r
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE r.customer_id = ?
    ORDER BY r.start_datetime DESC
  ");
  $stmt->execute([$customer['id']]);

  echo json_encode([
    'customer' => $customer,
    'reservations' => $stmt->fetchAll(PDO::FETCH_ASSOC)
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
