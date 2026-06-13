<?php
require_once __DIR__ . '/../config/db.php';

$rows = [];
try {
  $pdo = db();
  $stmt = $pdo->query("
    SELECT r.id, r.start_datetime, r.end_datetime, r.status, r.payment_method,
           c.name AS customer_name, c.phone,
           s.name AS staff_name,
           m.name AS menu_name, m.price
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    ORDER BY r.start_datetime DESC
    LIMIT 200
  ");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>予約一覧 | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=6">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">ON;ME OS</p><h1>Reservations</h1></div>
  <nav class="admin-nav">
    <a href="./index.php">Dashboard</a>
    <a href="./calendar.php">Calendar</a>
    <a class="active" href="./reservations.php">予約</a>
    <a href="./customers.php">顧客</a>
    <a href="./menus.php">メニュー</a>
  </nav>
</header>
<main class="admin-shell">
  <?php if (!empty($error)): ?><section class="panel notice"><?= htmlspecialchars($error) ?></section><?php endif; ?>
  <section class="panel">
    <div class="panel-head">
      <div><p class="eyebrow">LIST</p><h2>予約一覧</h2></div>
    </div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>日時</th><th>お客様</th><th>電話</th><th>担当</th><th>メニュー</th><th>支払</th><th>状態</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="7" class="empty-cell">予約データがありません。</td></tr><?php endif; ?>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= date('Y/m/d H:i', strtotime($r['start_datetime'])) ?>〜<?= date('H:i', strtotime($r['end_datetime'])) ?></td>
              <td><?= htmlspecialchars($r['customer_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['phone'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['staff_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['menu_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['payment_method'] ?? '-') ?></td>
              <td><span class="status-pill"><?= htmlspecialchars($r['status'] ?? '-') ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>
