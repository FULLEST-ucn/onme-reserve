<?php
require_once __DIR__ . '/../config/db.php';

$month = $_GET['month'] ?? date('Y-m');
$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));

$stats = [
  'sales' => 0,
  'reservations' => 0,
  'customers' => 0,
  'avg_unit' => 0,
  'new_customers' => 0,
  'repeat_customers' => 0,
];

$daily = [];
$staffSales = [];
$menuSales = [];
$error = '';

try {
  $pdo = db();

  $stmt = $pdo->prepare("
    SELECT
      COALESCE(SUM(m.price),0) AS sales,
      COUNT(r.id) AS reservations,
      COUNT(DISTINCT r.customer_id) AS customers
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
  ");
  $stmt->execute([$start, $end]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  $stats['sales'] = (int)($row['sales'] ?? 0);
  $stats['reservations'] = (int)($row['reservations'] ?? 0);
  $stats['customers'] = (int)($row['customers'] ?? 0);
  $stats['avg_unit'] = $stats['reservations'] > 0 ? floor($stats['sales'] / $stats['reservations']) : 0;

  $stmt = $pdo->prepare("
    SELECT DATE(start_datetime) AS d, COUNT(*) AS count, COALESCE(SUM(m.price),0) AS sales
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
    GROUP BY DATE(start_datetime)
    ORDER BY d ASC
  ");
  $stmt->execute([$start, $end]);
  $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT s.name, COUNT(r.id) AS count, COALESCE(SUM(m.price),0) AS sales
    FROM reservations r
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
    GROUP BY s.id, s.name
    ORDER BY sales DESC
  ");
  $stmt->execute([$start, $end]);
  $staffSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT m.name, COUNT(r.id) AS count, COALESCE(SUM(m.price),0) AS sales
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
    GROUP BY m.id, m.name
    ORDER BY sales DESC
  ");
  $stmt->execute([$start, $end]);
  $menuSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT customer_id, COUNT(*) AS c
    FROM reservations
    WHERE DATE(start_datetime) BETWEEN ? AND ?
      AND status IN ('reserved','confirmed','completed')
    GROUP BY customer_id
  ");
  $stmt->execute([$start, $end]);
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    if ((int)$r['c'] >= 2) $stats['repeat_customers']++;
    else $stats['new_customers']++;
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
}

$maxDaily = 1;
foreach ($daily as $d) $maxDaily = max($maxDaily, (int)$d['sales']);
$maxStaff = 1;
foreach ($staffSales as $s) $maxStaff = max($maxStaff, (int)$s['sales']);
$maxMenu = 1;
foreach ($menuSales as $m) $maxMenu = max($maxMenu, (int)$m['sales']);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Analytics Pro | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=15">
  <link rel="stylesheet" href="../assets/css/analytics-pro.css?v=15">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Analytics</strong></div>
    <nav>
      <a href="./index.php">Dashboard</a>
      <a href="./calendar-pro.php">Calendar Pro</a>
      <a href="./carte-pro.php">Carte Pro</a>
      <a class="active" href="./analytics-pro.php">Analytics</a>
      <a href="./reservations.php">Reservations</a>
      <a href="./menus.php">Menus</a>
      <a href="./staff.php">Staff</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">SALON PERFORMANCE</p>
        <h1>Analytics Pro</h1>
      </div>
      <form class="analytics-filter" method="get">
        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
        <button type="submit">表示</button>
        <a href="./analytics-csv.php?month=<?= htmlspecialchars($month) ?>">CSV</a>
      </form>
    </header>

    <?php if ($error): ?>
      <section class="analytics-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="analytics-kpi">
      <article>
        <span>売上</span>
        <strong>¥<?= number_format($stats['sales']) ?></strong>
      </article>
      <article>
        <span>予約件数</span>
        <strong><?= number_format($stats['reservations']) ?></strong>
      </article>
      <article>
        <span>客単価</span>
        <strong>¥<?= number_format($stats['avg_unit']) ?></strong>
      </article>
      <article>
        <span>顧客数</span>
        <strong><?= number_format($stats['customers']) ?></strong>
      </article>
    </section>

    <section class="analytics-layout">
      <article class="analytics-panel wide">
        <div class="panel-title">
          <div>
            <p class="eyebrow">DAILY SALES</p>
            <h2>日別売上</h2>
          </div>
        </div>
        <div class="bar-chart">
          <?php if (!$daily): ?><p class="muted-text">データがありません。</p><?php endif; ?>
          <?php foreach($daily as $d): ?>
            <div class="bar-row">
              <span><?= date('m/d', strtotime($d['d'])) ?></span>
              <div><i style="width:<?= max(2, ((int)$d['sales'] / $maxDaily) * 100) ?>%"></i></div>
              <strong>¥<?= number_format((int)$d['sales']) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="analytics-panel">
        <div class="panel-title">
          <div>
            <p class="eyebrow">CUSTOMER</p>
            <h2>新規 / 再来</h2>
          </div>
        </div>
        <div class="ratio-grid">
          <div>
            <span>新規</span>
            <strong><?= number_format($stats['new_customers']) ?></strong>
          </div>
          <div>
            <span>再来</span>
            <strong><?= number_format($stats['repeat_customers']) ?></strong>
          </div>
        </div>
      </article>
    </section>

    <section class="analytics-layout bottom">
      <article class="analytics-panel">
        <div class="panel-title">
          <div>
            <p class="eyebrow">STAFF RANKING</p>
            <h2>スタッフ別売上</h2>
          </div>
        </div>
        <div class="ranking-list">
          <?php if (!$staffSales): ?><p class="muted-text">データがありません。</p><?php endif; ?>
          <?php foreach($staffSales as $i => $s): ?>
            <div class="rank-item">
              <b><?= $i + 1 ?></b>
              <div>
                <strong><?= htmlspecialchars($s['name'] ?? '-') ?></strong>
                <span><?= (int)$s['count'] ?>件 / ¥<?= number_format((int)$s['sales']) ?></span>
                <i style="width:<?= max(2, ((int)$s['sales'] / $maxStaff) * 100) ?>%"></i>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="analytics-panel">
        <div class="panel-title">
          <div>
            <p class="eyebrow">MENU RANKING</p>
            <h2>メニュー別売上</h2>
          </div>
        </div>
        <div class="ranking-list">
          <?php if (!$menuSales): ?><p class="muted-text">データがありません。</p><?php endif; ?>
          <?php foreach($menuSales as $i => $m): ?>
            <div class="rank-item">
              <b><?= $i + 1 ?></b>
              <div>
                <strong><?= htmlspecialchars($m['name'] ?? '-') ?></strong>
                <span><?= (int)$m['count'] ?>件 / ¥<?= number_format((int)$m['sales']) ?></span>
                <i style="width:<?= max(2, ((int)$m['sales'] / $maxMenu) * 100) ?>%"></i>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
