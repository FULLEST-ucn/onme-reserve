<?php
require_once __DIR__ . '/../config/auth.php';
$user = require_login();

$today = date('Y-m-d');
$rows = [];
$todaySales = 0;

try {
  $pdo = db();
  $where = "DATE(r.start_datetime) = ?";
  $params = [$today];

  if (($user['role'] ?? '') !== 'owner') {
    $where .= " AND r.staff_id = ?";
    $params[] = $user['id'];
  }

  $stmt = $pdo->prepare("
    SELECT r.*, c.name AS customer_name, c.phone, s.name AS staff_name, m.name AS menu_name, m.price
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE {$where}
      AND r.status IN ('reserved','confirmed','completed')
    ORDER BY r.start_datetime ASC
  ");
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach($rows as $r) $todaySales += (int)($r['price'] ?? 0);
} catch(Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Staff Dashboard | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=20">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=20">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong><?= htmlspecialchars($user['name']) ?></strong></div>
    <nav>
      <a class="active" href="./staff-dashboard.php">Today</a>
      <a href="./calendar-pro.php">Calendar</a>
      <a href="./carte-pro.php">Carte</a>
      <?php if (($user['role'] ?? '') === 'owner'): ?>
        <a href="./analytics-pro.php">Analytics</a>
        <a href="./crm-pro.php">LINE CRM</a>
        <a href="./pos-pro.php">POS</a>
        <a href="./owner-dashboard.php">Owner</a>
      <?php endif; ?>
      <a href="./logout.php">Logout</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top">
      <div><p class="eyebrow">TODAY'S WORK</p><h1>Dashboard</h1></div>
    </header>

    <section class="analytics-kpi">
      <article><span>本日予約</span><strong><?= count($rows) ?></strong></article>
      <article><span>本日売上</span><strong>¥<?= number_format($todaySales) ?></strong></article>
      <article><span>権限</span><strong><?= htmlspecialchars($user['role']) ?></strong></article>
      <article><span>日付</span><strong><?= date('m/d') ?></strong></article>
    </section>

    <section class="os-panel">
      <p class="eyebrow">NEXT RESERVATIONS</p>
      <h2>本日の予約</h2>
      <div class="os-list">
        <?php if (!$rows): ?><p class="muted-text">本日の予約はありません。</p><?php endif; ?>
        <?php foreach($rows as $r): ?>
          <article class="os-list-item">
            <strong><?= date('H:i', strtotime($r['start_datetime'])) ?> <?= htmlspecialchars($r['customer_name'] ?? 'お客様') ?></strong>
            <span><?= htmlspecialchars($r['menu_name'] ?? '-') ?> / <?= htmlspecialchars($r['staff_name'] ?? '-') ?> / ¥<?= number_format((int)($r['price'] ?? 0)) ?></span>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
