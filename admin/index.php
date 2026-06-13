<?php
require_once __DIR__ . '/../config/db.php';

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$stats = [
  'today_reservations' => 0,
  'month_reservations' => 0,
  'today_sales' => 0,
  'month_sales' => 0,
];
$todayRows = [];
$error = '';

try {
  $pdo = db();

  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS c, COALESCE(SUM(m.price),0) AS sales
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) = ?
      AND r.status IN ('reserved','confirmed','completed')
  ");
  $stmt->execute([$today]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  $stats['today_reservations'] = (int)($row['c'] ?? 0);
  $stats['today_sales'] = (int)($row['sales'] ?? 0);

  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS c, COALESCE(SUM(m.price),0) AS sales
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
  ");
  $stmt->execute([$monthStart, $monthEnd]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  $stats['month_reservations'] = (int)($row['c'] ?? 0);
  $stats['month_sales'] = (int)($row['sales'] ?? 0);

  $stmt = $pdo->prepare("
    SELECT r.*, c.name AS customer_name, s.name AS staff_name, m.name AS menu_name, m.price
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) = ?
      AND r.status IN ('reserved','confirmed','completed')
    ORDER BY r.start_datetime ASC
  ");
  $stmt->execute([$today]);
  $todayRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Dashboard | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=21">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=21">
  <link rel="stylesheet" href="../assets/css/admin-dashboard-pro.css?v=1">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand">
      <small>ON;ME OS</small>
      <strong>Salon</strong>
    </div>
    <nav>
      <a class="active" href="./index.php">Dashboard</a>
      <a href="./calendar-pro.php">Calendar Pro</a>
      <a href="./reservations.php">Reservations</a>
      <a href="./customers.php">Customers</a>
      <a href="./carte-pro.php">Carte Pro</a>
      <a href="./menus.php">Menus</a>
      <a href="./analytics-pro.php">Analytics</a>
      <a href="./crm-pro.php">LINE CRM</a>
      <a href="./pos-pro.php">POS</a>
      <a href="./ai-concierge.php">AI</a>
      <a href="./owner-dashboard.php">Owner</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top dashboard-hero">
      <div>
        <p class="eyebrow">ON;ME OS</p>
        <h1>Dashboard</h1>
        <p class="dashboard-lead">本日の予約・売上・運用状況を確認できます。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./calendar-pro.php">カレンダーを見る</a>
        <a class="pro-button" href="./staff-dashboard.php">スタッフ画面</a>
      </div>
    </header>

    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="analytics-kpi">
      <article>
        <span>Today Reservations</span>
        <strong><?= number_format($stats['today_reservations']) ?></strong>
      </article>
      <article>
        <span>Month Reservations</span>
        <strong><?= number_format($stats['month_reservations']) ?></strong>
      </article>
      <article>
        <span>Today Sales</span>
        <strong>¥<?= number_format($stats['today_sales']) ?></strong>
      </article>
      <article>
        <span>Month Sales</span>
        <strong>¥<?= number_format($stats['month_sales']) ?></strong>
      </article>
    </section>

    <section class="dashboard-layout">
      <article class="os-panel dashboard-main-panel">
        <div class="panel-title">
          <div>
            <p class="eyebrow">TODAY</p>
            <h2>本日の予約</h2>
          </div>
          <a class="pro-button" href="./calendar-pro.php">カレンダーを見る</a>
        </div>

        <div class="dashboard-reservation-list">
          <?php if (!$todayRows): ?>
            <p class="muted-text">本日の予約はありません。</p>
          <?php endif; ?>

          <?php foreach($todayRows as $r): ?>
            <article class="dashboard-reservation">
              <time><?= date('H:i', strtotime($r['start_datetime'])) ?></time>
              <div>
                <strong><?= htmlspecialchars($r['customer_name'] ?? 'お客様') ?></strong>
                <span><?= htmlspecialchars($r['staff_name'] ?? '-') ?> / <?= htmlspecialchars($r['menu_name'] ?? '-') ?></span>
              </div>
              <em><?= htmlspecialchars($r['status']) ?></em>
            </article>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel">
        <p class="eyebrow">SHORTCUT</p>
        <h2>Quick Actions</h2>
        <div class="quick-grid">
          <a href="./calendar-pro.php">受付時間を追加</a>
          <a href="./carte-pro.php">カルテを見る</a>
          <a href="./analytics-pro.php">売上分析</a>
          <a href="./crm-pro.php">LINE配信</a>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
