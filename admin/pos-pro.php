<?php
require_once __DIR__ . '/../config/auth.php';
$user = require_login();

$today = date('Y-m-d');
$sales = [];
$total = 0;
$saved = false;

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = (int)($_POST['reservation_id'] ?? 0) ?: null;
    $customerId = (int)($_POST['customer_id'] ?? 0) ?: null;
    $staffId = (int)($_POST['staff_id'] ?? $user['id']);
    $subtotal = (int)($_POST['subtotal'] ?? 0);
    $discount = (int)($_POST['discount'] ?? 0);
    $payTotal = max(0, $subtotal - $discount);
    $payment = $_POST['payment_method'] ?? 'cash';
    $memo = trim($_POST['memo'] ?? '');

    $stmt = $pdo->prepare("
      INSERT INTO pos_sales (reservation_id, customer_id, staff_id, subtotal, discount, total, payment_method, memo, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$reservationId, $customerId, $staffId, $subtotal, $discount, $payTotal, $payment, $memo]);
    $saved = true;
  }

  $stmt = $pdo->prepare("
    SELECT p.*, s.name AS staff_name, c.name AS customer_name
    FROM pos_sales p
    LEFT JOIN staffs s ON s.id = p.staff_id
    LEFT JOIN customers c ON c.id = p.customer_id
    WHERE DATE(p.created_at) = ?
    ORDER BY p.created_at DESC
  ");
  $stmt->execute([$today]);
  $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach($sales as $s) $total += (int)$s['total'];

  $reservations = $pdo->query("
    SELECT r.id, c.id AS customer_id, c.name AS customer_name, s.id AS staff_id, s.name AS staff_name, m.name AS menu_name, m.price
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) = CURDATE()
    ORDER BY r.start_datetime ASC
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
  $error = $e->getMessage();
  $reservations = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>POS | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=20">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=20">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>POS</strong></div>
    <nav>
      <a href="./staff-dashboard.php">Dashboard</a>
      <a class="active" href="./pos-pro.php">POS</a>
      <a href="./analytics-pro.php">Analytics</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">CHECKOUT</p><h1>POS</h1></div></header>
    <?php if (!empty($saved)): ?><section class="os-panel success">会計を登録しました。</section><?php endif; ?>
    <section class="analytics-kpi">
      <article><span>本日会計</span><strong><?= count($sales) ?></strong></article>
      <article><span>POS売上</span><strong>¥<?= number_format($total) ?></strong></article>
      <article><span>担当</span><strong><?= htmlspecialchars($user['name']) ?></strong></article>
      <article><span>日付</span><strong><?= date('m/d') ?></strong></article>
    </section>

    <section class="os-two">
      <section class="os-panel">
        <p class="eyebrow">NEW CHECKOUT</p>
        <h2>会計登録</h2>
        <form method="post" class="os-form">
          <label>本日予約
            <select id="reservationSelect" name="reservation_id">
              <option value="">予約なし会計</option>
              <?php foreach($reservations as $r): ?>
                <option value="<?= (int)$r['id'] ?>" data-customer="<?= (int)$r['customer_id'] ?>" data-staff="<?= (int)$r['staff_id'] ?>" data-price="<?= (int)$r['price'] ?>">
                  <?= htmlspecialchars($r['customer_name'].' / '.$r['menu_name'].' / ¥'.number_format((int)$r['price'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <input type="hidden" name="customer_id" id="customerId">
          <input type="hidden" name="staff_id" id="staffId" value="<?= (int)$user['id'] ?>">
          <label>小計<input type="number" name="subtotal" id="subtotal" value="0"></label>
          <label>値引き<input type="number" name="discount" value="0"></label>
          <label>支払方法
            <select name="payment_method">
              <option value="cash">現金</option>
              <option value="card">クレジット</option>
              <option value="paypay">PayPay</option>
            </select>
          </label>
          <label>メモ<textarea name="memo" rows="4"></textarea></label>
          <button>会計登録</button>
        </form>
      </section>

      <section class="os-panel">
        <p class="eyebrow">TODAY SALES</p>
        <h2>本日会計</h2>
        <div class="os-list">
          <?php foreach($sales as $s): ?>
            <article class="os-list-item">
              <strong>¥<?= number_format((int)$s['total']) ?> / <?= htmlspecialchars($s['payment_method']) ?></strong>
              <span><?= htmlspecialchars($s['customer_name'] ?? '-') ?> / <?= htmlspecialchars($s['staff_name'] ?? '-') ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </section>
  </main>
<script>
document.getElementById('reservationSelect')?.addEventListener('change', e => {
  const opt = e.target.selectedOptions[0];
  document.getElementById('customerId').value = opt.dataset.customer || '';
  document.getElementById('staffId').value = opt.dataset.staff || <?= (int)$user['id'] ?>;
  document.getElementById('subtotal').value = opt.dataset.price || 0;
});
</script>
</body>
</html>
