<?php
require_once __DIR__ . '/../config/db.php';

$today = date('Y-m-d');
$stats = [
  'today_reservations' => 0,
  'month_reservations' => 0,
  'today_sales' => 0,
  'month_sales' => 0,
];

$reservations = [];

try {
  $pdo = db();

  $stats['today_reservations'] = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE DATE(start_datetime) = CURDATE() AND status IN ('reserved','confirmed')")->fetchColumn();
  $stats['month_reservations'] = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE DATE_FORMAT(start_datetime, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') AND status IN ('reserved','confirmed')")->fetchColumn();

  $stmt = $pdo->query("
    SELECT r.id, r.start_datetime, r.end_datetime, r.status,
           c.name AS customer_name, s.name AS staff_name, m.name AS menu_name, m.price
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) = CURDATE()
    ORDER BY r.start_datetime ASC
  ");
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($reservations as $r) {
    if (in_array($r['status'], ['reserved','confirmed'], true)) {
      $stats['today_sales'] += (int)($r['price'] ?? 0);
    }
  }

  $stmt = $pdo->query("
    SELECT COALESCE(SUM(m.price),0)
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE_FORMAT(r.start_datetime, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
      AND r.status IN ('reserved','confirmed')
  ");
  $stats['month_sales'] = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>ON;ME Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=6">
</head>
<body class="admin-body">
  <header class="admin-header">
    <div>
      <p class="eyebrow">ON;ME OS</p>
      <h1>Dashboard</h1>
    </div>
    <nav class="admin-nav">
      <a class="active" href="./index.php">Dashboard</a>
      <a href="./calendar.php">Calendar</a>
      <a href="./reservations.php">予約</a>
      <a href="./customers.php">顧客</a>
      <a href="./menus.php">メニュー</a>
    </nav>
  </header>

  <main class="admin-shell">
    <?php if (!empty($error)): ?>
      <section class="panel notice">
        <strong>DB接続確認：</strong><?= htmlspecialchars($error) ?>
      </section>
    <?php endif; ?>

    <section class="dashboard-grid">
      <article class="metric-card">
        <span>Today Reservations</span>
        <strong><?= number_format($stats['today_reservations']) ?></strong>
      </article>
      <article class="metric-card">
        <span>Month Reservations</span>
        <strong><?= number_format($stats['month_reservations']) ?></strong>
      </article>
      <article class="metric-card">
        <span>Today Sales</span>
        <strong>¥<?= number_format($stats['today_sales']) ?></strong>
      </article>
      <article class="metric-card">
        <span>Month Sales</span>
        <strong>¥<?= number_format($stats['month_sales']) ?></strong>
      </article>
    </section>

    <section class="panel">
      <div class="panel-head">
        <div>
          <p class="eyebrow">TODAY</p>
          <h2>本日の予約</h2>
        </div>
        <a class="admin-link-button" href="./calendar.php">カレンダーを見る</a>
      </div>

      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>時間</th>
              <th>お客様</th>
              <th>スタッフ</th>
              <th>メニュー</th>
              <th>状態</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$reservations): ?>
              <tr><td colspan="5" class="empty-cell">本日の予約はありません。</td></tr>
            <?php endif; ?>
            <?php foreach($reservations as $r): ?>
              <tr>
                <td><?= date('H:i', strtotime($r['start_datetime'])) ?>〜<?= date('H:i', strtotime($r['end_datetime'])) ?></td>
                <td><?= htmlspecialchars($r['customer_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['staff_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['menu_name'] ?? '-') ?></td>
                <td><span class="status-pill"><?= htmlspecialchars($r['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
