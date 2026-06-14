<?php
require_once __DIR__ . '/../config/auth.php';
$user = require_login();

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$kpi = ['today_sales'=>0,'month_sales'=>0,'today_count'=>0,'month_count'=>0,'customers'=>0,'repeat_rate'=>0];
$daily = [];
$staffs = [];

try {
  $pdo = db();

  $stmt = $pdo->prepare("
    SELECT COUNT(r.id) c, COALESCE(SUM(m.price),0) sales
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime)=?
      AND r.status IN ('reserved','confirmed','completed')
  ");
  $stmt->execute([$today]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $kpi['today_sales'] = (int)$row['sales'];
  $kpi['today_count'] = (int)$row['c'];

  $stmt = $pdo->prepare("
    SELECT COUNT(r.id) c, COALESCE(SUM(m.price),0) sales, COUNT(DISTINCT r.customer_id) customers
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
  ");
  $stmt->execute([$monthStart,$monthEnd]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $kpi['month_sales'] = (int)$row['sales'];
  $kpi['month_count'] = (int)$row['c'];
  $kpi['customers'] = (int)$row['customers'];

  $stmt = $pdo->prepare("
    SELECT DATE(r.start_datetime) d, COALESCE(SUM(m.price),0) sales, COUNT(r.id) c
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
    GROUP BY DATE(r.start_datetime)
    ORDER BY d ASC
  ");
  $stmt->execute([$monthStart,$monthEnd]);
  $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT s.name, COALESCE(SUM(m.price),0) sales, COUNT(r.id) c
    FROM reservations r
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
    GROUP BY s.id, s.name
    ORDER BY sales DESC
    LIMIT 5
  ");
  $stmt->execute([$monthStart,$monthEnd]);
  $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT customer_id, COUNT(*) c
    FROM reservations
    WHERE DATE(start_datetime) BETWEEN ? AND ?
    GROUP BY customer_id
  ");
  $stmt->execute([$monthStart,$monthEnd]);
  $total = 0; $repeat = 0;
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $total++; if((int)$r['c']>=2) $repeat++; }
  $kpi['repeat_rate'] = $total ? round($repeat/$total*100) : 0;
} catch(Throwable $e) { $error=$e->getMessage(); }

$maxDaily = 1;
foreach($daily as $d) $maxDaily = max($maxDaily,(int)$d['sales']);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard V2 | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=40">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>V2</strong></div>
    <nav>
      <a class="active" href="./dashboard-v2.php">Dashboard V2</a>
      <a href="./calendar-master.php">Calendar Master</a>
      <a href="./carte-master.php">Carte Master</a>
      <a href="./line-miniapp.php">LINE Mini App</a>
      <a href="./staff-shifts.php">Shift</a>
      <a href="./menu-master.php">Menu Master</a>
      <a href="./consent-pro.php">Consent</a>
      <a href="./subscription-pro.php">Subscription</a>
      <a href="./ai-report.php">AI Report</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top">
      <div><p class="eyebrow">REALTIME SALON BI</p><h1>Dashboard V2</h1></div>
      <div class="pro-actions"><a class="pro-button primary" href="./owner-dashboard.php">Owner</a></div>
    </header>

    <?php if(!empty($error)): ?><section class="os-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="suite40-hero">
      <article><span>本日売上</span><strong>¥<?= number_format($kpi['today_sales']) ?></strong></article>
      <article><span>今月売上</span><strong>¥<?= number_format($kpi['month_sales']) ?></strong></article>
      <article><span>本日予約</span><strong><?= number_format($kpi['today_count']) ?></strong></article>
      <article><span>リピート率</span><strong><?= $kpi['repeat_rate'] ?>%</strong></article>
    </section>

    <section class="suite40-layout">
      <article class="os-panel">
        <p class="eyebrow">MONTHLY SALES</p>
        <h2>日別売上</h2>
        <div class="suite40-bars">
          <?php foreach($daily as $d): ?>
          <div><span><?= date('m/d', strtotime($d['d'])) ?></span><i style="width:<?= max(2,((int)$d['sales']/$maxDaily)*100) ?>%"></i><b>¥<?= number_format((int)$d['sales']) ?></b></div>
          <?php endforeach; ?>
          <?php if(!$daily): ?><p class="muted-text">データがありません。</p><?php endif; ?>
        </div>
      </article>
      <article class="os-panel">
        <p class="eyebrow">STAFF TOP</p>
        <h2>スタッフランキング</h2>
        <div class="os-list">
          <?php foreach($staffs as $i=>$s): ?>
          <article class="os-list-item"><strong><?= $i+1 ?>. <?= htmlspecialchars($s['name'] ?? '-') ?></strong><span><?= (int)$s['c'] ?>件 / ¥<?= number_format((int)$s['sales']) ?></span></article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
