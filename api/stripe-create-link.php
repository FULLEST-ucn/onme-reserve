<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/store.php';
header('Content-Type: application/json; charset=utf-8');

$reservationId = (int)($_POST['reservation_id'] ?? 0);
$amount = (int)($_POST['amount'] ?? 3000);

try {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id=? LIMIT 1");
  $stmt->execute([$reservationId]);
  $r = $stmt->fetch(PDO::FETCH_ASSOC);

  $customerId = $r['customer_id'] ?? null;

  // 実運用ではStripe Checkout SessionをAPI作成。
  // ここではローカル検証用の仮URLを生成。
  $url = '../customer/payment-demo.php?reservation_id=' . $reservationId . '&amount=' . $amount;

  $stmt = $pdo->prepare("
    INSERT INTO stripe_payments
      (store_id, reservation_id, customer_id, amount, currency, status, checkout_url, created_at, updated_at)
    VALUES (?, ?, ?, ?, 'jpy', 'pending', ?, NOW(), NOW())
  ");
  $stmt->execute([current_store_id(), $reservationId ?: null, $customerId, $amount, $url]);

  echo json_encode(['ok'=>true, 'checkout_url'=>$url], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
